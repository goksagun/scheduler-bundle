<?php

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;

final class ScheduledTaskLogBuilder
{
    private ScheduledTaskLog $scheduledTaskLog;

    public function __construct()
    {
        $this->scheduledTaskLog = new ScheduledTaskLog();
    }

    public function name(string $name): static
    {
        $this->scheduledTaskLog->setName($name);

        return $this;
    }

    public function status(string $status): static
    {
        $this->scheduledTaskLog->setStatus($status);

        return $this;
    }

    public function message(string $message): static
    {
        $this->scheduledTaskLog->setMessage($message);

        return $this;
    }

    public function output(string $output): static
    {
        $this->scheduledTaskLog->setOutput($output);

        return $this;
    }

    public function remaining(int $times): static
    {
        $this->scheduledTaskLog->setRemaining($times);

        return $this;
    }

    public function build(): ScheduledTaskLog
    {
        return $this->scheduledTaskLog;
    }
}