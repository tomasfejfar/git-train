<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Model;

use JsonSerializable;

class Branch implements JsonSerializable
{

    const STATUS_FOUND = 'found';

    /** @var string */
    private string $name;

    /** @var string */
    private string $hash;

    /** @var string */
    private string $status;

    public function __construct(
        string $name,
        string $hash,
        string $status
    )
    {
        $this->name = $name;
        $this->hash = $hash;
        $this->status = $status;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'hash' => $this->getHash(),
            'status' => $this->getStatus(),
        ];
    }
}