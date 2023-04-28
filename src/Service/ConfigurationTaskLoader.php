<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;

class ConfigurationTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    protected const RESOURCE = ResourceInterface::RESOURCE_CONFIG;

    public function load(?string $status = null, ?string $resource = null): array
    {
        if (!$this->supports($resource)) {
            return [];
        }

        foreach ($this->getTasks() as $configTask) {
            $task = $this->createTask($configTask);

            if (!$this->shouldFilterByStatus($status, $task)) {
                $this->tasks[] = $this->filterPropsIfExists($task);
            }
        }

        return $this->tasks;
    }

    private function getTasks(): array
    {
        return $this->service->getConfig()['tasks'];
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
                default:
                    $task[$attr] = $data[$attr] ?? null;
                    break;
            }
        }

        return $task;
    }
}