<?php

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduledTaskListCommand extends Command
{
    use ConfiguredCommandTrait, AnnotatedCommandTrait, DatabasedCommandTrait;

    const TABLE_HEADERS = ['#', 'Id', 'Name', 'Expression', 'Times', 'Start', 'Stop', 'Status', 'Resource'];

    private array $config;

    /**
     * @var array<int, array>
     */
    private array $tasks = [];

    private ScheduledTaskRepository $repository;

    private ScheduledTaskService $service;

    public function __construct(array $config, ScheduledTaskRepository $repository, ScheduledTaskService $service)
    {
        parent::__construct();

        $this->config = $config;
        $this->repository = $repository;
        $this->service = $service;
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
        $time = -microtime(1);

        $this->handleTaskList($input, $output);

        $time += microtime(1);

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
        $this->setConfiguredTasks($status, $resource, $props);
        $this->setAnnotatedTasks($status, $resource, $props);
        $this->setDatabasedTasks($status, $resource, $props);
    }
}
