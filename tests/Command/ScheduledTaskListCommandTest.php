<?php

namespace Goksagun\SchedulerBundle\Tests\Command;

use Goksagun\SchedulerBundle\Command\ScheduledTaskListCommand;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\ArrayArgumentCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTaskListCommandTest extends KernelTestCase
{

    public function testScheduleTaskListOption()
    {
        $application = $this->getApplication();
        $scheduledTaskService = $this->createScheduledTaskService();

        $application->add(new ArrayArgumentCommand());
        $application->add(new ScheduledTaskListCommand($scheduledTaskService));

        $command = $application->find('scheduler:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("3", $output);

        $this->assertStringContainsString(
            "Name",
            $output
        );

        $this->assertStringContainsString(
            "Expression",
            $output
        );

        $this->assertStringContainsString(
            "Resource",
            $output
        );
    }

    private function getApplication(): Application
    {
        return new Application();
    }

    private function createConfigMock(bool $enabled = true, bool $async = false, bool $log = false, array $tasks = []): array
    {
        return ['enabled' => $enabled, 'async' => $async, 'log' => $log, 'tasks' => $tasks];
    }

    private function createScheduledTaskService(): ScheduledTaskService
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'schedule:annotate --foo=baz',
                    'expression' => '*/10 * * * *',
                ],
            ]
        );

        $scheduledTaskService = $this->createMock(ScheduledTaskService::class);
        $scheduledTaskService
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        return $scheduledTaskService;
    }
}
