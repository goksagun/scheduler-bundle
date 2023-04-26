<?php

namespace Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command;

use Goksagun\SchedulerBundle\Attribute\Schedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[Schedule(name: 'schedule:attribute', expression: '* * * * *')]
#[Schedule(name: 'schedule:attribute --foo=bar', expression: '* * * * *')]
class AttributeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('schedule:attribute')
            ->setDescription('This command do not return any output.')
            ->addOption('foo', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $foo = $input->getOption('foo');

        if ($foo) {
            $output->writeln("This is an foo: {$foo}");
        }

        $output->writeln("Hello from schedule by attribute");

        return Command::SUCCESS;
    }
}
