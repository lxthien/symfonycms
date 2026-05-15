<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class MediaRepository extends EntityRepository
{
    public function createFilteredQueryBuilder(array $filters)
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.folder', 'f')->addSelect('f')
            ->leftJoin('m.tags', 't')->addSelect('t');

        if ($filters['q'] !== '') {
            $qb->andWhere('m.originalName LIKE :q OR m.filename LIKE :q OR m.alt LIKE :q OR m.caption LIKE :q OR m.credit LIKE :q')
                ->setParameter('q', '%' . $filters['q'] . '%');
        }

        if ($filters['folder'] !== '') {
            $qb->andWhere('f.id = :folderId')->setParameter('folderId', $filters['folder']);
        }

        if ($filters['tag'] !== '') {
            $qb->andWhere('t.id = :tagId')->setParameter('tagId', $filters['tag']);
        }

        if ($filters['type'] === 'image') {
            $qb->andWhere('m.mimeType LIKE :mimeType')->setParameter('mimeType', 'image/%');
        } elseif ($filters['type'] === 'pdf') {
            $qb->andWhere('m.mimeType = :mimeType')->setParameter('mimeType', 'application/pdf');
        }

        if ($filters['alt'] === 'missing') {
            $qb->andWhere('m.mimeType LIKE :imageMime')
                ->andWhere('m.alt IS NULL OR m.alt = :emptyAlt')
                ->setParameter('imageMime', 'image/%')
                ->setParameter('emptyAlt', '');
        }

        return $qb->orderBy('m.createdAt', 'DESC');
    }
}
