<?php

namespace Goksagun\SchedulerBundle\Tests\Utils;

use Goksagun\SchedulerBundle\Utils\StringHelper;
use PHPUnit\Framework\TestCase;

class StringHelperTest extends TestCase
{

    public function testStartsWith()
    {
        $this->assertTrue(StringHelper::startsWith('Foo bar baz', 'Foo'));
        $this->assertTrue(StringHelper::startsWith('Foo bar baz', ['Foo']));
        $this->assertFalse(StringHelper::startsWith('Foo bar baz', 'bar'));
        $this->assertFalse(StringHelper::startsWith('Foo bar baz', ['bar']));
    }

    public function testEndsWith()
    {
        $this->assertTrue(StringHelper::endsWith('Foo bar baz', 'baz'));
        $this->assertTrue(StringHelper::endsWith('Foo bar baz', ['baz']));
        $this->assertFalse(StringHelper::endsWith('Foo bar baz', 'bar'));
        $this->assertFalse(StringHelper::endsWith('Foo bar baz', ['bar']));
    }

    public function testContains()
    {
        $this->assertTrue(StringHelper::contains('Foo bar baz', 'bar'));
        $this->assertTrue(StringHelper::contains('Foo bar baz', ['bar']));
        $this->assertFalse(StringHelper::contains('Foo bar baz', 'fuz'));
        $this->assertFalse(StringHelper::contains('Foo bar baz', ['fuz']));
    }

    public function testLimit()
    {
        $this->assertEquals('Foo bar baz...', StringHelper::limit('Foo bar baz fuzz', 11));
    }

    public function testInterpolate()
    {
        $this->assertEquals('Hello John!', StringHelper::interpolate('Hello {{name}}!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringHelper::interpolate('Hello {{ name }}!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringHelper::interpolate('Hello {{name }}!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringHelper::interpolate('Hello {{ name}}!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringHelper::interpolate('Hello {{  name}}!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringHelper::interpolate('Hello {{  name }}!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringHelper::interpolate('Hello {{  name        }}!', ['name' => 'John']));
        $this->assertEquals('Hello John Doe!', StringHelper::interpolate('Hello {{firstName}} {{lastName}}!', ['firstName' => 'John', 'lastName' => 'Doe']));
    }
}
