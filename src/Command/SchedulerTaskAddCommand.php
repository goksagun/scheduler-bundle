<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerTaskAddCommand extends Command
{
    use SchedulerTaskAddEditValidateOptionsTrait;

    public function __construct(
        private readonly ScheduledTaskService $service
    ) {
        parent::__construct();
    }

    protected function configure(): void
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

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $options = ArrayUtils::only($input->getOptions(), ['times', 'start', 'stop', 'status']);

        $this->validateOptions($options);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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

    private function storeTask($name, $expression, $times = null, $start = null, $stop = null, $status = null): void
    {
        $this->service->create($name, $expression, $times, $start, $stop, $status);
    }
}
