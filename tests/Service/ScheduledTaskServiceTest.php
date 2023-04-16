<?php

namespace Service;

use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ScheduledTaskServiceTest extends TestCase
{
    private ScheduledTaskService $service;

    protected function setUp(): void
    {
        $repository = $this->createPartialMock(ScheduledTaskRepository::class, ['save']);

        $container = $this->createMock(ContainerInterface::class);
        $kernel = $this->createMock(KernelInterface::class);

        $this->service = new ScheduledTaskService([], $container, $kernel, $repository);
    }

    public function testCreateWithFullParams()
    {
        $scheduledTask = $this->service->create(
            'Foo',
            '@daily',
            3,
            '2023-01-01 00:00:00',
            '2023-01-02 00:00:00',
            'active'
        );

        $this->assertEquals('Foo', $scheduledTask->getName());
        $this->assertEquals('@daily', $scheduledTask->getExpression());
        $this->assertEquals(3, $scheduledTask->getTimes());
        $this->assertEquals(new \DateTime('2023-01-01'), $scheduledTask->getStart());
        $this->assertEquals(new \DateTime('2023-01-02'), $scheduledTask->getStop());
        $this->assertEquals(StatusInterface::STATUS_ACTIVE, $scheduledTask->getStatus());
    }

    public function testCreateWithRequiredParams()
    {
        $scheduledTask = $this->service->create(
            'Foo',
            '@daily',
        );

        $this->assertEquals('Foo', $scheduledTask->getName());
        $this->assertEquals('@daily', $scheduledTask->getExpression());
        $this->assertEquals(null, $scheduledTask->getTimes());
        $this->assertEquals(null, $scheduledTask->getStart());
        $this->assertEquals(null, $scheduledTask->getStop());
        $this->assertEquals(StatusInterface::STATUS_ACTIVE, $scheduledTask->getStatus());
    }
}
