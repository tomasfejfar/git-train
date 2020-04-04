<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tomasfejfar\GitTrain\Repository\BranchesRepository;
use tomasfejfar\GitTrain\Repository\TrainRepository;

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
        $branchRepo = new BranchesRepository($this->git);
        $trainRepo = new TrainRepository($branchRepo, $this->git);
        $train = $trainRepo->getRebaseTrain('test');

        die(var_dump(json_encode($train, \JSON_PRETTY_PRINT)));

        //$this->writeStatus()
        $table->render();

        return 0;
    }


}
