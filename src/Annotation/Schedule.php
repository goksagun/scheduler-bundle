<?php

namespace Goksagun\SchedulerBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayHelper;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Schedule implements StatusInterface
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
    public $stop;

    /**
     * @var string
     */
    public $status = self::STATUS_ACTIVE;

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
    public function getStop(): string
    {
        return $this->stop;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function toArray(array $props = [])
    {
        $allProps = array_map(
            function ($value) {
                if (null === $value) {
                    return $value;
                }

                // Cleanup expression backslashes like: "*\/10 * * * *" to: "*/10 * * * *".
                return preg_replace('/\\\\/', '', $value);
            },
            get_object_vars($this)
        );

        return empty($props)
            ? $allProps
            : ArrayHelper::only($allProps, $props);
    }
}