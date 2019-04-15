<?php

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Command\AnnotatedCommandTrait;
use Goksagun\SchedulerBundle\Command\ConfiguredCommandTrait;
use Goksagun\SchedulerBundle\Command\DatabasedCommandTrait;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduledTaskService
{
    use ConfiguredCommandTrait, AnnotatedCommandTrait, DatabasedCommandTrait;

    private $config;
    private $commands;
    private $container;
    private $repository;

    private $tasks = [];

    public function __construct(
        array $config,
        array $commands,
        ContainerInterface $container,
        ScheduledTaskRepository $repository
    ) {
        $this->config = $config;
        $this->commands = $commands;
        $this->container = $container;
        $this->repository = $repository;
    }

    public function list($status = null, $resource = null, $props = [])
    {
        $this->setTasks($status, $resource, $props);

        return $this->tasks;
    }

    public function create($name, $expression, $times = null, $start = null, $stop = null, $status = null)
    {
        $scheduledTask = new ScheduledTask();
        $scheduledTask
            ->setName($name)
            ->setExpression($expression);

        if ($times) {
            $scheduledTask->setTimes(intval($times));
        }

        if ($start) {
            $scheduledTask->setStart(DateHelper::date($start));
        }

        if ($stop) {
            $scheduledTask->setStop(DateHelper::date($stop));
        }

        if ($status) {
            $scheduledTask->setStatus($status);
        }

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

    public function update($id, $name, $expression, $times = null, $start = null, $stop = null, $status = null)
    {
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

    public function delete($id)
    {
        $scheduledTask = $this->repository->find($id);

        if (!$scheduledTask instanceof ScheduledTask) {
            throw new NotFoundHttpException(sprintf('The task by id "%s" is not found', $id));
        }

        $this->repository->delete($scheduledTask);
    }

    private function setTasks($status = null, $resource = null, $props = [])
    {
        $this->setConfiguredTasks($status, $resource, $props);
        $this->setAnnotatedTasks($status, $resource, $props);
        $this->setDatabasedTasks($status, $resource, $props);
    }

    private function getContainer()
    {
        return $this->container;
    }

    private function getRepository()
    {
        return $this->repository;
    }
}