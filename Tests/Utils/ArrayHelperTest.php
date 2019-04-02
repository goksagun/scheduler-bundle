<?php

namespace Goksagun\SchedulerBundle\Tests\Utils;

use Goksagun\SchedulerBundle\Utils\ArrayHelper;
use PHPUnit\Framework\TestCase;

class ArrayHelperTest extends TestCase
{
    public function testExists()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];

        $condition = ArrayHelper::exists($array, 'foo');

        $this->assertTrue($condition);
    }

    public function testOnly()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['foo' => 'bar'];

        $actual = ArrayHelper::only($array, ['foo']);

        $this->assertSame($expected, $actual);
    }

    public function testOnlyByStringParameter()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['foo' => 'bar'];

        $actual = ArrayHelper::only($array, 'foo');

        $this->assertSame($expected, $actual);
    }

    public function testExcept()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['baz' => 'buz'];

        $actual = ArrayHelper::except($array, ['foo']);

        $this->assertSame($expected, $actual);
    }

    public function testExceptByStringParameter()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['baz' => 'buz'];

        $actual = ArrayHelper::except($array, 'foo');

        $this->assertSame($expected, $actual);
    }

    public function testForget()
    {
        $array = ['foo' => 'bar', 'baz' => 'buz'];
        $expected = ['baz' => 'buz'];

        ArrayHelper::forget($array, 'foo');

        $this->assertSame($expected, $array);
    }
}