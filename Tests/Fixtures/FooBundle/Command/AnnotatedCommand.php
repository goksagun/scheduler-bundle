<?php

namespace Tests\Fixtures\FooBundle\Command;

use Goksagun\SchedulerBundle\Annotation\Schedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Schedule(name="schedule:annotate", expression="* * * * *")
 */
class AnnotatedCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('schedule:annotate')
            ->setDescription('This command do not return any output.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Hello from schedule by annotation");
    }
}
