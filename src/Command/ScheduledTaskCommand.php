<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Command;

use Cron\CronExpression;
use Doctrine\ORM\EntityManagerInterface;
use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Process\ProcessInfo;
use Goksagun\SchedulerBundle\Service\ScheduledTaskLogService;
use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Goksagun\SchedulerBundle\Utils\TaskHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
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

    private string $projectDir;

    /**
     * @var array<int, array>
     */
    private array $tasks = [];
    /**
     * @var array<int, ProcessInfo>
     */
    private array $processes = [];

    public function __construct(
        private readonly array $config,
        private readonly EntityManagerInterface $entityManager,
        private readonly ScheduledTaskService $service,
        private readonly ScheduledTaskLogService $logService
    ) {
        parent::__construct();
    }

    public function setProjectDir(string $projectDir): void
    {
        $this->projectDir = $projectDir;
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
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
            throw new RuntimeException(
                'Scheduled task(s) disabled. You should enable in scheduler.yaml config before running this command.'
            );
        }

        if (!$this->tasks) {
            throw new RuntimeException(
                'There is no task scheduled. You should add task in scheduler.yaml config file.'
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isAsync = $this->getAsyncOption($input);

        foreach ($this->getTasks() as $task) {
            if (!$this->isTaskValid($task, $output)) {
                continue;
            }

            if (!$this->isTaskDue($task)) {
                continue;
            }

            $this->executeTask($task, $isAsync, $output);
        }

        if ($isAsync) {
            $this->finishAsyncProcesses($output);
        }

        return Command::SUCCESS;
    }

    private function setTasks(?string $resource): void
    {
        $this->setConfiguredTasks(StatusInterface::STATUS_ACTIVE, $resource);
        $this->setAnnotatedTasks(StatusInterface::STATUS_ACTIVE, $resource);
        $this->setDatabasedTasks(StatusInterface::STATUS_ACTIVE, $resource);
    }

    private function getAsyncOption(InputInterface $input): bool
    {
        if (null !== $this->config['async']) {
            return $this->config['async'];
        }

        return $input->getOption('async');
    }

    private function isTaskValid(array $task, OutputInterface $output): bool
    {
        $errors = $this->validateTask($task);

        if (!empty($errors)) {
            $output->writeln("The task '{$task['name']}' has errors:");
            foreach ($errors as $error) {
                $output->writeln(sprintf('  - %s', $error));
            }

            return false;
        }

        return true;
    }

    private function validateTask(array $task): array
    {
        return (new TaskValidator())->validateTask($task);
    }

    private function isTaskDue(array $task): bool
    {
        if (
            $this->isTaskPastStartDate($task)
            && $this->isTaskNotPastEndDate($task)
            && $this->isTaskNotExceededMaxExecutions($task)) {
            $cron = CronExpression::factory($task['expression']);

            return $cron->isDue();
        }

        return false;
    }

    private function isTaskPastStartDate(array $task): bool
    {
        $start = $task['start'];
        if (null === $start) {
            return true;
        }

        $now = DateHelper::date();
        $startDate = DateHelper::date($start);

        return $startDate <= $now;
    }

    private function isTaskNotPastEndDate(array $task): bool
    {
        $end = $task['stop'];
        if (null === $end) {
            return true;
        }

        $now = DateHelper::date();
        $endDate = DateHelper::date($end);

        return $endDate >= $now;
    }

    private function isTaskNotExceededMaxExecutions(array $task): bool
    {
        if (!$this->isLoggingEnabled() || null === $task['times']) {
            return true;
        }

        $scheduledTask = $this->logService->getLatestScheduledTaskLog($task['name']);

        if (null === $scheduledTask) {
            return true;
        }

        return !$scheduledTask->isRemainingZero();
    }

    private function executeTask(array $task, bool $isAsync, OutputInterface $output): void
    {
        $name = $task['name'];
        $times = $task['times'];

        $scheduledTaskLog = $this->createScheduledTaskLogStatusAsQueued($name, $times);

        if (!$this->validateCommand($name, $scheduledTaskLog, $output)) {
            return;
        }

        try {
            if ($isAsync) {
                $this->startAsyncProcess($name, $scheduledTaskLog, $output);
            } else {
                $this->runSyncTask($name, $scheduledTaskLog, $output);
            }
        } catch (\Exception $e) {
            $this->handleTaskException($scheduledTaskLog, $e->getMessage(), $output, $name);
        }
    }

    private function startAsyncProcess(string $name, ScheduledTaskLog $scheduledTaskLog, OutputInterface $output): void
    {
        $phpBinaryPath = $this->getPhpBinaryPath();
        $projectRoot = $this->getProjectDir();

        $process = Process::fromShellCommandline($phpBinaryPath . ' ' . $projectRoot . '/bin/console ' . $name);
        $process->start();

        $this->updateScheduledTaskLogStatusAsStarted($scheduledTaskLog);

        $this->processes[] = new ProcessInfo($process, $scheduledTaskLog);

        $output->writeln(" - Started async process: {$process->getCommandLine()}");
    }

    private function runSyncTask(string $name, ScheduledTaskLog $scheduledTaskLog, OutputInterface $output): void
    {
        $command = $this->getCommand($name);
        $command->mergeApplicationDefinition();
        $stringInput = new StringInput($name);
        $stringInput->bind($command->getDefinition());

        $bufferedOutput = new BufferedOutput();
        $command->run($stringInput, $bufferedOutput);

        $this->updateScheduledTaskLogStatusAsExecuted(
            $scheduledTaskLog,
            'Task was successfully executed as synchronously.',
            $bufferedOutput->fetch()
        );

        $output->writeln("The '{$name}' completed!");
    }

    private function validateCommand(
        string $name,
        ScheduledTaskLog $scheduledTaskLog,
        OutputInterface $output
    ): ?Command {
        try {
            return $this->getCommand($name);
        } catch (CommandNotFoundException $e) {
            $this->updateScheduledTaskLogStatusAsFailed($scheduledTaskLog, $e->getMessage());

            $output->writeln("The '{$name}' task not found!");
        }

        return null;
    }

    private function getCommand(mixed $name): Command
    {
        return $this->getApplication()->find(TaskHelper::getCommandName($name));
    }

    private function handleTaskException(
        ScheduledTaskLog $scheduledTaskLog,
        string $message,
        OutputInterface $output,
        mixed $name
    ): void {
        $this->updateScheduledTaskLogStatusAsFailed($scheduledTaskLog, $message);

        $output->writeln("The '{$name}'  failed!");
    }

    private function getTasks(): \Generator
    {
        foreach ($this->tasks as $task) {
            yield $task;
        }
    }

    private function createScheduledTaskLogStatusAsQueued(string $name, ?int $times = null): ScheduledTaskLog
    {
        return $this->logService->create($name, $times);
    }

    private function updateScheduledTaskStatus(
        ScheduledTaskLog $scheduledTask,
        string $status,
        ?string $message = null,
        ?string $output = null
    ): ScheduledTaskLog {
        return $this->logService->updateStatus($scheduledTask, $status, $message, $output, $this->shouldStoreToDb());
    }

    private function updateScheduledTaskLogStatusAsStarted(
        ScheduledTaskLog $scheduledTask,
        ?string $message = null,
        ?string $output = null
    ): ScheduledTaskLog {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTaskLog::STATUS_STARTED, $message, $output);
    }

    private function updateScheduledTaskLogStatusAsExecuted(
        ScheduledTaskLog $scheduledTask,
        ?string $message = null,
        ?string $output = null
    ): ScheduledTaskLog {
        if ($scheduledTask->getRemaining()) {
            $scheduledTask->decreaseRemaining();
        }

        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTaskLog::STATUS_EXECUTED, $message, $output);
    }

    private function updateScheduledTaskLogStatusAsFailed(
        ScheduledTaskLog $scheduledTask,
        ?string $message = null,
        ?string $output = null
    ): ScheduledTaskLog {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTaskLog::STATUS_FAILED, $message, $output);
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

                $scheduledTaskLog = $processInfo->getScheduledTaskLog();

                if (!$process->isSuccessful()) {
                    $this->updateScheduledTaskLogStatusAsFailed(
                        $scheduledTaskLog,
                        'Task was failed executing as synchronously.',
                        $process->getErrorOutput()
                    );

                    $output->writeln(
                        [
                            " - Failed process: {$process->getCommandLine()}",
                            '========== Error ==========',
                            $process->getErrorOutput(),
                        ]
                    );

                    continue;
                }

                $this->updateScheduledTaskLogStatusAsExecuted(
                    $scheduledTaskLog,
                    'Task was successfully executed as asynchronously.',
                    $process->getOutput()
                );

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

    private function shouldStoreToDb(): bool
    {
        if (!$this->isLoggingEnabled()) {
            return false;
        }

        return $this->checkTableExists();
    }

    private function checkTableExists(): bool
    {
        $tableName = $this->getLogTableName();

        return $this->entityManager->getConnection()->createSchemaManager()->tablesExist((array)$tableName);
    }

    private function isLoggingEnabled(): bool
    {
        return $this->config['log'];
    }

    private function getLogTableName(): string
    {
        return $this->entityManager->getClassMetadata(ScheduledTaskLog::class)->getTableName();
    }
}
