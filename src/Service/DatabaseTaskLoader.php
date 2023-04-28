<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;

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

    private function getTask(ScheduledTask $database): array
    {
        return $database->toArray();
    }
}