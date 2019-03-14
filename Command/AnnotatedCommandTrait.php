<?php

namespace Goksagun\SchedulerBundle\Command;

use Doctrine\Common\Annotations\AnnotationReader;
use Goksagun\SchedulerBundle\Annotation\Schedule;

trait AnnotatedCommandTrait
{
    private function setAnnotatedTasks()
    {
        $commands = $this->getApplication()->all();

        $reader = new AnnotationReader();

        foreach ($commands as $command) {
            $annotations = $reader->getClassAnnotations(new \ReflectionClass(get_class($command)));

            if (!$annotations) {
                continue;
            }

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Schedule) {
                    array_push($this->tasks, $annotation->toArray());
                }
            }
        }
    }
}