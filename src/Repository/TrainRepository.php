<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Repository;

use Exception;
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

    public function getRebaseTrain(string $rootBranchName, string $rebaseRoot): Train
    {
        $branchesContainingCommit = $this->git->getBranchesContainingCommit($rootBranchName);
        $sorted = $this->sortBranchesThatContainCommit($branchesContainingCommit);

        $train = new Train($this->branchesRepository->getBranch($rebaseRoot), $rebaseRoot);
        foreach ($sorted as $branch) {
            if (!$this->git->isBranchSameAsBranch($branch, $branch.'@{u}')) {
                throw new Exception(sprintf(
                    'Your branch "%s" is not pushed or does not have upstream set, refusing to continue',
                    $branch
                ));
            }
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
