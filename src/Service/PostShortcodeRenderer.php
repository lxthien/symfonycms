<?php

namespace App\Service;

use App\Entity\News;
use App\Entity\NewsCategory;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

/**
 * Render shortcode [bai_viet] trong nội dung CKEditor (trang danh mục & bài viết).
 *
 * Ví dụ:
 *   [bai_viet danh_muc="3,4,5" so_luong="6"]
 *   [bai_viet bai_viet="10,12,15"]
 *   [bai_viet id="10,12,15"]
 */
class PostShortcodeRenderer
{
    private const DEFAULT_LIMIT = 6;
    private const MAX_LIMIT = 24;

    private $em;
    private $twig;

    public function __construct(EntityManagerInterface $em, Environment $twig)
    {
        $this->em = $em;
        $this->twig = $twig;
    }

    public function render(?string $content, ?int $excludePostId = null): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        return Shortcode::replaceBlocks(
            $content,
            'bai_viet',
            fn (string $shortcode): string => $this->renderPostList($shortcode, $excludePostId)
        );
    }

    private function renderPostList(string $shortcode, ?int $excludePostId): string
    {
        $params = Shortcode::attributes($shortcode);

        $categoryIds = $this->parseIds($params['danh_muc'] ?? '');
        $postIds = $this->parseIds($params['bai_viet'] ?? ($params['id'] ?? ($params['ids'] ?? '')));

        $hasExplicitLimit = isset($params['so_luong']) && (int) $params['so_luong'] > 0;
        if ($hasExplicitLimit) {
            $limit = max(1, min(self::MAX_LIMIT, (int) $params['so_luong']));
        } elseif ($postIds !== []) {
            $limit = min(self::MAX_LIMIT, count($postIds));
        } else {
            $limit = self::DEFAULT_LIMIT;
        }

        $qb = $this->em->createQueryBuilder()
            ->select('DISTINCT n')
            ->from(News::class, 'n')
            ->where('n.status = :status')
            ->andWhere('n.postType = :postType')
            ->setParameter('status', News::STATUS_PUBLISHED)
            ->setParameter('postType', 'post')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($postIds !== []) {
            $qb->andWhere('n.id IN (:postIds)')
                ->setParameter('postIds', $postIds);
        }

        if ($categoryIds !== []) {
            $qb->innerJoin('n.category', 'c')
                ->andWhere('c.id IN (:categories)')
                ->setParameter('categories', $this->withChildCategories($categoryIds));
        }

        if ($excludePostId && !in_array($excludePostId, $postIds, true)) {
            $qb->andWhere('n.id != :current')
                ->setParameter('current', $excludePostId);
        }

        $posts = $qb->getQuery()->getResult();
        if ($posts === []) {
            return '';
        }

        // Khi chỉ định danh sách bài viết ID, giữ đúng thứ tự ID người dùng nhập.
        if ($postIds !== []) {
            $orderMap = array_flip($postIds);
            usort($posts, static function (News $a, News $b) use ($orderMap): int {
                return ($orderMap[$a->getId()] ?? PHP_INT_MAX) <=> ($orderMap[$b->getId()] ?? PHP_INT_MAX);
            });
        }

        return $this->twig->render('news/components/postListShortcode.html.twig', [
            'posts' => $posts,
        ]);
    }

    /**
     * @return list<int>
     */
    private function parseIds(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', preg_split('/[\s,]+/', $raw) ?: []),
            static fn (int $id): bool => $id > 0
        )));
    }

    /**
     * @param list<int> $ids
     *
     * @return list<int>
     */
    private function withChildCategories(array $ids): array
    {
        $children = $this->em->createQueryBuilder()
            ->select('c.id')
            ->from(NewsCategory::class, 'c')
            ->where('IDENTITY(c.parentcat) IN (:ids)')
            ->andWhere('c.enable = true')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_unique(array_merge($ids, array_map('intval', $children))));
    }
}

