<?php

namespace Goksagun\SchedulerBundle\Command;

use AppBundle\Entity\ScheduledTask;
use AppBundle\Utils\Helper;
use Cron\CronExpression;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command scheduler allows you to fluently and expressively define your command
 * schedule within application itself. When using the scheduler, only a single
 * Cron entry is needed on your server. Your task schedule is defined in the
 * SCHEDULED_TASKS const. When using the scheduler, you only need to add the
 * following Cron entry to your server:
 *
 *      * * * * * php /path-to-your-project/bin/console scheduler:run >> /dev/null 2>&1
 *
 * This Cron will call the Laravel command scheduler every minute. When the scheduler:run
 * command is executed, application will evaluate your scheduled tasks and runs the tasks that are due.
 */
class ScheduledTaskCommand extends ContainerAwareCommand
{
    const TIME_FORMAT = 'Y-m-d H:i';

    /**
     * @var bool
     */
    private $enable;

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * ScheduledTaskCommand constructor.
     * @param bool $enable
     * @param array $config
     */
    public function __construct($enable, array $config)
    {
        parent::__construct();

        $this->enable = $enable;
        $this->config = $config;
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
            return;
        }

        // Run scheduled tasks if is due time.
        $this->runScheduledTasks($output);
    }

    protected function runScheduledTasks($output)
    {
        $scheduledTasks = $this->getScheduledTasks();

        foreach ($scheduledTasks as $name => $args) {
            $expression = is_array($args) ? $args['expression'] : $args;

            $cron = CronExpression::factory($expression);

            if ($cron->isDue()) {
                // Create new scheduled task entry.
                $scheduledTask = $this->createScheduledTask($name);

                $commandName = $this->getCommandName($name);

                try {
                    $command = $this->getApplication()->find($commandName);
                } catch (CommandNotFoundException $e) {
                    // Log error message.
                    $this->updateScheduledTaskStatusAndMessage(
                        $scheduledTask,
                        ScheduledTask::STATUS_FAILED,
                        $e->getMessage()
                    );

                    continue;
                }

                try {
                    $arguments = $this->getCommandArguments($command, $name, $args);

                    $input = new ArrayInput($arguments);

                    $command->run($input, $output);

                    // Update scheduled task status as executed.
                    $this->updateScheduledTaskStatus($scheduledTask, ScheduledTask::STATUS_EXECUTED);
                } catch (\Exception $e) {
                    // Log error message.
                    $this->updateScheduledTaskStatusAndMessage(
                        $scheduledTask,
                        ScheduledTask::STATUS_FAILED,
                        $e->getMessage()
                    );

                    continue;
                }
            }
        }
    }

    protected function createScheduledTask($name)
    {
        $scheduledTask = new ScheduledTask;
        $scheduledTask
            ->setName($name)
            ->setTime(new \DateTimeImmutable);

        $em = $this->getEntityManger();

        $em->persist($scheduledTask);
        $em->flush($scheduledTask);

        return $scheduledTask;
    }

    protected function updateScheduledTaskStatusAndMessage(ScheduledTask $scheduledTask, $status, $message) {
        $scheduledTask->setStatus($status);
        $scheduledTask->setMessage($message);

        $em = $this->getEntityManger();

        $em->flush($scheduledTask);

        return $scheduledTask;
    }

    protected function updateScheduledTaskStatus(ScheduledTask $scheduledTask, $status)
    {
        $scheduledTask->setStatus($status);

        $em = $this->getEntityManger();

        $em->flush($scheduledTask);

        return $scheduledTask;
    }

    protected function getScheduledTasks()
    {
        return $this->config;
    }

    protected function getCommandName($name)
    {
        $arguments = explode(' ', $name);

        return current($arguments);
    }

    protected function getCommandArguments($command, $name, $arguments = null)
    {
        if (is_array($arguments)) {
            unset($arguments['expression']);
            $arguments = array_merge((array)$name, array_values($arguments));
        } else {
            $arguments = explode(' ', $name);
        }

        $commandName = ['command' => array_shift($arguments)];

        $commandOptions = [];
        foreach ($arguments as $key => $argument) {
            if (Helper::startsWith($argument, '--')) {
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

    protected function getEntityManger()
    {
        if (null === $this->entityManager) {
            $this->entityManager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        }

        return $this->entityManager;
    }
}
