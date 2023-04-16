<?php

namespace Goksagun\SchedulerBundle\Doctrine\ORM\Id;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Goksagun\SchedulerBundle\Utils\HashHelper;

class HashIdGenerator extends AbstractIdGenerator
{

    /**
     * Generates an identifier for an entity.
     *
     * @param EntityManager $em
     * @param object|null $entity
     * @return mixed
     */
    public function generate(EntityManager $em, $entity)
    {
        return HashHelper::generateIdFromProps(
            $entity->toArray(['name', 'expression', 'times', 'start', 'stop'])
        );
    }
}