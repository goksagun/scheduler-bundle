<?php

namespace Goksagun\SchedulerBundle\Process;

use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Symfony\Component\Process\Process;

class ProcessInfo
{
    private $process;
    private $scheduledTask;

    public function __construct(Process $process, ScheduledTask $scheduledTask)
    {
        $this->process = $process;
        $this->scheduledTask = $scheduledTask;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function setProcess(Process $process)
    {
        $this->process = $process;
    }

    public function getScheduledTask(): ScheduledTask
    {
        return $this->scheduledTask;
    }

    public function setScheduledTask(ScheduledTask $scheduledTask)
    {
        $this->scheduledTask = $scheduledTask;
    }
}