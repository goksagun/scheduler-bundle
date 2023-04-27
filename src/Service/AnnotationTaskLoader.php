<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Goksagun\SchedulerBundle\Annotation\Schedule;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;

class AnnotationTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

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

        $commands = $this->getCommands();

        if (!$commands) {
            return [];
        }

        $tasks = [];

        foreach ($commands as $command) {
            $annotations = $this->getScheduleAnnotations($command);

            if (!$annotations) {
                continue;
            }

            foreach ($annotations as $annotation) {
                $task = $this->createTaskFromAnnotation($annotation);

                // Filter by status
                if (null !== $status && $status !== $task[AttributeInterface::ATTRIBUTE_STATUS]) {
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

    private function supports(?string $resource): bool
    {
        return null === $resource || $resource === ResourceInterface::RESOURCE_ANNOTATION;
    }

    private function getCommands(): array
    {
        return $this->getApplication()->all();
    }

    private function getScheduleAnnotations(mixed $command): array
    {
        $annotations = $this->reader->getClassAnnotations(new \ReflectionClass(get_class($command)));

        return array_filter($annotations, fn($annotation) => $annotation instanceof Schedule);
    }

    private function createTaskFromAnnotation(Schedule $annotation): array
    {
        $annotationTask = $annotation->toArray();

        $task = [];
        foreach (AttributeInterface::ATTRIBUTES as $attribute) {
            switch ($attribute) {
                case AttributeInterface::ATTRIBUTE_ID:
                    $task[AttributeInterface::ATTRIBUTE_ID] = $this->generateTaskId($annotationTask);
                    break;
                case AttributeInterface::ATTRIBUTE_STATUS:
                    $task[AttributeInterface::ATTRIBUTE_STATUS] = $this->getTaskStatus($annotationTask);
                    break;
                case AttributeInterface::ATTRIBUTE_RESOURCE:
                    $task[AttributeInterface::ATTRIBUTE_RESOURCE] = ResourceInterface::RESOURCE_ANNOTATION;
                    break;
                default:
                    $task[$attribute] = $annotationTask[$attribute] ?? null;
                    break;
            }
        }

        return $task;
    }

    private function generateTaskId(array $annotationTask): string
    {
        return HashHelper::generateIdFromProps(ArrayUtils::only($annotationTask, HashHelper::GENERATED_PROPS));
    }

    private function getTaskStatus(array $annotationTask): string
    {
        return $annotationTask[AttributeInterface::ATTRIBUTE_STATUS] ?? StatusInterface::STATUS_ACTIVE;
    }
}