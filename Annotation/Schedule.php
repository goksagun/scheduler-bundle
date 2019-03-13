<?php

namespace Goksagun\SchedulerBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Schedule
{
    /**
     * @Required()
     *
     * @var string
     */
    public $name;

    /**
     * @Required()
     *
     * @var string
     */
    public $expression;

    /**
     * @var int
     */
    public $times;

    /**
     * @var string
     */
    public $start;

    /**
     * @var string
     */
    public $end;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @return int
     */
    public function getTimes(): int
    {
        return $this->times;
    }

    /**
     * @return string
     */
    public function getStart(): string
    {
        return $this->start;
    }

    /**
     * @return string
     */
    public function getEnd(): string
    {
        return $this->end;
    }

    public function toArray()
    {
        return array_map(
            function ($value) {
                if (null === $value) {
                    return $value;
                }

                return preg_replace('/\\\\/', '', $value);
            },
            (array)$this
        );
    }
}