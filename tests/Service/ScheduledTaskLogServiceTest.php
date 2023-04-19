<?php

namespace Goksagun\SchedulerBundle\Tests\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
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
            ->expects($this->any())
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

    public function testGetLatestScheduledTaskLog()
    {
        $scheduledTaskLog = $this->service->getLatestScheduledTaskLog('Foo');

        $this->assertEquals('Foo', $scheduledTaskLog->getName());
    }

    public function testUpdateStatus()
    {
        $scheduledTaskLog = $this->service->updateStatus(
            (new ScheduledTaskLog())
                ->setName('Foo'),
            StatusInterface::STATUS_INACTIVE);

        $this->assertEquals(StatusInterface::STATUS_INACTIVE, $scheduledTaskLog->getStatus());
    }

}
