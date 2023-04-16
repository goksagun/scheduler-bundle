<?php

namespace Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GreetingSayGoodbyeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('greeting:say-goodbye')
            ->setDescription('Greeting say goodbye command')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name')
            ->addOption('twice', null, InputOption::VALUE_NONE, 'Twice')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        if ($times = $input->getOption('twice')) {
            $output->writeln("Goodbye {$name}");
        }

        $output->writeln("Goodbye {$name}");

        return Command::SUCCESS;
    }

}
