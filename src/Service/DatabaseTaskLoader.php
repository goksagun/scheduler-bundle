<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Utils\DateHelper;

class DatabaseTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    protected const RESOURCE = ResourceInterface::RESOURCE_DATABASE;

    public function load(?string $status = null, ?string $resource = null): array
    {
        if (!$this->supports($resource)) {
            return [];
        }

        foreach ($this->getTasks() as $database) {
            $task = $this->createTask($this->getTask($database));

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

    private function createTask(array $data): array
    {
        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attr) {
            switch ($attr) {
                case AttributeInterface::ATTRIBUTE_ID:
                    $task[AttributeInterface::ATTRIBUTE_ID] = $this->generateTaskId($data);
                    break;
                case AttributeInterface::ATTRIBUTE_STATUS:
                    $task[AttributeInterface::ATTRIBUTE_STATUS] = $this->getTaskStatus($data);
                    break;
                case AttributeInterface::ATTRIBUTE_RESOURCE:
                    $task[AttributeInterface::ATTRIBUTE_RESOURCE] = self::RESOURCE;
                    break;
                case AttributeInterface::ATTRIBUTE_START:
                    $task[AttributeInterface::ATTRIBUTE_START] = $this->getTaskStartTime($data);
                    break;
                case AttributeInterface::ATTRIBUTE_STOP:
                    $task[AttributeInterface::ATTRIBUTE_STOP] = $this->getTaskStopTime($data);
                    break;
                default:
                    $task[$attr] = $data[$attr] ?? null;
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

    private function getTask(ScheduledTask $database): array
    {
        return $database->toArray();
    }
}