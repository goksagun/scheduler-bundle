<?php

namespace Goksagun\SchedulerBundle\Tests\Command;

use Goksagun\SchedulerBundle\Command\ScheduledTaskListCommand;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Service\AnnotationTaskLoader;
use Goksagun\SchedulerBundle\Service\AttributeTaskLoader;
use Goksagun\SchedulerBundle\Service\ConfigurationTaskLoader;
use Goksagun\SchedulerBundle\Service\DatabaseTaskLoader;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Goksagun\SchedulerBundle\Service\TaskLoader;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\AnnotatedCommand;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\ArrayArgumentCommand;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\AttributeCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTaskListCommandTest extends KernelTestCase
{

    public function testScheduleTaskList()
    {
        $config = $this->createConfigMock(
            tasks: [
                [
                    'name' => 'schedule:annotate --foo=baz',
                    'expression' => '*/10 * * * *',
                ],
            ]
        );
        $application = $this->getApplication();
        $service = $this->createScheduledTaskService(config: $config, application: $application);
        $taskLoader = new TaskLoader(
            [
                new DatabaseTaskLoader($service),
                new AttributeTaskLoader($service),
                new AnnotationTaskLoader($service),
                new ConfigurationTaskLoader($service),
            ]
        );

        $application->add(new ArrayArgumentCommand());
        $application->add(new ScheduledTaskListCommand($taskLoader));

        $command = $application->find('scheduler:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("Name", $output);
        $this->assertStringContainsString("Expression", $output);
        $this->assertStringContainsString("Resource", $output);

        $this->assertStringContainsString("230b65619e342b673b0f583bf3b31a96", $output);
        $this->assertStringContainsString("schedule:annotate --foo=baz", $output);
        $this->assertStringContainsString("*/10 * * * *", $output);
        $this->assertStringContainsString("active", $output);
        $this->assertStringContainsString("config", $output);
    }

    public function testScheduleTaskListWithOptionResourceAnnotation()
    {
        $config = $this->createConfigMock(
            tasks: [
                [
                    'name' => 'schedule:config --foo=baz',
                    'expression' => '*/10 * * * *',
                ],
            ]
        );
        $data = [
            [
                'name' => 'schedule:database',
                'expression' => '* * * * *',
                'resource' => 'database',
            ],
        ];
        $application = $this->getApplication();
        $service = $this->createScheduledTaskService(config: $config, data: $data, application: $application);
        $taskLoader = new TaskLoader(
            [
                new DatabaseTaskLoader($service),
                new AttributeTaskLoader($service),
                new AnnotationTaskLoader($service),
                new ConfigurationTaskLoader($service),
            ]
        );

        $application->add(new AnnotatedCommand());
        $application->add(new AttributeCommand());
        $application->add(new ScheduledTaskListCommand($taskLoader));

        $command = $application->find('scheduler:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--resource' => 'annotation',
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("0c3f5195b5f1205aff62d743ce141a95", $output);
        $this->assertStringContainsString("8b748ae733ea60089ee4c399cb929c2f", $output);
        $this->assertStringContainsString("schedule:annotate --foo=bar", $output);
        $this->assertStringContainsString("* * * * * ", $output);
        $this->assertStringContainsString("active", $output);
        $this->assertStringContainsString("annotation", $output);

        $this->assertStringNotContainsString("schedule:database", $output);
        $this->assertStringNotContainsString("schedule:attribute", $output);
        $this->assertStringNotContainsString("schedule:attribute --foo=bar", $output);
        $this->assertStringNotContainsString("schedule:config --foo=baz", $output);
    }

    public function testScheduleTaskListWithOptionResourceAttribute()
    {
        $config = $this->createConfigMock(
            tasks: [
                [
                    'name' => 'schedule:config --foo=baz',
                    'expression' => '*/10 * * * *',
                ],
            ]
        );
        $data = [
            [
                'name' => 'schedule:database',
                'expression' => '* * * * *',
                'resource' => 'database',
            ],
        ];
        $application = $this->getApplication();
        $service = $this->createScheduledTaskService(config: $config, data: $data, application: $application);
        $taskLoader = new TaskLoader(
            [
                new DatabaseTaskLoader($service),
                new AttributeTaskLoader($service),
                new AnnotationTaskLoader($service),
                new ConfigurationTaskLoader($service),
            ]
        );

        $application->add(new AnnotatedCommand());
        $application->add(new AttributeCommand());
        $application->add(new ScheduledTaskListCommand($taskLoader));

        $command = $application->find('scheduler:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--resource' => 'attribute',
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("c6cfb66ac599e49ae735494e70a53183", $output);
        $this->assertStringContainsString("c72bc821d47eee2ebe9e13b5ca4cfa7b", $output);
        $this->assertStringContainsString("schedule:attribute --foo=bar", $output);
        $this->assertStringContainsString("* * * * * ", $output);
        $this->assertStringContainsString("active", $output);
        $this->assertStringContainsString("attribute", $output);

        $this->assertStringNotContainsString("schedule:database", $output);
        $this->assertStringNotContainsString("schedule:annotation", $output);
        $this->assertStringNotContainsString("schedule:annotation --foo=bar", $output);
        $this->assertStringNotContainsString("schedule:config --foo=baz", $output);
    }

    public function testScheduleTaskListWithOptionResourceDatabase()
    {
        $config = $this->createConfigMock(
            tasks: [
                [
                    'name' => 'schedule:config --foo=baz',
                    'expression' => '*/10 * * * *',
                ],
            ]
        );
        $data = [
            [
                'name' => 'schedule:database',
                'expression' => '* * * * *',
                'resource' => 'database',
            ],
        ];
        $application = $this->getApplication();
        $service = $this->createScheduledTaskService(config: $config, data: $data, application: $application);
        $taskLoader = new TaskLoader(
            [
                new DatabaseTaskLoader($service),
                new AttributeTaskLoader($service),
                new AnnotationTaskLoader($service),
                new ConfigurationTaskLoader($service),
            ]
        );

        $application->add(new AnnotatedCommand());
        $application->add(new AttributeCommand());
        $application->add(new ScheduledTaskListCommand($taskLoader));

        $command = $application->find('scheduler:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--resource' => 'database',
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("8ee1b1f7329073f90b1acee62280ca38", $output);
        $this->assertStringContainsString("schedule:database", $output);
        $this->assertStringContainsString("* * * * * ", $output);
        $this->assertStringContainsString("active", $output);
        $this->assertStringContainsString("database", $output);

        $this->assertStringNotContainsString("schedule:attribute", $output);
        $this->assertStringNotContainsString("schedule:attribute --foo=bar", $output);
        $this->assertStringNotContainsString("schedule:annotation", $output);
        $this->assertStringNotContainsString("schedule:annotation --foo=bar", $output);
        $this->assertStringNotContainsString("schedule:config --foo=baz", $output);
    }

    public function testScheduleTaskListWithOptionResourceConfig()
    {
        $config = $this->createConfigMock(
            tasks: [
                [
                    'name' => 'schedule:config --foo=baz',
                    'expression' => '*/10 * * * *',
                ],
            ]
        );
        $data = [
            [
                'name' => 'schedule:database',
                'expression' => '* * * * *',
                'resource' => 'database',
            ],
        ];
        $application = $this->getApplication();
        $service = $this->createScheduledTaskService(config: $config, data: $data, application: $application);
        $taskLoader = new TaskLoader(
            [
                new DatabaseTaskLoader($service),
                new AttributeTaskLoader($service),
                new AnnotationTaskLoader($service),
                new ConfigurationTaskLoader($service),
            ]
        );

        $application->add(new AnnotatedCommand());
        $application->add(new AttributeCommand());
        $application->add(new ScheduledTaskListCommand($taskLoader));

        $command = $application->find('scheduler:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--resource' => 'config',
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("3ec311e111f08085d5b066455f44a8c8", $output);
        $this->assertStringContainsString("schedule:config --foo=baz", $output);
        $this->assertStringContainsString("*/10 * * * *", $output);
        $this->assertStringContainsString("active", $output);
        $this->assertStringContainsString("config", $output);

        $this->assertStringNotContainsString("schedule:attribute", $output);
        $this->assertStringNotContainsString("schedule:attribute --foo=bar", $output);
        $this->assertStringNotContainsString("schedule:annotation", $output);
        $this->assertStringNotContainsString("schedule:annotation --foo=bar", $output);
        $this->assertStringNotContainsString("schedule:database", $output);
    }

    private function getApplication(): Application
    {
        return new Application(static::createKernel());
    }

    private function createConfigMock(
        bool $enabled = true,
        bool $async = false,
        bool $log = false,
        array $tasks = []
    ): array {
        return ['enabled' => $enabled, 'async' => $async, 'log' => $log, 'tasks' => $tasks];
    }

    private function createScheduledTaskService(
        array $config = [],
        array $data = [],
        ?Application $application = null
    ): ScheduledTaskService {
        $scheduledTaskService = $this->createConfiguredMock(ScheduledTaskService::class, ['getConfig' => $config]);

        $scheduledTasks = [];
        foreach ($data as $datum) {
            $scheduledTask = new ScheduledTask();
            $scheduledTask->setName($datum['name']);
            $scheduledTask->setExpression($datum['expression']);
            $scheduledTask->setTimes($datum['times'] ?? null);
            $scheduledTask->setStart($datum['start'] ?? null);
            $scheduledTask->setStop($datum['stop'] ?? null);
            $scheduledTask->setStatus($datum['status'] ?? StatusInterface::STATUS_ACTIVE);

            $scheduledTask->preUpdate();

            $scheduledTasks[] = $scheduledTask;
        }

        if ($scheduledTasks) {
            $scheduledTaskService
                ->expects($this->any())
                ->method('getScheduledTasks')
                ->willReturn($scheduledTasks);
        }

        if ($application) {
            $scheduledTaskService
                ->expects($this->any())
                ->method('getApplication')
                ->willReturn($application);
        }

        return $scheduledTaskService;
    }
}
