<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Repository;

use tomasfejfar\GitTrain\Git;
use tomasfejfar\GitTrain\Model\Branch;

class BranchesRepository
{

    /** @var Git */
    private Git $git;

    public function __construct(

        Git $git
    )
    {
        $this->git = $git;
    }

    public function getBranch(string $branchName): Branch
    {
        $hash = $this->git->getGitCommandStringResult(['rev-parse', $branchName]);
        $name = $branchName;
        $status = Branch::STATUS_FOUND;
        return new Branch($name, $hash, $status);
    }
}
