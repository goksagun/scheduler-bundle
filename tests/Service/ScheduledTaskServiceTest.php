<?php

namespace Goksagun\SchedulerBundle\Tests\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class ScheduledTaskServiceTest extends KernelTestCase
{
    private ScheduledTaskService $service;

    protected function setUp(): void
    {
        $repository = $this->createMock(ScheduledTaskRepository::class);
        $repository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([
                (new ScheduledTask())
                    ->setName('Foo')
                    ->setExpression('@daily'),
            ]);

        $kernel = $this->createMock(KernelInterface::class);

        $this->service = new ScheduledTaskService([], $kernel, $repository);
    }

    public function testCreateWithAllParams()
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

    public function testUpdateWithAllParams()
    {
        $repository = $this->createMock(ScheduledTaskRepository::class);

        $repository
            ->expects($this->once())
            ->method('find')
            ->withAnyParameters()
            ->willReturn(
                (new ScheduledTask())
                    ->setName('Foo')
                    ->setExpression('@hourly')
                    ->setTimes(3)
                    ->setStart(new \DateTime('2023-01-01'))
                    ->setStop(new \DateTime('2023-01-02'))
                    ->setStatus(StatusInterface::STATUS_INACTIVE)
            );

        $kernel = $this->createMock(KernelInterface::class);

        $this->service = new ScheduledTaskService([], $kernel, $repository);

        $scheduledTask = $this->service->update(
            1,
            'Bar',
            '@hourly',
            1,
            '2023-02-01',
            '2023-02-02',
            StatusInterface::STATUS_ACTIVE
        );

        $this->assertEquals('Bar', $scheduledTask->getName());
        $this->assertEquals('@hourly', $scheduledTask->getExpression());
        $this->assertEquals(1, $scheduledTask->getTimes());
        $this->assertEquals(new \DateTime('2023-02-01'), $scheduledTask->getStart());
        $this->assertEquals(new \DateTime('2023-02-02'), $scheduledTask->getStop());
        $this->assertEquals(StatusInterface::STATUS_ACTIVE, $scheduledTask->getStatus());
    }

    public function testUpdateWithRequiredParams()
    {
        $repository = $this->createMock(ScheduledTaskRepository::class);

        $repository
            ->expects($this->once())
            ->method('find')
            ->withAnyParameters()
            ->willReturn(
                (new ScheduledTask())
                    ->setName('Foo')
                    ->setExpression('@hourly')
            );

        $kernel = $this->createMock(KernelInterface::class);

        $this->service = new ScheduledTaskService([], $kernel, $repository);

        $scheduledTask = $this->service->update(
            1,
            'Bar',
            '@hourly'
        );

        $this->assertEquals('Bar', $scheduledTask->getName());
        $this->assertEquals('@hourly', $scheduledTask->getExpression());
        $this->assertEquals(null, $scheduledTask->getStart());
        $this->assertEquals(null, $scheduledTask->getStop());
        $this->assertEquals(StatusInterface::STATUS_ACTIVE, $scheduledTask->getStatus());
    }

    public function testGetScheduledTasks()
    {
        $scheduledTasks = $this->service->getScheduledTasks();

        $this->assertCount(1, $scheduledTasks);
    }
}
