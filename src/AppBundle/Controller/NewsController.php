<?php

namespace AppBundle\Controller;

use AppBundle\Analytics\NewsViewTracker;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\News;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Tag;
use AppBundle\Entity\Rating;

use blackknight467\StarRatingBundle\Form\RatingType as RatingType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

use AppBundle\Utils\ConvertImages;

class NewsController extends Controller
{
    private $helper;
    private $convertImages;

    public function __construct(UploaderHelper $helper, ConvertImages $convertImages)
    {
        $this->helper = $helper;
        $this->convertImages = $convertImages;
    }

    /**
     * Render the list posts by the category
     * 
     * @return News
     */
    public function listAction($level1, $level2 = null, $page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $categoryRepository = $em->getRepository(NewsCategory::class);
        $category = $categoryRepository->findOneBy(array('url' => $level1, 'enable' => true));

        if (!$category) {
            throw $this->createNotFoundException("The item does not exist");
        }

        $currentCategory = $category;
        $subCategory = null;
        $listCategories = array();
        $listCategoriesIds = array($category->getId());

        if (!empty($level2)) {
            $subCategory = $categoryRepository->findOneBy(array('url' => $level2, 'enable' => true));

            if (!$subCategory) {
                throw $this->createNotFoundException("The item does not exist");
            }

            if (!is_object($subCategory->getParentcat()) || $subCategory->getParentcat()->getId() != $category->getId()) {
                return $this->redirectToRoute('homepage', [], 301);
            }

            $currentCategory = $subCategory;
            $listCategoriesIds = array($subCategory->getId());
        } else {
            $listCategories = $categoryRepository
                ->createQueryBuilder('c')
                ->where('c.parentcat = :parentcat')
                ->andWhere('c.enable = :enabled')
                ->setParameter('parentcat', $category)
                ->setParameter('enabled', true)
                ->orderBy('c.name', 'ASC')
                ->getQuery()
                ->getResult();

            foreach ($listCategories as $value) {
                $listCategoriesIds[] = $value->getId();
            }
        }

        // Init breadcrum for category page
        $breadcrumbs = $this->buildBreadcrums($currentCategory, null, null);

        $ordering = $this->resolveCategoryOrdering($currentCategory, $category);

        $query = $em->getRepository(News::class)
            ->createQueryBuilder('n')
            ->select('DISTINCT n')
            ->leftJoin('n.category', 't')
            ->where('t.id IN (:listCategoriesIds)')
            ->andWhere('n.status = :status')
            ->andWhere('n.postType = :postType')
            ->setParameter('listCategoriesIds', array_values(array_unique($listCategoriesIds)))
            ->setParameter('status', News::STATUS_PUBLISHED)
            ->setParameter('postType', 'post')
            ->orderBy('n.' . $ordering['field'], $ordering['direction'])
            ->getQuery();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            max(1, (int) $page),
            $this->get('settings_manager')->get('numberRecordOnPage') ?: 10
        );

