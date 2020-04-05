<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Exception\ProcessFailedException;
use tomasfejfar\GitTrain\Model\Train;
use tomasfejfar\GitTrain\Repository\BranchesRepository;
use tomasfejfar\GitTrain\Repository\StatusRepository;
use tomasfejfar\GitTrain\Repository\TrainRepository;

class RebaseCommand extends Command
{
    const ARG_FIRST_BRANCH = 'firstBranch';
    const ARG_REBASE_TARGET = 'rebaseTarget';
    const OPT_ABORT = 'abort';
    const OPT_CONTINUE = 'continue';

    /** @var Git */
    private Git $git;

    private BranchesRepository $branchesRepo;

    private TrainRepository $trainRepo;

    private StatusRepository $statusRepo;

    private InputInterface $input;

    private OutputInterface $output;

    protected function configure()
    {
        $this->setName('rebase')
            ->addArgument(self::ARG_FIRST_BRANCH, InputArgument::OPTIONAL)
            ->addArgument(self::ARG_REBASE_TARGET, InputArgument::OPTIONAL)
            ->addOption(self::OPT_CONTINUE, null, InputOption::VALUE_NONE, 'Continue with existing process')
            ->addOption(self::OPT_ABORT, null, InputOption::VALUE_NONE, 'Abort and reset existing process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->git = new Git(getcwd());
        $this->branchesRepo = new BranchesRepository($this->git);
        $this->trainRepo = new TrainRepository($this->branchesRepo, $this->git);
        $this->statusRepo = new StatusRepository($this->git);

        $isContinue = $this->input->getOption(self::OPT_CONTINUE);
        $isAbort = $this->input->getOption(self::OPT_ABORT);

        if ($isAbort) {
            return $this->handleAbort();
        }
        $firstBranch = $this->input->getArgument(self::ARG_FIRST_BRANCH);
        $rebaseTarget = $this->input->getArgument(self::ARG_REBASE_TARGET);
        if ($isContinue) {
            if ($firstBranch || $rebaseTarget) {
                throw new Exception('Too many parameters');
            }
        } else {
            if (!$firstBranch) {
                throw new Exception('First branch missing');
            } elseif (!$rebaseTarget) {
                throw new Exception('Rebase target missing');
            }
        };

        if ($isContinue) {
            $train = $this->statusRepo->readStatus();
        } else {
            if ($this->statusRepo->isInProgress()) {
                throw new Exception('There is already a train rebase in progress, use --continue');
            }
            $train = $this->trainRepo->getRebaseTrain($firstBranch, $rebaseTarget);
        }

        $this->processRebase($train);
        $this->processReview($train);
        if ($this->ask('Would you like to push the changes? [yN]', true)) {
            $this->processPush($train);
        }
        $this->statusRepo->removeStatusFile();
        return 0;
    }

    protected function dumpTrain(
        Model\Train $train
    ) {
        echo json_encode($train, \JSON_PRETTY_PRINT);
    }

    private function processRebase(Train $train): void
    {
        if ($train->areAllRebased()) {
            $this->output->writeln('All branches are already rebased, skipping',);
            return;
        }
        $this->output->writeln(sprintf(
            'Rebasing train of branches starting from "%s" on "%s"',
            $train->getNextUnrebased()->getName(),
            $train->getRebaseRoot()->getName()
        ));
        $this->statusRepo->writeStatus($train);

        $currentBranch = $train->getRebaseRoot();
        while (!$train->areAllRebased()) {
            $previousBranch = $currentBranch;
            $currentBranch = $train->getNext($currentBranch);
            if ($currentBranch->isRebased()) {
                $this->output->writeln(sprintf(
                    'Branch "%s" already rebased, skipping',
                    $currentBranch->getName()
                ));
                continue;
            }
            $this->output->writeln(sprintf('Rebasing "%s" on "%s"', $currentBranch->getName(), $previousBranch->getName()));

            $this->git->checkout($currentBranch->getName());
            try {
                $this->git->rebase($previousBranch->getName());
            } catch (ProcessFailedException $e) {
                throw new Exception(sprintf(
                    'Failed to rebase "%s" to "%s" failed with message "%s". Rebase manually and continue with %s',
                    $currentBranch->getName(),
                    $previousBranch->getName(),
                    $e->getMessage(),
                    '--continue'
                ));
            }
            $currentBranch->setRebased();
            $this->statusRepo->writeStatus($train);
        }
        $this->output->writeln('Rebase of branch train finished');
    }

    private function ask($question = 'Press any key to continue', $wantAnswer = false)
    {
        $confirmation = new ConfirmationQuestion($question, false, $wantAnswer ? '/^y/i' : '/.*/');
        $helper = new QuestionHelper();
        return $helper->ask($this->input, $this->output, $confirmation);
    }

    private function handleAbort()
    {
        $train = $this->statusRepo->readStatus();
        foreach ($train->getBranches() as $branch) {
            $this->git->checkout($branch->getName());
            $originalHash = $this->git->getHash($branch->getName());
            $upstreamHash = $this->git->getHash($branch->getName() . '@{u}');
            $this->git->reset($branch->getName() . '@{u}', Git::RESET_HARD);
            $this->output->writeln(sprintf(
                'Reset "%s" from "%s" to "%s"',
                $branch->getName(),
                $originalHash,
                $upstreamHash
            ));
        }
        $this->statusRepo->removeStatusFile();
        return 0;
    }

    private function processReview(Train $train): void
    {
        if ($train->areAllReviewed()) {
            $this->output->writeln('All branches are already reviewed, skipping',);
            return;
        }
        $this->output->writeln('Commencing review sequence');
        $currentBranch = $train->getRebaseRoot();
        while (!$train->areAllReviewed()) {
            $previousBranch = $currentBranch;
            $currentBranch = $train->getNext($currentBranch);
            if ($currentBranch->isReviewed()) {
                $this->output->writeln(sprintf(
                    'Branch "%s" already reviewed, skipping',
                    $currentBranch->getName()
                ));
                continue;
            }
            $upstream = $this->git->getGitCommandStringResult([
                'rev-parse',
                '--symbolic-full-name',
                '--abbrev-ref=strict',
                $currentBranch->getName() . '@{u}',
            ]);
            $this->output->writeln('Please review the diff from following command' . PHP_EOL);
            $command = sprintf(
                'git range-diff %s %s %s',
                escapeshellarg($previousBranch->getName()),
                escapeshellarg($upstream),
                escapeshellarg($currentBranch->getName())
            );
            $this->output->writeln($command . PHP_EOL);
            $this->ask();
            $this->output->writeln('Please review the diff from following command' . PHP_EOL);
            $command = sprintf(
                'git diff %s..%s',
                escapeshellarg($upstream),
                escapeshellarg($currentBranch->getName())
            );
            $this->output->writeln($command . PHP_EOL);
            $this->ask();
            $currentBranch->setReviewed();
            $this->statusRepo->writeStatus($train);
        }
        $this->output->writeln('All rebased branches reviewed');
    }

    private function processPush(Train $train)
    {
        if ($train->areAllPushed()) {
            return;
        }
        while (!$train->areAllPushed()) {
            $branch = $train->getNextUnpushed();
            $this->git->checkout($branch->getName());
            $this->git->push(null, null, true);
            $this->output->writeln(sprintf(
                'Pushed "%s"',
                $branch->getName()
            ));
            $branch->setPushed();
            $this->statusRepo->writeStatus($train);
        }
        $this->output->writeln('All branches pushed');
    }
}
