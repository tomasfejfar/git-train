<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain;

use Symfony\Component\Process\Process;

class Git
{

    /** @var string */
    private $repositoryPath;

    public function __construct(
        string $repositoryPath
    )
    {
        $this->setRepositoryPath($repositoryPath);
    }

    /**
     * @param string $output
     * @return array
     */
    public static function processBranchOutput(string $output): array
    {
        $branches = explode(
            "\n", trim($output)
        );

        $branches = array_map(fn($value) => trim($value, " \t\n\r\0\x0B*"), $branches);
        return $branches;
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

        return self::processBranchOutput($output);
    }

    public function getBranchesContainingCommit(string $commit): array
    {
        $parsedCommit = $this->getGitCommandStringResult(
            [
                'rev-parse',
                '--abbrev-ref',
                $commit,
            ]
        );

        $output = $this->runGitCommand(
            [
                'branch',
                '--contains',
                $parsedCommit,
            ]
        )->getOutput();
        return self::processBranchOutput($output);
    }

    public function setRepositoryPath(string $repositoryPath): void
    {
        $root = $this->findGitRoot($repositoryPath);
        $this->repositoryPath = $root;
    }

    private function runCommand(array $command): Process
    {
        $process = new Process($command, $this->repositoryPath);
        $process->mustRun();
        return $process;
    }

    public function runGitCommand(array $command): Process
    {
        $command = array_merge([$this->getGitExecutable()], $command);
        return $this->runCommand($command);
    }

    public function getGitCommandStringResult(array $command): string
    {
        return trim($this->runGitCommand($command)->getOutput());
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
