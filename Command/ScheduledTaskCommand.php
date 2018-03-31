<?php

namespace Goksagun\SchedulerBundle\Command;

use Cron\CronExpression;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setDescription('Checks scheduled tasks and execute them');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->enable) {
            $output->writeln('Scheduled task(s) disabled. You should enable in scheduler.yml config before running this command.');

            return;
        }

        if (!$this->tasks) {
            $output->writeln('There is no task scheduled. You should add task in scheduler.yml config file.');

            return;
        }

        $this->runScheduledTasks($output);
    }

    private function runScheduledTasks(OutputInterface $output)
    {
        $scheduledTasks = $this->getScheduledTasks();

        foreach ($scheduledTasks as $scheduledTask) {
            $name = $scheduledTask['name'] ?? null;
            if (empty($name)) {
                throw new \InvalidArgumentException("The task command name should be defined.");
            }

            $expression = $scheduledTask['expression'] ?? null;
            if (empty($expression)) {
                throw new \InvalidArgumentException("The task expression should be defined.");
            }

            $cron = CronExpression::factory($expression);

            if (!$cron->isDue()) {
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

                $output->writeln("The '{$name}' task failed!");

                continue;
            }

            try {
                $arguments = $this->getCommandArguments($command, $name);

                $input = new ArrayInput($arguments);

                $command->run($input, $output);

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

                continue;
            }
        }
    }

    private function getScheduledTasks()
    {
        return $this->tasks;
    }

    private function getCommandName($name)
    {
        $arguments = explode(' ', $name);

        return current($arguments);
    }

    private function getCommandArguments(Command $command, $name)
    {
        $arguments = explode(' ', $name);

        $commandName = ['command' => array_shift($arguments)];

        $commandOptions = [];
        foreach ($arguments as $key => $argument) {
            if (static::startsWith($argument, '--')) {
                $option = explode('=', $argument);

                $commandOptions[$option[0]] = $option[1] ?? null;
                unset($arguments[$key]);
            }
        }

        $definition = $command->getDefinition();

        $commandArguments = [];
        if ($arguments) {
            $argumentNames = array_slice(array_keys($definition->getArguments()), 0, count($arguments));

            $commandArguments = array_combine($argumentNames, $arguments);
        }

        return array_merge(
            $commandName,
            $commandArguments,
            $commandOptions
        );
    }

    private static function startsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) === 0) return true;
        }

        return false;
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
