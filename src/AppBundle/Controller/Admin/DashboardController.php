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
        $publishedPostCount = $this->countNewsBy(['postType' => 'post', 'enable' => true]);
        $draftPostCount = $this->countNewsBy(['postType' => 'post', 'enable' => false]);
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
            ->andWhere('n.enable = :enable')
            ->setParameter('postType', 'post')
            ->setParameter('enable', true)
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
            ->where('n.enable = :enable')
            ->andWhere('n.createdAt >= :fromDate')
            ->setParameter('enable', true)
            ->setParameter('fromDate', $thirtyDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $indexReadiness = $averageSeoScore;

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
            ->where('n.enable = :enable')
            ->andWhere('n.postType = :postType')
            ->setParameter('enable', true)
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
}
