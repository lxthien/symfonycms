<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\News;
use AppBundle\Entity\ProductCat;
use AppBundle\Entity\Product;

class HomepageController extends Controller
{
    public function indexAction(Request $request)
    {
        $listCategoriesOnHomepage = $this->get('settings_manager')->get('listCategoryOnHomepage');
        $blocksOnHomepage = array();

        if (!empty($listCategoriesOnHomepage)) {
            $listCategoriesOnHomepage = json_decode($listCategoriesOnHomepage, true);

            if (is_array($listCategoriesOnHomepage)) {
                for ($i = 0; $i < count($listCategoriesOnHomepage); $i++) {
                    $blockOnHomepage = [];
                    $category = $this->getDoctrine()
                                    ->getRepository(NewsCategory::class)
                                    ->find($listCategoriesOnHomepage[$i]["id"]);

                    if ($category) {
                        $posts = $this->getDoctrine()
                                ->getRepository(News::class)
                                ->findBy(
                                    array('postType' => 'post', 'enable' => 1, 'category' => $category->getId()),
                                    array('createdAt' => 'DESC'),
                                    $listCategoriesOnHomepage[$i]["items"]
                                );
                    }

                    $blockOnHomepage = (object) array('category' => $category, 'posts' => $posts);
                    $blocksOnHomepage[] = $blockOnHomepage;
                }
            }
        }

        $productsNew = $this->getDoctrine()
            ->getRepository(Product::class)
            ->createQueryBuilder('n')
            ->where('n.enable = :enable')
            ->setParameter('enable', 1)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults( 10 )
            ->getQuery()->getResult();

        foreach ($productsNew as $row) {
            $repositoryRating = $this->getDoctrine()->getManager();
            $queryRating = $repositoryRating->createQuery(
                'SELECT AVG(r.rating) as ratingValue
                FROM AppBundle:Rating r
                WHERE r.product = :product_id'
            )->setParameter('product_id', $row->getId());
            $rating = $queryRating->setMaxResults(1)->getOneOrNullResult();

            $ratingValue = !empty($rating['ratingValue']) ? str_replace('.0', '', number_format($rating['ratingValue'], 1)) : 0;

            $row->ratingNumber = $ratingValue;
        }

        $productsHot = $this->getDoctrine()
            ->getRepository(Product::class)
            ->createQueryBuilder('n')
            ->where('n.enable = :enable')
            ->andWhere('n.isHot = :isHot')
            ->setParameter('enable', 1)
            ->setParameter('isHot', 1)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()->getResult();

        foreach ($productsHot as $row) {
            $repositoryRating = $this->getDoctrine()->getManager();
            $queryRating = $repositoryRating->createQuery(
                'SELECT AVG(r.rating) as ratingValue
                FROM AppBundle:Rating r
                WHERE r.product = :product_id'
            )->setParameter('product_id', $row->getId());
            $rating = $queryRating->setMaxResults(1)->getOneOrNullResult();

            $ratingValue = !empty($rating['ratingValue']) ? str_replace('.0', '', number_format($rating['ratingValue'], 1)) : 0;

            $row->ratingNumber = $ratingValue;
        }

        return $this->render('homepage/index.html.twig', [
            'blocksOnHomepage' => $blocksOnHomepage,
            'productsNew' => $productsNew,
            'productsHot' => $productsHot,
            'showSlide' => true
        ]);
    }
}
