<?php

namespace Goksagun\SchedulerBundle\Tests\Utils;

use Goksagun\SchedulerBundle\Utils\TaskHelper;
use PHPUnit\Framework\TestCase;

class TaskHelperTest extends TestCase
{
    public function testParseName()
    {
        $actual = TaskHelper::parseName('foo:command bar --baz');

        $expected = ['foo:command', 'bar', '--baz'];

        $this->assertSame($expected, $actual);
    }

    public function testParseNameMoreComplexArgs()
    {
        $actual = TaskHelper::parseName('foo:command \'bar baz buzz\' --baz');

        $expected = ['foo:command', 'bar baz buzz', '--baz'];

        $this->assertSame($expected, $actual);
    }

    public function testParseNameMoreArgsWithSingleQuoted()
    {
        $actual = TaskHelper::parseName('foo:command --bar=10 --baz --buzz=\'0 day\'');

        $expected = ['foo:command', '--bar=10', '--baz', '--buzz=0 day'];

        $this->assertSame($expected, $actual);
    }

    public function testParseNameMoreArgsWithDoubleQuoted()
    {
        $actual = TaskHelper::parseName('foo:command --bar=10 --baz --buzz="0 day"');

        $expected = ['foo:command', '--bar=10', '--baz', '--buzz=0 day'];

        $this->assertSame($expected, $actual);
    }

    public function testParseNameNotSame()
    {
        $actual = TaskHelper::parseName('foo:command bar --baz');

        $expected = ['foo:command', 'bar', '--buzz'];

        $this->assertNotSame($expected, $actual);
    }

    public function testGetCommandName()
    {
        $actual = TaskHelper::getCommandName('foo:command bar --baz');

        $expected = 'foo:command';

        $this->assertEquals($expected, $actual);
    }

    public function testGetCommandNameNotEquals()
    {
        $actual = TaskHelper::getCommandName('foo:command bar --baz');

        $expected = 'buzz:command';

        $this->assertNotEquals($expected, $actual);
    }
}