<?php

namespace Goksagun\SchedulerBundle\Command;

use Cron\CronExpression;
use Doctrine\ORM\EntityManagerInterface;
use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Process\ProcessInfo;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskLogRepository;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Goksagun\SchedulerBundle\Utils\StringHelper;
use Goksagun\SchedulerBundle\Utils\TaskHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
class ScheduledTaskCommand extends Command
{
    use ConfiguredCommandTrait, AnnotatedCommandTrait, DatabasedCommandTrait;

    const PROCESS_TIMEOUT = 3600 * 24; // 24 hours

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var ScheduledTaskRepository
     */
    private $repository;

    /**
     * @var ScheduledTaskLogRepository
     */
    private $logRepository;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var array
     */
    private $tasks = [];

    /**
     * @var ProcessInfo[]
     */
    private $processes = [];

    public function __construct(array $config, EntityManagerInterface $entityManager, ScheduledTaskRepository $repository, ScheduledTaskLogRepository $logRepository)
    {
        parent::__construct();

        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->logRepository = $logRepository;
    }

    protected function configure()
    {
        $this
            ->setName('scheduler:run')
            ->setDescription('Checks scheduled tasks and execute if exists any')
            ->addOption('async', 'a', InputOption::VALUE_OPTIONAL, 'Run task(s) asynchronously')
            ->addOption(
                'resource',
                'r',
                InputOption::VALUE_REQUIRED,
                'Run task(s) by resource. [values: "config|annotation|database"]'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $resource = $input->getOption('resource');

        if (!is_null($resource) && !in_array($resource, ResourceInterface::RESOURCES)) {
            throw new RuntimeException(
                sprintf(
                    'The option "resource" should be valid. [values: "%s"]',
                    implode('|', ResourceInterface::RESOURCES)
                )
            );
        }

        $this->setTasks($resource);

        if (!$this->config['enabled']) {
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

    private function setTasks($resource)
    {
        $this->setConfiguredTasks(StatusInterface::STATUS_ACTIVE, $resource);
        $this->setAnnotatedTasks(StatusInterface::STATUS_ACTIVE, $resource);
        $this->setDatabasedTasks(StatusInterface::STATUS_ACTIVE, $resource);
    }

    private function runScheduledTasks(InputInterface $input, OutputInterface $output)
    {
        $isAsync = $input->getOption('async');

        // Override is async property is set.
        if (null !== $this->config['async']) {
            $isAsync = $this->config['async'];
        }

        foreach ($this->getTasks() as $i => $task) {
            $errors = $this->validateTask($task);

            if (!empty($errors)) {
                $output->writeln(" - The task '{$task['name']}' has errors:");
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
            $scheduledTask = $this->createScheduledTaskLog($name, $times);

            if (!$command = $this->validateCommand($output, $i, $name, $scheduledTask)) {
                continue;
            }

            try {
                $arguments = $this->getCommandArguments($command, $name);

                if ($isAsync) {
                    $phpBinaryPath = $this->getPhpBinaryPath();
                    $projectRoot = $this->getProjectDir();

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

                    $process = new Process($asyncCommand, null, null, null, $timeout = static::PROCESS_TIMEOUT);

                    $process->start();

                    // Update scheduled task status as started.
                    $this->updateScheduledTaskStatusAsStarted($scheduledTask);

                    $this->processes[] = new ProcessInfo($process, $scheduledTask);

                    $output->writeln(" - Started async process: {$process->getCommandLine()}");
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

        $stop = $task['stop'] ?? null;
        if (!empty($stop)
            && !(DateHelper::isDateValid($stop) || DateHelper::isDateValid($stop, DateHelper::DATETIME_FORMAT))
        ) {
            $errors['stop'] = sprintf(
                'The stop should be date (%s) or datetime (%s).',
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

    private function getCommandArguments(Command $command, $name)
    {
        // Get parts from full task name.
        // First part is the command name the others arguments and options.
        $parts = TaskHelper::parseName($name);

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

    private function getEntityManager()
    {
        return $this->entityManager;
    }

    private function getRepository()
    {
        return $this->repository;
    }

    private function getLogRepository()
    {
        return $this->logRepository;
    }

    private function getLatestScheduledTaskLog($name, $status = null)
    {
        $criteria = [
            'name' => $name,
        ];

        if (null !== $status) {
            $criteria['status'] = $status;
        }

        return $this->getLogRepository()->findOneBy(
            $criteria,
            [
                'id' => 'desc',
            ]
        );
    }

    private function createScheduledTaskLog($name, $times = null)
    {
        $scheduledTask = new ScheduledTaskLog;

        if (!$this->config['log']) {
            return $scheduledTask;
        }

        $scheduledTask->setName($name);
        $scheduledTask->setRemaining($times);

        if ($latestExecutedScheduledTask = $this->getLatestScheduledTaskLog($name)) {
            $scheduledTask->setRemaining(
                $latestExecutedScheduledTask->getRemaining()
            );
        }

        if ($this->checkTableExists()) {
            $this->getLogRepository()->save($scheduledTask);
        }

        return $scheduledTask;
    }

    private function updateScheduledTaskStatus(
        ScheduledTaskLog $scheduledTask,
        $status,
        $message = null,
        $output = null
    ) {
        if (!$this->config['log']) {
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
            $this->getLogRepository()->save();
        }

        return $scheduledTask;
    }

    private function updateScheduledTaskStatusAsStarted(
        ScheduledTaskLog $scheduledTask,
        $message = null,
        $output = null
    ) {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTaskLog::STATUS_STARTED, $message, $output);
    }

    private function updateScheduledTaskStatusAsExecuted(
        ScheduledTaskLog $scheduledTask,
        $message = null,
        $output = null
    ) {
        if ($scheduledTask->getRemaining()) {
            $scheduledTask->decreaseRemaining();
        }

        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTaskLog::STATUS_EXECUTED, $message, $output);
    }

    private function updateScheduledTaskStatusAsFailed(ScheduledTaskLog $scheduledTask, $message = null, $output = null)
    {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTaskLog::STATUS_FAILED, $message, $output);
    }

    private function checkTableExists()
    {
        $em = $this->getEntityManager();

        $tableName = $em->getClassMetadata('SchedulerBundle:ScheduledTaskLog')->getTableName();

        return $em->getConnection()->getSchemaManager()->tablesExist((array)$tableName);
    }

    private function isTaskDue($task)
    {
        // Check remaining.
        if ($this->config['log'] && null !== $task['times']) {
            $scheduledTask = $this->getLatestScheduledTaskLog($task['name']);

            if ($scheduledTask instanceof ScheduledTaskLog && $scheduledTask->isRemainingZero()) {
                return false;
            }
        }

        $now = DateHelper::date();

        // Check start date.
        if (null !== $task['start']) {
            $start = DateHelper::date($task['start']);

            if ($start > $now) {
                return false;
            }
        }

        // Check start date.
        if (null !== $task['stop']) {
            $stop = DateHelper::date($task['stop']);

            if ($stop < $now) {
                return false;
            }
        }

        $expression = $task['expression'];

        $cron = CronExpression::factory($expression);

        // TRUE if the cron is due to run or FALSE if not.
        return $cron->isDue();
    }

    private function validateCommand(OutputInterface $output, int $i, string $name, ScheduledTaskLog $scheduledTask)
    {
        $commandName = TaskHelper::getCommandName($name);

        try {
            return $this->getApplication()->find($commandName);
        } catch (CommandNotFoundException $e) {
            // Log error message.
            $this->updateScheduledTaskStatusAsFailed($scheduledTask, $e->getMessage());

            $output->writeln(" - The '{$name}' task not found!");
        }

        return null;
    }

    public function setProjectDir(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    private function getPhpBinaryPath(): string
    {
        $phpBinaryFinder = new PhpExecutableFinder();

        if (!$phpBinaryPath = $phpBinaryFinder->find()) {
            throw new RuntimeException('PHP binary path not found.');
        }

        return $phpBinaryPath;
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

                $scheduledTask = $processInfo->getScheduledTaskLog();

                if (!$process->isSuccessful()) {
                    $this->updateScheduledTaskStatusAsFailed($scheduledTask, null, $process->getErrorOutput());

                    $output->writeln(
                        [
                            " - Failed process: {$process->getCommandLine()}",
                            '========== Error ==========',
                            $process->getErrorOutput(),
                        ]
                    );

                    continue;
                }

                $this->updateScheduledTaskStatusAsExecuted($scheduledTask, null, $process->getOutput());

                $output->writeln(
                    [
                        " - Successful process: {$process->getCommandLine()}",
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
