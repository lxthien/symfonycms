<?php

namespace AppBundle\Repository;

use AppBundle\Entity\News;
use Doctrine\ORM\EntityRepository;

class NewsMediaRepository extends EntityRepository
{
    public function findOrderedByNews(News $news)
    {
        return $this->createQueryBuilder('nm')
            ->leftJoin('nm.media', 'm')->addSelect('m')
            ->where('nm.news = :news')
            ->setParameter('news', $news)
            ->orderBy('nm.ordering', 'ASC')
            ->addOrderBy('nm.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
