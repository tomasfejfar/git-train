<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use tomasfejfar\GitTrain\Model\Branch;
use tomasfejfar\GitTrain\Repository\BranchesRepository;
use tomasfejfar\GitTrain\Repository\StatusRepository;
use tomasfejfar\GitTrain\Repository\TrainRepository;

class RebaseCommand extends Command
{
    const ARG_FIRST_BRANCH = 'firstBranch';
    const ARG_REBASE_TARGET = 'rebaseTarget';
    const OPT_CONTINUE = 'continue';

    /** @var Git */
    private Git $git;

    private BranchesRepository $branchesRepo;

    private TrainRepository $trainRepo;

    private StatusRepository $statusRepo;

    protected function configure()
    {
        $this->setName('rebase')
            ->addArgument(self::ARG_FIRST_BRANCH, InputArgument::OPTIONAL)
            ->addArgument(self::ARG_REBASE_TARGET, InputArgument::OPTIONAL)
            ->addOption(self::OPT_CONTINUE, null, InputOption::VALUE_NONE, 'Continue with existing process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->git = new Git(getcwd());
        $this->branchesRepo = new BranchesRepository($this->git);
        $this->trainRepo = new TrainRepository($this->branchesRepo, $this->git);
        $this->statusRepo = new StatusRepository($this->git);

        $firstBranch = $input->getArgument(self::ARG_FIRST_BRANCH);
        $rebaseTarget = $input->getArgument(self::ARG_REBASE_TARGET);
        $isContinue = $input->getOption(self::OPT_CONTINUE);
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
        }

        if ($isContinue) {
            $train = $this->statusRepo->readStatus();
        } else {
            $train = $this->trainRepo->getRebaseTrain($firstBranch, $rebaseTarget);
        }

        $output->writeln(sprintf(
            'Rebasing train of branches starting from "%s" on "%s"',
            $train->getNextUnrebased()->getName(),
            $rebaseTarget
        ));
        $this->statusRepo->writeStatus($train);

        $currentBranch = new Branch($rebaseTarget, $rebaseTarget, 'dummy');
        while (!$train->areAllRebased()) {
            $previousBranch = $currentBranch;
            $currentBranch = $train->getNextUnrebased();
            $output->writeln(sprintf('Rebasing "%s" on "%s"', $currentBranch->getName(), $previousBranch->getName()));

            $this->git->checkout($currentBranch->getName());
            try {
                $this->git->rebase($previousBranch->getName());
            } catch (ProcessFailedException $e) {
                $output->writeln(sprintf(
                    'Failed to rebase "%s" to "%s" failed with message "%s". Rebase manually and continue with %s',
                    $currentBranch->getName(),
                    $previousBranch->getName(),
                    $e->getMessage(),
                    ' --continue'
                ));
                return 1;
            }
            $currentBranch->setRebased();
            $this->statusRepo->writeStatus($train);
            //$this->dumpTrain($train);
        }
        $output->writeln('Rebase of branch train finished');
        return 0;
    }

    protected function dumpTrain(Model\Train $train)
    {
        echo json_encode($train, \JSON_PRETTY_PRINT);
    }
}
