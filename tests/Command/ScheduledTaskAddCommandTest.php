<?php

namespace Command;

use Goksagun\SchedulerBundle\Command\SchedulerTaskAddCommand;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTaskAddCommandTest extends KernelTestCase
{
    private function getApplication(): Application
    {
        return new Application();
    }

    public function testAddNewTaskOnlyRequiredParams()
    {
        $application = $this->getApplication();
        $service = $this->createPartialMock(ScheduledTaskService::class, ['create']);

        $application->add(new SchedulerTaskAddCommand($service));

        $command = $application->find('scheduler:add');
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


}