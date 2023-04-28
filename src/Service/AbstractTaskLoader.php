<?php

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Goksagun\SchedulerBundle\Utils\HashHelper;
use Symfony\Component\Console\Application;

abstract class AbstractTaskLoader
{
    public function __construct(
        protected readonly ScheduledTaskService $service,
        protected iterable $props = [],
        protected iterable $tasks = []
    ) {
    }

    protected function supports(?string $resource): bool
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

    protected function shouldFilterByStatus(?string $status, array $task): bool
    {
        return null !== $status && $status !== $task[AttributeInterface::ATTRIBUTE_STATUS];
    }

    protected function filterPropsIfExists(array $task): array
    {
        if ($this->props) {
            $task = ArrayUtils::only($task, $this->props);
        }

        return $task;
    }

    protected function createTask(array $data): array
    {
        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attr) switch ($attr) {
            case AttributeInterface::ATTRIBUTE_ID:
                $task[AttributeInterface::ATTRIBUTE_ID] = $this->generateTaskId($data);
                break;
            case AttributeInterface::ATTRIBUTE_STATUS:
                $task[AttributeInterface::ATTRIBUTE_STATUS] = $this->getTaskStatus($data);
                break;
            case AttributeInterface::ATTRIBUTE_START:
                $task[AttributeInterface::ATTRIBUTE_START] = $this->getTaskStartTime($data);
                break;
            case AttributeInterface::ATTRIBUTE_STOP:
                $task[AttributeInterface::ATTRIBUTE_STOP] = $this->getTaskStopTime($data);
                break;
            case AttributeInterface::ATTRIBUTE_RESOURCE:
                $task[AttributeInterface::ATTRIBUTE_RESOURCE] = static::RESOURCE;
                break;
            default:
                $task[$attr] = $data[$attr] ?? null;
                break;
        }

        return $task;
    }

    protected function getTaskStartTime(array $databaseTask): ?string
    {
        $startTime = $databaseTask[AttributeInterface::ATTRIBUTE_START] ?? null;
        if ($startTime instanceof \DateTimeInterface) {
            return $startTime->format(DateHelper::DATETIME_FORMAT);
        }

        return $startTime;
    }

    protected function getTaskStopTime(array $databaseTask): ?string
    {
        $stopTime = $databaseTask[AttributeInterface::ATTRIBUTE_STOP] ?? null;
        if ($stopTime instanceof \DateTimeInterface) {
            return $stopTime->format(DateHelper::DATETIME_FORMAT);
        }

        return $stopTime;
    }
}