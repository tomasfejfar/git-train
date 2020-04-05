<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Repository;

use Exception;
use RuntimeException;
use tomasfejfar\GitTrain\Git;
use tomasfejfar\GitTrain\Model\Train;

class StatusRepository
{
    private Git $git;

    public function __construct(
        Git $git
    ) {
        $this->git = $git;
    }

    public function writeStatus($train)
    {
        file_put_contents($this->getStatusFilePath(), json_encode($train, JSON_THROW_ON_ERROR));
    }

    public function readStatus(): Train
    {
        $path = $this->getStatusFilePath();
        if (!$this->isInProgress()) {
            throw new RuntimeException(sprintf('There is no train rebase in progress', $path));
        }
        $contents = json_decode(file_get_contents($path), true);
        return Train::fromSerialized($contents);
    }

    public function isInProgress(): bool
    {
        $path = $this->getStatusFilePath();
        if (!file_exists($path)) {
            return false;
        }
        return true;
    }

    private function getStatusFilePath(): string
    {
        $statusFile = implode(
            DIRECTORY_SEPARATOR,
            [
                $this->git->getRepositoryPath(),
                '.git',
                'gittrain-status.json',
            ]
        );
        return $statusFile;
    }

    public function removeStatusFile()
    {
        if (!unlink($this->getStatusFilePath())) {
            throw new Exception('Failed to unlink "%s"', $this->getStatusFilePath());
        }
    }
}
