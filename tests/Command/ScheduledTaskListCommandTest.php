<?php

namespace Command;

use Goksagun\SchedulerBundle\Command\ScheduledTaskListCommand;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\ArrayArgumentCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTaskListCommandTest extends TestCase
{

    public function testScheduleTaskListOption()
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
        $application = $this->getApplication();
        $scheduledTaskService = $this->createScheduledTaskService();

        $application->add(new ArrayArgumentCommand());
        $application->add(new ScheduledTaskListCommand($config, $scheduledTaskService));

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

    private function createScheduledTaskService(array $data = []): ScheduledTaskService
    {
        $scheduledTasks = [];
        foreach ($data as $datum) {
            $scheduledTask = new ScheduledTask();
            $scheduledTask->setName($datum['name']);
            $scheduledTask->setExpression($datum['expression']);
            $scheduledTask->setTimes($datum['times'] ?? null);
            $scheduledTask->setStart($datum['start'] ?? null);
            $scheduledTask->setStop($datum['stop'] ?? null);
            $scheduledTask->setStatus($datum['status'] ?? StatusInterface::STATUS_ACTIVE);

            $scheduledTasks[] = $scheduledTask;
        }

        $scheduledTaskService = $this->createMock(ScheduledTaskService::class);
        $scheduledTaskService
            ->expects($this->any())
            ->method('getScheduledTasks')
            ->willReturn($scheduledTasks);

        return $scheduledTaskService;
    }
}
