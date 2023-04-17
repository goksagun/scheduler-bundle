<?php

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Symfony\Component\Console\Exception\RuntimeException;

trait SchedulerTaskAddEditValidateOptionsTrait
{

    protected function validateOptions(array $options): void
    {
        $times = $options['times'];
        if (!is_null($times) && !is_numeric($times)) {
            throw new RuntimeException('The option "times" should be numeric value.');
        }

        $start = $options['start'];
        if (!is_null($start) && !DateHelper::isDateValid($start)) {
            throw new RuntimeException('The option "start" should be date or date and time value.');
        }

        $stop = $options['stop'];
        if (!is_null($stop) && !DateHelper::isDateValid($stop)) {
            throw new RuntimeException('The option "stop" should be date or date and time value.');
        }

        $status = $options['status'];
        if (!is_null($status) && !in_array($status, StatusInterface::STATUSES)) {
            throw new RuntimeException(
                sprintf(
                    'The option "status" should be valid. [values: "%s"]',
                    implode('|', StatusInterface::STATUSES)
                )
            );
        }
    }
}