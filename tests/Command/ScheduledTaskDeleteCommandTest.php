<?php

namespace Goksagun\SchedulerBundle\Tests\Command;

use Goksagun\SchedulerBundle\Command\ScheduledTaskDeleteCommand;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class ScheduledTaskDeleteCommandTest extends KernelTestCase
{

    public function testScheduledTaskDelete()
    {
        $command = $this->getCommand();

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'id' => 'Foo',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Scheduled task "Foo" deleted.', $output);
    }

    public function testIdArgumentShouldBeProvide()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "id").');

        $command = $this->getCommand();

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    private function getApplication(): Application
    {
        return new Application();
    }

    private function getCommand(): Command
    {
        $application = $this->getApplication();
        $service = $this->createPartialMock(ScheduledTaskService::class, ['delete']);

        $application->add(new ScheduledTaskDeleteCommand($service));

        return $application->find('scheduler:delete');
    }
}
