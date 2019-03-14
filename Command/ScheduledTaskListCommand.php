<?php

namespace Goksagun\SchedulerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ScheduledTaskListCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait, AnnotatedCommandTrait;

    /**
     * @var array
     */
    private $tasks;

    public function __construct(array $tasks)
    {
        parent::__construct();

        $this->tasks = $tasks;
    }

    protected function configure()
    {
        $this
            ->setName('scheduler:list')
            ->setDescription('List all scheduled tasks');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->setAnnotatedTasks();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handleTaskList($input, $output);
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
            ->setHeaders(['#', 'Name', 'Expression', 'Times', 'Start', 'End'])
            ->setRows($rows);

        $table->render();
    }
}
