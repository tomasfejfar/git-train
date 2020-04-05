<?php

declare(strict_types=1);

namespace tomasfejfar\GitTrain\Tests\Model;

use tomasfejfar\GitTrain\Model\Branch;
use tomasfejfar\GitTrain\Model\Train;

use PHPUnit\Framework\TestCase;

class TrainTest extends TestCase
{
    public function testGetNext(): void
    {
        $rebaseRoot = new Branch('master', '123456', Branch::STATUS_FOUND);
        $first = new Branch('first', '123abc', Branch::STATUS_REVIEWED);
        $second = new Branch('second', '456def', Branch::STATUS_REBASED);
        $third = new Branch('third', '789fed', Branch::STATUS_FOUND);

        $train = new Train($rebaseRoot);
        $train->addBranch($first);
        $train->addBranch($second);
        $train->addBranch($third);

        $this->assertEquals($second, $train->getLastRebased());
        $this->assertEquals($first, $train->getLastReviewed());

        $this->assertEquals($first, $train->getNext($rebaseRoot));
        $this->assertEquals($second, $train->getNext($first));
        $this->assertEquals($third, $train->getNext($second));


        $this->assertEquals($third, $train->getNextUnrebased());
        $this->assertEquals($second, $train->getNextUnreviewed());

        $this->assertFalse($train->areAllRebased());
        $this->assertFalse($train->areAllReviewed());
    }

    public function testNextForLastElementThrows(): void
    {
        $rebaseRoot = new Branch('master', '123456', Branch::STATUS_FOUND);
        $first = new Branch('first', '123abc', Branch::STATUS_REVIEWED);
        $train = new Train($rebaseRoot);
        $train->addBranch($first);

        $this->expectExceptionMessage('No next branch, "third" is the last branch');
        $train->getNext($first);
    }
}
