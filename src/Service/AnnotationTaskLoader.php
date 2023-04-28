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
use Symfony\Component\Console\Command\Command;

class AnnotationTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    private AnnotationReader $reader;
    private iterable $tasks = [];

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

    public function supports(?string $resource): bool
    {
        return null === $resource || $resource === ResourceInterface::RESOURCE_ANNOTATION;
    }

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
            $task = $this->createTaskFromAnnotation($annotation);

            if (!$this->shouldFilterByStatus($status, $task)) {
                $this->tasks[] = $this->filterPropsIfExists($task);
            }
        }
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

    private function shouldFilterByStatus(?string $status, $task): bool
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