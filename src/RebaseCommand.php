<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RebaseCommand extends Command
{

    const ARG_FIRST_BRANCH = 'firstBranch';

    /** @var Git */
    private Git $git;

    protected function configure()
    {
        $this->setName('rebase');
        $this->addArgument(self::ARG_FIRST_BRANCH, InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->git = new Git(getcwd());

        $table = new Table($output);
        $firstBranch = $input->getArgument(self::ARG_FIRST_BRANCH);
        $branchesContainingCommit = $this->git->getBranchesContainingCommit($firstBranch);
        $sortedBranches = $this->sortBranchesThatContainCommit($branchesContainingCommit);
        $table->addRow($sortedBranches);
        $table->render();

        return 0;
    }

    private function sortBranchesThatContainCommit(array $branchesContainingCommit)
    {
        usort(
            $branchesContainingCommit,
            function ($first, $second) {
                $countFirst = count($this->git->getBranchesContainingCommit($first));
                $countSecond = count($this->git->getBranchesContainingCommit($second));
                return $countSecond <=> $countFirst;
            }
        );
        return $branchesContainingCommit;
    }
}
