<?php

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayHelper;
use Goksagun\SchedulerBundle\Utils\HashHelper;

trait ConfiguredCommandTrait
{
    public function setConfiguredTasks(
        $status = StatusInterface::STATUS_ACTIVE,
        $resource = ResourceInterface::RESOURCE_CONFIG,
        $props = []
    ) {
        if (null !== $resource && ResourceInterface::RESOURCE_CONFIG !== $resource) {
            return;
        }

        foreach ($this->config['tasks'] as $configTask) {
            $task = [];
            foreach (AttributeInterface::ATTRIBUTES as $attribute) {
                if (AttributeInterface::ATTRIBUTE_ID == $attribute) {
                    // Generate Id.
                    $id = HashHelper::generateIdFromProps(
                        ArrayHelper::only($configTask, HashHelper::GENERATED_PROPS)
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
            if (null !== $status && $status !== $task[AttributeInterface::ATTRIBUTE_STATUS]) {
                continue;
            }

            // Filter props if exists
            if ($props) {
                $task = ArrayHelper::only($task, $props);
            }

            array_push($this->tasks, $task);
        }
    }
}