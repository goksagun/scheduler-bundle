<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Attribute\Schedule;
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
            $task = $this->createTask($this->getTaskDataFrom($attribute));

            if (!$this->shouldFilterByStatus($status, $task)) {
                $this->tasks[] = $this->filterPropsIfExists($task);
            }
        }
    }

    private function getTaskDataFrom(\ReflectionAttribute $attribute): array
    {
        return $attribute->getArguments();
    }
}