<?php

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Goksagun\SchedulerBundle\Repository\ScheduledTaskLogRepository;

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

    private function getLatestScheduledTaskLog(string $name, ?string $status = null)
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
}