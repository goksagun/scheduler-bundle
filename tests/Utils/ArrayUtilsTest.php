<?php

namespace Goksagun\SchedulerBundle\Tests\Utils;

use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use PHPUnit\Framework\TestCase;

class ArrayUtilsTest extends TestCase
{
    public function testExistsWithArray()
    {
        $array = ['foo' => 'bar'];

        $this->assertTrue(ArrayUtils::exists($array, 'foo'));
        $this->assertFalse(ArrayUtils::exists($array, 'baz'));
    }

    public function testExistsWithDotNotation()
    {
        $array = [
            'foo' => [
                'bar' => 'baz',
            ],
        ];

        $this->assertTrue(ArrayUtils::exists($array, 'foo.bar'));
        $this->assertFalse(ArrayUtils::exists($array, 'foo.baz'));
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

    public function testForgetWithNestedKeys()
    {
        $array = [
            'foo' => [
                'bar' => 'baz',
                'qux' => [
                    'quux' => 'corge',
                    'grault' => 'garply',
                ],
            ],
            'waldo' => 'fred',
        ];

        $expected = [
            'foo' => [
                'qux' => [
                    'quux' => 'corge',
                ],
            ],
            'waldo' => 'fred',
        ];

        ArrayUtils::forget($array, ['foo.bar', 'foo.qux.grault']);

        $this->assertSame($expected, $array);
    }

    public function testForgetWithNoneExistentKeys()
    {
        $array = [
            'foo' => [
                'bar' => 'baz',
            ],
        ];

        $expected = $array;

        ArrayUtils::forget($array, ['foo.baz', 'qux']);

        $this->assertSame($expected, $array);
    }

    public function testForgetWithEmptyKeys()
    {
        $array = [
            'foo' => [
                'bar' => 'baz',
            ],
        ];

        $expected = $array;

        ArrayUtils::forget($array, []);

        $this->assertSame($expected, $array);
    }
}