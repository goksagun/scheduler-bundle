<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\DateHelper;

class DatabaseTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    private iterable $tasks = [];

    public function load(?string $status = null, ?string $resource = null): array
    {
        if (!$this->supports($resource)) {
            return [];
        }

        foreach ($this->getTasks() as $database) {
            $task = $this->createTaskFromDatabase($database);

            if (!$this->shouldFilterByStatus($status, $task)) {
                $this->tasks[] = $this->filterPropsIfExists($task);
            }
        }

        return $this->tasks;
    }

    /**
     * @return array<int, ScheduledTask>
     */
    public function getTasks(): array
    {
        return $this->service->getScheduledTasks();
    }

    public function supports(?string $resource): bool
    {
        return null === $resource || $resource === ResourceInterface::RESOURCE_DATABASE;
    }

    private function createTaskFromDatabase(ScheduledTask $database): array
    {
        $databaseTask = $database->toArray();

        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attr) {
            switch ($attr) {
                case AttributeInterface::ATTRIBUTE_RESOURCE:
                    $task[AttributeInterface::ATTRIBUTE_RESOURCE] = ResourceInterface::RESOURCE_DATABASE;
                    break;
                case AttributeInterface::ATTRIBUTE_START:
                    $task[AttributeInterface::ATTRIBUTE_START] = $this->getTaskStartTime($databaseTask);
                    break;
                case AttributeInterface::ATTRIBUTE_STOP:
                    $task[AttributeInterface::ATTRIBUTE_STOP] = $this->getTaskStopTime($databaseTask);
                    break;
                default:
                    $task[$attr] = $databaseTask[$attr] ?? null;
                    break;
            }
        }

        return $task;
    }

    private function getTaskStartTime(array $databaseTask): ?string
    {
        if ($databaseTask[AttributeInterface::ATTRIBUTE_START] instanceof \DateTimeInterface) {
            return $databaseTask[AttributeInterface::ATTRIBUTE_START]->format(DateHelper::DATETIME_FORMAT);
        }

        return null;
    }

    private function getTaskStopTime(array $databaseTask): ?string
    {
        if ($databaseTask[AttributeInterface::ATTRIBUTE_STOP] instanceof \DateTimeInterface) {
            return $databaseTask[AttributeInterface::ATTRIBUTE_STOP]->format(DateHelper::DATETIME_FORMAT);
        }

        return null;
    }

    private function shouldFilterByStatus(?string $status, array $task): bool
    {
        return null !== $status && $status !== $task[AttributeInterface::ATTRIBUTE_STATUS];
    }

    private function filterPropsIfExists(array $task): array
    {
        if ($this->props) {
            $task = ArrayUtils::only($task, $this->props);
        }

        return $task;
    }
}