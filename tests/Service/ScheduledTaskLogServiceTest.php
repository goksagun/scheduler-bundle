<?php

namespace Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskLogRepository;
use Goksagun\SchedulerBundle\Service\ScheduledTaskLogService;
use PHPUnit\Framework\TestCase;

class ScheduledTaskLogServiceTest extends TestCase
{
    private ScheduledTaskLogService $service;

    protected function setUp(): void
    {
        $repository = $this->createMock(ScheduledTaskLogRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->withAnyParameters()
            ->willReturn((new ScheduledTaskLog())->setName('Foo'));

        $this->service = new ScheduledTaskLogService(['log' => true], $repository);
    }

    public function testCreate()
    {
        $scheduledTaskLog = $this->service->create('Foo');

        $this->assertEquals('Foo', $scheduledTaskLog->getName());
    }

}
