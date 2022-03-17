<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\News;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Tag;
use AppBundle\Entity\Rating;

use blackknight467\StarRatingBundle\Form\RatingType as RatingType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

class NewsController extends Controller
{
    /**
     * @var UploaderHelper
     */
    private $helper;

    /**
     * Constructs a new instance of UploaderExtension.
     *
     * @param UploaderHelper $helper
     */
    public function __construct(UploaderHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Render the list posts by the category
     * 
     * @return News
     */
    public function listAction($level1, $level2 = null, $page = 1)
    {
        $category = $this->getDoctrine()
            ->getRepository(NewsCategory::class)
            ->findOneBy(array('url' => $level1, 'enable' => 1));

        if (!$category) {
            throw $this->createNotFoundException("The item does not exist");
        }

        if (!empty($level2)) {
            $subCategory = $this->getDoctrine()
                ->getRepository(NewsCategory::class)
                ->findOneBy(array('url' => $level2, 'enable' => 1));

            if (!$subCategory) {
                throw $this->createNotFoundException("The item does not exist");
            }
        }

        // Init breadcrum for category page
        $breadcrumbs = $this->buildBreadcrums(!empty($level2) ? $subCategory : $category, null, null);

        $listCategories = array();
        
        if (empty($level2)) {
            // Get all post for this category and sub category
            $listCategoriesIds[] = $category->getId();

            $allSubCategories = $this->getDoctrine()
                ->getRepository(NewsCategory::class)
                ->createQueryBuilder('c')
                ->where('c.parentcat = (:parentcat)')
                ->setParameter('parentcat', $category->getId())
                ->getQuery()->getResult();

            foreach ($allSubCategories as $value) {
                $listCategories[] = $value;
                $listCategoriesIds[] = $value->getId();
            }

            $news = $this->getDoctrine()
                ->getRepository(News::class)
                ->createQueryBuilder('n')
                ->innerJoin('n.category', 't')
                ->where('t.id IN (:listCategoriesIds)')
                ->andWhere('n.enable = :enable')
                ->setParameter('listCategoriesIds', $listCategoriesIds)
                ->setParameter('enable', 1)
                ->orderBy('n.createdAt', 'DESC')
                ->getQuery()->getResult();

            // No items on this page
            if (count($news) === 0) {
                $tag = $this->getDoctrine()
                    ->getRepository(Tag::class)
                    ->findOneBy(
                        array('url' => $level1)
                    );

                $news = $this->getDoctrine()
                    ->getRepository(News::class)
                    ->createQueryBuilder('n')
                    ->innerJoin('n.tags', 't')
                    ->where('t.id = :tags_id')
                    ->setParameter('tags_id', $tag->getId())
                    ->orderBy('n.createdAt', 'DESC')
                    ->getQuery()->getResult();
            }
        } else {
            $news = $this->getDoctrine()
                ->getRepository(News::class)
                ->createQueryBuilder('n')
                ->innerJoin('n.category', 't')
                ->where('t.id = :newscategory_id')
                ->andWhere('n.enable = :enable')
                ->setParameter('newscategory_id', $subCategory->getId())
                ->setParameter('enable', 1)
                ->orderBy('n.createdAt', 'DESC')
                ->getQuery()->getResult();
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $news,
            $page,
            $this->get('settings_manager')->get('numberRecordOnPage') ?: 10
        );

        return $this->render('news/list.html.twig', [
            'category' => !empty($level2) ? $subCategory : $category,
            'listCategories' => count($listCategories) > 0 ? $listCategories : NULL,
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("{slug}.html",
     *      defaults={"_format"="html"},
     *      name="news_show",
     *      requirements={
     *          "slug": "[^/\.]++"
     *      })
     */
    public function showAction($slug, Request $request)
    {
        if ($request->query->get('preview') === false || $request->query->get('preview_id') === null) {
            $post = $this->getDoctrine()
                ->getRepository(News::class)
                ->findOneBy(
                    array('url' => $slug, 'enable' => 1)
                );
        } else {
            $post = $this->getDoctrine()
                ->getRepository(News::class)
                ->find($request->query->get('preview_id'));
        }

        if (!$post) {
            throw $this->createNotFoundException("The item does not exist");
        }

        // Update viewCount for post
        //$post->setViewCounts( $post->getViewCounts() + 1 );
        //$this->getDoctrine()->getManager()->flush();

        $categoryPrimary = $request->query->get('cat');

        if (!$categoryPrimary) {
            if ($post->getCategoryPrimary() > 0) {
                $categoryPrimary = $post->getCategoryPrimary();
            } else {
                if (!$post->getCategory()->isEmpty()) {
                    $categoryPrimary = $post->getCategory()[0]->getId();
                }
            }
        } else {
            $catPrimary = $this->getDoctrine()
                ->getRepository(NewsCategory::class)
                ->findOneByUrl($categoryPrimary);
            
            $categoryPrimary = $catPrimary->getId();
        }

        if ($categoryPrimary > 0) {
            $category = $this->getDoctrine()
                ->getRepository(NewsCategory::class)
                ->find($categoryPrimary);

            // Get news related
            $relatedNews = $this->getDoctrine()
                ->getRepository(News::class)
                ->createQueryBuilder('r')
                ->innerJoin('r.category', 't')
                ->where('t.id = :newscategory_id')
                ->andWhere('r.id <> :id')
                ->andWhere('r.postType = :postType')
                ->andWhere('r.enable = :enable')
                ->setParameter('newscategory_id', $categoryPrimary)
                ->setParameter('id', $post->getId())
                ->setParameter('postType', $post->getPostType())
                ->setParameter('enable', 1)
                ->setMaxResults( 8 )
                ->orderBy('r.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Get the list comment for post
        $comments = $this->getDoctrine()
            ->getRepository(Comment::class)
            ->createQueryBuilder('c')
            ->where('c.news_id = :news_id')
            ->andWhere('c.approved = :approved')
            ->setParameter('news_id', $post->getId())
            ->setParameter('approved', 1)
            ->getQuery()->getResult();

        // Render form comment for post.
        $form = $this->renderFormComment($post);

        // Render form rating for post.
        $formRating = $this->createFormBuilder(null, array(
                'csrf_protection' => false,
            ))
            ->setAction($this->generateUrl('rating'))
            ->add('rating', RatingType::class)
            ->getForm();


        // Get rating of the post
        $repositoryRating = $this->getDoctrine()->getManager();

        $queryRating = $repositoryRating->createQuery(
            'SELECT AVG(r.rating) as ratingValue, COUNT(r) as ratingCount
            FROM AppBundle:Rating r
            WHERE r.news_id = :news_id'
        )->setParameter('news_id', $post->getId());

        $rating = $queryRating->setMaxResults(1)->getOneOrNullResult();

        // Init breadcrum for the post
        $breadcrumbs = $this->buildBreadcrums(null, $post, null, $categoryPrimary);

        // Filter content to support Lazy Loading
        $contentsLazy = $this->lazyloadContent($post);

        if ($post->isPage()) {
            $imagePath = $this->helper->asset($post, 'imageFile');
            $imagePath = substr($imagePath, 1);
            $imageSize = @getimagesize($imagePath);

            return $this->render('news/page.html.twig', [
                'post'          => $post,
                'contentsLazy'  => $contentsLazy,
                'form'          => $form->createView(),
                'formRating'    => $formRating->createView(),
                'rating'        => !empty($rating['ratingValue']) ? str_replace('.0', '', number_format($rating['ratingValue'], 1)) : 0,
                'ratingPercent' => str_replace('.00', '', number_format(($rating['ratingValue'] * 100) / 5, 2)),
                'ratingValue'   => round($rating['ratingValue']),
                'ratingCount'   => round($rating['ratingCount']),
                'comments'      => $comments,
                'imageSize'     => $imageSize
            ]);
        } else {
            $imagePath = $this->helper->asset($post, 'imageFile');
            $imagePath = substr($imagePath, 1);
            $imageSize = @getimagesize($imagePath);

            return $this->render('news/show.html.twig', [
                'post'          => $post,
                'contentsLazy'  => $contentsLazy,
                'relatedNews'   => !empty($relatedNews) ? $relatedNews : NULL,
                'form'          => $form->createView(),
                'formRating'    => $formRating->createView(),
                'rating'        => !empty($rating['ratingValue']) ? str_replace('.0', '', number_format($rating['ratingValue'], 1)) : 0,
                'ratingPercent' => str_replace('.00', '', number_format(($rating['ratingValue'] * 100) / 5, 2)),
                'ratingValue'   => round($rating['ratingValue']),
                'ratingCount'   => round($rating['ratingCount']),
                'comments'      => $comments,
                'imageSize'     => $imageSize,
                'category'      => !empty($category) ? $category : NULL
            ]);
        }
    }

    private function lazyloadContent($post) {
        $content = $post->getContents();
        $dom = new \DOMDocument();

        // set error level
        $internalErrors = libxml_use_internal_errors(true);

        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

        // Restore error level
        libxml_use_internal_errors($internalErrors);

        $imgs = $dom->getElementsByTagName('img');
        
        foreach ( $imgs as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');

            list($width, $height) = @getimagesize(substr($src, 1));

            $img->setAttribute('src', 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
            $img->setAttribute('data-src', $src);
            $img->setAttribute('alt', $alt);
            $img->setAttribute('width', !empty($width) ? $width : 500);
            $img->setAttribute('height', !empty($height) ? $height : 500);
            $img->setAttribute('class', 'lazyload');
        }
        
        return html_entity_decode($dom->saveHTML());
    }

    /**
     * @Route("amp/{slug}.html",
     *      defaults={"_format"="html"},
     *      name="amp_show",
     *      requirements={
     *          "slug": "[^/\.]++"
     *      })
     */
    public function ampShowAction($slug, Request $request)
    {
        $post = $this->getDoctrine()
                ->getRepository(News::class)
                ->findOneBy(
                    array('url' => $slug, 'enable' => 1)
                );

        if (!$post) {
            throw $this->createNotFoundException("The post does not exist");
        }

        // Update viewCount for post
        $post->setViewCounts( $post->getViewCounts() + 1 );
        $this->getDoctrine()->getManager()->flush();

        $categoryPrimary = $request->query->get('cat');
        
        if (!$categoryPrimary) {
            if ($post->getCategoryPrimary() > 0) {
                $categoryPrimary = $post->getCategoryPrimary();
            } else {
                if (!$post->getCategory()->isEmpty()) {
                    $categoryPrimary = $post->getCategory()[0]->getId();
                }
            }
        } else {
            $catPrimary = $this->getDoctrine()
                ->getRepository(NewsCategory::class)
                ->findOneByUrl($categoryPrimary);
            
            $categoryPrimary = $catPrimary->getId();
        }

        if ($categoryPrimary > 0) {
            $category = $this->getDoctrine()
                ->getRepository(NewsCategory::class)
                ->find($categoryPrimary);

            // Get news related
            $relatedNews = $this->getDoctrine()
                ->getRepository(News::class)
                ->createQueryBuilder('r')
                ->innerJoin('r.category', 't')
                ->where('t.id = :newscategory_id')
                ->andWhere('r.id <> :id')
                ->andWhere('r.postType = :postType')
                ->andWhere('r.enable = :enable')
                ->setParameter('newscategory_id', $categoryPrimary)
                ->setParameter('id', $post->getId())
                ->setParameter('postType', $post->getPostType())
                ->setParameter('enable', 1)
                ->setMaxResults( 8 )
                ->orderBy('r.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Get the list comment for post
        $comments = $this->getDoctrine()
            ->getRepository(Comment::class)
            ->createQueryBuilder('c')
            ->where('c.news_id = :news_id')
            ->andWhere('c.approved = :approved')
            ->setParameter('news_id', $post->getId())
            ->setParameter('approved', 1)
            ->getQuery()->getResult();

        // Get rating of the post
        $repositoryRating = $this->getDoctrine()->getManager();

        $queryRating = $repositoryRating->createQuery(
            'SELECT AVG(r.rating) as ratingValue, COUNT(r) as ratingCount
            FROM AppBundle:Rating r
            WHERE r.news_id = :news_id'
        )->setParameter('news_id', $post->getId());

        $rating = $queryRating->setMaxResults(1)->getOneOrNullResult();

        // Init breadcrum for the post
        $breadcrumbs = $this->buildBreadcrums(null, $post, null);

        // Filter content to support Lazy Loading
        $contentsAmp = $this->amploadContent($post);

        return $this->render('amp/amp-theme/index.html.twig', [
            'post'          => $post,
            'contentsAmp'   => $contentsAmp,
            'relatedNews'   => !empty($relatedNews) ? $relatedNews : NULL,
            'category'      => !empty($category) ? $category : NULL,
            'rating'        => !empty($rating['ratingValue']) ? str_replace('.0', '', number_format($rating['ratingValue'], 1)) : 0,
            'ratingPercent' => str_replace('.00', '', number_format(($rating['ratingValue'] * 100) / 5, 2)),
            'ratingValue'   => round($rating['ratingValue']),
            'ratingCount'   => round($rating['ratingCount']),
            'comments'      => $comments
        ]);
    }

    private function amploadContent($post) {
        $html = $post->getContents();
        preg_match_all("#<img(.*?)\\/?>#", $html, $img_matches);

        foreach ($img_matches[1] as $key => $img_tag) {
            preg_match_all('/(alt|src|width|height)=["\'](.*?)["\']/i', $img_tag, $attribute_matches);
            $attributes = array_combine($attribute_matches[1], $attribute_matches[2]);

            if (!array_key_exists('width', $attributes) || !array_key_exists('height', $attributes)) {
                if (array_key_exists('src', $attributes)) {
                    list($width, $height) = @getimagesize(substr($attributes['src'], 1));
                    $attributes['width'] = !empty($width) ? $width : 500;
                    $attributes['height'] = !empty($height) ? $height : 500;
                }
            }

            $amp_tag = '<amp-img ';
            foreach ($attributes as $attribute => $val) {
                $amp_tag .= $attribute .'="'. $val .'" ';
            }

            $amp_tag .= 'layout="responsive"';
            $amp_tag .= '>';
            $amp_tag .= '</amp-img>';

            $html = str_replace($img_matches[0][$key], $amp_tag, $html);
        }

        return html_entity_decode($html);
    }

    /**
     * @Route("/tags/{slug}.html",
     *      defaults={"_format"="html"},
     *      name="tags",
     *      requirements={
     *          "slug": "[^\n]+"
     *      }))
     */
    public function tagAction($slug, Request $request)
    {
        $tag = $this->getDoctrine()
            ->getRepository(Tag::class)
            ->findOneBy(
                array('url' => $slug)
            );

        // Get the list post related to tag
        $posts = $this->getDoctrine()
            ->getRepository(News::class)
            ->createQueryBuilder('n')
            ->innerJoin('n.tags', 't')
            ->where('t.id = :tags_id')
            ->andWhere('n.enable = :enable')
            ->setParameter('tags_id', $tag->getId())
            ->setParameter('enable', 1)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()->getResult();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $posts,
            !empty($request->query->get('page')) ? $request->query->get('page') : 1,
            $this->get('settings_manager')->get('numberRecordOnPage') ?: 10
        );

        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem("home", $this->generateUrl("homepage"));
        $breadcrumbs->addItem('Tags > ' . $tag->getName());

        return $this->render('news/tags.html.twig', [
            'tag' => $tag,
            'pagination' => $pagination
        ]);
    }

    /**
     * Render list recent news
     * @return News
     */
    public function recentNewsAction()
    {
        $posts = $this->getDoctrine()
            ->getRepository(News::class)
            ->findBy(
                array('postType' => 'post', 'enable' => 1),
                array('createdAt' => 'DESC'),
                10
            );

        return $this->render('news/recent.html.twig', [
            'posts' => $posts,
        ]);
    }

    /**
     * Render list hot news
     * @return News
     */
    public function hotNewsAction()
    {
        $posts = $this->getDoctrine()
            ->getRepository(News::class)
            ->findBy(
                array('postType' => 'post', 'enable' => 1),
                array('viewCounts' => 'DESC'),
                15
            );

        return $this->render('news/hot.html.twig', [
            'posts' => $posts,
        ]);
    }

    /**
     * Render list news by category
     * @return News
     */
    public function listNewsByCategoryAction($categoryId, $description = null)
    {
        $category = $this->getDoctrine()
            ->getRepository(NewsCategory::class)
            ->find($categoryId);

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
            ->setMaxResults( 10 )
            ->orderBy('n.viewCounts', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('news/listByCategory.html.twig', [
            'posts' => $posts,
            'category' => $category,
            'description' => $description
        ]);
    }

    /**
     * @Route("/rating", name="rating")
     * 
     * @return JSON
     */
    public function ratingAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $rating = new Rating();
        $rating->setNewsId($request->request->get('newsId'));
        $rating->setRating($request->request->get('rating'));

        $em->persist($rating);

        $em->flush();
        
        return new Response(
            json_encode(
                array(
                    'status'=>'success',
                    'message' => 'Cảm ơn đánh giá của bạn'
                )
            )
        );
    }

    /**
     * @Route("/search", name="news_search")
     * 
     * @return News
     */
    public function handleSearchFormAction(Request $request)
    {
        $page = !empty($request->query->get('page')) ? $request->query->get('page') : 1;
        
        $form = $this->createFormBuilder(null, array(
                'csrf_protection' => false,
            ))
            ->setAction($this->generateUrl('news_search'))
            ->setMethod('POST')
            ->add('q', TextType::class)
            ->add('search', ButtonType::class, array('label' => 'Search'))
            ->getForm();

        $form->handleRequest($request);
        
        if (!$form->isSubmitted() && empty($request->query->get('q'))) {
            return $this->render('news/formSearch.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $q = $form->getData()['q'];
        if (!empty($q)) {
            return $this->redirectToRoute('news_search', array('q' => $q));
        }

        $query = $this->getDoctrine()
            ->getRepository(News::class)
            ->createQueryBuilder('p')
            ->where('p.title LIKE :q')
            ->andWhere('p.enable = :enable')
            ->andWhere('p.postType = :postType')
            ->setParameter('q', '%'.$request->query->get('q').'%')
            ->setParameter('enable', 1)
            ->setParameter('postType', 'post')
            ->getQuery();
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query->getResult(),
            $page,
            $this->get('settings_manager')->get('numberRecordOnPage') ?: 10
        );

        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem("home", $this->generateUrl("homepage"));
        $breadcrumbs->addItem('search');
        $breadcrumbs->addItem(ucfirst($request->query->get('q')));

        return $this->render('news/search.html.twig', [
            'q' => ucfirst($request->query->get('q')),
            'pagination' => $pagination
        ]);
    }

    /**
     * Render the form comment of news
     * 
     * @return Form
     **/
    private function renderFormComment($post)
    {
        $comment = new Comment();
        $comment->setIp( $this->container->get('request_stack')->getCurrentRequest()->getClientIp() );
        $comment->setNewsId( $post->getId() );

        $form = $this->createFormBuilder($comment)
            ->setAction($this->generateUrl('handle_comment_form'))
            ->add('content', TextareaType::class, array(
                'required' => true,
                'label' => 'label.content',
                'attr' => array('rows' => '7')
            ))
            ->add('author', TextType::class, array('label' => 'label.author'))
            ->add('email', EmailType::class, array('label' => 'label.author_email'))
            ->add('ip', HiddenType::class)
            ->add('news_id', HiddenType::class)
            ->add('comment_id', HiddenType::class)
            ->add('send', ButtonType::class, array('label' => 'label.send'))
            ->getForm();

        return $form;
    }

    /**
     * Handle form comment for post
     * 
     * @return JSON
     **/
    public function handleCommentFormAction(Request $request, \Swift_Mailer $mailer)
    {
        if (!$request->isXmlHttpRequest()) {
            return new Response(
                json_encode(
                    array(
                        'status'=>'error',
                        'message' => 'You can access this only using Ajax!'
                    )
                )
            );
        } else {
            $comment = new Comment();
            
            $form = $this->createFormBuilder($comment)
                ->add('content', TextareaType::class)
                ->add('author', TextType::class)
                ->add('email', EmailType::class)
                ->add('ip', HiddenType::class)
                ->add('news_id', HiddenType::class)
                ->add('comment_id', HiddenType::class)
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($comment);
                $em->flush();

                if (null !== $comment->getId()) {
                    $message = \Swift_Message::newInstance()
                        ->setSubject($this->get('translator')->trans('comment.email.title', ['%siteName%' => $this->get('settings_manager')->get('siteName')]))
                        ->setFrom(['hotro.xaydungminhduy@gmail.com' => $this->get('settings_manager')->get('siteName')])
                        ->setTo($this->get('settings_manager')->get('emailContact'))
                        ->setBody(
                            $this->renderView(
                                'Emails/comment.html.twig',
                                array(
                                    'name' => $request->request->get('form')['author'],
                                    'body' => $request->request->get('form')['content']
                                )
                            ),
                            'text/html'
                        )
                    ;

                    $mailer->send($message);
    
                    return new Response(
                        json_encode(
                            array(
                                'status'=>'success',
                                'message' => '<div class="alert alert-success" role="alert">'.$this->get('translator')->trans('comment.thank_for_your_comment').'</div>'
                            )
                        )
                    );
                } else {
                    return new Response(
                        json_encode(
                            array(
                                'status'=>'error',
                                'message' => '<div class="alert alert-warning" role="alert">'.$this->get('translator')->trans('comment.have_a_problem_on_your_request').'</div>'
                            )
                        )
                    );
                }
            } else {
                return new Response(
                    json_encode(
                        array(
                            'status'=>'error',
                            'message' => '<div class="alert alert-warning" role="alert">'.$this->get('translator')->trans('comment.have_a_problem_on_your_request').'</div>'
                        )
                    )
                );
            }
        }
    }

    /**
     * Handle the breadcrumb
     * 
     * @return Breadcrums
     **/
    private function buildBreadcrums($category = null, $post = null, $page = null, $categoryPrimary = null)
    {
        // Init october breadcrum
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        
        // Add home item into first breadcrum.
        $breadcrumbs->addItem("home", $this->generateUrl("homepage"));
        
        // Breadcrum for category page
        if (!empty($category)) {
            if ($category->getParentcat() === 'root') {
                $breadcrumbs->addItem($category->getName(), $this->generateUrl("news_category", array('level1' => $category->getUrl() )));
            } else {
                $breadcrumbs->addItem($category->getParentcat()->getName(), $this->generateUrl("news_category", array('level1' => $category->getParentcat()->getUrl() )));
                $breadcrumbs->addItem($category->getName(), $this->generateUrl("list_category", array('level1' => $category->getParentcat()->getUrl(), 'level2' => $category->getUrl() )));
            }
        }

        // Breadcrum for post page
        if (!empty($post)) {
            $category;

            if (!$categoryPrimary) {
                $categoryPrimary = $post->getCategoryPrimary();
                if ($categoryPrimary > 0 ) {
                    $category = $this->getDoctrine()
                        ->getRepository(NewsCategory::class)
                        ->find($categoryPrimary);
                } else {
                    if (!$post->getCategory()->isEmpty()) {
                        $category = $post->getCategory()[0];
                    }
                }
            } else {
                $category = $this->getDoctrine()
                    ->getRepository(NewsCategory::class)
                    ->find($categoryPrimary);
            }

            if (!empty($category)) {
                if ($category->getParentcat() === 'root') {
                    $breadcrumbs->addItem($category->getName(), $this->generateUrl("news_category", array('level1' => $category->getUrl() )));
                    $breadcrumbs->addItem($post->getTitle(), $this->generateUrl('news_show', array('slug' => $post->getUrl())) );
                } else {
                    $parentCategory = $category->getParentcat();
                    $breadcrumbs->addItem($parentCategory->getName(), $this->generateUrl("news_category", array('level1' => $parentCategory->getUrl() )));
                    $breadcrumbs->addItem($category->getName(), $this->generateUrl("list_category", array('level1' => $parentCategory->getUrl(), 'level2' => $category->getUrl() )));
                    $breadcrumbs->addItem($post->getTitle(), $this->generateUrl('news_show', array('slug' => $post->getUrl())) );
                }
            } else {
                $breadcrumbs->addItem($post->getTitle(), $this->generateUrl('news_show', array('slug' => $post->getUrl())) );
            }
        }

        return $breadcrumbs;
    }

    /**
     * @Route("/chi-phi-xay-dung", name="caculator_cost_construction")
     * 
     */
    public function caculatorCostConstructionAction($type = null, Request $request) {
        $form = $this->createFormBuilder(null, array(
                'csrf_protection' => false,
            ))
            ->setAction($this->generateUrl('caculator_cost_construction'))
            ->setMethod('POST')
            ->add('type', ChoiceType::class, array(
                'choices'  => array(
                    'Nhà phố' => 1,
                    'Biệt thự' => 2,
                    'Nhà cấp 4' => 3,
                ),
                'label' => 'Loại nhà'
            ))
            ->add('method', ChoiceType::class, array(
                'choices'  => array(
                    'Xây phần thô' => 1,
                    'Xây trọn gói' => 2,
                ),
                'label' => 'Hình thức xây dựng'
            ))
            ->add('wide', TextType::class, array(
                'label' => 'Chiều rộng (m)',
                'attr' => array(
                    'placeholder' => 'VD: Nhập 4 hoặc 4.5'
                )
            ))
            ->add('long', TextType::class, array(
                'label' => 'Chiều dài (m)',
                'attr' => array(
                    'placeholder' => 'VD: Nhập 12 hoặc 12.3'
                )
            ))
            ->add('floor', ChoiceType::class, array(
                'choices'  => array(
                    '1 trệt' => 1,
                    '1 trệt 1 lầu' => 2,
                    '1 trệt 2 lầu' => 3,
                    '1 trệt 3 lầu' => 4,
                    '1 trệt 4 lầu' => 5,
                    '1 trệt 5 lầu' => 6,
                    '1 trệt 6 lầu' => 7,
                ),
                'label' => 'Số tầng'
            ))
            ->add('mong', ChoiceType::class, array(
                'choices'  => array(
                    'Móng đài cọc' => 1,
                    'Móng băng' => 2,
                    'Móng đơn' => 3,
                ),
                'label' => 'Móng nhà'
            ))
            ->add('mai', ChoiceType::class, array(
                'choices'  => array(
                    'Mái bằng đúc BTCT' => 1,
                    'Mái lợp tôn lạnh' => 2,
                    'Mái xà gồ thép lợp ngói' => 3,
                    'Mái đúc BTCT lợp ngói' => 4,
                ),
                'label' => 'Mái nhà'
            ))
            ->add('reset', ResetType::class, array(
                'label' => 'Nhập lại'
            ))
            ->add('caculator', SubmitType::class, array(
                'label' => 'Dự toán chi phí'
            ))
            ->getForm();

        $form->handleRequest($request);

        $costs = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $type = $form->get('type')->getData();
            $method = $form->get('method')->getData();
            $long = $form->get('long')->getData();
            $wide = $form->get('wide')->getData();
            $floor = $form->get('floor')->getData() ? $form->get('floor')->getData() : 1;
            $mong = $form->get('mong')->getData();
            $mai = $form->get('mai')->getData();
            $cost = 0;
            $title = '';
            $titleMong = '';
            $areaMong = 0;
            $titleMai = '';
            $areaMai = 0;
            $note = 'Chi phí xây dựng trên chỉ áp dụng đối với diện tích xây dựng 80 m<sup>2</sup>/1sàn trở lên. Áp dụng với các nhà phố thông dụng không có các kiến trúc kết cấu đặc biệt.';

            if (!is_numeric($long) || !is_numeric($wide) || !is_numeric($type) || !is_numeric($method) || !is_numeric($floor) || !is_numeric($mong) || !is_numeric($mai)) {
                $this->addFlash(
                    'error',
                    "Vui lòng nhập đúng dữ liệu"
                );
                return $this->redirectToRoute('caculator_cost_construction');
            }

            $area = $long * $wide;

            if ($type === 1) {
                if ($method === 1) {
                    $cost = 2950000;
                    $title = "Đơn giá nhà phố phần thô";
                } else {
                    $cost = 4600000;
                    $title = "Đơn giá nhà phố trọn gói";
                }
            } elseif ($type === 3) {
                if ($method === 1) {
                    $cost = 2750000;
                    $title = "Đơn giá nhà cấp 4 phần thô";
                } else {
                    $cost = 3900000;
                    $title = "Đơn giá nhà cấp 4 trọn gói";
                }
            } else {
                if ($method === 1) {
                    $cost = 3200000;
                    $title = "Đơn giá biệt thự phần thô";
                } else {
                    $cost = 6000000;
                    $title = "Đơn giá biệt thự trọn gói";
                }
            }

            if ($type !== 3) {
                if ($mong === 1) {
                    $areaMong = $area * 0.5;
                } elseif ($mong === 2) {
                    $areaMong = $area * 0.55;
                } else {
                    $areaMong = $area * 0.3;
                }

                if ($mai === 1) {
                    $areaMai = $area * 0.4;
                } elseif ($mai === 2) {
                    $areaMai = $area * 0.25;
                } elseif ($mai === 3) {
                    $areaMai = $area * 0.7;
                } else {
                    $areaMai = $area * 1;
                }

                $areaTotal = ($area * $floor) + $areaMong + $areaMai;
            } else {
                $areaTotal = $area;
            }

            if ($mong === 1) {
                $titleMong = "Móng đài cọc";
            } elseif ($mong === 2) {
                $titleMong = "Móng băng";
            } else {
                $titleMong = "Móng đơn";
            }

            if ($mai === 1) {
                $titleMai = "Mái bằng đúc BTCT";
            } elseif ($mai === 2) {
                $titleMai = "Mái lợp tôn lạnh";
            } elseif ($mai === 3) {
                $titleMai = "Mái xà gồ thép lợp ngói";
            } else {
                $titleMai = "Mái đúc BTCT lợp ngói";
            }
            
            $costs = (object) array(
                'area' => $area,
                'floor' => $floor,
                'titleMong' => $titleMong,
                'areaMong' => $areaMong,
                'titleMai' => $titleMai,
                'areaMai' => $areaMai,
                'areaTotal' => $areaTotal,
                'cost' => $cost,
                'costTotal' => $cost * $areaTotal,
                'title' => $title,
                'note' => $note
            );
        }

        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem("home", $this->generateUrl("homepage"));
        $breadcrumbs->addItem('Dự toán chi phí xây dựng');

        $post = $this->getDoctrine()
            ->getRepository(News::class)
            ->findOneBy(
                array('url' => 'chi-phi-xay-dung')
            );

        if (!empty($type) && $type === 'page') {
            return $this->render('form/caculatorcost/page.html.twig', [
                'form' => $form->createView()
            ]);
        } elseif (!empty($type) && $type === 'sidebar') {
            return $this->render('form/caculatorcost/sidebar.html.twig', [
                'form' => $form->createView()
            ]);
        } else {
            return $this->render('form/caculatorcost/caculator.html.twig', [
                'form' => $form->createView(),
                'costs' => $costs ? $costs : null,
                'post' => $post
            ]);
        }
    }
}