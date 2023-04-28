<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Attribute\Schedule;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;
use Symfony\Component\Console\Command\Command;

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

    /**
     * @return array<int, Command>
     */
    private function getCommands(): array
    {
        return $this->getApplication()->all();
    }

    private function getScheduleAttributes(Command $command): array
    {
        $attributes = (new \ReflectionObject($command))->getAttributes();

        return array_filter($attributes, fn($attribute) => $attribute->getName() === Schedule::class);
    }

    private function addTaskFromAttributes(array $attributes, ?string $status): void
    {
        foreach ($attributes as $attribute) {
            $task = $this->createTaskFromAttribute($attribute);

            if (!$this->shouldFilterByStatus($status, $task)) {
                $this->tasks[] = $this->filterPropsIfExists($task);
            }
        }
    }

    private function createTaskFromAttribute(\ReflectionAttribute $attribute): array
    {
        $attributeTask = $attribute->getArguments();

        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attr) {
            switch ($attr) {
                case AttributeInterface::ATTRIBUTE_ID:
                    $task[AttributeInterface::ATTRIBUTE_ID] = $this->generateTaskId($attributeTask);
                    break;
                case AttributeInterface::ATTRIBUTE_STATUS:
                    $task[AttributeInterface::ATTRIBUTE_STATUS] = $this->getTaskStatus($attributeTask);
                    break;
                case AttributeInterface::ATTRIBUTE_RESOURCE:
                    $task[AttributeInterface::ATTRIBUTE_RESOURCE] = ResourceInterface::RESOURCE_ATTRIBUTE;
                    break;
                default:
                    $task[$attr] = $attributeTask[$attr] ?? null;
                    break;
            }
        }
        return $task;
    }

    private function generateTaskId(array $attributeTask): string
    {
        return HashHelper::generateIdFromProps(ArrayUtils::only($attributeTask, HashHelper::GENERATED_PROPS));
    }

    private function getTaskStatus(array $attributeTask): string
    {
        return $attributeTask[AttributeInterface::ATTRIBUTE_STATUS] ?? StatusInterface::STATUS_ACTIVE;
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