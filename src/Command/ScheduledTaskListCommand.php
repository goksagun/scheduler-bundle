<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Service\TaskLoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduledTaskListCommand extends Command
{
    const TABLE_HEADERS = ['#', 'Id', 'Name', 'Expression', 'Times', 'Start', 'Stop', 'Status', 'Resource'];

    /**
     * @var array<int, array>
     */
    private array $tasks = [];

    public function __construct(
        private readonly TaskLoaderInterface $loader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('scheduler:list')
            ->setDescription('List all scheduled tasks')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Scheduled task status')
            ->addOption('resource', null, InputOption::VALUE_REQUIRED, 'Scheduled task resource');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->setTasks(
            $input->getOption('status'),
            $input->getOption('resource')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $time = -microtime(true);

        $this->handleTaskList($input, $output);

        $time += microtime(true);

        $output->writeln("Rendered in {$time} seconds.");

        return Command::SUCCESS;
    }

    private function handleTaskList(InputInterface $input, OutputInterface $output): void
    {
        $i = 0;
        $rows = array_map(
            function ($row) use (&$i) {
                ++$i;

                return array_merge(['index' => $i], $row);
            },
            $this->tasks
        );

        $table = new Table($output);
        $table
            ->setHeaders(static::TABLE_HEADERS)
            ->setRows($rows);

        $table->render();
    }

    private function setTasks($status, $resource, $props = []): void
    {
        $this->tasks = $this->loader->load($status, $resource);
    }
}
