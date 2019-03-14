<?php

namespace Goksagun\SchedulerBundle\Command;

use Cron\CronExpression;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Process\ProcessInfo;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Goksagun\SchedulerBundle\Utils\StringHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Command scheduler allows you to fluently and expressively define your command
 * schedule within application itself. When using the scheduler, only a single
 * Cron entry is needed on your server. Your task schedule is defined in the
 * scheduler.yml file. When using the scheduler, you only need to add the
 * following Cron entry to your server:
 *
 *      * * * * * php /path-to-your-project/bin/console scheduler:run >> /dev/null 2>&1
 *
 * This Cron will call the command scheduler every minute. When the scheduler:run
 * command is executed, application will evaluate your scheduled tasks and runs the tasks that are due.
 */
class ScheduledTaskCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait, AnnotatedCommandTrait;

    /**
     * @var bool
     */
    private $enable;

    /**
     * @var bool
     */
    private $async;

    /**
     * @var bool
     */
    private $log;

    /**
     * @var array
     */
    private $tasks;

    /**
     * @var ProcessInfo[]
     */
    private $processes = [];

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(bool $enable, $async, $log, array $tasks)
    {
        parent::__construct();

        $this->enable = $enable;
        $this->async = $async;
        $this->log = $log;
        $this->tasks = $tasks;
    }

    protected function configure()
    {
        $this
            ->setName('scheduler:run')
            ->setDescription('Checks scheduled tasks and execute if exists any')
            ->addOption('async', 'a', InputOption::VALUE_OPTIONAL, 'Run task(s) asynchronously')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List all task(s)');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->setAnnotatedTasks();

        if (!$this->enable) {
            $output->writeln(
                'Scheduled task(s) disabled. You should enable in scheduler.yml (or scheduler.yaml) config before running this command.'
            );

            return;
        }

        if (!$this->tasks) {
            $output->writeln(
                'There is no task scheduled. You should add task in scheduler.yml (or scheduler.yaml) config file.'
            );

            return;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->runScheduledTasks($input, $output);
    }

    private function runScheduledTasks(InputInterface $input, OutputInterface $output)
    {
        $isAsync = $input->getOption('async');

        // Override is async property is set.
        if (null !== $this->async) {
            $isAsync = $this->async;
        }

        foreach ($this->getTasks() as $i => $task) {
            $errors = $this->validateTask($task);

            if (!empty($errors)) {
                $output->writeln(sprintf('The task "%s" has errors:', $i));
                foreach ($errors as $error) {
                    $output->writeln(sprintf('  - %s', $error));
                }

                continue;
            }

            if (!$this->isTaskDue($task)) {
                continue;
            }

            $name = $task['name'];
            $times = $task['times'];

            // Create scheduled task status as queued.
            $scheduledTask = $this->createScheduledTask($name, $times);

            if (!$command = $this->validateCommand($output, $name, $scheduledTask)) {
                continue;
            }

            try {
                $arguments = $this->getCommandArguments($command, $name);

                if ($isAsync) {
                    $phpBinaryFinder = new PhpExecutableFinder();
                    $phpBinaryPath = $phpBinaryFinder->find();

                    $projectRoot = $this->container->get('kernel')->getProjectDir();

                    $asyncCommand = [$phpBinaryPath, $projectRoot.'/bin/console'];

                    // Add scheduled task command arguments to async process command.
                    foreach ($arguments as $key => $value) {
                        $commandArgument = $value;
                        if (StringHelper::startsWith($key, '--')) {
                            if (null !== $value) {
                                $commandArgument = $key.'='.$value;
                            } else {
                                $commandArgument = $key;
                            }
                        }

                        array_push($asyncCommand, $commandArgument);
                    }

                    $process = new Process($asyncCommand, null, null, null, $timeout = 3600);

                    $process->start();

                    // Update scheduled task status as started.
                    $this->updateScheduledTaskStatusAsStarted($scheduledTask);

                    $this->processes[] = new ProcessInfo($process, $scheduledTask);

                    $output->writeln("{$i} - Started async process: {$process->getCommandLine()}");
                } else {
                    $input = new ArrayInput($arguments);

                    $command->run($input, $output);

                    // Update scheduled task status as executed.
                    $this->updateScheduledTaskStatusAsExecuted($scheduledTask);

                    $output->writeln("The '{$name}' completed!");
                }
            } catch (\Exception $e) {
                // Log error message.
                $this->updateScheduledTaskStatusAsFailed($scheduledTask, $e->getMessage());

                $output->writeln("The '{$name}'  failed!");

                continue;
            }
        }

        // Finish task(s) if is async.
        if ($isAsync) {
            $this->finishAsyncProcesses($output);
        }
    }

    private function validateTask($task)
    {
        $errors = [];

        if (!isset($task['name'])) {
            $errors['name'] = "The task command name should be defined.";
        }

        if (!isset($task['expression'])) {
            $errors['expression'] = "The task command expression should be defined.";
        }

        $times = $task['times'] ?? null;
        if (!empty($times)) {
            if (!is_int($times)) {
                $errors['times'] = "The times should be integer.";
            }
        }

        $start = $task['start'] ?? null;
        if (!empty($start)
            && !(DateHelper::isDateValid($start) || DateHelper::isDateValid($start, DateHelper::DATETIME_FORMAT))
        ) {
            $errors['start'] = sprintf(
                'The start should be date (%s) or datetime (%s).',
                DateHelper::DATE_FORMAT,
                DateHelper::DATETIME_FORMAT
            );
        }

        $end = $task['end'] ?? null;
        if (!empty($end)
            && !(DateHelper::isDateValid($end) || DateHelper::isDateValid($end, DateHelper::DATETIME_FORMAT))
        ) {
            $errors['end'] = sprintf(
                'The end should be date (%s) or datetime (%s).',
                DateHelper::DATE_FORMAT,
                DateHelper::DATETIME_FORMAT
            );
        }

        return $errors;
    }

    private function getTasks()
    {
        foreach ($this->tasks as $task) {
            yield $task;
        }
    }

    private function parseName($name)
    {
        return explode(' ', preg_replace('!\s+!', ' ', $name));
    }

    private function getCommandName($name)
    {
        $parts = $this->parseName($name);

        return current($parts);
    }

    private function getCommandArguments(Command $command, $name)
    {
        // Get parts from full task name.
        // First part is the command name the others arguments and options.
        $parts = $this->parseName($name);

        // Shift command name from arguments.
        $arguments = [
            'command' => array_shift($parts),
        ];

        foreach ($parts as $key => $part) {
            if (StringHelper::startsWith($part, '--')) {
                $option = explode('=', $part);

                $arguments[$option[0]] = $option[1] ?? null;

                unset($parts[$key]);
            }
        }

        if ($parts) {
            // Remove "command" argument index
            $argumentNames = array_filter(
                array_keys($command->getDefinition()->getArguments()),
                function ($argumentName) {
                    return 'command' !== $argumentName;
                }
            );

            $arguments = array_merge(
                $arguments,
                array_combine(
                    array_slice($argumentNames, 0, count($parts)),
                    $parts
                )
            );
        }

        /*
         * [
         *      'command' => 'command-name'
         *      'argument-one' => 'argument-one-value',
         *      'argument-two' => 'argument-two-value',
         *      '--option-one' => 'option-one-value',
         *      '--option-two' => null,
         * ]
         */

        return $arguments;
    }

    private function getEntityManger()
    {
        if (null === $this->entityManager) {
            $this->entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        }

        return $this->entityManager;
    }

    private function getLatestScheduledTask($name, $status = null)
    {
        $criteria = [
            'name' => $name,
        ];

        if (null !== $status) {
            $criteria['status'] = $status;
        }

        return $this->getEntityManger()->getRepository('SchedulerBundle:ScheduledTask')->findOneBy(
            $criteria,
            [
                'id' => 'desc',
            ]
        );
    }

    private function createScheduledTask($name, $times = null)
    {
        $scheduledTask = new ScheduledTask;

        if (!$this->log) {
            return $scheduledTask;
        }

        $scheduledTask->setName($name);
        $scheduledTask->setRemaining($times);

        if ($latestExecutedScheduledTask = $this->getLatestScheduledTask($name)) {
            $scheduledTask->setRemaining(
                $latestExecutedScheduledTask->getRemaining()
            );
        }

        if ($this->checkTableExists()) {
            $this->getEntityManger()->getRepository('SchedulerBundle:ScheduledTask')->save($scheduledTask);
        }

        return $scheduledTask;
    }

    private function updateScheduledTaskStatus(ScheduledTask $scheduledTask, $status, $message = null, $output = null)
    {
        if (!$this->log) {
            return $scheduledTask;
        }

        $scheduledTask->setStatus($status);
        if (!empty($message)) {
            $scheduledTask->setMessage(StringHelper::limit($message, 252));
        }

        if (!empty($output)) {
            $scheduledTask->setOutput($output);
        }

        if ($this->checkTableExists()) {
            $this->getEntityManger()->getRepository('SchedulerBundle:ScheduledTask')->save();
        }

        return $scheduledTask;
    }

    private function updateScheduledTaskStatusAsStarted(ScheduledTask $scheduledTask, $message = null, $output = null)
    {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTask::STATUS_STARTED, $message, $output);
    }

    private function updateScheduledTaskStatusAsExecuted(ScheduledTask $scheduledTask, $message = null, $output = null)
    {
        if ($scheduledTask->getRemaining()) {
            $scheduledTask->decreaseRemaining();
        }

        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTask::STATUS_EXECUTED, $message, $output);
    }

    private function updateScheduledTaskStatusAsFailed(ScheduledTask $scheduledTask, $message = null, $output = null)
    {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTask::STATUS_FAILED, $message, $output);
    }

    private function checkTableExists()
    {
        $em = $this->getEntityManger();

        $tableName = $em->getClassMetadata('SchedulerBundle:ScheduledTask')->getTableName();

        return $em->getConnection()->getSchemaManager()->tablesExist((array)$tableName);
    }

    private function isTaskDue($task)
    {
        // Check remaining.
        if ($this->log && null !== $task['times']) {
            $scheduledTask = $this->getLatestScheduledTask($task['name']);

            if ($scheduledTask instanceof ScheduledTask && $scheduledTask->isRemainingZero()) {
                return false;
            }
        }

        $now = new \DateTime();

        // Check start date.
        if (null !== $task['start']) {
            $start = new \DateTime($task['start']);

            if ($start > $now) {
                return false;
            }
        }

        // Check start date.
        if (null !== $task['end']) {
            $end = new \DateTime($task['end']);

            if ($end < $now) {
                return false;
            }
        }

        $expression = $task['expression'];

        $cron = CronExpression::factory($expression);

        // TRUE if the cron is due to run or FALSE if not.
        return $cron->isDue();
    }

    private function validateCommand(OutputInterface $output, string $name, ScheduledTask $scheduledTask)
    {
        $commandName = $this->getCommandName($name);

        try {
            return $this->getApplication()->find($commandName);
        } catch (CommandNotFoundException $e) {
            // Log error message.
            $this->updateScheduledTaskStatusAsFailed($scheduledTask, $e->getMessage());

            $output->writeln("The '{$name}' task not found!");
        }

        return null;
    }

    private function finishAsyncProcesses(OutputInterface $output)
    {
        do {
            // Loop active process and remove if successful.
            foreach ($this->processes as $j => $processInfo) {
                $process = $processInfo->getProcess();

                if ($process->isRunning()) {
                    continue;
                }

                // Remove finished process from active processes list.
                unset($this->processes[$j]);

                $scheduledTask = $processInfo->getScheduledTask();

                if (!$process->isSuccessful()) {
                    $this->updateScheduledTaskStatusAsFailed($scheduledTask, null, $process->getErrorOutput());

                    $output->writeln(
                        [
                            "{$j} - Failed process: {$process->getCommandLine()}",
                            '========== Error ==========',
                            $process->getErrorOutput(),
                        ]
                    );

                    continue;
                }

                $this->updateScheduledTaskStatusAsExecuted($scheduledTask, null, $process->getOutput());

                $output->writeln(
                    [
                        "{$j} - Successful process: {$process->getCommandLine()}",
                        '========== Output ==========',
                        $process->getOutput(),
                    ]
                );
            }

            // Check every second.
            sleep(1);
        } while (count($this->processes));
    }
}
