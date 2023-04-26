<?php

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Attribute\Schedule;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;

trait AttributedCommandTrait
{
    private function setAttributedTasks(
        ?string $status = StatusInterface::STATUS_ACTIVE,
        ?string $resource = ResourceInterface::RESOURCE_ATTRIBUTE,
        array $props = []
    ): void {
        if (null !== $resource && ResourceInterface::RESOURCE_ATTRIBUTE !== $resource) {
            return;
        }

        if (method_exists($this, 'getApplication')) {
            $commands = $this->getApplication()->all();
        } else {
            $commands = array_map(
                function ($id) {
                    return $this->getContainer()->get($id);
                },
                $this->getContainer()->getParameter('console.command.ids')
            );
        }

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
                if (null !== $status && $status !== $task[AttributeInterface::ATTRIBUTE_STATUS]) {
                    continue;
                }

                // Filter props if exists
                if ($props) {
                    $task = ArrayUtils::only($task, $props);
                }

                $this->tasks[] = $task;
            }
        }
    }
}