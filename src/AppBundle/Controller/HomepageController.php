<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\News;

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
                        if ($category->getId() != 2) {
                            $posts = $this->getDoctrine()
                                ->getRepository(News::class)
                                ->createQueryBuilder('n')
                                ->innerJoin('n.category', 't')
                                ->where('t.id = :newscategory_id')
                                ->andWhere('n.enable = :enable')
                                ->setParameter('newscategory_id', $category->getId())
                                ->setParameter('enable', 1)
                                ->setMaxResults( $listCategoriesOnHomepage[$i]["items"] )
                                ->orderBy('n.createdAt', 'DESC')
                                ->getQuery()
                                ->getResult();
                        } else {
                            $listCategoriesIds = array($category->getId());

                            $allSubCategories = $this->getDoctrine()
                                ->getRepository(NewsCategory::class)
                                ->createQueryBuilder('c')
                                ->where('c.parentcat = (:parentcat)')
                                ->setParameter('parentcat', $category->getId())
                                ->getQuery()->getResult();

                            foreach ($allSubCategories as $value) {
                                $listCategoriesIds[] = $value->getId();
                            }

                            $posts = $this->getDoctrine()
                                ->getRepository(News::class)
                                ->createQueryBuilder('n')
                                ->innerJoin('n.category', 't')
                                ->where('t.id IN (:listCategoriesIds)')
                                ->andWhere('n.enable = :enable')
                                ->setParameter('listCategoriesIds', $listCategoriesIds)
                                ->setParameter('enable', 1)
                                ->orderBy('n.createdAt', 'DESC')
                                ->getQuery()->getResult();
                        }
                    }

                    $blockOnHomepage = (object) array('category' => $category, 'posts' => $posts, 'description' => $listCategoriesOnHomepage[$i]["description"]);
                    $blocksOnHomepage[] = $blockOnHomepage;
                }
            }
        }

        return $this->render('homepage/index.html.twig', [
            'blocksOnHomepage' => $blocksOnHomepage,
            'showSlide' => true
        ]);
    }
}
