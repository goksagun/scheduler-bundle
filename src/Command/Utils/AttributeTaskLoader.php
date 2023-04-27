<?php

namespace Goksagun\SchedulerBundle\Command\Utils;

use Goksagun\SchedulerBundle\Attribute\Schedule;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;

class AttributeTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    public function load(): array
    {
        $commands = $this->getApplication()->all();

        if (!$commands) {
            return [];
        }

        $tasks = [];
        foreach ($commands as $command) {
            $attributes = (new \ReflectionObject($command))->getAttributes();

            if (!$attributes) {
                continue;
            }

            foreach ($attributes as $attribute) {
                if ($attribute->getName() !== Schedule::class) {
                    continue;
                }

                $attributeTask = $attribute->getArguments();

                $task = [];
                foreach (AttributeInterface::ATTRIBUTES as $attr) {
                    if (AttributeInterface::ATTRIBUTE_ID === $attr) {
                        // Generate Id.
                        $id = HashHelper::generateIdFromProps(
                            ArrayUtils::only($attributeTask, HashHelper::GENERATED_PROPS)
                        );

                        $task[$attr] = $id;

                        continue;
                    }

                    if (AttributeInterface::ATTRIBUTE_STATUS === $attr) {
                        if (!isset($attributes[$attr])) {
                            $task[$attr] = StatusInterface::STATUS_ACTIVE;
                        } else {
                            $task[$attr] = $attributeTask[$attr];
                        }

                        continue;
                    }

                    if (AttributeInterface::ATTRIBUTE_RESOURCE === $attr) {
                        $task[$attr] = ResourceInterface::RESOURCE_ATTRIBUTE;

                        continue;
                    }

                    $task[$attr] = $attributeTask[$attr] ?? null;
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
        }

        return $tasks;
    }
}