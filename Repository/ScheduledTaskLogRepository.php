<?php

namespace Goksagun\SchedulerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Goksagun\SchedulerBundle\Entity\ScheduledTaskLog;
use Symfony\Bridge\Doctrine\RegistryInterface;

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
class ScheduledTaskLogRepository extends ServiceEntityRepository
{
    use CrudTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ScheduledTaskLog::class);
    }
}
