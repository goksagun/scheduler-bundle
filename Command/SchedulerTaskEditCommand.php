<?php

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Utils\ArrayHelper;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerTaskEditCommand extends SchedulerTaskAddCommand
{
    protected function configure()
    {
        $this
            ->setName('scheduler:edit')
            ->setDescription('Edit scheduled task in database resource')
            ->addArgument('id', InputArgument::REQUIRED, 'Scheduled task id')
            ->addArgument('name', InputArgument::REQUIRED, 'Scheduled task name with own argument(s) and option(s)')
            ->addArgument('expression', InputArgument::REQUIRED, 'Scheduled task cron expression')
            ->addOption('times', null, InputOption::VALUE_REQUIRED, 'Scheduled task execution count')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Scheduled task execution start date and time')
            ->addOption('stop', null, InputOption::VALUE_REQUIRED, 'Scheduled task execution stop date and time')
            ->addOption(
                'status',
                null,
                InputOption::VALUE_REQUIRED,
                'Scheduled task status. [values: "active|inactive"]'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $options = ArrayHelper::only($input->getOptions(), ['times', 'start', 'stop', 'status']);

        $this->validateOptions($options);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');
        $name = $input->getArgument('name');
        $expression = $input->getArgument('expression');

        $times = $input->getOption('times');
        $start = $input->getOption('start');
        $stop = $input->getOption('stop');
        $status = $input->getOption('status');

        $this->updateTask($id, $name, $expression, $times, $start, $stop, $status);

        $output->writeln(sprintf('Scheduled task "%s" edited.', $name));
    }

    private function updateTask($id, $name, $expression, $times = null, $start = null, $stop = null, $status = null)
    {
        $repository = $this->getRepository();

        $scheduledTask = $repository->find($id);

        if (!$scheduledTask instanceof ScheduledTask) {
            throw new RuntimeException(sprintf('The task by id "%s" is not found', $id));
        }

        $scheduledTask
            ->setName($name)
            ->setExpression($expression)
            ->setTimes($times ? intval($times) : null)
            ->setStart($start ? DateHelper::date($start) : null)
            ->setStop($stop ? DateHelper::date($stop) : null);

        if ($status) {
            $scheduledTask->setStatus($status);
        }

        $repository->save($scheduledTask);

        return $scheduledTask;
    }

    /**
     * @param $id
     * @param $name
     * @param $expression
     * @param null $times
     * @param null $start
     * @param null $stop
     * @param null $status
     * @return ScheduledTask|object|null
     */
    public function editTask($id, $name, $expression, $times = null, $start = null, $stop = null, $status = null)
    {
        $this->validateOptions(compact('name', 'expression', 'times', 'start', 'stop', 'status'));

        return $this->updateTask($id, $name, $expression, $times, $start, $stop, $status);
    }
}
