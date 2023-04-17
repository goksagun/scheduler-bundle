<?php

namespace Goksagun\SchedulerBundle\Doctrine\ORM\Id;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Goksagun\SchedulerBundle\Utils\HashHelper;

class HashIdGenerator extends AbstractIdGenerator
{

    /**
     * @inheritdoc
     */
    public function generate(EntityManager $em, $entity)
    {
        return HashHelper::generateIdFromProps(
            $entity->toArray(['name', 'expression', 'times', 'start', 'stop'])
        );
    }
}