<?php

namespace Goksagun\SchedulerBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduledTaskListCommand extends Command
{
    use ConfiguredCommandTrait, AnnotatedCommandTrait, DatabasedCommandTrait;

    const TABLE_HEADERS = ['#', 'Id', 'Name', 'Expression', 'Times', 'Start', 'Stop', 'Status', 'Resource'];

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $tasks = [];

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(array $config, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('scheduler:list')
            ->setDescription('List all scheduled tasks')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Scheduled task status')
            ->addOption('resource', null, InputOption::VALUE_REQUIRED, 'Scheduled task resource');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->setTasks(
            $input->getOption('status'),
            $input->getOption('resource')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = -microtime(1);

        $this->handleTaskList($input, $output);

        $time += microtime(1);

        $output->writeln("Rendered in {$time} seconds.");
    }

    private function handleTaskList(InputInterface $input, OutputInterface $output)
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

    /**
     * @param null $status
     * @param null $resource
     * @param array $props
     * @return array
     */
    public function listTasks($status = null, $resource = null, $props = [])
    {
        $this->setTasks($status, $resource, $props);

        return $this->tasks;
    }

    private function getEntityManager()
    {
        return $this->entityManager;
    }

    private function setTasks($status, $resource, $props = [])
    {
        $this->setConfiguredTasks($status, $resource, $props);
        $this->setAnnotatedTasks($status, $resource, $props);
        $this->setDatabasedTasks($status, $resource, $props);
    }
}
