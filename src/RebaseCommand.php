<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultCommand extends Command
{

    protected function configure()
    {
        $this->setName('default');
        $this->addArgument('firstBranch', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $git = new Git(getcwd());

        $table = new Table($output);
        $rows = $git->getBranches();
        $table->addRow($rows);
        $table->render();

        return 0;
    }
}
