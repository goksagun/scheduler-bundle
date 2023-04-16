<?php

namespace Goksagun\SchedulerBundle\Process;

use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Symfony\Component\Process\Process;

class ProcessInfo
{
    private $process;
    private $scheduledTaskLog;

    public function __construct(Process $process, ScheduledTaskLog $scheduledTaskLog)
    {
        $this->process = $process;
        $this->scheduledTaskLog = $scheduledTaskLog;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function setProcess(Process $process)
    {
        $this->process = $process;
    }

    public function getScheduledTaskLog(): ScheduledTaskLog
    {
        return $this->scheduledTaskLog;
    }

    public function setScheduledTaskLog(ScheduledTaskLog $scheduledTaskLog)
    {
        $this->scheduledTaskLog = $scheduledTaskLog;
    }
}