<?php

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;
use Symfony\Component\Console\Application;

abstract class AbstractTaskLoader
{
    protected ScheduledTaskService $service;

    protected array $props = [];
    protected iterable $tasks = [];

    public function __construct(ScheduledTaskService $service)
    {
        $this->service = $service;
    }

    public function supports(?string $resource): bool
    {
        return null === $resource || $resource === static::RESOURCE;
    }

    protected function getApplication(): ?Application
    {
        return $this->service->getApplication();
    }

    protected function generateTaskId(array $annotationTask): string
    {
        return HashHelper::generateIdFromProps(ArrayUtils::only($annotationTask, HashHelper::GENERATED_PROPS));
    }

    protected function getTaskStatus(array $annotationTask): string
    {
        return $annotationTask[AttributeInterface::ATTRIBUTE_STATUS] ?? StatusInterface::STATUS_ACTIVE;
    }

    protected function filterPropsIfExists(array $task): array
    {
        if ($this->props) {
            $task = ArrayUtils::only($task, $this->props);
        }

        return $task;
    }

    protected function shouldFilterByStatus(?string $status, array $task): bool
    {
        return null !== $status && $status !== $task[AttributeInterface::ATTRIBUTE_STATUS];
    }
}