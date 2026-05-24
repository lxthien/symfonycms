<?php

namespace App\Controller;

use App\Controller\LegacyController as AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\NewsCategory;
use App\Entity\News;

class HomepageController extends AbstractController
{
    public function indexAction(Request $request)
    {
        $listCategoriesOnHomepage = $this->get('settings_manager')->get('listCategoryOnHomepage');
        $blocksOnHomepage = array();
        $items = $this->normalizeHomepageCategoryConfig($listCategoriesOnHomepage);

        if (!empty($items)) {
            $em = $this->getDoctrine()->getManager();
            $categories = $this->findHomepageCategories($items);
            $childCategoryIds = array();

            foreach ($items as $item) {
                if (isset($categories[$item['id']]) && $categories[$item['id']]->getId() == 2) {
                    $childCategoryIds[$item['id']] = $this->findEnabledChildCategoryIds($categories[$item['id']]);
                }
            }

            foreach ($items as $item) {
                if (!isset($categories[$item['id']])) {
                    continue;
                }

                $category = $categories[$item['id']];
                $categoryIds = array($category->getId());

                if (isset($childCategoryIds[$category->getId()])) {
                    $categoryIds = array_merge($categoryIds, $childCategoryIds[$category->getId()]);
                }

                $posts = $em->getRepository(News::class)
                    ->createQueryBuilder('n')
                    ->select('DISTINCT n')
                    ->innerJoin('n.category', 't')
                    ->where('t.id IN (:categoryIds)')
                    ->andWhere('n.status = :status')
                    ->andWhere('n.postType = :postType')
                    ->setParameter('categoryIds', array_values(array_unique($categoryIds)))
                    ->setParameter('status', News::STATUS_PUBLISHED)
                    ->setParameter('postType', 'post')
                    ->setMaxResults($item['items'])
                    ->orderBy('n.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();

                $blocksOnHomepage[] = (object) array(
                    'category' => $category,
                    'posts' => $posts,
                    'description' => $item['description'],
                );
            }
        }

        return $this->render('homepage/index.html.twig', [
            'blocksOnHomepage' => $blocksOnHomepage,
            'showSlide' => true
        ]);
    }

    private function normalizeHomepageCategoryConfig($rawConfig)
    {
        if (empty($rawConfig)) {
            return array();
        }

        $decoded = json_decode($rawConfig, true);

        if (!is_array($decoded)) {
            return array();
        }

        $items = array();

        foreach ($decoded as $item) {
            $id = isset($item['id']) ? (int) $item['id'] : 0;

            if ($id <= 0) {
                continue;
            }

            $limit = isset($item['items']) ? (int) $item['items'] : 8;
            $limit = max(1, min(24, $limit));

            $items[] = array(
                'id' => $id,
                'items' => $limit,
                'description' => isset($item['description']) ? trim((string) $item['description']) : '',
            );
        }

        return $items;
    }

    private function findHomepageCategories(array $items)
    {
        $ids = array();

        foreach ($items as $item) {
            $ids[] = $item['id'];
        }

        if (!$ids) {
            return array();
        }

        $categories = $this->getDoctrine()
            ->getRepository(NewsCategory::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.parentcat', 'p')
            ->addSelect('p')
            ->where('c.id IN (:ids)')
            ->andWhere('c.enable = :enabled')
            ->setParameter('ids', array_values(array_unique($ids)))
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult();

        $indexed = array();

        foreach ($categories as $category) {
            $indexed[$category->getId()] = $category;
        }

        return $indexed;
    }

    private function findEnabledChildCategoryIds(NewsCategory $category)
    {
        $rows = $this->getDoctrine()
            ->getRepository(NewsCategory::class)
            ->createQueryBuilder('c')
            ->select('c.id')
            ->where('c.parentcat = :parentcat')
            ->andWhere('c.enable = :enabled')
            ->setParameter('parentcat', $category)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getArrayResult();

        $ids = array();

        foreach ($rows as $row) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }
}
