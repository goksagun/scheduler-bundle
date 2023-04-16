<?php

namespace Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NoOutputCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('no:output')
            ->setDescription('This command do not return any output.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return 0;
    }

}
