<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Comment;
use AppBundle\Entity\Contact;
use AppBundle\Entity\News;
use AppBundle\Entity\NewsCategory;
use AppBundle\Seo\SeoAnalyzer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * 
 * @Route("/admin")
 * @Route("/admin/dashboard")
 * @Security("has_role('ROLE_ADMIN')")
 */

class DashboardController extends Controller
{
    /**
     * 
     * @Route("/", name="admin_dashboard_index")
     * @Method("GET")
     */
    public function indexAction(SeoAnalyzer $seoAnalyzer)
    {
        $em = $this->getDoctrine()->getManager();

        $now = new \DateTime();
        $sevenDaysAgo = (clone $now)->modify('-7 days');
        $thirtyDaysAgo = (clone $now)->modify('-30 days');

        $postCount = $this->countNewsBy(['postType' => 'post']);
        $publishedPostCount = $this->countNewsBy(['postType' => 'post', 'status' => 'published']);
        $draftPostCount = $this->countNewsBy(['postType' => 'post', 'status' => 'draft']);
        $pageCount = $this->countNewsBy(['postType' => 'page']);
        $categoryCount = $em->getRepository(NewsCategory::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pendingCommentCount = $em->getRepository(Comment::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.approved = :approved')
            ->setParameter('approved', false)
            ->getQuery()
            ->getSingleScalarResult();

        $newLeadCount = $em->getRepository(Contact::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :fromDate')
            ->setParameter('fromDate', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $topPosts = $em->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.postType = :postType')
            ->andWhere('n.status = :status')
            ->setParameter('postType', 'post')
            ->setParameter('status', 'published')
            ->orderBy('n.viewCounts', 'DESC')
            ->addOrderBy('n.updatedAt', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        $recentLeads = $em->getRepository(Contact::class)->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        $seoAudits = $this->getSeoAudits($seoAnalyzer);
        $seoIssues = array_values(array_filter($seoAudits, function ($audit) {
            return $audit['score'] < 80 || count($audit['reasons']) > 0;
        }));
        $seoIssueCount = count($seoIssues);
        $averageSeoScore = $seoAnalyzer->calculateAverageScore($seoAudits);
        $recentPublishedCount = $em->getRepository(News::class)->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.status = :status')
            ->andWhere('n.createdAt >= :fromDate')
            ->setParameter('status', 'published')
            ->setParameter('fromDate', $thirtyDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $pendingPosts = $em->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.postType = :postType')
            ->andWhere('n.status = :status')
            ->setParameter('postType', 'post')
            ->setParameter('status', 'pending_review')
            ->orderBy('n.updatedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $scheduledPosts = $em->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.postType = :postType')
            ->andWhere('n.status = :status')
            ->setParameter('postType', 'post')
            ->setParameter('status', 'scheduled')
            ->orderBy('n.scheduledAt', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $indexReadiness = $averageSeoScore;
        $dashboardCharts = $this->getDashboardCharts($topPosts);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'postCount' => (int) $postCount,
                'publishedPostCount' => (int) $publishedPostCount,
                'draftPostCount' => (int) $draftPostCount,
                'pageCount' => (int) $pageCount,
                'categoryCount' => (int) $categoryCount,
                'pendingCommentCount' => (int) $pendingCommentCount,
                'newLeadCount' => (int) $newLeadCount,
                'seoIssueCount' => (int) $seoIssueCount,
                'averageSeoScore' => (int) $averageSeoScore,
                'recentPublishedCount' => (int) $recentPublishedCount,
                'indexReadiness' => (int) $indexReadiness,
            ],
            'topPosts' => $topPosts,
            'recentLeads' => $recentLeads,
            'seoIssues' => array_slice($seoIssues, 0, 8),
            'dashboardCharts' => $dashboardCharts,
            'pendingPosts' => $pendingPosts,
            'scheduledPosts' => $scheduledPosts,
        ]);
    }

    private function countNewsBy(array $criteria)
    {
        $qb = $this->getDoctrine()->getRepository(News::class)->createQueryBuilder('n')
            ->select('COUNT(n.id)');

        foreach ($criteria as $field => $value) {
            $qb->andWhere('n.' . $field . ' = :' . $field)
                ->setParameter($field, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    private function getSeoAudits(SeoAnalyzer $seoAnalyzer)
    {
        $posts = $this->getDoctrine()->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.postType = :postType')
            ->setParameter('status', 'published')
            ->setParameter('postType', 'post')
            ->orderBy('n.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $audits = [];

        foreach ($posts as $post) {
            $audits[] = $seoAnalyzer->analyze($post);
        }

        usort($audits, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['post']->getUpdatedAt()->getTimestamp() <=> $a['post']->getUpdatedAt()->getTimestamp();
            }

            return $a['score'] <=> $b['score'];
        });

        return $audits;
    }

    private function getDashboardCharts(array $topPosts)
    {
        $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $start = (clone $now)->modify('-29 days')->setTime(0, 0, 0);
        $end = (clone $now)->setTime(23, 59, 59);
        $series = $this->createDateSeries($start, $end);

        return [
            'views' => $this->getDailyViewsChart($series, $start, $end),
            'posts' => $this->getPostGrowthChart($series, $start, $end),
            'topPosts' => $this->getTopViewedPostsChart($topPosts),
            'comments' => $this->getCommentTrendChart($series, $start, $end),
        ];
    }

    private function createDateSeries(\DateTime $start, \DateTime $end)
    {
        $series = [];
        $cursor = clone $start;

        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $series[$key] = [
                'label' => $cursor->format('d/m'),
                'value' => 0,
            ];
            $cursor->modify('+1 day');
        }

        return $series;
    }

    private function getDailyViewsChart(array $series, \DateTime $start, \DateTime $end)
    {
        $rows = $this->getDoctrine()->getConnection()->fetchAll(
            'SELECT viewDate AS day, SUM(views) AS total
             FROM news_view_stat
             WHERE viewDate BETWEEN :startDate AND :endDate
             GROUP BY viewDate
             ORDER BY viewDate ASC',
            [
                'startDate' => $start->format('Y-m-d'),
                'endDate' => $end->format('Y-m-d'),
            ]
        );

        foreach ($rows as $row) {
            $day = $row['day'] instanceof \DateTimeInterface ? $row['day']->format('Y-m-d') : substr((string) $row['day'], 0, 10);

            if (isset($series[$day])) {
                $series[$day]['value'] = (int) $row['total'];
            }
        }

        return [
            'labels' => array_column($series, 'label'),
            'views' => array_column($series, 'value'),
        ];
    }

    private function getPostGrowthChart(array $series, \DateTime $start, \DateTime $end)
    {
        $connection = $this->getDoctrine()->getConnection();
        $dailyRows = $connection->fetchAll(
            'SELECT DATE(createdAt) AS day, COUNT(id) AS total
             FROM news
             WHERE postType = :postType
               AND status = :status
               AND createdAt BETWEEN :startDate AND :endDate
             GROUP BY DATE(createdAt)
             ORDER BY day ASC',
            [
                'postType' => 'post',
                'status' => 'published',
                'startDate' => $start->format('Y-m-d H:i:s'),
                'endDate' => $end->format('Y-m-d H:i:s'),
            ]
        );

        foreach ($dailyRows as $row) {
            $day = substr((string) $row['day'], 0, 10);

            if (isset($series[$day])) {
                $series[$day]['value'] = (int) $row['total'];
            }
        }

        $totalBeforeStart = (int) $connection->fetchColumn(
            'SELECT COUNT(id)
             FROM news
             WHERE postType = :postType
               AND status = :status
               AND createdAt < :startDate',
            [
                'postType' => 'post',
                'status' => 'published',
                'startDate' => $start->format('Y-m-d H:i:s'),
            ]
        );

        $cumulative = [];
        $runningTotal = $totalBeforeStart;

        foreach ($series as $day) {
            $runningTotal += $day['value'];
            $cumulative[] = $runningTotal;
        }

        return [
            'labels' => array_column($series, 'label'),
            'daily' => array_column($series, 'value'),
            'cumulative' => $cumulative,
        ];
    }

    private function getTopViewedPostsChart(array $topPosts)
    {
        $labels = [];
        $views = [];

        foreach (array_slice($topPosts, 0, 8) as $post) {
            $title = trim((string) $post->getTitle());
            $labels[] = mb_strlen($title) > 42 ? mb_substr($title, 0, 39) . '...' : $title;
            $views[] = (int) $post->getViewCounts();
        }

        return [
            'labels' => $labels,
            'views' => $views,
        ];
    }

    private function getCommentTrendChart(array $series, \DateTime $start, \DateTime $end)
    {
        $rows = $this->getDoctrine()->getConnection()->fetchAll(
            'SELECT DATE(createdAt) AS day,
                    COUNT(id) AS total,
                    SUM(CASE WHEN approved = 1 THEN 1 ELSE 0 END) AS approvedTotal
             FROM comment
             WHERE createdAt BETWEEN :startDate AND :endDate
             GROUP BY DATE(createdAt)
             ORDER BY day ASC',
            [
                'startDate' => $start->format('Y-m-d H:i:s'),
                'endDate' => $end->format('Y-m-d H:i:s'),
            ]
        );

        $approvedSeries = $series;

        foreach ($rows as $row) {
            $day = substr((string) $row['day'], 0, 10);

            if (isset($series[$day])) {
                $series[$day]['value'] = (int) $row['total'];
                $approvedSeries[$day]['value'] = (int) $row['approvedTotal'];
            }
        }

        return [
            'labels' => array_column($series, 'label'),
            'total' => array_column($series, 'value'),
            'approved' => array_column($approvedSeries, 'value'),
        ];
    }
}
