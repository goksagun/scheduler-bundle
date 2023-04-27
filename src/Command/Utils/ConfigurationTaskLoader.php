<?php

namespace Goksagun\SchedulerBundle\Command\Utils;

use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;

class ConfigurationTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    public function load(): array
    {
        $tasks = [];
        foreach ($this->getTasks() as $configTask) {
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

            // Filter by status
            if (null !== $this->status && $this->status !== $task[AttributeInterface::ATTRIBUTE_STATUS]) {
                continue;
            }

            // Filter props if exists
            if ($this->props) {
                $task = ArrayUtils::only($task, $this->props);
            }

            $tasks[] = $task;
        }

        return $tasks;
    }

    private function getTasks(): array
    {
        return $this->service->getConfig()['tasks'];
    }
}