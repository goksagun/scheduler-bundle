<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Cron\CronExpression;
use Goksagun\SchedulerBundle\Command\AnnotatedCommandTrait;
use Goksagun\SchedulerBundle\Command\ConfiguredCommandTrait;
use Goksagun\SchedulerBundle\Command\DatabasedCommandTrait;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Goksagun\SchedulerBundle\Utils\TaskHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

class ScheduledTaskService
{
    use ConfiguredCommandTrait;
    use AnnotatedCommandTrait;
    use DatabasedCommandTrait;

    private array $config;
    private ContainerInterface $container;
    private Application $application;
    private ScheduledTaskRepository $repository;

    private array $tasks = [];

    public function __construct(
        array $config,
        ContainerInterface $container,
        KernelInterface $kernel,
        ScheduledTaskRepository $repository
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->application = new Application($kernel);
        $this->repository = $repository;
    }

    private function getRepository(): ScheduledTaskRepository
    {
        return $this->repository;
    }

    public function list($status = null, $resource = null, $props = []): array
    {
        $this->setTasks($status, $resource, $props);

        return $this->tasks;
    }

    public function create(
        string $name,
        string $expression,
        ?int $times = null,
        ?string $start = null,
        ?string $stop = null,
        ?string $status = null
    ): ScheduledTask {
        $scheduledTask = ScheduledTaskBuilderFactory::create($name, $expression)
            ->times($times)
            ->start($start ? DateHelper::date($start) : null)
            ->stop($stop ? DateHelper::date($stop) : null)
            ->status($status)
            ->build();

        $this->repository->save($scheduledTask);

        return $scheduledTask;
    }

    public function get($id)
    {
        $this->setTasks();

        $task = array_filter(
            $this->tasks,
            function ($task) use ($id) {
                return $id == $task['id'];
            }
        );

        return current($task);
    }

    public function update(
        $id,
        $name,
        $expression,
        $times = null,
        $start = null,
        $stop = null,
        $status = null
    ): ScheduledTask {
        $scheduledTask = $this->repository->find($id);

        if (!$scheduledTask instanceof ScheduledTask) {
            throw new NotFoundHttpException(sprintf('The task by id "%s" is not found', $id));
        }

        $scheduledTask
            ->setName($name)
            ->setExpression($expression)
            ->setTimes($times ? intval($times) : null)
            ->setStart($start ? DateHelper::date($start) : null)
            ->setStop($stop ? DateHelper::date($stop) : null);

        if ($status) {
            $scheduledTask->setStatus($status);
        }

        $this->repository->save($scheduledTask);

        return $scheduledTask;
    }

    public function delete($id): void
    {
        $scheduledTask = $this->repository->find($id);

        if (!$scheduledTask instanceof ScheduledTask) {
            throw new NotFoundHttpException(sprintf('The task by id "%s" is not found', $id));
        }

        $this->repository->delete($scheduledTask);
    }

    public function isValidName($name): bool
    {
        return $this->application->has(TaskHelper::getCommandName($name));
    }

    public function isValidExpression($expression): bool
    {
        return CronExpression::isValidExpression($expression);
    }

    private function setTasks(?string $status = null, ?string $resource = null, array $props = []): void
    {
        $this->setConfiguredTasks($status, $resource, $props);
        $this->setAnnotatedTasks($status, $resource, $props);
        $this->setDatabasedTasks($status, $resource, $props);
    }

    private function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}