<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Goksagun\SchedulerBundle\Annotation\Schedule;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Symfony\Component\Console\Command\Command;

class AnnotationTaskLoader extends AbstractTaskLoader implements TaskLoaderInterface
{

    protected const RESOURCE = ResourceInterface::RESOURCE_ANNOTATION;

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
        $annotations = (new AnnotationReader())->getClassAnnotations(new \ReflectionObject($command));

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

    private function getTask(Schedule $annotation): array
    {
        return $annotation->toArray();
    }
}