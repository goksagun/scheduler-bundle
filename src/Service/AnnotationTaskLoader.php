<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Goksagun\SchedulerBundle\Annotation\Schedule;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Symfony\Component\Console\Command\Command;

class AnnotationTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    protected const RESOURCE = ResourceInterface::RESOURCE_ANNOTATION;
    private AnnotationReader $reader;

    public function __construct(ScheduledTaskService $service)
    {
        $this->reader = new AnnotationReader();

        parent::__construct($service);
    }


    public function load(?string $status = null, ?string $resource = null): array
    {
        if (!$this->supports($resource)) {
            return [];
        }

        foreach ($this->getCommands() as $command) {
            if ($annotations = $this->getScheduleAnnotations($command)) {
                $this->addTaskFromAnnotations($annotations, $status);
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

    private function getScheduleAnnotations(Command $command): array
    {
        $annotations = $this->reader->getClassAnnotations(new \ReflectionObject($command));

        return array_filter($annotations, fn($annotation) => $annotation instanceof Schedule);
    }

    private function addTaskFromAnnotations(array $annotations, ?string $status): void
    {
        foreach ($annotations as $annotation) {
            $task = $this->createTask($this->getTask($annotation));

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

    private function getTask(Schedule $annotation): array
    {
        return $annotation->toArray();
    }
}