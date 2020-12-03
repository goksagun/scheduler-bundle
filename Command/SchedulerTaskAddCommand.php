<?php

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Utils\ArrayHelper;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerTaskAddCommand extends Command
{
    /**
     * @var ScheduledTaskRepository
     */
    protected $repository;

    public function __construct(ScheduledTaskRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    protected function configure()
    {
        $this
            ->setName('scheduler:add')
            ->setDescription('Add console command as a scheduled task to database resource')
            ->addArgument('name', InputArgument::REQUIRED, 'Scheduled task name with own argument(s) and option(s)')
            ->addArgument('expression', InputArgument::REQUIRED, 'Scheduled task cron expression')
            ->addOption('times', null, InputOption::VALUE_REQUIRED, 'Scheduled task execution count')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Scheduled task execution start date and time')
            ->addOption('stop', null, InputOption::VALUE_REQUIRED, 'Scheduled task execution stop date and time')
            ->addOption(
                'status',
                null,
                InputOption::VALUE_REQUIRED,
                'Scheduled task status. [values: "active|inactive", default: "active"]'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $options = ArrayHelper::only($input->getOptions(), ['times', 'start', 'stop', 'status']);

        $this->validateOptions($options);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $expression = $input->getArgument('expression');

        $times = $input->getOption('times');
        $start = $input->getOption('start');
        $stop = $input->getOption('stop');
        $status = $input->getOption('status');

        $this->storeTask($name, $expression, $times, $start, $stop, $status);

        $output->writeln(sprintf('Command "%s" added to scheduled task list.', $name));

        return 0;
    }

    private function storeTask($name, $expression, $times = null, $start = null, $stop = null, $status = null)
    {
        $scheduledTask = new ScheduledTask();
        $scheduledTask
            ->setName($name)
            ->setExpression($expression);

        if ($times) {
            $scheduledTask->setTimes(intval($times));
        }

        if ($start) {
            $scheduledTask->setStart(DateHelper::date($start));
        }

        if ($stop) {
            $scheduledTask->setStop(DateHelper::date($stop));
        }

        if ($status) {
            $scheduledTask->setStatus($status);
        }

        $this->getRepository()->save($scheduledTask);

        return $scheduledTask;
    }

    /**
     * @param $name
     * @param $expression
     * @param null $times
     * @param null $start
     * @param null $stop
     * @param null $status
     * @return ScheduledTask
     */
    public function addTask($name, $expression, $times = null, $start = null, $stop = null, $status = null)
    {
        $this->validateOptions(compact('name', 'expression', 'times', 'start', 'stop', 'status'));

        return $this->storeTask($name, $expression, $times, $start, $stop, $status);
    }

    protected function getRepository()
    {
        return $this->repository;
    }

    protected function validateOptions(array $options)
    {
        if (!is_null($options['times']) && !is_numeric($options['times'])) {
            throw new RuntimeException('The option "times" should be numeric value.');
        }

        if (!is_null($options['start']) && !DateHelper::isDateValid($options['start'])) {
            throw new RuntimeException('The option "start" should be date or date and time value.');
        }

        if (!is_null($options['stop']) && !DateHelper::isDateValid($options['stop'])) {
            throw new RuntimeException('The option "stop" should be date or date and time value.');
        }

        if (!is_null($options['status']) && !in_array($options['status'], StatusInterface::STATUSES)) {
            throw new RuntimeException(
                sprintf(
                    'The option "status" should be valid. [values: "%s"]',
                    implode('|', StatusInterface::STATUSES)
                )
            );
        }
    }
}
