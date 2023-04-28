<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Cron\CronExpression;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Goksagun\SchedulerBundle\Utils\TaskHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

class ScheduledTaskService
{
    public function __construct(
        private readonly array $config,
        private readonly KernelInterface $kernel,
        private readonly ScheduledTaskRepository $repository,
    ) {
    }

    public function getConfig(): array
    {
        return $this->config;
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
        return $this->getApplication()->has(TaskHelper::getCommandName($name));
    }

    public function isValidExpression($expression): bool
    {
        return CronExpression::isValidExpression($expression);
    }

    public function getScheduledTasks(): array
    {
        return $this->repository->findAll();
    }

    public function getApplication(): Application
    {
        return new Application($this->kernel);
    }
}