<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ContentRevisionRepository extends EntityRepository
{
    public function findRecentByNews($news, $limit = 20)
    {
        return $this->createQueryBuilder('r')
            ->where('r.news = :news')
            ->setParameter('news', $news)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findLatestAutosaveByNews($news)
    {
        return $this->createQueryBuilder('r')
            ->where('r.news = :news')
            ->andWhere('r.revisionType = :revisionType')
            ->setParameter('news', $news)
            ->setParameter('revisionType', 'autosave')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
