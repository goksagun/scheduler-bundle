<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Command;

use Cron\CronExpression;
use Doctrine\ORM\EntityManagerInterface;
use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Process\ProcessInfo;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Service\ScheduledTaskLogService;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Goksagun\SchedulerBundle\Utils\TaskHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Command scheduler allows you to fluently and expressively define your command
 * schedule within application itself. When using the scheduler, only a single
 * Cron entry is needed on your server. Your task schedule is defined in the
 * scheduler.yaml file. When using the scheduler, you only need to add the
 * following Cron entry to your server:
 *
 *      * * * * * php /path-to-your-project/bin/console scheduler:run >> /dev/null 2>&1
 *
 * This Cron will call the command scheduler every minute. When the scheduler:run
 * command is executed, application will evaluate your scheduled tasks and runs the tasks that are due.
 */
class ScheduledTaskCommand extends Command
{
    use ConfiguredCommandTrait;
    use AnnotatedCommandTrait;
    use DatabasedCommandTrait;

    private array $config;

    private EntityManagerInterface $entityManager;

    private ScheduledTaskRepository $repository;

    private ScheduledTaskLogService $logService;

    private string $projectDir;

    private array $tasks = [];
    /**
     * @var $processes array<int, ProcessInfo>
     */
    private array $processes = [];

    public function __construct(
        array $config,
        EntityManagerInterface $entityManager,
        ScheduledTaskRepository $repository,
        ScheduledTaskLogService $logService,
    ) {
        parent::__construct();

        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->logService = $logService;
    }

    protected function configure(): void
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

    protected function initialize(InputInterface $input, OutputInterface $output): void
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
                'Scheduled task(s) disabled. You should enable in scheduler.yaml config before running this command.'
            );

            return;
        }

        if (!$this->tasks) {
            $output->writeln(
                'There is no task scheduled. You should add task in scheduler.yaml config file.'
            );

            return;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->runScheduledTasks($input, $output);

        return Command::SUCCESS;
    }

    private function setTasks(?string $resource): void
    {
        $this->setConfiguredTasks(StatusInterface::STATUS_ACTIVE, $resource);
        $this->setAnnotatedTasks(StatusInterface::STATUS_ACTIVE, $resource);
        $this->setDatabasedTasks(StatusInterface::STATUS_ACTIVE, $resource);
    }

    private function runScheduledTasks(InputInterface $input, OutputInterface $output): void
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

            if (!$command = $this->validateCommand($output, $name, $scheduledTask)) {
                continue;
            }

            try {
                if ($isAsync) {
                    $phpBinaryPath = $this->getPhpBinaryPath();
                    $projectRoot = $this->getProjectDir();

                    $asyncCommand = $phpBinaryPath . ' ' . $projectRoot . '/bin/console ' . $name;
                    $process = Process::fromShellCommandline($asyncCommand);

                    $process->start();

                    // Update scheduled task status as started.
                    $this->updateScheduledTaskStatusAsStarted($scheduledTask);

                    $this->processes[] = new ProcessInfo($process, $scheduledTask);

                    $output->writeln(" - Started async process: {$process->getCommandLine()}");
                } else {
                    $taskInput = new StringInput($name);
                    $command->mergeApplicationDefinition();
                    $taskInput->bind($command->getDefinition());

                    $command->run($taskInput, $output);

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

    private function validateTask(array $task): array
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

    private function getTasks(): \Generator
    {
        foreach ($this->tasks as $task) {
            yield $task;
        }
    }

    private function createScheduledTaskLog(string $name, ?int $times = null): ScheduledTaskLog
    {
        return $this->logService->create($name, $times);
    }

    private function updateScheduledTaskStatus(
        ScheduledTaskLog $scheduledTask,
        string $status,
        ?string $message = null,
        ?string $output = null
    ): ScheduledTaskLog {
        return $this->logService->updateStatus($scheduledTask, $status, $message, $output);
    }

    private function updateScheduledTaskStatusAsStarted(
        ScheduledTaskLog $scheduledTask,
        ?string $message = null,
        ?string $output = null
    ): ScheduledTaskLog {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTaskLog::STATUS_STARTED, $message, $output);
    }

    private function updateScheduledTaskStatusAsExecuted(
        ScheduledTaskLog $scheduledTask,
        ?string $message = null,
        ?string $output = null
    ): ScheduledTaskLog {
        if ($scheduledTask->getRemaining()) {
            $scheduledTask->decreaseRemaining();
        }

        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTaskLog::STATUS_EXECUTED, $message, $output);
    }

    private function updateScheduledTaskStatusAsFailed(
        ScheduledTaskLog $scheduledTask,
        ?string $message = null,
        ?string $output = null
    ): ScheduledTaskLog {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTaskLog::STATUS_FAILED, $message, $output);
    }

    private function checkTableExists(): bool
    {
        $tableName = $this->entityManager->getClassMetadata(ScheduledTaskLog::class)->getTableName();

        return $this->entityManager->getConnection()->createSchemaManager()->tablesExist((array)$tableName);
    }

    private function isTaskDue(array $task): bool
    {
        // Check remaining.
        if ($this->config['log'] && null !== $task['times']) {
            $scheduledTask = $this->logService->getLatestScheduledTaskLog($task['name']);

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

    private function validateCommand(
        OutputInterface $output,
        string $name,
        ScheduledTaskLog $scheduledTask
    ): ?Command {
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

    public function setProjectDir(string $projectDir): void
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

    private function finishAsyncProcesses(OutputInterface $output): void
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
