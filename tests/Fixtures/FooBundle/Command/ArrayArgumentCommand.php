<?php

namespace Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ArrayArgumentCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('schedule:array-argument')
            ->setDescription('This command has array argument.')
            ->addArgument('foo', InputArgument::IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $foo = $input->getArgument('foo');

        $output->writeln(implode(' - ', $foo));

        return Command::SUCCESS;
    }
}
