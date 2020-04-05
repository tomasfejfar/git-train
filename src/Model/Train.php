<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Model;

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
        ];
    }

    public function getBranches(): array
    {
        return $this->branches;
    }

    public function areAllRebased()
    {
        return count(array_filter(
                $this->branches,
                fn(Branch $branch) => $branch->getStatus() !== Branch::STATUS_REBASED
            )) === 0;
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
        if (count ($filteredBranches) < 1) {
            throw new UnexpectedValueException(sprintf('Branch "%s" not found',$branchName));
        }
        return reset($filteredBranches);
    }

    public function getNextUnrebased(): Branch
    {
        foreach ($this->branches as $branch) {
            if ($branch->getStatus() === Branch::STATUS_FOUND) {
                return $branch;
            }
        }

    }
}
