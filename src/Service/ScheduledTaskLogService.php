<?php

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskLogRepository;
use Goksagun\SchedulerBundle\Utils\StringHelper;

class ScheduledTaskLogService
{
    public function __construct(private readonly array $config, private readonly ScheduledTaskLogRepository $repository)
    {
    }

    public function create(string $name, ?int $times = null, bool $save = false): ScheduledTaskLog
    {
        $scheduledTaskLog = new ScheduledTaskLog();

        if (!$this->config['log']) {
            return $scheduledTaskLog;
        }

        $scheduledTaskLog
            ->setName($name)
            ->setRemaining($times)
        ;

        if ($latestExecutedScheduledTask = $this->getLatestScheduledTaskLog($name)) {
            $scheduledTaskLog->setRemaining(
                $latestExecutedScheduledTask->getRemaining()
            );
        }

        if ($save) {
            $this->repository->save($scheduledTaskLog);
        }

        return $scheduledTaskLog;
    }

    public function getLatestScheduledTaskLog(string $name, ?string $status = null): ?ScheduledTaskLog
    {
        $criteria = [
            'name' => $name,
        ];

        if (null !== $status) {
            $criteria['status'] = $status;
        }

        return $this->repository->findOneBy(
            $criteria,
            [
                'id' => 'desc',
            ]
        );
    }

    public function updateStatus(
        ScheduledTaskLog $scheduledTask,
        string $status,
        ?string $message = null,
        ?string $output = null,
        bool $save = false
    ): ScheduledTaskLog {
        if (!$this->config['log']) {
            return $scheduledTask;
        }

        $scheduledTask->setStatus($status);
        if (!empty($message)) {
            $scheduledTask->setMessage(StringHelper::limit($message, 252));
        }

        if (!empty($output)) {
            $scheduledTask->setOutput($output);
        }

        if ($save) {
            $this->repository->save();
        }

        return $scheduledTask;
    }
}