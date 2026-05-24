<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class SeoRedirectRepository extends EntityRepository
{
    public function findEnabledBySourcePath($sourcePath)
    {
        return $this->createQueryBuilder('r')
            ->where('r.sourcePath = :sourcePath')
            ->andWhere('r.enable = :enable')
            ->setParameter('sourcePath', $sourcePath)
            ->setParameter('enable', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
