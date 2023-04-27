<?php

namespace Goksagun\SchedulerBundle\Service;

class TaskLoader implements TaskLoaderInterface
{
    /**
     * @var array <int, TaskLoaderInterface>
     */
    private array $loaders;

    public function __construct(array $loaders)
    {
        $this->loaders = $loaders;
    }

    public function load(?string $status = null, ?string $resource = null): array
    {
        $tasks = [];
        foreach ($this->loaders as $loader) {
            if (!$loader instanceof TaskLoaderInterface) {
                throw new \InvalidArgumentException();
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