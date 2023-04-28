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

    private iterable $tasks = [];

    public function load(?string $status = null, ?string $resource = null): array
    {
        if (!$this->supports($resource)) {
            return [];
        }

        foreach ($this->getTasks() as $configTask) {
            $task = $this->createTaskFromConfiguration($configTask);

            if ($this->shouldFilterByStatus($status, $task)) {
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

    private function getTasks(): array
    {
        return $this->service->getConfig()['tasks'];
    }

    public function supports(?string $resource): bool
    {
        return null === $resource || $resource === ResourceInterface::RESOURCE_CONFIG;
    }

    private function createTaskFromConfiguration(array $configTask): array
    {
        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attribute) {
            switch ($attribute) {
                case AttributeInterface::ATTRIBUTE_ID:
                    $task[AttributeInterface::ATTRIBUTE_ID] = $this->generateTaskId($configTask);
                    break;
                case AttributeInterface::ATTRIBUTE_STATUS:
                    $task[AttributeInterface::ATTRIBUTE_STATUS] = $this->getTaskStatus($configTask);
                    break;
                case AttributeInterface::ATTRIBUTE_RESOURCE:
                    $task[$attribute] = ResourceInterface::RESOURCE_CONFIG;
                    break;
                default:
                    $task[$attribute] = $configTask[$attribute] ?? null;
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
}