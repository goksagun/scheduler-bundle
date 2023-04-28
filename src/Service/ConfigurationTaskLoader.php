<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;

class ConfigurationTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    private const RESOURCE = ResourceInterface::RESOURCE_CONFIG;
    private iterable $tasks = [];

    public function load(?string $status = null, ?string $resource = null): array
    {
        if (!$this->supports($resource)) {
            return [];
        }

        foreach ($this->getTasks() as $configTask) {
            $task = $this->createTaskFromConfiguration($configTask);

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

    private function createTaskFromConfiguration(array $configTask): array
    {
        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attr) {
            switch ($attr) {
                case AttributeInterface::ATTRIBUTE_ID:
                    $task[AttributeInterface::ATTRIBUTE_ID] = $this->generateTaskId($configTask);
                    break;
                case AttributeInterface::ATTRIBUTE_STATUS:
                    $task[AttributeInterface::ATTRIBUTE_STATUS] = $this->getTaskStatus($configTask);
                    break;
                case AttributeInterface::ATTRIBUTE_RESOURCE:
                    $task[AttributeInterface::ATTRIBUTE_RESOURCE] = self::RESOURCE;
                    break;
                default:
                    $task[$attr] = $configTask[$attr] ?? null;
                    break;
            }
        }

        return $task;
    }

    private function generateTaskId(array $configTask): string
    {
        return HashHelper::generateIdFromProps(ArrayUtils::only($configTask, HashHelper::GENERATED_PROPS));
    }

    private function getTaskStatus(array $configTask): string
    {
        return $configTask[AttributeInterface::ATTRIBUTE_STATUS] ?? StatusInterface::STATUS_ACTIVE;
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