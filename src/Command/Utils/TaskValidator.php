<?php

namespace Goksagun\SchedulerBundle\Command\Utils;

use Cron\CronExpression;
use Goksagun\SchedulerBundle\Service\ScheduledTaskLogService;
use Goksagun\SchedulerBundle\Utils\DateHelper;

class TaskValidator
{
    public function __construct(private readonly ScheduledTaskLogService $taskLogService)
    {
    }

    public function validateTask(array $task): array
    {
        $errors = [];

        $this->validateName($task, $errors);
        $this->validateExpression($task, $errors);
        $this->validateTimes($task, $errors);
        $this->validateStart($task, $errors);
        $this->validateStop($task, $errors);

        return $errors;
    }

    private function validateName(array $task, array &$errors): void
    {
        if (!isset($task['name'])) {
            $errors['name'] = "The task command name should be defined.";
        }
    }

    private function validateExpression(array $task, array &$errors): void
    {
        if (!isset($task['expression'])) {
            $errors['expression'] = "The task command expression should be defined.";
        }
    }

    private function validateTimes(array $task, array &$errors): void
    {
        $times = $task['times'] ?? null;

        if (!empty($times) && !is_int($times)) {
            $errors['times'] = "The times should be integer.";
        }
    }

    private function validateStart(array $task, array &$errors): void
    {
        $start = $task['start'] ?? null;

        if (!empty($start) && !$this->isValidDate($start)) {
            $errors['start'] = $this->getDateValidationErrorMessage('start');
        }
    }

    private function validateStop(array $task, array &$errors): void
    {
        $stop = $task['stop'] ?? null;

        if (!empty($stop) && !$this->isValidDate($stop)) {
            $errors['stop'] = $this->getDateValidationErrorMessage('stop');
        }
    }

    private function isValidDate(mixed $date): bool
    {
        return DateHelper::isDateValid($date) || DateHelper::isDateValid($date, DateHelper::DATETIME_FORMAT);
    }

    private function getDateValidationErrorMessage(string $field): string
    {
        return sprintf(
            'The %s should be date (%s) or datetime (%s).',
            $field,
            DateHelper::DATE_FORMAT,
            DateHelper::DATETIME_FORMAT
        );
    }

    public function isTaskDue(array $task): bool
    {
        if (
            $this->isTaskPastStartDate($task)
            && $this->isTaskNotPastEndDate($task)
            && $this->isTaskNotExceededMaxExecutions($task)) {
            $cron = CronExpression::factory($task['expression']);

            return $cron->isDue();
        }

        return false;
    }

    private function isTaskPastStartDate(array $task): bool
    {
        $start = $task['start'];
        if (null === $start) {
            return true;
        }

        $now = DateHelper::date();
        $startDate = DateHelper::date($start);

        return $startDate <= $now;
    }

    private function isTaskNotPastEndDate(array $task): bool
    {
        $end = $task['stop'];
        if (null === $end) {
            return true;
        }

        $now = DateHelper::date();
        $endDate = DateHelper::date($end);

        return $endDate >= $now;
    }

    private function isTaskNotExceededMaxExecutions(array $task): bool
    {
        if (!$this->taskLogService->isLoggingEnabled() || null === $task['times']) {
            return true;
        }

        $scheduledTask = $this->taskLogService->getLatestScheduledTaskLog($task['name']);

        if (null === $scheduledTask) {
            return true;
        }

        return !$scheduledTask->isRemainingZero();
    }
}