<?php

namespace AppBundle\InternalLink;

use AppBundle\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

class InternalLinkSuggestionManager
{
    private $em;
    private $router;

    private $stopWords = array(
        'cho', 'cua', 'cung', 'cac', 'con', 'khi', 'mot', 'nhung', 'the', 'thi',
        'trong', 'tren', 'voi', 'va', 've', 'nay', 'ban', 'duoc', 'khong', 'can',
        'gia', 'bao', 'dich', 'vu', 'xay', 'dung', 'nha',
    );

    public function __construct(EntityManagerInterface $em, RouterInterface $router)
    {
        $this->em = $em;
        $this->router = $router;
    }

    public function suggest(News $post = null, array $context = array(), $limit = 10)
    {
        $context = $this->buildContext($post, $context);
        $candidates = $this->findCandidates($post ? $post->getId() : 0, 120);
        $items = array();

        foreach ($candidates as $candidate) {
            $score = 0;
            $reasons = array();

            $score += $this->scoreCategory($candidate, $context['categoryIds'], $context['primaryCategoryId'], $reasons);
            $score += $this->scoreTags($candidate, $context['tagNames'], $reasons);
            $score += $this->scoreKeywords($candidate, $context['keywords'], $reasons);

            if ($candidate->getViewCounts() > 0) {
                $score += min(8, (int) floor(log($candidate->getViewCounts() + 1, 10) * 2));
            }

            if ($candidate->getUpdatedAt() instanceof \DateTime && $candidate->getUpdatedAt() > new \DateTime('-180 days')) {
                $score += 3;
            }

            if ($score <= 0) {
                continue;
            }

            $items[] = array(
                'id' => $candidate->getId(),
                'title' => $candidate->getTitle(),
                'url' => $this->router->generate('news_show', array('slug' => $candidate->getUrl())),
                'description' => strip_tags((string) $candidate->getDescription()),
                'type' => $candidate->getPostType(),
                'score' => $score,
                'reasons' => array_values(array_unique($reasons)),
            );
        }

        usort($items, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['id'] - $a['id'];
            }

            return $b['score'] - $a['score'];
        });

        return array_slice($items, 0, $limit);
    }

    public function search($query, $currentId = 0, $limit = 12)
    {
        $query = trim((string) $query);
        $qb = $this->em->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.postType IN (:postTypes)')
            ->andWhere('n.status = :status')
            ->setParameter('postTypes', array('post', 'page'))
            ->setParameter('status', News::STATUS_PUBLISHED)
            ->orderBy('n.updatedAt', 'DESC')
            ->setMaxResults($limit);

        if ($currentId > 0) {
            $qb->andWhere('n.id != :currentId')->setParameter('currentId', $currentId);
        }

        if ($query !== '') {
            $qb->andWhere('n.title LIKE :q OR n.url LIKE :q OR n.description LIKE :q OR n.pageKeyword LIKE :q')
                ->setParameter('q', '%' . $query . '%');
        }

        $items = array();

        foreach ($qb->getQuery()->getResult() as $post) {
            $items[] = array(
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'url' => $this->router->generate('news_show', array('slug' => $post->getUrl())),
                'description' => strip_tags((string) $post->getDescription()),
                'type' => $post->getPostType(),
                'score' => 0,
                'reasons' => $query !== '' ? array('Khớp từ khóa tìm kiếm') : array(),
            );
        }

        return $items;
    }

    private function buildContext(News $post = null, array $context = array())
    {
        $categoryIds = $this->normalizeIds(isset($context['categoryIds']) ? $context['categoryIds'] : array());
        $primaryCategoryId = (int) (isset($context['primaryCategoryId']) ? $context['primaryCategoryId'] : 0);
        $tagNames = $this->normalizeNames(isset($context['tagNames']) ? $context['tagNames'] : array());
        $keywordSource = implode(' ', array(
            isset($context['title']) ? $context['title'] : '',
            isset($context['description']) ? $context['description'] : '',
            isset($context['keywords']) ? $context['keywords'] : '',
        ));

        if ($post) {
            foreach ($post->getCategory() as $category) {
                $categoryIds[] = (int) $category->getId();
            }

            if (!$primaryCategoryId) {
                $primaryCategoryId = (int) $post->getCategoryPrimary();
            }

            foreach ($post->getTags() as $tag) {
                $tagNames[] = $tag->getName();
            }

            $keywordSource .= ' ' . implode(' ', array(
                $post->getTitle(),
                $post->getDescription(),
                $post->getPageTitle(),
                $post->getPageDescription(),
                $post->getPageKeyword(),
            ));
        }

        return array(
            'categoryIds' => array_values(array_unique($this->normalizeIds($categoryIds))),
            'primaryCategoryId' => $primaryCategoryId,
            'tagNames' => array_values(array_unique($this->normalizeNames($tagNames))),
            'keywords' => $this->tokenize($keywordSource),
        );
    }

    private function findCandidates($currentId = 0, $limit = 120)
    {
        $qb = $this->em->getRepository(News::class)->createQueryBuilder('n')
            ->leftJoin('n.category', 'c')->addSelect('c')
            ->leftJoin('n.tags', 't')->addSelect('t')
            ->where('n.postType IN (:postTypes)')
            ->andWhere('n.status = :status')
            ->setParameter('postTypes', array('post', 'page'))
            ->setParameter('status', News::STATUS_PUBLISHED)
            ->orderBy('n.updatedAt', 'DESC')
            ->setMaxResults($limit);

        if ($currentId > 0) {
            $qb->andWhere('n.id != :currentId')->setParameter('currentId', $currentId);
        }

        return $qb->getQuery()->getResult();
    }

    private function scoreCategory(News $candidate, array $categoryIds, $primaryCategoryId, array &$reasons)
    {
        if (!$categoryIds) {
            return 0;
        }

        $score = 0;

        foreach ($candidate->getCategory() as $category) {
            $categoryId = (int) $category->getId();

            if (!in_array($categoryId, $categoryIds, true)) {
                continue;
            }

            $score += $primaryCategoryId && $categoryId === (int) $primaryCategoryId ? 36 : 24;
            $reasons[] = 'Cùng danh mục';
        }

        return min($score, 40);
    }

    private function scoreTags(News $candidate, array $tagNames, array &$reasons)
    {
        if (!$tagNames) {
            return 0;
        }

        $score = 0;

        foreach ($candidate->getTags() as $tag) {
            if (in_array($this->normalizeText($tag->getName()), $tagNames, true)) {
                $score += 18;
                $reasons[] = 'Cùng tag';
            }
        }

        return min($score, 36);
    }

    private function scoreKeywords(News $candidate, array $keywords, array &$reasons)
    {
        if (!$keywords) {
            return 0;
        }

        $candidateTokens = $this->tokenize(implode(' ', [
            $candidate->getTitle(),
            $candidate->getDescription(),
            $candidate->getPageTitle(),
            $candidate->getPageDescription(),
            $candidate->getPageKeyword(),
        ]));

        $matches = array_intersect($keywords, $candidateTokens);
        $matchCount = count($matches);

        if ($matchCount > 0) {
            $reasons[] = 'Khớp keyword: ' . implode(', ', array_slice($matches, 0, 3));
        }

        return min(24, $matchCount * 6);
    }

    private function normalizeIds($values)
    {
        $ids = array();

        foreach ($this->normalizeArray($values) as $value) {
            $id = (int) $value;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    private function normalizeNames($values)
    {
        $names = array();

        foreach ($this->normalizeArray($values) as $value) {
            $name = $this->normalizeText($value);

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function normalizeArray($values)
    {
        if ($values === null || $values === '') {
            return array();
        }

        if (!is_array($values)) {
            $values = preg_split('/[,;]+/', (string) $values);
        }

        return array_filter(array_map('trim', $values), function ($value) {
            return $value !== '';
        });
    }

    private function tokenize($value)
    {
        $value = $this->normalizeText(strip_tags((string) $value));
        $parts = preg_split('/[^a-z0-9]+/', $value);
        $tokens = array();

        foreach ($parts as $part) {
            if (strlen($part) < 3 || in_array($part, $this->stopWords, true)) {
                continue;
            }

            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }

    private function normalizeText($value)
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
        $value = mb_strtolower($value, 'UTF-8');
        $from = array('à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ','è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ','ì','í','ị','ỉ','ĩ','ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ','ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ','ỳ','ý','ỵ','ỷ','ỹ','đ');
        $to = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d');

        return trim(str_replace($from, $to, $value));
    }
}
