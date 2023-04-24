<?php

namespace Goksagun\SchedulerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GoksagunSchedulerBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
