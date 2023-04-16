<?php

namespace Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GreetingSayHelloCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('greeting:say-hello')
            ->setDescription('Greeting say hello command')
            ->addArgument('name', InputArgument::REQUIRED, 'Name')
            ->addArgument('city', InputArgument::OPTIONAL, 'City')
            ->addOption('twice', null, InputOption::VALUE_NONE, 'Twice')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $city = $input->getArgument('city');

        $result = $city ? "Hello {$name} from {$city}" : "Hello {$name}";

        if ($times = $input->getOption('twice')) {
            $output->writeln($result);
        }

        $output->writeln($result);

        return Command::SUCCESS;
    }
}
