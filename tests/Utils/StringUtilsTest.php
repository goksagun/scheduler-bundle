<?php

namespace Goksagun\SchedulerBundle\Tests\Utils;

use Goksagun\SchedulerBundle\Utils\StringUtils;
use PHPUnit\Framework\TestCase;

class StringUtilsTest extends TestCase
{

    public function testStartsWith()
    {
        $this->assertTrue(StringUtils::startsWith('Foo bar baz', 'Foo'));
        $this->assertTrue(StringUtils::startsWith('Foo bar baz', ['Foo']));
        $this->assertFalse(StringUtils::startsWith('Foo bar baz', 'bar'));
        $this->assertFalse(StringUtils::startsWith('Foo bar baz', ['bar']));
    }

    public function testEndsWith()
    {
        $this->assertTrue(StringUtils::endsWith('Foo bar baz', 'baz'));
        $this->assertTrue(StringUtils::endsWith('Foo bar baz', ['baz']));
        $this->assertFalse(StringUtils::endsWith('Foo bar baz', 'bar'));
        $this->assertFalse(StringUtils::endsWith('Foo bar baz', ['bar']));
    }

    public function testContains()
    {
        $this->assertTrue(StringUtils::contains('Foo bar baz', 'bar'));
        $this->assertTrue(StringUtils::contains('Foo bar baz', ['bar']));
        $this->assertFalse(StringUtils::contains('Foo bar baz', 'fuz'));
        $this->assertFalse(StringUtils::contains('Foo bar baz', ['fuz']));
    }

    public function testLimit()
    {
        $this->assertEquals('Foo bar baz...', StringUtils::limit('Foo bar baz fuzz', 11));
    }

    public function testInterpolate()
    {
        $this->assertEquals('Hello John!', StringUtils::interpolate('Hello {name}!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringUtils::interpolate('Hello { name }!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringUtils::interpolate('Hello {name }!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringUtils::interpolate('Hello { name}!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringUtils::interpolate('Hello {  name}!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringUtils::interpolate('Hello {  name }!', ['name' => 'John']));
        $this->assertEquals('Hello John!', StringUtils::interpolate('Hello {  name        }!', ['name' => 'John']));
        $this->assertEquals('Hello John Doe!', StringUtils::interpolate('Hello {firstName} {lastName}!', ['firstName' => 'John', 'lastName' => 'Doe']));
        $this->assertEquals('Hello John!', StringUtils::interpolate('Hello [name]!', ['name' => 'John'], '[]'));
        $this->assertEquals('Hello John!', StringUtils::interpolate('Hello [[name]]!', ['name' => 'John'], '[[]]'));
        $this->assertEquals('Hello John and Welcome!', StringUtils::interpolate('Hello [ name ] and [greeting]!', ['name' => 'John', 'greeting' => 'Welcome'], '[]'));
    }
}
