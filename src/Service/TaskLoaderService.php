<?php

namespace Goksagun\SchedulerBundle\Service;

class TaskLoaderService implements TaskLoaderInterface
{
    /**
     * @var iterable <int, TaskLoaderInterface>
     */
    private iterable $loaders;

    public function __construct(iterable $loaders)
    {
        $this->loaders = $loaders;
    }

    public function load(?string $status = null, ?string $resource = null): array
    {
        $tasks = [];
        foreach ($this->loaders as $loader) {
            if (!$loader instanceof TaskLoaderInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The loaders should be implement "%s". You provided "%s"',
                        TaskLoaderInterface::class,
                        gettype($loader)
                    )
                );
            }

            $tasks = [...$tasks, ...$loader->load($status, $resource)];
        }

        return $tasks;
    }

    public function addLoader(TaskLoaderInterface $loader): void
    {
        $this->loaders[] = $loader;
    }
}