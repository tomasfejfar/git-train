<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain;

use Symfony\Component\Process\Process;

class GitHelper
{

    /** @var string */
    private $path;

    public function __construct(
        string $path
    )
    {
        $this->setPath($path);
    }

    public function getCurrentBranch()
    {
        return trim(
            $this->runCommand(
                [
                    'git',
                    'rev-parse',
                    '--abbrev-ref',
                    'HEAD',
                ]
            )->getOutput()
        );
    }

    public function getBranches(): array
    {
        $output = $this->runCommand(
            [
                'git',
                'branch',
            ]
        )->getOutput();

        $branches = explode(
            "\n", trim($output)
        );

        $branches = array_map(fn($value) => trim($value, " \t\n\r\0\x0B*"), $branches);
        return $branches;
    }

    public function setPath(string $path): void
    {
        $root = $this->findGitRoot($path);
        $this->path = $root;
    }

    private function runCommand(array $command): Process
    {
        $process = new Process($command, $this->path);
        $process->mustRun();
        return $process;
    }

    public function runGitCommand(array $command): Process
    {
        $command = array_merge([$this->getGitExecutable()], $command);
        return $this->runCommand($command);
    }

    private function findGitRoot($path): string
    {

        $command = [
            $this->getGitExecutable(),
            'rev-parse',
            '--show-toplevel',
        ];
        $process = new Process($command, $path);
        $process->mustRun();
        return trim($process->getOutput());
    }

    private function getGitExecutable()
    {
        return 'git';
    }
}
