<?php

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTask;

class ScheduledTaskBuilder
{
    private ScheduledTask $schedulerTask;

    public function __construct(string $name, string $expression)
    {
        $this->schedulerTask = new ScheduledTask();
        $this->schedulerTask
            ->setName($name)
            ->setExpression($expression);
    }

    public function build(): ScheduledTask
    {
        return $this->schedulerTask;
    }

    public function times(?int $times): static
    {
        if (null !== $times) {
            $this->schedulerTask->setTimes($times);
        }

        return $this;
    }

    public function start(?\DateTimeInterface $time): static
    {
        if (null !== $time) {
            $this->schedulerTask->setStart($time);
        }

        return $this;
    }

    public function stop(?\DateTimeInterface $time): static
    {
        if (null !== $time) {
            $this->schedulerTask->setStop($time);
        }

        return $this;
    }

    public function status(?string $status): static
    {
        if (null !== $status) {
            $this->schedulerTask->setStatus($status);
        }

        return $this;
    }
}