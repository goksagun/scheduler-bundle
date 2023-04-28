<?php

namespace Goksagun\SchedulerBundle\Tests\Service\Fixtures;

class FooTaskLoader implements \Goksagun\SchedulerBundle\Service\TaskLoaderInterface
{

    public function load(?string $status = null, ?string $resource = null): array
    {
        return [
            'name' => 'foo:command',
            'expression' => '@daily',
        ];
    }
}