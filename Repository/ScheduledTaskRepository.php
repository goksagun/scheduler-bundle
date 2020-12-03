<?php

namespace Goksagun\SchedulerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Goksagun\SchedulerBundle\Entity\ScheduledTask;

/**
 * This custom Doctrine repository contains some methods which are useful when
 * querying for blog post information.
 *
 * See https://symfony.com/doc/current/doctrine/repository.html
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ScheduledTaskRepository extends ServiceEntityRepository
{
    use CrudTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledTask::class);
    }
}
