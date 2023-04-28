<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Attribute\Schedule;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;

class AttributeTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    private iterable $tasks = [];

    public function load(?string $status = null, ?string $resource = null): array
    {
        if (!$this->supports($resource)) {
            return [];
        }

        foreach ($this->getCommands() as $command) {
            $attributes = $this->getScheduleAttributes($command);

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
                if ($this->props) {
                    $task = ArrayUtils::only($task, $this->props);
                }

                $this->tasks[] = $task;
            }
        }

        return $this->tasks;
    }

    public function supports(?string $resource): bool
    {
        return null === $resource || $resource === ResourceInterface::RESOURCE_ATTRIBUTE;
    }

    private function getCommands(): array
    {
        return $this->getApplication()->all();
    }

    private function getScheduleAttributes(mixed $command): array
    {
        $attributes = (new \ReflectionObject($command))->getAttributes();
        return $attributes;
    }
}