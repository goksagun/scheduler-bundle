<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

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
            $databaseTask = $database->toArray();

            $task = [];
            foreach (AttributeInterface::ATTRIBUTES as $attribute) {
                if (AttributeInterface::ATTRIBUTE_RESOURCE == $attribute) {
                    $task[$attribute] = ResourceInterface::RESOURCE_DATABASE;

                    continue;
                }

                if (AttributeInterface::ATTRIBUTE_START == $attribute
                    && $databaseTask[AttributeInterface::ATTRIBUTE_START] instanceof \DateTimeInterface
                ) {
                    $task[AttributeInterface::ATTRIBUTE_START] = $databaseTask[AttributeInterface::ATTRIBUTE_START]->format(
                        DateHelper::DATETIME_FORMAT
                    );

                    continue;
                }

                if (AttributeInterface::ATTRIBUTE_STOP == $attribute
                    && $databaseTask[AttributeInterface::ATTRIBUTE_STOP] instanceof \DateTimeInterface
                ) {
                    $task[AttributeInterface::ATTRIBUTE_STOP] = $databaseTask[AttributeInterface::ATTRIBUTE_STOP]->format(
                        DateHelper::DATETIME_FORMAT
                    );

                    continue;
                }

                $task[$attribute] = $databaseTask[$attribute] ?? null;
            }

            // Filter by status
            if (null !== $status && $task[AttributeInterface::ATTRIBUTE_STATUS] !== $status) {
                continue;
            }

            // Filter props if exists
            if ($this->props) {
                $task = ArrayUtils::only($task, $this->props);
            }

            $this->tasks[] = $task;
        }

        return $this->tasks;
    }

    public function getTasks(): array
    {
        return $this->service->getScheduledTasks();
    }

    public function supports(?string $resource): bool
    {
        return null === $resource || $resource === ResourceInterface::RESOURCE_DATABASE;
    }
}