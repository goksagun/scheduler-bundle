<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduledTaskDeleteCommand extends Command
{
    public function __construct(
        private readonly ScheduledTaskService $service
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('scheduler:delete')
            ->setDescription('Remove console command as a scheduled task from database')
            ->addArgument('id', InputArgument::REQUIRED, 'Scheduled task identifier');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');

        $this->service->delete($id);

        $output->writeln(sprintf('Scheduled task "%s" deleted.', $id));

        return Command::SUCCESS;
    }
}