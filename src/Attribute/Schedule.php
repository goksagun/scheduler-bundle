<?php

namespace Goksagun\SchedulerBundle\Attribute;

use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Symfony\Contracts\Service\Attribute\Required;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Schedule
{
    public function __construct(
        #[Required] public string $name,
        #[Required] public string $expression,
        public ?string $times = null,
        public ?string $start = null,
        public ?string $stop = null,
        public ?string $status = null,
    ) {
        if (null === $this->status) {
            $this->status = StatusInterface::STATUS_ACTIVE;
        }
    }

    public function toArray(array $props = []): array
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
            : ArrayUtils::only($allProps, $props);
    }
}