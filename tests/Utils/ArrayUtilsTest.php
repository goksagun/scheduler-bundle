<?php

namespace Goksagun\SchedulerBundle\Tests\Utils;

use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use PHPUnit\Framework\TestCase;

class ArrayUtilsTest extends TestCase
{
    public function testExists()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];

        $condition = ArrayUtils::exists($array, 'foo');

        $this->assertTrue($condition);
    }

    public function testOnly()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['foo' => 'bar'];

        $actual = ArrayUtils::only($array, ['foo']);

        $this->assertSame($expected, $actual);
    }

    public function testOnlyByStringParameter()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['foo' => 'bar'];

        $actual = ArrayUtils::only($array, 'foo');

        $this->assertSame($expected, $actual);
    }

    public function testExcept()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['baz' => 'buz'];

        $actual = ArrayUtils::except($array, ['foo']);

        $this->assertSame($expected, $actual);
    }

    public function testExceptByStringParameter()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['baz' => 'buz'];

        $actual = ArrayUtils::except($array, 'foo');

        $this->assertSame($expected, $actual);
    }

    public function testForget()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['baz' => 'buz'];

        ArrayUtils::forget($array, 'foo');

        $this->assertSame($expected, $array);
    }
}