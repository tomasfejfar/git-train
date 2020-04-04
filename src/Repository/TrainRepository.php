<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Repository;

use tomasfejfar\GitTrain\Git;
use tomasfejfar\GitTrain\Model\Train;

class TrainRepository
{

    /** @var BranchesRepository */
    private BranchesRepository $branchesRepository;

    /** @var Git */
    private Git $git;

    public function __construct(
        BranchesRepository $branchesRepository,
        Git $git
    )
    {
        $this->branchesRepository = $branchesRepository;
        $this->git = $git;
    }

    public function getRebaseTrain(string $rootBranchName): Train
    {
        $branchesContainingCommit = $this->git->getBranchesContainingCommit($rootBranchName);
        $sorted = $this->sortBranchesThatContainCommit($branchesContainingCommit);

        $train = new Train();
        foreach ($sorted as $branch) {
            $train->addBranch($this->branchesRepository->getBranch($branch));
        }
        return $train;
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
