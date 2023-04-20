<?php

namespace Goksagun\SchedulerBundle\Command;

use Doctrine\Common\Annotations\AnnotationReader;
use Goksagun\SchedulerBundle\Annotation\Schedule;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;

trait AnnotatedCommandTrait
{
    private function setAnnotatedTasks(
        $status = StatusInterface::STATUS_ACTIVE,
        $resource = ResourceInterface::RESOURCE_ANNOTATION,
        $props = []
    ): void {
        if (null !== $resource && ResourceInterface::RESOURCE_ANNOTATION !== $resource) {
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

        $reader = new AnnotationReader();

        foreach ($commands as $command) {
            $annotations = $reader->getClassAnnotations(new \ReflectionClass(get_class($command)));

            if (!$annotations) {
                continue;
            }

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Schedule) {
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
                    if ($props) {
                        $task = ArrayUtils::only($task, $props);
                    }

                    $this->tasks[] = $task;
                }
            }
        }
    }
}