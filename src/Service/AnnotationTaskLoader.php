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
                $annotationTask = $annotation->toArray();

                $task = [];
                foreach (AttributeInterface::ATTRIBUTES as $attribute) {
                    if (AttributeInterface::ATTRIBUTE_ID == $attribute) {
                        // Generate Id.
                        $id = HashHelper::generateIdFromProps(
                            ArrayUtils::only($annotationTask, HashHelper::GENERATED_PROPS)
                        );

                        $task[$attribute] = $id;

                        continue;
                    }

                    if (AttributeInterface::ATTRIBUTE_STATUS == $attribute) {
                        if (!isset($annotationTask[$attribute])) {
                            $task[$attribute] = StatusInterface::STATUS_ACTIVE;
                        } else {
                            $task[$attribute] = $annotationTask[$attribute];
                        }

                        continue;
                    }

                    if (AttributeInterface::ATTRIBUTE_RESOURCE == $attribute) {
                        $task[$attribute] = ResourceInterface::RESOURCE_ANNOTATION;

                        continue;
                    }

                    $task[$attribute] = $annotationTask[$attribute] ?? null;
                }

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
}