<?php

namespace Tests\Fixtures\FooBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasedCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('schedule:database')
            ->setDescription('This command scheduled from database.')
            ->addOption('foo', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $foo = $input->getOption('foo');

        if ($foo) {
            $output->writeln("This is an foo: {$foo}");
        }

        $output->writeln("Hello from schedule by database");

        return 0;
    }
}
