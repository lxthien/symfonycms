<?php

namespace AppBundle\Controller;

use AppBundle\Entity\News;
use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\Tag;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends Controller
{
    /**
     * @Route("/sitemap.xml", name="sitemap", defaults={"_format"="xml"})
     */
    public function indexAction()
    {
        $urls = [];
        $em = $this->getDoctrine()->getManager();

        $latestContentUpdate = $em->getRepository(News::class)->createQueryBuilder('n')
            ->select('MAX(n.updatedAt)')
            ->where('n.enable = :enable')
            ->andWhere('n.isIndex = :isIndex')
            ->setParameter('enable', true)
            ->setParameter('isIndex', true)
            ->getQuery()
            ->getSingleScalarResult();

        $urls[] = [
            'loc' => $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lastmod' => $latestContentUpdate ? new \DateTime($latestContentUpdate) : new \DateTime(),
            'priority' => '1.0',
            'changefreq' => 'daily',
        ];

        $urls = array_merge($urls, $this->getStaticRouteUrls($latestContentUpdate));

        $categories = $em->getRepository(NewsCategory::class)->createQueryBuilder('c')
            ->where('c.enable = :enable')
            ->andWhere('c.isIndex = :isIndex')
            ->setParameter('enable', true)
            ->setParameter('isIndex', true)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($categories as $category) {
            $urls[] = [
                'loc' => $this->getCategoryUrl($category),
                'lastmod' => $category->getUpdatedAt(),
                'priority' => $category->getParentcat() === 'root' ? '0.8' : '0.7',
                'changefreq' => 'weekly',
            ];
        }

        $posts = $em->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.enable = :enable')
            ->andWhere('n.isIndex = :isIndex')
            ->setParameter('enable', true)
            ->setParameter('isIndex', true)
            ->orderBy('n.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => $this->generateUrl('news_show', ['slug' => $post->getUrl()], UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod' => $post->getUpdatedAt(),
                'priority' => $post->isPage() ? '0.8' : '0.7',
                'changefreq' => $post->isPage() ? 'monthly' : 'weekly',
            ];
        }

        $urls = array_merge($urls, $this->getTagUrls());
        //$urls = array_merge($urls, $this->getAuthorUrls());

        $response = new Response($this->renderView('sitemap/index.xml.twig', [
            'urls' => $urls,
        ]));
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }

    private function getCategoryUrl(NewsCategory $category)
    {
        if ($category->getParentcat() === 'root') {
            return $this->generateUrl('news_category', [
                'level1' => $category->getUrl(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->generateUrl('list_category', [
            'level1' => $category->getParentcat()->getUrl(),
            'level2' => $category->getUrl(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function getStaticRouteUrls($latestContentUpdate)
    {
        $fallbackLastmod = $latestContentUpdate ? new \DateTime($latestContentUpdate) : new \DateTime();
        $urls = [];
        $contactPage = $this->getPublishedPostByUrl('lien-he');
        $calculatorPage = $this->getPublishedPostByUrl('chi-phi-xay-dung');

        if (!$contactPage || $contactPage->getIsIndex()) {
            $urls[] = [
                'loc' => $this->generateUrl('contact', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod' => $contactPage ? $contactPage->getUpdatedAt() : $fallbackLastmod,
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        if (!$calculatorPage || $calculatorPage->getIsIndex()) {
            $urls[] = [
                'loc' => $this->generateUrl('caculator_cost_construction', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod' => $calculatorPage ? $calculatorPage->getUpdatedAt() : $fallbackLastmod,
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        return $urls;
    }

    private function getTagUrls()
    {
        $rows = $this->getDoctrine()->getRepository(Tag::class)->createQueryBuilder('t')
            ->select('t, MAX(n.updatedAt) AS latestUpdatedAt')
            ->innerJoin('t.news', 'n')
            ->where('t.isIndex = :isIndex')
            ->andWhere('n.isIndex = :isIndex')
            ->andWhere('n.enable = :enable')
            ->andWhere('n.postType = :postType')
            ->setParameter('isIndex', true)
            ->setParameter('enable', true)
            ->setParameter('postType', 'post')
            ->groupBy('t.id')
            ->orderBy('latestUpdatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $urls = [];

        foreach ($rows as $row) {
            $tag = $row[0];
            $urls[] = [
                'loc' => $this->generateUrl('tags', ['slug' => $tag->getUrl()], UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod' => new \DateTime($row['latestUpdatedAt']),
                'priority' => '0.5',
                'changefreq' => 'weekly',
            ];
        }

        return $urls;
    }

    private function getAuthorUrls()
    {
        $rows = $this->getDoctrine()->getRepository(User::class)->createQueryBuilder('u')
            ->select('u, MAX(n.updatedAt) AS latestUpdatedAt')
            ->innerJoin(News::class, 'n', 'WITH', 'n.author = u.id')
            ->where('n.enable = :enable')
            ->andWhere('n.isIndex = :isIndex')
            ->andWhere('n.postType = :postType')
            ->setParameter('enable', true)
            ->setParameter('isIndex', true)
            ->setParameter('postType', 'post')
            ->groupBy('u.id')
            ->orderBy('latestUpdatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $urls = [];

        foreach ($rows as $row) {
            $user = $row[0];
            $urls[] = [
                'loc' => $this->generateUrl('author', ['slug' => $user->getUsername(), 'page' => 1], UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod' => new \DateTime($row['latestUpdatedAt']),
                'priority' => '0.4',
                'changefreq' => 'weekly',
            ];
        }

        return $urls;
    }

    private function getPublishedPostByUrl($url)
    {
        return $this->getDoctrine()->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.url = :url')
            ->andWhere('n.enable = :enable')
            ->setParameter('url', $url)
            ->setParameter('enable', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
