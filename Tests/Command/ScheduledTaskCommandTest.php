<?php

namespace Goksagun\SchedulerBundle\Tests\Command;

use Goksagun\SchedulerBundle\Command\ScheduledTaskCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTaskCommandTest extends KernelTestCase
{
    public function testDisabledCommand()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new ScheduledTaskCommand(false, false, []));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            'Scheduled task(s) disabled. You should enable in scheduler.yml config before running this command.',
            $output
        );
    }

    public function testEmptyTaskCommand()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new ScheduledTaskCommand(true, false, []));

        $command = $application->find('scheduler:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains(
            'There is no task scheduled. You should add task in scheduler.yml config file.',
            $output
        );
    }

    public function testInvalidTaskCommand()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new ScheduledTaskCommand(true, false, [
            ['name' => 'invalid:command', 'expression' => '* * * * *']
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
}