<?php

namespace Goksagun\SchedulerBundle\Command;

use Cron\CronExpression;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Utils\StringHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
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
class ScheduledTaskCommand extends ContainerAwareCommand
{
    /**
     * @var bool
     */
    private $enable;

    /**
     * @var bool
     */
    private $log;

    /**
     * @var array
     */
    private $tasks;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * ScheduledTaskCommand constructor.
     * @param bool $enable
     * @param bool $log
     * @param array $tasks
     */
    public function __construct(bool $enable, bool $log, array $tasks)
    {
        parent::__construct();

        $this->enable = $enable;
        $this->log = $log;
        $this->tasks = $tasks;
    }

    protected function configure()
    {
        $this
            ->setName('scheduler:run')
            ->setDescription('Checks scheduled tasks and execute if exists any')
            ->addOption('async', 'a', InputOption::VALUE_NONE, 'Run task(s) asynchronously')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->enable) {
            $output->writeln(
                'Scheduled task(s) disabled. You should enable in scheduler.yml config before running this command.'
            );

            return;
        }

        if (!$this->tasks) {
            $output->writeln('There is no task scheduled. You should add task in scheduler.yml config file.');

            return;
        }

        $this->runScheduledTasks($input, $output);
    }

    private function runScheduledTasks(InputInterface $input, OutputInterface $output)
    {
        $isAsync = $input->getOption('async');

        $scheduledTasks = $this->getScheduledTasks();

        foreach ($scheduledTasks as $i => $scheduledTask) {
            $name = $scheduledTask['name'] ?? null;
            if (empty($name)) {
                throw new \InvalidArgumentException("The task command name should be defined.");
            }

            $expression = $scheduledTask['expression'] ?? null;
            if (empty($expression)) {
                throw new \InvalidArgumentException("The task expression should be defined.");
            }

            $cron = CronExpression::factory($expression);

            // TRUE if the cron is due to run or FALSE if not.
            if (!$cron->isDue()) {
                // Remove task from the list.
                unset($scheduledTasks[$i]);

                continue;
            }

            if ($this->log) {
                // Create scheduled task status as queued.
                $scheduledTask = $this->createScheduledTask($name);
            }

            $commandName = $this->getCommandName($name);

            try {
                $command = $this->getApplication()->find($commandName);
            } catch (CommandNotFoundException $e) {
                if ($this->log) {
                    // Log error message.
                    $this->updateScheduledTaskStatusAsFailed($scheduledTask, $e->getMessage());
                }

                $output->writeln("The '{$name}' task not found!");

                // Remove task from the list.
                unset($scheduledTasks[$i]);

                continue;
            }

            try {
                if ($isAsync) {
                    $phpBinaryFinder = new PhpExecutableFinder();
                    $phpBinaryPath = $phpBinaryFinder->find();

                    $projectRoot = $this->getContainer()->get('kernel')->getProjectDir();

                    $asyncCommand = [$phpBinaryPath, $projectRoot . '/bin/console', $name];

                    ${'process'.$i} = new Process($asyncCommand);

                    ${'process'.$i}->start();
                } else {
                    $arguments = $this->getCommandArguments($command, $name);

                    $input = new ArrayInput($arguments);

                    $command->run($input, $output);
                }

                if ($this->log) {
                    // Update scheduled task status as executed.
                    $this->updateScheduledTaskStatusAsExecuted($scheduledTask);
                }

                $output->writeln("The '{$name}' completed!");
            } catch (\Exception $e) {
                if ($this->log) {
                    // Log error message.
                    $this->updateScheduledTaskStatusAsFailed($scheduledTask, $e->getMessage());
                }

                $output->writeln("The '{$name}'  failed!");

                // Remove task from the list.
                unset($scheduledTasks[$i]);

                continue;
            }
        }

        // Complete task(s) if is async.
        if ($isAsync) {
            foreach ($scheduledTasks as $j => $scheduledTask) {
                ${'process'.$j}->wait();
            }
        }
    }

    private function getScheduledTasks()
    {
        return $this->tasks;
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
            'command' => array_shift($parts)
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

            $arguments = array_merge($arguments, array_combine(
                array_slice($argumentNames, 0, count($parts)),
                $parts
            ));
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
            $this->entityManager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        }

        return $this->entityManager;
    }

    private function createScheduledTask($name)
    {
        $scheduledTask = new ScheduledTask;
        $scheduledTask->setName($name);

        if ($this->checkTableExists()) {
            $this->getEntityManger()->getRepository('SchedulerBundle:ScheduledTask')->save($scheduledTask);
        }

        return $scheduledTask;
    }

    private function updateScheduledTaskStatus(ScheduledTask $scheduledTask, $status, $message = null)
    {
        $scheduledTask->setStatus($status);
        if (null !== $message) {
            $scheduledTask->setMessage($message);
        }

        if ($this->checkTableExists()) {
            $this->getEntityManger()->getRepository('SchedulerBundle:ScheduledTask')->save();
        }

        return $scheduledTask;
    }

    private function updateScheduledTaskStatusAsExecuted(ScheduledTask $scheduledTask, $message = null)
    {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTask::STATUS_EXECUTED, $message);
    }

    private function updateScheduledTaskStatusAsFailed(ScheduledTask $scheduledTask, $message = null)
    {
        return $this->updateScheduledTaskStatus($scheduledTask, ScheduledTask::STATUS_FAILED, $message);
    }

    private function checkTableExists()
    {
        $em = $this->getEntityManger();

        $tableName = $em->getClassMetadata('SchedulerBundle:ScheduledTask')->getTableName();

        return $em->getConnection()->getSchemaManager()->tablesExist((array)$tableName);
    }
}
