<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Model;

use Exception;
use JsonSerializable;
use UnexpectedValueException;

class Train implements JsonSerializable
{
    const STATUS_CREATED = 'created';

    /** @var Branch[] */
    private array $branches;

    /** @var Branch */
    private Branch $rebaseRoot;

    /** @var string */
    private string $status;

    public function __construct(
        Branch $rebaseRoot,
        ?string $status = null
    ) {
        $this->rebaseRoot = $rebaseRoot;
        $this->status = $status ?? self::STATUS_CREATED;
    }

    public static function fromSerialized(array $deserializedJson): self
    {
        $train = new self(Branch::fromSerialized($deserializedJson['rebaseRoot']), $deserializedJson['status']);
        foreach ($deserializedJson['branches'] as $branch) {
            $train->addBranch(Branch::fromSerialized($branch));
        }
        return $train;
    }

    public function addBranch(Branch $branch)
    {
        $this->branches[] = $branch;
    }

    public function jsonSerialize()
    {
        return [
            'branches' => $this->branches,
            'status' => $this->status,
            'rebaseRoot' => $this->rebaseRoot,
        ];
    }

    /** @return Branch[] */
    public function getBranches(): array
    {
        return $this->branches;
    }

    public function areAllRebased()
    {
        return !$this->isAnyBranchOfStatus(Branch::STATUS_FOUND);
    }

    public function getBranchByName(string $branchName): Branch
    {
        $filteredBranches = array_filter(
            $this->branches,
            fn(Branch $branch) => $branch->getName() === $branchName
        );
        if (count($filteredBranches) > 1) {
            throw new UnexpectedValueException(sprintf('There should be exactly one branch "%s"', $branchName));
        }
        if (count($filteredBranches) < 1) {
            throw new UnexpectedValueException(sprintf('Branch "%s" not found', $branchName));
        }
        return reset($filteredBranches);
    }

    public function getNextUnrebased(): Branch
    {
        return $this->getNextOfStatus(Branch::STATUS_FOUND);
    }

    public function getLastRebased(): Branch
    {
        return $this->getLastOfStatus(Branch::STATUS_REBASED);
    }

    public function getRebaseRoot(): Branch
    {
        return $this->rebaseRoot;
    }

    public function areAllReviewed(): bool
    {
        return !$this->isAnyBranchOfStatus(Branch::STATUS_REBASED, Branch::STATUS_FOUND);
    }

    /**
     * @param string $status
     * @return bool
     */
    private function isAnyBranchOfStatus(string ...$statuses): bool
    {
        return count(array_filter(
                $this->branches,
                fn(Branch $branch) => in_array($branch->getStatus(), $statuses)
            )) > 0;
    }

    public function getNextUnreviewed(): Branch
    {
        return $this->getNextOfStatus(Branch::STATUS_REBASED);
    }

    /**
     * @param string $status
     * @return mixed|Branch
     */
    private function getNextOfStatus(string $status): Branch
    {
        foreach ($this->branches as $branch) {
            if ($branch->getStatus() === $status) {
                return $branch;
            }
        }
        throw new Exception(sprintf('No branches of status "%s" found', $status));
    }

    public function areAllPushed(): bool
    {
        return !$this->isAnyBranchOfStatus(Branch::STATUS_REVIEWED, Branch::STATUS_REBASED, Branch::STATUS_FOUND);
    }

    public function getNextUnpushed(): Branch
    {
        return $this->getNextOfStatus(Branch::STATUS_REVIEWED);
    }

    private function getLastOfStatus(string $status)
    {
        $branchOfStatus = null;
        foreach ($this->branches as $branch) {
            if ($branch->getStatus() !== $status) {
                continue;
            }
            $branchOfStatus = $branch;
        }
        if (!$branchOfStatus) {
            throw new Exception(sprintf('No branches of status "%s" found', $status));
        }
        return $branchOfStatus;
    }

    public function getLastReviewed()
    {
        return $this->getLastOfStatus(Branch::STATUS_REVIEWED);
    }

    public function getNext(Branch $currentBranch): Branch
    {
        if ($currentBranch === $this->getRebaseRoot()) {
            return reset($this->branches);
        }
        if ($currentBranch === end($this->branches)) {
            throw new Exception(sprintf(
                'No next branch, "%s" is the last branch',
                $currentBranch->getName()
            ));
        }
        $next = $this->getRebaseRoot();
        $key = array_search($currentBranch, $this->branches);
        if ($key !== false) {
            $next = $this->branches[$key + 1];
        }
        return $next;
    }
}
