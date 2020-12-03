<?php

namespace Tests\Fixtures\FooBundle\Command;

use Goksagun\SchedulerBundle\Annotation\Schedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Schedule(name="schedule:annotate", expression="*\/10 * * * *")
 * @Schedule(name="schedule:annotate --foo=bar", expression="* * * * *")
 */
class AnnotatedCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('schedule:annotate')
            ->setDescription('This command do not return any output.')
            ->addOption('foo', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $foo = $input->getOption('foo');

        if ($foo) {
            $output->writeln("This is an foo: {$foo}");
        }

        $output->writeln("Hello from schedule by annotation");
    }
}
