<?php

namespace Command;

use Goksagun\SchedulerBundle\Command\SchedulerTaskEditCommand;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTaskEditCommandTest extends KernelTestCase
{
    private function getApplication(): Application
    {
        return new Application();
    }

    public function testEditTaskOnlyRequiredParams()
    {
        $command = $this->getCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'id' => 1,
            'name' => 'Bar',
            'expression' => '@hourly',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(
            'Scheduled task "Bar" edited.',
            $output
        );
    }

    public function testIdArgumentShouldBeProvide()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "id").');

        $command = $this->getCommandForValidation();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'Bar',
            'expression' => '@hourly',
        ]);
    }

    public function testTimesOptionShouldBeNumeric()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The option "times" should be numeric value.');

        $command = $this->getCommandForValidation();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'id' => 1,
            'name' => 'Bar',
            'expression' => '@hourly',
            '--times' => 'non-numeric',
        ]);
    }

    public function testStartOptionShouldBeValidDate()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The option "start" should be date or date and time value.');

        $command = $this->getCommandForValidation();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'Bar',
            'expression' => '@hourly',
            '--start' => 'non-valid-date',
        ]);
    }

    public function testStopOptionShouldBeValidDate()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The option "stop" should be date or date and time value.');

        $command = $this->getCommandForValidation();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'Bar',
            'expression' => '@hourly',
            '--stop' => 'non-valid-date',
        ]);
    }

    public function testStatusOptionShouldBeValid()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The option "status" should be valid. [values: "active|inactive"]');

        $command = $this->getCommandForValidation();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'Bar',
            'expression' => '@hourly',
            '--status' => 'non-valid-status',
        ]);
    }

    private function getCommand(): Command
    {
        $application = $this->getApplication();
        $repository = $this->createMock(ScheduledTaskRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->withAnyParameters()
            ->willReturn(
                (new ScheduledTask())
                    ->setName('Foo')
                    ->setExpression('@daily')
                    ->setTimes(3)
                    ->setStart(new \DateTime('2023-01-01'))
                    ->setStop(new \DateTime('2023-01-02'))
                    ->setStatus(StatusInterface::STATUS_INACTIVE)
            );

        $application->add(new SchedulerTaskEditCommand($repository));

        return $application->find('scheduler:edit');
    }

    public function getCommandForValidation(): Command
    {
        $application = $this->getApplication();
        $repository = $this->createPartialMock(ScheduledTaskRepository::class, ['find']);

        $application->add(new SchedulerTaskEditCommand($repository));

        return $application->find('scheduler:edit');
    }


}