<?php

namespace Command;

use Goksagun\SchedulerBundle\Command\SchedulerTaskAddCommand;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTaskAddCommandTest extends KernelTestCase
{
    private function getApplication(): Application
    {
        return new Application();
    }

    public function testAddNewTaskOnlyRequiredParams()
    {
        $command = $this->getCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'Wouter',
            'expression' => '@daily',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(
            'Command "Wouter" added to scheduled task list.',
            $output
        );
    }

    public function testTimesOptionShouldBeNumeric()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The option "times" should be numeric value.');

        $command = $this->getCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'Wouter',
            'expression' => '@daily',
            '--times' => 'non-numeric',
        ]);
    }

    public function testStartOptionShouldBeValidDate()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The option "start" should be date or date and time value.');

        $command = $this->getCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'Wouter',
            'expression' => '@daily',
            '--start' => 'non-valid-date',
        ]);
    }

    public function testStopOptionShouldBeValidDate()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The option "stop" should be date or date and time value.');

        $command = $this->getCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'Wouter',
            'expression' => '@daily',
            '--stop' => 'non-valid-date',
        ]);
    }

    public function testStatusOptionShouldBeValid()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The option "status" should be valid. [values: "active|inactive"]');

        $command = $this->getCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'Wouter',
            'expression' => '@daily',
            '--status' => 'non-valid-status',
        ]);
    }

    private function getCommand(): Command
    {
        $application = $this->getApplication();
        $service = $this->createPartialMock(ScheduledTaskService::class, ['create']);

        $application->add(new SchedulerTaskAddCommand($service));

        return $application->find('scheduler:add');
    }


}