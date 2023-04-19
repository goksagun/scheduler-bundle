<?php

namespace Goksagun\SchedulerBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Goksagun\SchedulerBundle\Command\ScheduledTaskCommand;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Service\ScheduledTaskLogService;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\AnnotatedCommand;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\ArrayArgumentCommand;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\ArrayOptionCommand;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\DatabasedCommand;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\GreetingSayGoodbyeCommand;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\GreetingSayHelloCommand;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command\NoOutputCommand;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTaskCommandTest extends KernelTestCase
{
    public function testDisabledCommand()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Scheduled task(s) disabled. You should enable in scheduler.yaml config before running this command.'
        );

        $config = $this->createConfigMock(false);
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );
    }

    public function testEmptyTaskCommand()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('There is no task scheduled. You should add task in scheduler.yaml config file.');

        $config = $this->createConfigMock();
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );
    }

    public function testInvalidTaskCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'invalid:command',
                    'expression' => '* * * * *',
                    'start' => null,
                    'stop' => null,
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals("The 'invalid:command' task not found!\n", $output);
    }

    public function testNoOutputTaskCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'no:output',
                    'expression' => '* * * * *',
                    'start' => null,
                    'stop' => null,
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new NoOutputCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals("The 'no:output' completed!\n", $output);
    }

    public function testGreetingSayHelloWithArgumentTaskCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'greeting:say-hello John Alaska',
                    'expression' => '* * * * *',
                    'start' => null,
                    'stop' => null,
                    'times' => null,
                ],
                [
                    'name' => 'greeting:say-hello Jane Alaska',
                    'expression' => '* * * * *',
                    'start' => null,
                    'stop' => null,
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new GreetingSayHelloCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("The 'greeting:say-hello John Alaska' completed!\n", $output);
        $this->assertStringContainsString("The 'greeting:say-hello Jane Alaska' completed!\n", $output);
    }

    public function testGreetingSayHelloWithArgumentAndOptionTaskCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'greeting:say-hello John --twice',
                    'expression' => '* * * * *',
                    'start' => null,
                    'stop' => null,
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new GreetingSayHelloCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals("The 'greeting:say-hello John --twice' completed!\n", $output);
    }

    public function testGreetingSayGoodbyeWithStartDateOptionTaskCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'greeting:say-goodbye John',
                    'expression' => '* * * * *',
                    'start' => (new \DateTime('now'))->format(DateHelper::DATETIME_FORMAT),
                    'stop' => null,
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals("The 'greeting:say-goodbye John' completed!\n", $output);
    }

    public function testGreetingSayGoodbyeWithWrongOptionsTaskCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'greeting:say-goodbye John',
                    'expression' => '* * * * *',
                    'start' => (new \DateTime('now'))->format('Y/m/d H:i'),
                    'stop' => strtotime('now'),
                    'times' => 'integer',
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals(
            "The task 'greeting:say-goodbye John' has errors:
  - The times should be integer.
  - The start should be date (Y-m-d) or datetime (Y-m-d H:i).
  - The stop should be date (Y-m-d) or datetime (Y-m-d H:i).\n",
            $output
        );
    }

    public function testGreetingSayGoodbyeWithStartDateValidateTaskCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'greeting:say-goodbye John',
                    'expression' => '* * * * *',
                    'start' => (new \DateTime('+1 hour'))->format(DateHelper::DATETIME_FORMAT),
                    'stop' => null,
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEmpty($output);
    }

    public function testGreetingSayGoodbyeWithEndDateValidateTaskCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'greeting:say-goodbye John',
                    'expression' => '* * * * *',
                    'start' => (new \DateTime('-1 hour'))->format(DateHelper::DATETIME_FORMAT),
                    'stop' => (new \DateTime('now'))->format(DateHelper::DATETIME_FORMAT),
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEmpty($output);
    }

    public function testGreetingSayGoodbyeWithStartAndEndDateValidateTaskCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'greeting:say-goodbye John',
                    'expression' => '* * * * *',
                    'start' => (new \DateTime('-1 hour'))->format(DateHelper::DATETIME_FORMAT),
                    'stop' => (new \DateTime('+1 hour'))->format(DateHelper::DATETIME_FORMAT),
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals("The 'greeting:say-goodbye John' completed!\n", $output);
    }

    public function testScheduleAnnotatedTaskCommand()
    {
        $config = $this->createConfigMock();
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new AnnotatedCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("The 'schedule:annotate' completed!\n", $output);
        $this->assertStringContainsString("The 'schedule:annotate --foo=bar' completed!\n", $output);
    }

    public function testScheduleDatabasedTaskCommand()
    {
        $config = $this->createConfigMock();
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock([
            [
                'name' => 'schedule:database',
                'expression' => '* * * * *',
                'resource' => 'database',
            ],
        ]);
        $scheduledTaskService = $this->createScheduledTaskService([
            [
                'name' => 'schedule:database',
                'expression' => '* * * * *',
                'resource' => 'database',
            ],
        ]);
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new DatabasedCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals("The 'schedule:database' completed!\n", $output);
    }

    public function testArrayArgumentCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'schedule:array-argument argument1 argument2',
                    'expression' => '* * * * *',
                    'start' => null,
                    'stop' => null,
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new ArrayArgumentCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals("The 'schedule:array-argument argument1 argument2' completed!\n", $output);
    }

    public function testArrayOptionCommand()
    {
        $config = $this->createConfigMock(
            true,
            false,
            false,
            [
                [
                    'name' => 'schedule:array-option --foo option1 --foo option2',
                    'expression' => '* * * * *',
                    'start' => null,
                    'stop' => null,
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $application->add(new ArrayOptionCommand());
        $application->add(
            new ScheduledTaskCommand(
                $config,
                $entityManager,
                $scheduledTaskService,
                $scheduledTaskLogService
            )
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals("The 'schedule:array-option --foo option1 --foo option2' completed!\n", $output);
    }

    public function testAsyncCommand()
    {
        $config = $this->createConfigMock(
            true,
            true,
            false,
            [
                [
                    'name' => 'schedule:array-option --foo option1 --foo option2',
                    'expression' => '* * * * *',
                    'start' => null,
                    'stop' => null,
                    'times' => null,
                ],
            ]
        );
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskService = $this->createScheduledTaskService();
        $scheduledTaskLogService = $this->createScheduledTaskLogService();

        $scheduledTaskCommand = new ScheduledTaskCommand(
            $config,
            $entityManager,
            $scheduledTaskService,
            $scheduledTaskLogService
        );
        $scheduledTaskCommand->setProjectDir($this->getContainer()->getParameter('kernel.project_dir'));

        $application->add(new ArrayOptionCommand());
        $application->add(
            $scheduledTaskCommand
        );

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('========== Error ==========', $output);
    }

    private function getApplication(): Application
    {
        return new Application();
    }

    private function createConfigMock(
        bool $enabled = true,
        bool $async = false,
        bool $log = false,
        array $tasks = []
    ): array {
        return ['enabled' => $enabled, 'async' => $async, 'log' => $log, 'tasks' => $tasks];
    }

    private function createEntityManagerMock($data = []): EntityManagerInterface
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

        $scheduledTaskRepository = $this->createMock(ScheduledTaskRepository::class);
        $scheduledTaskRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn($scheduledTasks);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($scheduledTaskRepository);

        return $entityManager;
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

    private function createScheduledTaskLogService(): ScheduledTaskLogService
    {
        $scheduledTaskLogService = $this->createMock(ScheduledTaskLogService::class);
        $scheduledTaskLogService
            ->expects($this->any())
            ->method('create')
            ->willReturn((new ScheduledTaskLog())->setName('Foo'));

        return $scheduledTaskLogService;
    }
}