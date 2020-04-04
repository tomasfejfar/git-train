<?php

declare(strict_types = 1);

namespace tomasfejfar\GitTrain\Tests;

use tomasfejfar\GitTrain\Git;

use PHPUnit\Framework\TestCase;

class GitTest extends TestCase
{

    public function testGetBranches(): void
    {
        $helper = $this->getGit();

        $this->assertSame(['master'], $helper->getBranches());
        $this->assertSame('master', $helper->getCurrentBranch());
    }

    public function testDev(): void
    {
        $helper = $this->getGit();
        $this->assertSame(['master', 'test'], $helper->getBranches());
    }

    public function getGit(): Git
    {
        return new Git('/data');
    }
}
