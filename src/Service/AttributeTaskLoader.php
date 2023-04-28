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
            if ($attributes = $this->getScheduleAttributes($command)) {
                $this->addTaskFromAttributes($attributes, $status);
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

        return array_filter($attributes, fn($attribute) => $attribute->getName() === Schedule::class);
    }

    private function addTaskFromAttributes(array $attributes, ?string $status): void
    {
        foreach ($attributes as $attribute) {
            $task = $this->createTaskFromAttribute($attribute, $attributes);

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

    private function createTaskFromAttribute(\ReflectionAttribute $attribute): array
    {
        $attributeTask = $attribute->getArguments();

        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attr) {
            if (AttributeInterface::ATTRIBUTE_ID === $attr) {
                $task[AttributeInterface::ATTRIBUTE_ID] = $this->generateTaskId($attributeTask);

                continue;
            }

            if (AttributeInterface::ATTRIBUTE_STATUS === $attr) {
                $task[AttributeInterface::ATTRIBUTE_STATUS] = $this->getTaskStatus($attributeTask);

                continue;
            }

            if (AttributeInterface::ATTRIBUTE_RESOURCE === $attr) {
                $task[AttributeInterface::ATTRIBUTE_RESOURCE] = ResourceInterface::RESOURCE_ATTRIBUTE;

                continue;
            }

            $task[$attr] = $attributeTask[$attr] ?? null;
        }
        return $task;
    }

    private function generateTaskId(array $attributeTask): string
    {
        return HashHelper::generateIdFromProps(ArrayUtils::only($attributeTask, HashHelper::GENERATED_PROPS));
    }

    private function getTaskStatus($attributeTask): string
    {
        return $attributeTask[AttributeInterface::ATTRIBUTE_STATUS] ?? StatusInterface::STATUS_ACTIVE;
    }
}