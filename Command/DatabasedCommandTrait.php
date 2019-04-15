<?php

namespace Goksagun\SchedulerBundle\Command;

use Goksagun\SchedulerBundle\Entity\ScheduledTask;
use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\ResourceInterface;
use Goksagun\SchedulerBundle\Utils\ArrayHelper;
use Goksagun\SchedulerBundle\Utils\DateHelper;

trait DatabasedCommandTrait
{
    public function setDatabasedTasks(
        $status = ScheduledTask::STATUS_ACTIVE,
        $resource = ResourceInterface::RESOURCE_DATABASE,
        $props = []
    ) {
        if (null !== $resource && ResourceInterface::RESOURCE_DATABASE !== $resource) {
            return;
        }

        if (method_exists($this, 'getEntityManager')) {
            $repository = $this->getEntityManager()->getRepository('SchedulerBundle:ScheduledTask');
        } else {
            $repository = $this->getRepository();
        }

        $databases = $repository->findAll();

        foreach ($databases as $database) {
            $databaseTask = $database->toArray();

            $task = [];
            foreach (AttributeInterface::ATTRIBUTES as $attribute) {
                if (AttributeInterface::ATTRIBUTE_RESOURCE == $attribute) {
                    $task[$attribute] = ResourceInterface::RESOURCE_DATABASE;

                    continue;
                }

                if (AttributeInterface::ATTRIBUTE_START == $attribute
                    && $databaseTask[AttributeInterface::ATTRIBUTE_START] instanceof \DateTimeInterface
                ) {
                    $task[AttributeInterface::ATTRIBUTE_START] = $databaseTask[AttributeInterface::ATTRIBUTE_START]->format(
                        DateHelper::DATETIME_FORMAT
                    );

                    continue;
                }

                if (AttributeInterface::ATTRIBUTE_STOP == $attribute
                    && $databaseTask[AttributeInterface::ATTRIBUTE_STOP] instanceof \DateTimeInterface
                ) {
                    $task[AttributeInterface::ATTRIBUTE_STOP] = $databaseTask[AttributeInterface::ATTRIBUTE_STOP]->format(
                        DateHelper::DATETIME_FORMAT
                    );

                    continue;
                }

                $task[$attribute] = $databaseTask[$attribute] ?? null;
            }

            // Filter by status
            if (null !== $status && $task[AttributeInterface::ATTRIBUTE_STATUS] !== $status) {
                continue;
            }

            // Filter props if exists
            if ($props) {
                $task = ArrayHelper::only($task, $props);
            }

            array_push($this->tasks, $task);
        }
    }
}