        return $this->render('news/list.html.twig', [
            'baseUrl' => !empty($level2) ? $this->generateUrl('list_category', array('level1' => $level1, 'level2' => $level2), UrlGeneratorInterface::ABSOLUTE_URL) : $this->generateUrl('news_category', array('level1' => $level1), UrlGeneratorInterface::ABSOLUTE_URL),
            'category' => $currentCategory,
            'listCategories' => count($listCategories) > 0 ? $listCategories : NULL,
            'pagination' => $pagination
        ]);
    }

    private function resolveCategoryOrdering(NewsCategory $currentCategory, NewsCategory $parentCategory)
    {
        $sortBy = $currentCategory->getSortBy() ?: $parentCategory->getSortBy();
        $orderingData = $sortBy ? json_decode($sortBy, true) : null;

        if (!is_array($orderingData) || count($orderingData) === 0) {
            $orderingData = array('createdAt' => 'DESC');
        }

        $field = key($orderingData);
        $direction = strtoupper((string) current($orderingData));
        $allowedFields = array(
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt',
            'publishedAt' => 'publishedAt',
            'ordering' => 'ordering',
            'viewCounts' => 'viewCounts',
            'title' => 'title',
            'name' => 'title',
            'id' => 'id',
        );

        if (!isset($allowedFields[$field])) {
            $field = 'createdAt';
        }

        if (!in_array($direction, array('ASC', 'DESC'), true)) {
            $direction = 'DESC';
        }

        return array(
            'field' => $allowedFields[$field],
            'direction' => $direction,
        );
    }

    /**
     * @Route("{slug}.html",
     *      defaults={"_format"="html"},
     *      name="news_show",
     *      requirements={
     *          "slug": "[^/\.]++"
     *      })
     */
    public function showAction($slug, Request $request, NewsViewTracker $viewTracker)
    {
        $previewToken = $request->query->get('preview_token');
        $em = $this->getDoctrine()->getManager();
        $postRepository = $em->getRepository(News::class);

        if ($previewToken) {
            if (!$this->isValidPreviewToken($previewToken)) {
                throw $this->createNotFoundException("The item does not exist");
            }

            // Secure preview: only accessible with a valid token.
            $post = $postRepository->createQueryBuilder('n')
                ->leftJoin('n.category', 'c')
                ->addSelect('c')
                ->where('n.previewToken = :previewToken')
                ->andWhere('n.url = :slug')
                ->setParameter('previewToken', $previewToken)
                ->setParameter('slug', $slug)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } else {
            // Normal access: only published content.
            $post = $postRepository->createQueryBuilder('n')
                ->leftJoin('n.category', 'c')
                ->addSelect('c')
                ->where('n.url = :slug')
                ->andWhere('n.status = :status')
                ->setParameter('slug', $slug)
                ->setParameter('status', News::STATUS_PUBLISHED)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        if (!$post) {
            throw $this->createNotFoundException("The item does not exist");
        }

        $viewCookie = $viewTracker->track($post, $request);

        $category = $this->resolvePrimaryCategory($post, $request->query->get('cat'));
        $categoryPrimary = $category ? $category->getId() : 0;
        $relatedNews = $category ? $this->findRelatedNews($post, $categoryPrimary, 16) : array();

        // Get the list comment for post
        $comments = $this->findApprovedComments($post);
        $commentThreads = $this->buildCommentThreads($comments);

        // Render form comment for post.
        $form = $this->renderFormComment($post);

        // Render form rating for post.
        $formRating = $this->createFormBuilder(null, array(
            'csrf_protection' => false,
        ))
            ->setAction($this->generateUrl('rating'))
            ->add('rating', RatingType::class)
            ->getForm();

        $rating = $this->getRatingSummary($post);

        // Init breadcrum for the post
        $breadcrumbs = $this->buildBreadcrums(null, $post, null, $categoryPrimary);

        // Filter content to support Lazy Loading
        $contentsLazy = $this->lazyloadContent($post);
        $articleBody = $this->strip_tags_content($contentsLazy);

        $qAs = $post->getQa();
        $imageSize = $this->getPostImageSize($post);
        $template = $post->isPage() ? 'news/page.html.twig' : 'news/show.html.twig';
        $parameters = array(
            'post' => $post,
            'qAs' => !empty($qAs) ? json_decode($qAs) : NULL,
            'contentsLazy' => $contentsLazy,
            'form' => $form->createView(),
            'formRating' => $formRating->createView(),
            'rating' => $rating['display'],
            'ratingPercent' => $rating['percent'],
            'ratingValue' => $rating['value'],
            'ratingCount' => $rating['count'],
            'comments' => $comments,
            'commentThreads' => $commentThreads,
            'imageSize' => $imageSize,
        );

        if (!$post->isPage()) {
            $parameters['articleBody'] = $articleBody;
            $parameters['wordCount'] = str_word_count($articleBody);
            $parameters['relatedNews'] = !empty($relatedNews) ? $relatedNews : NULL;
            $parameters['category'] = $category;
        }

        $response = $this->render($template, $parameters);

        if ($viewCookie) {
            $response->headers->setCookie($viewCookie);
        }

        return $response;
    }

    private function isValidPreviewToken($previewToken)
    {
        return is_string($previewToken) && preg_match('/^[a-f0-9]{32,64}$/i', $previewToken);
    }

    private function resolvePrimaryCategory(News $post, $categoryParam = null)
    {
        $categoryRepository = $this->getDoctrine()->getRepository(NewsCategory::class);

        if ($categoryParam) {
            $category = ctype_digit((string) $categoryParam)
                ? $categoryRepository->find((int) $categoryParam)
                : $categoryRepository->findOneByUrl($categoryParam);

            if ($category) {
                return $category;
            }
        }

        if ($post->getCategoryPrimary() > 0) {
            $category = $categoryRepository->find($post->getCategoryPrimary());

            if ($category) {
                return $category;
            }
        }

        if (!$post->getCategory()->isEmpty()) {
            return $post->getCategory()->first();
        }

        return null;
    }

    private function findRelatedNews(News $post, $categoryPrimary, $limit)
    {
        return $this->getDoctrine()
            ->getRepository(News::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.category', 't')
            ->where('t.id = :newscategory_id')
            ->andWhere('r.id <> :id')
            ->andWhere('r.postType = :postType')
            ->andWhere('r.status = :status')
            ->setParameter('newscategory_id', $categoryPrimary)
            ->setParameter('id', $post->getId())
            ->setParameter('postType', $post->getPostType())
            ->setParameter('status', News::STATUS_PUBLISHED)
            ->setMaxResults($limit)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function findApprovedComments(News $post)
    {
        return $this->getDoctrine()
            ->getRepository(Comment::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.parent', 'parentComment')
            ->addSelect('parentComment')
            ->where('c.news = :news')
            ->andWhere('c.approved = :approved')
            ->setParameter('news', $post)
            ->setParameter('approved', true)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function getRatingSummary(News $post)
    {
        $rating = $this->getDoctrine()->getManager()->createQuery(
            'SELECT AVG(r.rating) as ratingValue, COUNT(r) as ratingCount
            FROM AppBundle:Rating r
            WHERE r.news_id = :news_id'
        )
            ->setParameter('news_id', $post->getId())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        $value = !empty($rating['ratingValue']) ? (float) $rating['ratingValue'] : 0;
        $count = !empty($rating['ratingCount']) ? (int) $rating['ratingCount'] : 0;
        $percent = $value > 0 ? number_format(($value * 100) / 5, 2) : '0';

        return array(
            'display' => $value > 0 ? str_replace('.0', '', number_format($value, 1)) : 0,
            'percent' => str_replace('.00', '', $percent),
            'value' => round($value),
            'count' => $count,
        );
    }

    private function getPostImageSize(News $post)
    {
        $imagePath = $this->helper->asset($post, 'imageFile');

        if (!$imagePath) {
            return false;
        }

        $imagePath = ltrim($imagePath, '/');
        $candidates = array(
            $imagePath,
            $this->get('kernel')->getRootDir() . '/../web/' . $imagePath,
        );

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return @getimagesize($candidate);
            }
        }

        return false;
    }

    private function buildCommentThreads(array $comments)
    {
        $threads = array();
        $rootIndexes = array();

        foreach ($comments as $comment) {
            $parent = $comment->getParent();

            if (!$parent) {
                $rootIndexes[$comment->getId()] = count($threads);
                $threads[] = array(
                    'comment' => $comment,
                    'replies' => array(),
                );
            }
        }

        foreach ($comments as $comment) {
            $parent = $comment->getParent();

            if (!$parent) {
                continue;
            }

            $parentId = $parent->getId();

            if (isset($rootIndexes[$parentId])) {
                $threads[$rootIndexes[$parentId]]['replies'][] = $comment;
            }
        }

        return $threads;
    }

    private function strip_tags_content($string)
    {
        // ----- remove HTML TAGs -----
        $string = preg_replace('/<[^>]*>/', ' ', $string);
        // ----- remove control characters ----- 
        $string = str_replace("\r", '', $string);
        $string = str_replace("\n", ' ', $string);
        $string = str_replace("\t", ' ', $string);
        $string = str_replace("10E3 Đường 30, P. Tân Phong, Quận 7, TP.HCM", 'A45 Đường Số 2, KDC Kim Sơn, P. Tân Phong, Quận 7, TP HCM', $string);
        $string = str_replace("A45 Đường Số 2, KDC Kim Sơn, P. Tân Phong, Quận 7, TP HCM", '95/121 Đường Lê Văn Lương, P. Tân Hưng, TP.HCM', $string);
        // ----- remove multiple spaces -----
        $string = trim(preg_replace('/ {2,}/', ' ', $string));

        return $string;
    }

    private function lazyloadContent($post)
    {
        $content = $post->getContents();
        
        // Return early if no content
        if (empty($content)) {
            return '';
        }

        // Protect lone "<" characters that are not part of HTML tags
        // Match "<" followed by a digit, space, or other non-tag characters
        $placeholder = '___LESS_THAN_PLACEHOLDER___';
        $content = preg_replace('/<(?=[0-9\s\-\+\=\.\,])/', $placeholder, $content);

        $dom = new \DOMDocument();

        // set error level
        $internalErrors = libxml_use_internal_errors(true);

        // Wrap content to preserve structure and handle UTF-8 properly
        $wrappedContent = '<div id="lazyload-wrapper">' . $content . '</div>';
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $wrappedContent,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        // Remove the XML declaration that was added
        foreach ($dom->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $dom->removeChild($item);
            }
        }

        // Restore error level
        libxml_use_internal_errors($internalErrors);

        $imgs = $dom->getElementsByTagName('img');

        foreach ($imgs as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');

            list($width, $height) = @getimagesize(substr($src, 1));

            $src = !is_bool($this->convertImages->webpConvert2($src, '')) ? $this->convertImages->webpConvert2($src, '') : $src;

            $img->setAttribute('src', $src);
            $img->setAttribute('loading', 'lazy');
            $img->setAttribute('alt', !empty($alt) ? $alt : $post->getTitle());
            $img->setAttribute('width', !empty($width) ? ($width > 900 ? 900 : $width) : 500);
            $img->setAttribute('height', !empty($height) ? ($width > 900 ? round(($height * 900) / $width) : $height) : 500);
        }

        $newContent = $dom->saveHTML();
        
        // Remove the wrapper div we added
        $newContent = preg_replace('/<div id="lazyload-wrapper">/', '', $newContent);
        $newContent = preg_replace('/<\/div>$/', '', $newContent);
        
        // Clean up any remaining DOCTYPE, html, head, body tags
        $newContent = preg_replace('/^<!DOCTYPE[^>]*>/i', '', $newContent);
        $newContent = preg_replace('/<\/?html[^>]*>/i', '', $newContent);
        $newContent = preg_replace('/<\/?head[^>]*>/i', '', $newContent);
        $newContent = preg_replace('/<\/?body[^>]*>/i', '', $newContent);
        
        // Restore the "<" characters
        $newContent = str_replace($placeholder, '<', $newContent);
        
        return trim($newContent);
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
                array('url' => $slug, 'status' => 'published')
            );

        if (!$post) {
            throw $this->createNotFoundException("The post does not exist");
        }

        // Update viewCount for post
        $post->setViewCounts($post->getViewCounts() + 1);
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
                ->leftJoin('r.category', 't')
                ->where('t.id = :newscategory_id')
                ->andWhere('r.id <> :id')
                ->andWhere('r.postType = :postType')
                ->andWhere('r.status = :status')
                ->setParameter('newscategory_id', $categoryPrimary)
                ->setParameter('id', $post->getId())
                ->setParameter('postType', $post->getPostType())
                ->setParameter('status', 'published')
                ->setMaxResults(8)
                ->orderBy('r.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Get the list comment for post
        $comments = $this->getDoctrine()
            ->getRepository(Comment::class)
            ->createQueryBuilder('c')
            ->where('c.news = :news')
            ->andWhere('c.approved = :approved')
            ->setParameter('news', $post)
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

        $qAs = $post->getQa();

        return $this->render('amp/amp-theme/index.html.twig', [
            'post' => $post,
            'qAs' => !empty($qAs) ? json_decode($qAs) : NULL,
            'contentsAmp' => $contentsAmp,
            'relatedNews' => !empty($relatedNews) ? $relatedNews : NULL,
            'category' => !empty($category) ? $category : NULL,
            'rating' => !empty($rating['ratingValue']) ? str_replace('.0', '', number_format($rating['ratingValue'], 1)) : 0,
            'ratingPercent' => str_replace('.00', '', number_format(($rating['ratingValue'] * 100) / 5, 2)),
            'ratingValue' => round($rating['ratingValue']),
            'ratingCount' => round($rating['ratingCount']),
            'comments' => $comments
        ]);
    }

    private function amploadContent($post)
    {
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
                if ($attribute == 'src') {
                    $src = !is_bool($this->convertImages->webpConvert2($val, '')) ? '/' . $this->convertImages->webpConvert2($val, '') : $val;
                    $amp_tag .= $attribute . '="' . $src . '" ';
                } elseif ($attribute == 'alt') {
                    $alt = !empty($val) ? $val : $post->getTitle();
                    $amp_tag .= $attribute . '="' . $alt . '" ';
                } else {
                    $amp_tag .= $attribute . '="' . $val . '" ';
                }
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

        if (!$tag) {
            throw $this->createNotFoundException("The item does not exist");
        }

        // Get the list post related to tag
        $posts = $this->getDoctrine()
            ->getRepository(News::class)
            ->createQueryBuilder('n')
            ->leftJoin('n.tags', 't')
            ->where('t.id = :tags_id')
            ->andWhere('n.status = :status')
            ->setParameter('tags_id', $tag->getId())
            ->setParameter('status', 'published')
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()->getResult();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $posts,
            !empty($request->query->get('page')) ? $request->query->get('page') : 1,
            40
        );

        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem("home", $this->generateUrl("homepage"));
        $breadcrumbs->addItem($tag->getName());

        return $this->render('news/tags.html.twig', [
            'baseUrl' => $this->generateUrl('tags', array('slug' => $slug), UrlGeneratorInterface::ABSOLUTE_URL),
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
                array('postType' => 'post', 'status' => 'published'),
                array('createdAt' => 'DESC'),
                15
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
                array('postType' => 'post', 'status' => 'published'),
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
            ->leftJoin('n.category', 't')
            ->where('t.id IN (:listCategoriesIds)')
            ->andWhere('n.status = :status')
            ->setParameter('listCategoriesIds', $listCategoriesIds)
            ->setParameter('status', 'published')
            ->setMaxResults(10)
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
                    'status' => 'success',
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
            ->where('p.title LIKE :q OR p.description LIKE :q')
            ->andWhere('p.status = :status')
            ->andWhere('p.postType = :postType')
            ->setParameter('q', '%' . $request->query->get('q') . '%')
            ->setParameter('status', 'published')
            ->setParameter('postType', 'post')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $paginator = $this->get('knp_paginator');
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
            'baseUrl' => $this->generateUrl('news_search', array('q' => $request->query->get('q')), UrlGeneratorInterface::ABSOLUTE_URL),
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
        $comment->setIp($this->container->get('request_stack')->getCurrentRequest()->getClientIp());
        $comment->setNews($post);

        $form = $this->createFormBuilder($comment, array(
            'csrf_protection' => false,
        ))
            ->setAction($this->generateUrl('handle_comment_form'))
            ->add('content', TextareaType::class, array(
                'required' => true,
                'label' => 'label.content',
                'attr' => array('rows' => '7')
            ))
            ->add('author', TextType::class, array('label' => 'label.author'))
            ->add('phone', TextType::class, array('label' => 'label.phone'))
            ->add('ip', HiddenType::class)
            ->add('news_id', HiddenType::class, array('mapped' => false, 'data' => $post->getId()))
            ->add('comment_id', HiddenType::class, array('mapped' => false, 'required' => false))
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
                        'status' => 'error',
                        'message' => 'You can access this only using Ajax!'
                    )
                )
            );
        } else {
            $comment = new Comment();
            $em = $this->getDoctrine()->getManager();
            $submittedData = (array) $request->request->get('form', array());
            $newsId = isset($submittedData['news_id']) ? (int) $submittedData['news_id'] : 0;
            $parentId = isset($submittedData['comment_id']) ? (int) $submittedData['comment_id'] : 0;
            $news = $em->getRepository(News::class)->find($newsId);

            if (!$news) {
                return new Response(
                    json_encode(
                        array(
                            'status' => 'error',
                            'message' => '<div class="alert alert-warning" role="alert">' . $this->get('translator')->trans('comment.have_a_problem_on_your_request') . '</div>'
                        )
                    )
                );
            }

            $comment->setNews($news);
            $comment->setIp($request->getClientIp());

            $form = $this->createFormBuilder($comment, array(
                'csrf_protection' => false,
            ))
                ->add('content', TextareaType::class)
                ->add('author', TextType::class)
                ->add('phone', TextType::class)
                ->add('ip', HiddenType::class)
                ->add('news_id', HiddenType::class, array('mapped' => false))
                ->add('comment_id', HiddenType::class, array('mapped' => false, 'required' => false))
                ->getForm();

            $form->handleRequest($request);
            $comment->setNews($news);
            $comment->setIp($request->getClientIp());

            if ($form->isSubmitted()) {
                if ($parentId > 0) {
                    $parent = $em->getRepository(Comment::class)->find($parentId);

                    if ($parent && $parent->getNewsId() === $news->getId()) {
                        $comment->setParent($parent);
                    }
                }
            }

            if ($form->isValid()) {
                $em->persist($comment);
                $em->flush();

                if (null !== $comment->getId()) {
                    return new Response(
                        json_encode(
                            array(
                                'status' => 'success',
                                'message' => '<div class="alert alert-success" role="alert">' . $this->get('translator')->trans('comment.thank_for_your_comment') . '</div>'
                            )
                        )
                    );
                } else {
                    return new Response(
                        json_encode(
                            array(
                                'status' => 'error',
                                'message' => '<div class="alert alert-warning" role="alert">' . $this->get('translator')->trans('comment.have_a_problem_on_your_request') . '</div>'
                            )
                        )
                    );
                }
            } else {
                return new Response(
                    json_encode(
                        array(
                            'status' => 'error',
                            'message' => '<div class="alert alert-warning" role="alert">' . $this->get('translator')->trans('comment.have_a_problem_on_your_request') . '</div>'
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
                $breadcrumbs->addItem($category->getName(), $this->generateUrl("news_category", array('level1' => $category->getUrl())));
            } else {
                $breadcrumbs->addItem($category->getParentcat()->getName(), $this->generateUrl("news_category", array('level1' => $category->getParentcat()->getUrl())));
                $breadcrumbs->addItem($category->getName(), $this->generateUrl("list_category", array('level1' => $category->getParentcat()->getUrl(), 'level2' => $category->getUrl())));
            }
        }

        // Breadcrum for post page
        if (!empty($post)) {
            $category;

            if (!$categoryPrimary) {
                $categoryPrimary = $post->getCategoryPrimary();
                if ($categoryPrimary > 0) {
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
                    $breadcrumbs->addItem($category->getName(), $this->generateUrl("news_category", array('level1' => $category->getUrl())));
                    $breadcrumbs->addItem($post->getTitle(), $this->generateUrl('news_show', array('slug' => $post->getUrl())));
                } else {
                    $parentCategory = $category->getParentcat();
                    $breadcrumbs->addItem($parentCategory->getName(), $this->generateUrl("news_category", array('level1' => $parentCategory->getUrl())));
                    $breadcrumbs->addItem($category->getName(), $this->generateUrl("list_category", array('level1' => $parentCategory->getUrl(), 'level2' => $category->getUrl())));
                    $breadcrumbs->addItem($post->getTitle(), $this->generateUrl('news_show', array('slug' => $post->getUrl())));
                }
            } else {
                $breadcrumbs->addItem($post->getTitle(), $this->generateUrl('news_show', array('slug' => $post->getUrl())));
            }
        }

        return $breadcrumbs;
    }

    /**
     * @Route("/chi-phi-xay-dung", name="caculator_cost_construction")
     * 
     */
    public function caculatorCostConstructionAction($type = null, Request $request)
    {
        $form = $this->createFormBuilder(null, array(
            'csrf_protection' => false,
        ))
            ->setAction($this->generateUrl('caculator_cost_construction'))
            ->setMethod('POST')
            ->add('type', ChoiceType::class, array(
                'choices' => array(
                    'Nhà phố' => 1,
                    'Biệt thự' => 2,
                    'Nhà cấp 4' => 3,
                ),
                'label' => 'Loại nhà'
            ))
            ->add('method', ChoiceType::class, array(
                'choices' => array(
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
                'choices' => array(
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
                'choices' => array(
                    'Móng đài cọc' => 1,
                    'Móng băng' => 2,
                    'Móng đơn' => 3,
                ),
                'label' => 'Móng nhà'
            ))
            ->add('mai', ChoiceType::class, array(
                'choices' => array(
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
