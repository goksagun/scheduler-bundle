<?php

namespace Goksagun\SchedulerBundle\Repository;

trait CrudTrait
{
    public function save(?object $entity = null): void
    {
        $em = $this->getEntityManager();

        if (null !== $entity) {
            $em->persist($entity);
        }
        $em->flush();
    }

    public function delete(object $entity): void
    {
        $em = $this->getEntityManager();

        $em->remove($entity);
        $em->flush();
    }
}