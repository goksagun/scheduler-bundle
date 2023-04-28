<?php

namespace Goksagun\SchedulerBundle\Tests\Service;

use Goksagun\SchedulerBundle\Service\TaskLoader;
use Goksagun\SchedulerBundle\Service\TaskLoaderInterface;
use Goksagun\SchedulerBundle\Tests\Service\Fixtures\BarTaskLoader;
use Goksagun\SchedulerBundle\Tests\Service\Fixtures\FooTaskLoader;
use Goksagun\SchedulerBundle\Tests\Service\Fixtures\UnsupportedTaskLoader;
use PHPUnit\Framework\TestCase;

class TaskLoaderTest extends TestCase
{
    public function testTaskLoaderInstanceOf()
    {
        $taskLoader = new TaskLoader([
            new FooTaskLoader(),
        ]);

        $this->assertInstanceOf(TaskLoaderInterface::class, $taskLoader);
    }

    public function testUnsupportedTaskLoaderThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $taskLoader = new TaskLoader([
            new UnsupportedTaskLoader(),
        ]);

        $taskLoader->load();
    }

    public function testTaskLoaderHasMultipleLoaders()
    {
        $taskLoader = new TaskLoader([
            new FooTaskLoader(),
            new BarTaskLoader(),
        ]);

        $actual = $taskLoader->load();

        $this->assertCount(2, $actual);
    }

}
