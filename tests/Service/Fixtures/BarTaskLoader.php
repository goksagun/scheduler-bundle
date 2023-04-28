<?php

namespace Goksagun\SchedulerBundle\Tests\Service\Fixtures;

class BarTaskLoader implements \Goksagun\SchedulerBundle\Service\TaskLoaderInterface
{

    public function load(?string $status = null, ?string $resource = null): array
    {
        return [
            'name' => 'bar:command',
            'expression' => '@hourly',
        ];
    }
}