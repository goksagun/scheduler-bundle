<?php

namespace Tests\Fixtures\FooBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NoOutputCommand extends ContainerAwareCommand
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
        //
    }

}
