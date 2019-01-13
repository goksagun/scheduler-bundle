<?php

namespace Goksagun\SchedulerBundle\Utils;

final class DateHelper
{
    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i';

    public static function isDateValid($date, $format = self::DATE_FORMAT)
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }
}