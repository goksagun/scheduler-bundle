<?php

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;

final class ScheduledTaskLogBuilder
{
    public function __construct(
        private readonly ScheduledTaskLog $scheduledTaskLog = new ScheduledTaskLog()
    ) {
    }

    public function name(string $name): ScheduledTaskLogBuilder
    {
        $this->scheduledTaskLog->setName($name);

        return $this;
    }

    public function status(string $status): ScheduledTaskLogBuilder
    {
        $this->scheduledTaskLog->setStatus($status);

        return $this;
    }

    public function message(string $message): ScheduledTaskLogBuilder
    {
        $this->scheduledTaskLog->setMessage($message);

        return $this;
    }

    public function output(string $output): ScheduledTaskLogBuilder
    {
        $this->scheduledTaskLog->setOutput($output);

        return $this;
    }

    public function remaining(int $times): ScheduledTaskLogBuilder
    {
        $this->scheduledTaskLog->setRemaining($times);

        return $this;
    }

    public function build(): ScheduledTaskLog
    {
        return $this->scheduledTaskLog;
    }
}