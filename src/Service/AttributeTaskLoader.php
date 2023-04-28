<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Attribute\Schedule;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Symfony\Component\Console\Command\Command;

class AttributeTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    protected const RESOURCE = ResourceInterface::RESOURCE_ATTRIBUTE;

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
            $task = $this->createTask($this->getTask($attribute));

            if (!$this->shouldFilterByStatus($status, $task)) {
                $this->tasks[] = $this->filterPropsIfExists($task);
            }
        }
    }

    private function createTask(array $data): array
    {
        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attr) {
            switch ($attr) {
                case AttributeInterface::ATTRIBUTE_ID:
                    $task[AttributeInterface::ATTRIBUTE_ID] = $this->generateTaskId($data);
                    break;
                case AttributeInterface::ATTRIBUTE_STATUS:
                    $task[AttributeInterface::ATTRIBUTE_STATUS] = $this->getTaskStatus($data);
                    break;
                case AttributeInterface::ATTRIBUTE_RESOURCE:
                    $task[AttributeInterface::ATTRIBUTE_RESOURCE] = self::RESOURCE;
                    break;
                default:
                    $task[$attr] = $data[$attr] ?? null;
                    break;
            }
        }

        return $task;
    }

    private function getTask(\ReflectionAttribute $attribute): array
    {
        return $attribute->getArguments();
    }
}