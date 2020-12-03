<?php

namespace Goksagun\SchedulerBundle\Tests\Command;

use Doctrine\ORM\EntityManager;
use Goksagun\SchedulerBundle\Command\ScheduledTaskCommand;
use Goksagun\SchedulerBundle\Command\ScheduledTaskListCommand;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskLogRepository;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Fixtures\FooBundle\Command\AnnotatedCommand;
use Tests\Fixtures\FooBundle\Command\DatabasedCommand;
use Tests\Fixtures\FooBundle\Command\GreetingSayGoodbyeCommand;
use Tests\Fixtures\FooBundle\Command\GreetingSayHelloCommand;
use Tests\Fixtures\FooBundle\Command\NoOutputCommand;

class ScheduledTaskCommandTest extends KernelTestCase
{
    private function getApplication()
    {
        $application = new Application();

        return $application;
    }

    public function testDisabledCommand()
    {
        $config = $this->createConfigMock(false);
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains(
            'Scheduled task(s) disabled. You should enable in scheduler.yml (or scheduler.yaml) config before running this command.',
            $output
        );
    }

    public function testEmptyTaskCommand()
    {
        $config = $this->createConfigMock();
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains(
            'There is no task scheduled. You should add task in scheduler.yml (or scheduler.yaml) config file.',
            $output
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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "The 'invalid:command' task not found!",
            $output
        );
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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new NoOutputCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains("The 'no:output' completed!\n", $output);
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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new GreetingSayHelloCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Hello John from Alaska",
            $output
        );
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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new GreetingSayHelloCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Hello John\nHello John",
            $output
        );
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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Goodbye John",
            $output
        );
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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertSame(
            " - The task 'greeting:say-goodbye John' has errors:
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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Goodbye John",
            $output
        );
    }

    public function testScheduleAnnotatedTaskCommand()
    {
        $config = $this->createConfigMock();
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock();
        $scheduledTaskRepository = $this->createScheduledTaskRepository();
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new AnnotatedCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Hello from schedule by annotation",
            $output
        );

        $this->assertContains(
            "This is an foo: bar",
            $output
        );
    }

    public function testScheduleDatabasedTaskCommand()
    {
        $config = $this->createConfigMock();
        $application = $this->getApplication();
        $entityManager = $this->createEntityManagerMock(
            [
                [
                    'name' => 'schedule:database',
                    'expression' => '* * * * *',
                    'resource' => 'database',
                ],
            ]
        );
        $scheduledTaskRepository = $this->createScheduledTaskRepository(
            [
                [
                    'name' => 'schedule:database',
                    'expression' => '* * * * *',
                    'resource' => 'database',
                ],
            ]
        );
        $scheduledTaskLogRepository = $this->createScheduledTaskLogRepository();

        $application->add(new DatabasedCommand());
        $application->add(new ScheduledTaskCommand($config, $entityManager, $scheduledTaskRepository, $scheduledTaskLogRepository));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Hello from schedule by database",
            $output
        );
    }

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
        $scheduledTaskRepository = $this->createScheduledTaskRepository();

        $application->add(new AnnotatedCommand());
        $application->add(new ScheduledTaskListCommand($config, $scheduledTaskRepository));

        $command = $application->find('scheduler:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertContains("3", $output);

        $this->assertContains(
            "Name",
            $output
        );

        $this->assertContains(
            "Expression",
            $output
        );

        $this->assertContains(
            "Resource",
            $output
        );
    }

    private function createConfigMock($enabled = true, $async = false, $log = false, $tasks = [])
    {
        return ['enabled' => $enabled, 'async' => $async, 'log' => $log, 'tasks' => $tasks];
    }

    private function createEntityManagerMock($data = [])
    {
        $scheduledTasks = [];
        foreach ($data as $datum) {
            $scheduledTask = new ScheduledTask();
            $scheduledTask->setName($datum['name']);
            $scheduledTask->setExpression($datum['expression']);
            $scheduledTask->setTimes($datum['times'] ?? null);
            $scheduledTask->setStart($datum['start'] ?? null);
            $scheduledTask->setStop($datum['stop'] ?? null);
            $scheduledTask->setStatus($datum['status'] ?? ScheduledTask::STATUS_ACTIVE);

            array_push($scheduledTasks, $scheduledTask);
        }

        // Now, mock the repository so it returns the mock of the employee
        $scheduledTaskRepository = $this->createMock(ScheduledTaskRepository::class);
        $scheduledTaskRepository->expects($this->any())
            ->method('findAll')
            ->willReturn($scheduledTasks);

        // Last, mock the EntityManager to return the mock of the repository
        $entityManager = $this->createMock(EntityManager::class);
        // use getMock() on PHPUnit 5.3 or below
        // $entityManager = $this->getMock(ObjectManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($scheduledTaskRepository);

        return $entityManager;
    }

    private function createScheduledTaskRepository($data = [])
    {
        $scheduledTasks = [];
        foreach ($data as $datum) {
            $scheduledTask = new ScheduledTask();
            $scheduledTask->setName($datum['name']);
            $scheduledTask->setExpression($datum['expression']);
            $scheduledTask->setTimes($datum['times'] ?? null);
            $scheduledTask->setStart($datum['start'] ?? null);
            $scheduledTask->setStop($datum['stop'] ?? null);
            $scheduledTask->setStatus($datum['status'] ?? ScheduledTask::STATUS_ACTIVE);

            array_push($scheduledTasks, $scheduledTask);
        }

        $scheduledTaskRepository = $this->createMock(ScheduledTaskRepository::class);
        $scheduledTaskRepository->expects($this->any())
            ->method('findAll')
            ->willReturn($scheduledTasks);

        return $scheduledTaskRepository;
    }

    private function createScheduledTaskLogRepository()
    {
        $scheduledTaskLogRepository = $this->createMock(ScheduledTaskLogRepository::class);
        $scheduledTaskLogRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn([])
        ;

        return $scheduledTaskLogRepository;
    }
}