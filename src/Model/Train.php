<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Model;

use JsonSerializable;

class Train implements JsonSerializable
{

    /** @var Branch[] */
    private array $branches;

    public function addBranch(Branch $branch)
    {
        $this->branches[] = $branch;
    }

    public function jsonSerialize()
    {
        return [
            'branches' => $this->branches,
        ];
    }

    public function getBranches(): array
    {
        return $this->branches;
    }
}
