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

            // Filter by status
            if (null !== $status && $status !== $task[AttributeInterface::ATTRIBUTE_STATUS]) {
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

    private function createTaskFromConfiguration(mixed $configTask): array
    {
        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attribute) {
            if (AttributeInterface::ATTRIBUTE_ID == $attribute) {
                // Generate Id.
                $id = HashHelper::generateIdFromProps(
                    ArrayUtils::only($configTask, HashHelper::GENERATED_PROPS)
                );

                $task[$attribute] = $id;

                continue;
            }

            if (AttributeInterface::ATTRIBUTE_STATUS == $attribute) {
                if (!isset($configTask[$attribute])) {
                    $task[$attribute] = StatusInterface::STATUS_ACTIVE;
                } else {
                    $task[$attribute] = $configTask[$attribute];
                }

                continue;
            }

            if (AttributeInterface::ATTRIBUTE_RESOURCE == $attribute) {
                $task[$attribute] = ResourceInterface::RESOURCE_CONFIG;

                continue;
            }

            $task[$attribute] = $configTask[$attribute] ?? null;
        }

        return $task;
    }
}