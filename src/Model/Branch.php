<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Model;

use JsonSerializable;

class Branch implements JsonSerializable
{

    const STATUS_FOUND = 'found';
    const STATUS_PUSHED = 'pushed';
    const STATUS_REBASED = 'rebased';
    const STATUS_REVIEWED = 'reviewed';

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

    public static function fromSerialized($branch)
    {
        return new self(
            $branch['name'],
            $branch['hash'],
            $branch['status']
        );
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

    public function setRebased()
    {
        $this->status = self::STATUS_REBASED;
    }

    public function setReviewed()
    {
        $this->status = self::STATUS_REVIEWED;
    }

    public function setPushed()
    {
        $this->status = self::STATUS_PUSHED;
    }

    public function isRebased(): bool
    {
        return in_array($this->status, [
            self::STATUS_REBASED,
            self::STATUS_REVIEWED,
            self::STATUS_PUSHED
        ]);
    }

    public function isReviewed()
    {
        return in_array($this->status, [
            self::STATUS_REVIEWED,
            self::STATUS_PUSHED
        ]);
    }
}
