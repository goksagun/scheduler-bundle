<?php

namespace Goksagun\SchedulerBundle\Repository;

trait CrudTrait
{
    /**
     * @param $entity
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save($entity = null)
    {
        $em = $this->getEntityManager();

        if (null !== $entity) {
            $em->persist($entity);
        }
        $em->flush();
    }

    /**
     * @param $entity
     */
    public function delete($entity)
    {
        $em = $this->getEntityManager();

        $em->remove($entity);
        $em->flush();
    }
}