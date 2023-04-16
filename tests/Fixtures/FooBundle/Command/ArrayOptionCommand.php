<?php

namespace Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ArrayOptionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('schedule:array-option')
            ->setDescription('This command has array option.')
            ->addOption('foo', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $foo = $input->getOption('foo');

        $output->writeln(implode(' - ', $foo));

        return 0;
    }
}
