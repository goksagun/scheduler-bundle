<?php

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Goksagun\SchedulerBundle\Utils\ArrayHelper;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerTaskEditCommand extends Command
{
    private ScheduledTaskService $service;

    public function __construct(ScheduledTaskService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    protected function configure(): void
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

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $options = ArrayHelper::only($input->getOptions(), ['times', 'start', 'stop', 'status']);

        $this->validateOptions($options);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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

        return 0;
    }

    private function updateTask(
        $id,
        $name,
        $expression,
        $times = null,
        $start = null,
        $stop = null,
        $status = null
    ): void {
        $this->service->update($id, $name, $expression, $times, $start, $stop, $status);
    }

    protected function validateOptions(array $options): void
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
