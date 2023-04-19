<?php

namespace Goksagun\SchedulerBundle\Tests\Utils;

use Goksagun\SchedulerBundle\Utils\HashHelper;
use PHPUnit\Framework\TestCase;

class HashHelperTest extends TestCase
{

    public function testGenerateIdFromProps()
    {
        $this->assertEquals(
            '5c75028a785bd41d859477214fc226a6',
            HashHelper::generateIdFromProps(['foo' => 'Foo', 'bar' => 'Bar'])
        );
    }
}
