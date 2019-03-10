<?php

namespace Goksagun\SchedulerBundle\Tests\Command;

use Goksagun\SchedulerBundle\Command\ScheduledTaskCommand;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Fixtures\FooBundle\Command\AnnotatedCommand;
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
        $application = $this->getApplication();

        $application->add(new ScheduledTaskCommand(false, false, false, []));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            'Scheduled task(s) disabled. You should enable in scheduler.yml (or scheduler.yaml) config before running this command.',
            $output
        );
    }

    public function testEmptyTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new ScheduledTaskCommand(true, false, false, []));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            'There is no task scheduled. You should add task in scheduler.yml (or scheduler.yaml) config file.',
            $output
        );
    }

    public function testInvalidTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new ScheduledTaskCommand(true, false, false, [
            ['name' => 'invalid:command', 'expression' => '* * * * *', 'start' => null, 'end' => null, 'times' => null]
        ]));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "The 'invalid:command' task not found!",
            $output
        );
    }

    public function testNoOutputTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new NoOutputCommand());
        $application->add(new ScheduledTaskCommand(true, false, false, [
            ['name' => 'no:output', 'expression' => '* * * * *', 'start' => null, 'end' => null, 'times' => null]
        ]));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertSame("The 'no:output' completed!\n", $output);
    }

    public function testGreetingSayHelloWithArgumentTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new GreetingSayHelloCommand());
        $application->add(new ScheduledTaskCommand(true, false, false, [
            ['name' => 'greeting:say-hello John Alaska', 'expression' => '* * * * *', 'start' => null, 'end' => null, 'times' => null],
            ['name' => 'greeting:say-hello Jane Alaska', 'expression' => '* * * * *', 'start' => null, 'end' => null, 'times' => null],
        ]));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Hello John from Alaska",
            $output
        );
    }

    public function testGreetingSayHelloWithArgumentAndOptionTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new GreetingSayHelloCommand());
        $application->add(new ScheduledTaskCommand(true, false, false, [
            ['name' => 'greeting:say-hello John --twice', 'expression' => '* * * * *', 'start' => null, 'end' => null, 'times' => null]
        ]));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Hello John\nHello John",
            $output
        );
    }

    public function testGreetingSayGoodbyeWithStartDateOptionTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand(true, false, false, [
            [
                'name' => 'greeting:say-goodbye John',
                'expression' => '* * * * *',
                'start' => (new \DateTime('now'))->format(DateHelper::DATETIME_FORMAT),
                'end' => null,
                'times' => null
            ]
        ]));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Goodbye John",
            $output
        );
    }

    public function testGreetingSayGoodbyeWithWrongOptionsTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand(true, false, false, [
            [
                'name' => 'greeting:say-goodbye John',
                'expression' => '* * * * *',
                'start' => (new \DateTime('now'))->format('Y/m/d H:i'),
                'end' => strtotime('now'),
                'times' => 'integer'
            ]
        ]));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertSame(
            "The task \"0\" has errors:
  - The times should be integer.
  - The start should be date (Y-m-d) or datetime (Y-m-d H:i).
  - The end should be date (Y-m-d) or datetime (Y-m-d H:i).\n",
            $output
        );
    }

    public function testGreetingSayGoodbyeWithStartDateValidateTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand(true, false, false, [
            [
                'name' => 'greeting:say-goodbye John',
                'expression' => '* * * * *',
                'start' => (new \DateTime('+1 hour'))->format(DateHelper::DATETIME_FORMAT),
                'end' => null,
                'times' => null
            ]
        ]));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertEmpty($output);
    }

    public function testGreetingSayGoodbyeWithEndDateValidateTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand(true, false, false, [
            [
                'name' => 'greeting:say-goodbye John',
                'expression' => '* * * * *',
                'start' => (new \DateTime('-1 hour'))->format(DateHelper::DATETIME_FORMAT),
                'end' => (new \DateTime('now'))->format(DateHelper::DATETIME_FORMAT),
                'times' => null
            ]
        ]));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertEmpty($output);
    }

    public function testGreetingSayGoodbyeWithStartAndEndDateValidateTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new GreetingSayGoodbyeCommand());
        $application->add(new ScheduledTaskCommand(true, false, false, [
            [
                'name' => 'greeting:say-goodbye John',
                'expression' => '* * * * *',
                'start' => (new \DateTime('-1 hour'))->format(DateHelper::DATETIME_FORMAT),
                'end' => (new \DateTime('+1 hour'))->format(DateHelper::DATETIME_FORMAT),
                'times' => null
            ]
        ]));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Goodbye John",
            $output
        );
    }

    public function testScheduleAnnotatedTaskCommand()
    {
        $application = $this->getApplication();

        $application->add(new AnnotatedCommand());
        $application->add(new ScheduledTaskCommand(true, false, false, []));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            "Hello from schedule by annotation",
            $output
        );
    }
}