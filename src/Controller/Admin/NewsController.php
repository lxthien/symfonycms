<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;

use App\Category\NewsCategoryTreeBuilder;
use App\Entity\ContentRevision;
use App\Entity\NewsCategory;
use App\Entity\News;
use App\Entity\Rating;
use App\Entity\SeoRedirect;
use App\Form\NewsCategoryType;
use App\Form\NewsType;
use App\InternalLink\InternalLinkSuggestionManager;
use App\Media\MediaSelectionManager;
use App\Media\NewsMediaManager;
use App\Seo\RedirectManager;
use App\Revision\RevisionManager;
use App\Utils\Slugger;
use App\Controller\LegacyController as AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage post contents in the backend.
 *
 * @Route("/admin/news")
 */

class NewsController extends AbstractController
{
    /**
     * Lists all News entities.
     *
     * @Route("/", name="admin_news_index", methods={"GET"})
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_VIEW');

        $em = $this->getDoctrine()->getManager();
        $q = trim((string) $request->query->get('q'));
        $status = $request->query->get('status', '');
        $categoryId = $request->query->get('category', '');

        $qb = $em->getRepository(News::class)->createQueryBuilder('n')
            ->leftJoin('n.category', 'c')
            ->addSelect('c')
            ->where('n.postType = :postType')
            ->setParameter('postType', 'post');

        if ($q !== '') {
            $qb->andWhere('n.title LIKE :q OR n.url LIKE :q OR n.description LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($status !== '') {
            $qb->andWhere('n.status = :status')->setParameter('status', $status);
        }

        if ($categoryId !== '') {
            $qb->andWhere('c.id = :categoryId')->setParameter('categoryId', $categoryId);
        }

        $qb->orderBy('n.createdAt', 'DESC');

        $pagination = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        $categories = $em->getRepository(NewsCategory::class)->findBy([], ['name' => 'ASC']);

        return $this->render('admin/news/index.html.twig', [
            'pagination' => $pagination,
            'categories' => $categories,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'category' => $categoryId,
            ],
        ]);
    }

    /**
     * Lists all News entities by category.
     *
     * @Route("/list/{categoryId}", name="admin_news_list_by_category", methods={"GET"})
     */
    public function listAction(Request $request, $categoryId)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_VIEW');

        $em = $this->getDoctrine()->getManager();
        $news = $em->getRepository(News::class)->findAllPosts();

        $news = $this->getDoctrine()
                ->getRepository(News::class)
                ->createQueryBuilder('n')
                ->leftJoin('n.category', 't')
                ->where('t.id = :newscategory_id')
                ->setParameter('newscategory_id', $categoryId)
                ->orderBy('n.createdAt', 'DESC')
                ->getQuery()->getResult();

        return $this->render('admin/news/list.html.twig', ['objects' => $news]);
    }

    /**
     * Checks whether a News/Page slug is already used.
     *
     * @Route("/slug/check", name="admin_news_slug_check", methods={"GET"})
     */
    public function checkSlugAction(Request $request, Slugger $slugger)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_VIEW');

        $slug = $this->normalizeSlug($request->query->get('slug'), $slugger);
        $currentId = $request->query->getInt('id', 0);
        $em = $this->getDoctrine()->getManager();
        $duplicate = null;

        if ($slug !== '') {
            $duplicate = $em->getRepository(News::class)->createQueryBuilder('n')
                ->where('n.url = :slug')
                ->andWhere('n.id != :id')
                ->setParameter('slug', $slug)
                ->setParameter('id', $currentId ?: 0)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        $publicPath = $slug !== '' ? $this->generateUrl('news_show', ['slug' => $slug]) : '';
        $redirect = $publicPath !== ''
            ? $em->getRepository(SeoRedirect::class)->findEnabledBySourcePath($publicPath)
            : null;

        return new JsonResponse([
            'slug' => $slug,
            'available' => $slug !== '' && !$duplicate,
            'canonicalUrl' => $publicPath,
            'duplicate' => $duplicate ? [
                'id' => $duplicate->getId(),
                'title' => $duplicate->getTitle(),
                'editUrl' => $duplicate->isPage()
                    ? $this->generateUrl('admin_page_edit', ['id' => $duplicate->getId()])
                    : $this->generateUrl('admin_news_edit', ['id' => $duplicate->getId()]),
            ] : null,
            'redirectConflict' => $redirect ? [
                'targetPath' => $redirect->getTargetPath(),
            ] : null,
        ]);
    }

    /**
     * Searches existing posts/pages for the content block editor.
     *
     * @Route("/content-block/related-search", name="admin_content_block_related_search", methods={"GET"})
     */
    public function relatedSearchAction(Request $request, InternalLinkSuggestionManager $suggestionManager)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_VIEW');

        $q = trim((string) $request->query->get('q'));
        $currentId = $request->query->getInt('currentId', 0);

        if ($request->query->get('mode') === 'suggest') {
            $post = $currentId > 0
                ? $this->getDoctrine()->getRepository(News::class)->find($currentId)
                : null;

            $items = $suggestionManager->suggest($post, [
                'title' => $request->query->get('title', ''),
                'description' => $request->query->get('description', ''),
                'keywords' => $request->query->get('keywords', ''),
                'categoryIds' => $request->query->get('categoryIds', []),
                'tagNames' => $request->query->get('tagNames', []),
                'primaryCategoryId' => $request->query->getInt('primaryCategoryId', 0),
            ], 10);

            return new JsonResponse([
                'items' => $items,
                'generatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            ]);
        }

        return new JsonResponse([
            'items' => $suggestionManager->search($q, $currentId, 12),
        ]);
    }

    /**
     * Creates a new News entity.
     *
     * @Route("/new", name="admin_news_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, Slugger $slugger, RevisionManager $revisionManager, MediaSelectionManager $mediaSelection, NewsMediaManager $newsMediaManager, NewsCategoryTreeBuilder $categoryTreeBuilder)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_CREATE');

        $news = new News();
        $news->setAuthor($this->getUser());
        $news->generatePreviewToken();

        $form = $this->createForm(NewsType::class, $news)
            ->add('saveAndCreateNew', SubmitType::class);
        $form->get('albumItems')->setData($newsMediaManager->serializeAlbumForForm($news));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news->setUrl($this->normalizeSlug($news->getUrl(), $slugger));

            if ($this->hasDuplicateNewsSlug($news)) {
                $form->get('url')->addError(new FormError('URL này đã được dùng bởi bài viết hoặc page khác.'));

                return $this->render('admin/news/new.html.twig', [
                    'news' => $news,
                    'form' => $form->createView(),
                    'album_json' => $form->get('albumItems')->getData() ?: '[]',
                    'category_tree' => $this->getCategoryTree($categoryTreeBuilder),
                ]);
            }

            try {
                $em = $this->getDoctrine()->getManager();
                $this->applySelectedMedia($form, $news, $mediaSelection);
                $em->persist($news);
                $newsMediaManager->syncAlbumFromJson($news, $form->get('albumItems')->getData());
                $em->flush();

                // Update Ordering for post
                $news->setOrdering( $news->getId() );
                $revision = $revisionManager->createFromNews($news, $this->getUser(), 'manual');
                $revision->setDiffSummary('Tạo bài viết');
                $em->persist($revision);
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'action.created_successfully');

                if ($form->get('saveAndCreateNew')->isClicked()) {
                    return $this->redirectToRoute('admin_news_new');
                }

                return $this->redirectToRoute('admin_news_edit', array(
                    'id' => $news->getId()
                ));
            } catch (\DBALException $e) {
                $message = sprintf('DBALException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\PDOException $e) {
                $message = sprintf('PDOException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\ORMException $e) {
                $message = sprintf('ORMException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\Exception $e) {
                $message = sprintf('Exception [%i]: %s', $e->getCode(), $e->getMessage());
            }

            $this->addFlash('error', $message);

            return $this->render('admin/news/new.html.twig', [
                'news' => $news,
                'form' => $form->createView(),
                'album_json' => $form->get('albumItems')->getData() ?: '[]',
                'category_tree' => $this->getCategoryTree($categoryTreeBuilder),
            ]);
        }

        return $this->render('admin/news/new.html.twig', [
            'news' => $news,
            'form' => $form->createView(),
            'album_json' => $form->get('albumItems')->getData() ?: '[]',
            'category_tree' => $this->getCategoryTree($categoryTreeBuilder),
        ]);
    }

    /**
     * Displays a form to edit an existing News entity.
     *
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_news_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, $id, Slugger $slugger, RevisionManager $revisionManager, RedirectManager $redirectManager, MediaSelectionManager $mediaSelection, NewsMediaManager $newsMediaManager, NewsCategoryTreeBuilder $categoryTreeBuilder)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_EDIT');
        $news = $this->findPost($id);

        $originalData = $revisionManager->extractNewsData($news);
        $oldPublicPath = $this->generateUrl('news_show', ['slug' => $news->getUrl()]);
        $form = $this->createForm(NewsType::class, $news);
        $form->get('albumItems')->setData($newsMediaManager->serializeAlbumForForm($news));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news->setUrl($this->normalizeSlug($news->getUrl(), $slugger));

            if ($this->hasDuplicateNewsSlug($news)) {
                $form->get('url')->addError(new FormError('URL này đã được dùng bởi bài viết hoặc page khác.'));

                return $this->render('admin/news/edit.html.twig', [
                    'news' => $news,
                    'form' => $form->createView(),
                    'revisions' => $this->getDoctrine()->getRepository(ContentRevision::class)->findRecentByNews($news),
                    'latestAutosave' => $this->getDoctrine()->getRepository(ContentRevision::class)->findLatestAutosaveByNews($news),
                    'revisionDiffs' => $this->getRevisionDiffs($news, $revisionManager),
                    'album_json' => $form->get('albumItems')->getData() ?: '[]',
                    'category_tree' => $this->getCategoryTree($categoryTreeBuilder),
                ]);
            }

            try {
                $em = $this->getDoctrine()->getManager();
                $this->applySelectedMedia($form, $news, $mediaSelection);
                $newsMediaManager->syncAlbumFromJson($news, $form->get('albumItems')->getData());
                $currentData = $revisionManager->extractNewsData($news);

                if ($revisionManager->hasDataChanged($originalData, $currentData)) {
                    $revision = $revisionManager->createFromDataWithBaseline(
                        $news,
                        $currentData,
                        $originalData,
                        $this->getUser(),
                        'manual'
                    );

                    $em->persist($revision);
                }

                $newPublicPath = $this->generateUrl('news_show', ['slug' => $news->getUrl()]);
                $redirectManager->createOrUpdate($oldPublicPath, $newPublicPath, 301);

                $em->flush();
                $this->addFlash('success', 'action.updated_successfully');

                return $this->redirectToRoute('admin_news_edit', array(
                    'id' => $news->getId()
                ));
            } catch (\DBALException $e) {
                $message = sprintf('DBALException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\PDOException $e) {
                $message = sprintf('PDOException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\ORMException $e) {
                $message = sprintf('ORMException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\Exception $e) {
                $message = sprintf('Exception [%i]: %s', $e->getCode(), $e->getMessage());
            }

            $this->addFlash('error', $message);

            return $this->render('admin/news/edit.html.twig', [
                'news' => $news,
                'form' => $form->createView(),
                'revisions' => $this->getDoctrine()->getRepository(ContentRevision::class)->findRecentByNews($news),
                    'latestAutosave' => $this->getDoctrine()->getRepository(ContentRevision::class)->findLatestAutosaveByNews($news),
                    'revisionDiffs' => $this->getRevisionDiffs($news, $revisionManager),
                    'album_json' => $form->get('albumItems')->getData() ?: '[]',
                    'category_tree' => $this->getCategoryTree($categoryTreeBuilder),
                ]);
            }

        return $this->render('admin/news/edit.html.twig', [
            'news' => $news,
            'form' => $form->createView(),
            'revisions' => $this->getDoctrine()->getRepository(ContentRevision::class)->findRecentByNews($news),
            'latestAutosave' => $this->getDoctrine()->getRepository(ContentRevision::class)->findLatestAutosaveByNews($news),
            'revisionDiffs' => $this->getRevisionDiffs($news, $revisionManager),
            'album_json' => $form->get('albumItems')->getData() ?: '[]',
            'category_tree' => $this->getCategoryTree($categoryTreeBuilder),
        ]);
    }

    private function getCategoryTree(NewsCategoryTreeBuilder $categoryTreeBuilder)
    {
        $categories = $this->getDoctrine()->getRepository(NewsCategory::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.parentcat', 'parent')
            ->addSelect('parent')
            ->getQuery()
            ->getResult();

        return $categoryTreeBuilder->flatten($categories);
    }

    private function applySelectedMedia($form, News $news, MediaSelectionManager $mediaSelection)
    {
        $mediaImageId = $form->has('mediaImageId') ? $form->get('mediaImageId')->getData() : null;

        if ($mediaImageId) {
            $mediaSelection->applyToNewsById($news, $mediaImageId);
        }
    }

    /**
     * Stores an autosave revision without publishing the post.
     *
     * @Route("/{id}/autosave", requirements={"id": "\d+"}, name="admin_news_autosave", methods={"POST"})
     */
    public function autosaveAction(Request $request, $id, RevisionManager $revisionManager)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_EDIT');
        $news = $this->findPost($id);

        if (!$this->isCsrfTokenValid('autosave_news_' . $news->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['status' => 'error', 'message' => 'Token không hợp lệ'], 400);
        }

        $data = $this->getRevisionRequestData($request);
        $repository = $this->getDoctrine()->getRepository(ContentRevision::class);
        $latestAutosave = $repository->findLatestAutosaveByNews($news);
        $currentData = $revisionManager->extractNewsData($news);

        if (!$revisionManager->hasDataChanged($currentData, $data)) {
            return new JsonResponse([
                'status' => 'skipped',
                'message' => 'Không có thay đổi so với bản đã lưu',
            ]);
        }

        if ($latestAutosave && !$revisionManager->hasChanged($latestAutosave, $data)) {
            return new JsonResponse([
                'status' => 'skipped',
                'message' => 'Không có thay đổi mới',
                'savedAt' => $latestAutosave->getCreatedAt()->format('H:i:s d/m/Y'),
            ]);
        }

        $revision = $revisionManager->createFromRequestData($news, $data, $this->getUser(), 'autosave');
        $em = $this->getDoctrine()->getManager();
        $em->persist($revision);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Đã autosave',
            'revisionId' => $revision->getId(),
            'savedAt' => $revision->getCreatedAt()->format('H:i:s d/m/Y'),
        ]);
    }

    /**
     * Compares a revision with the current post.
     *
     * @Route("/{id}/revision/{revisionId}/compare", requirements={"id": "\d+", "revisionId": "\d+"}, name="admin_news_revision_compare", methods={"GET"})
     */
    public function compareRevisionAction($id, $revisionId, RevisionManager $revisionManager)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_VIEW');
        $news = $this->findPost($id);

        $revision = $this->findRevisionForNews($news, $revisionId);
        $currentRevision = $revisionManager->createFromNews($news, $this->getUser(), 'current');
        $diff = $revisionManager->buildDiff($revision, $currentRevision);

        return $this->render('admin/news/revision_compare.html.twig', [
            'news' => $news,
            'revision' => $revision,
            'diff' => $diff,
        ]);
    }

    /**
     * Restores content fields from a revision.
     *
     * @Route("/{id}/revision/{revisionId}/restore", requirements={"id": "\d+", "revisionId": "\d+"}, name="admin_news_revision_restore", methods={"POST"})
     */
    public function restoreRevisionAction(Request $request, $id, $revisionId, RevisionManager $revisionManager)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_EDIT');
        $news = $this->findPost($id);

        if (!$this->isCsrfTokenValid('restore_revision_' . $revisionId, $request->request->get('token'))) {
            return $this->redirectToRoute('admin_news_edit', ['id' => $news->getId()]);
        }

        $revision = $this->findRevisionForNews($news, $revisionId);
        $originalData = $revisionManager->extractNewsData($news);
        $revisionManager->applyRevision($news, $revision);

        $restoreRevision = $revisionManager->createFromDataWithBaseline(
            $news,
            $revisionManager->extractNewsData($news),
            $originalData,
            $this->getUser(),
            'restore'
        );
        $restoreRevision->setDiffSummary('Khôi phục từ revision #' . $revision->getId());

        $em = $this->getDoctrine()->getManager();
        $em->persist($restoreRevision);
        $em->flush();

        $this->addFlash('success', 'Đã khôi phục nội dung từ revision.');

        return $this->redirectToRoute('admin_news_edit', ['id' => $news->getId()]);
    }

    private function getRevisionRequestData(Request $request)
    {
        return [
            'title' => $request->request->get('title'),
            'url' => $request->request->get('url'),
            'description' => $request->request->get('description'),
            'contents' => $request->request->get('contents'),
            'pageTitle' => $request->request->get('pageTitle'),
            'pageDescription' => $request->request->get('pageDescription'),
            'pageKeyword' => $request->request->get('pageKeyword'),
            'qa' => $request->request->get('qa'),
            'template' => $request->request->get('template'),
        ];
    }

    private function findRevisionForNews(News $news, $revisionId)
    {
        $revision = $this->getDoctrine()->getRepository(ContentRevision::class)->find($revisionId);

        if (!$revision || $revision->getNews()->getId() !== $news->getId()) {
            throw $this->createNotFoundException('Revision không tồn tại.');
        }

        return $revision;
    }

    private function getRevisionDiffs(News $news, RevisionManager $revisionManager)
    {
        $revisions = $this->getDoctrine()->getRepository(ContentRevision::class)->findRecentByNews($news);
        $diffs = [];

        foreach ($revisions as $revision) {
            $diffs[$revision->getId()] = $revisionManager->buildDiffWithNews($revision, $news);
        }

        return $diffs;
    }

    private function normalizeSlug($slug, Slugger $slugger)
    {
        $slug = $slugger->slugifyVn((string) $slug);
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);

        return trim($slug, '-');
    }

    private function hasDuplicateNewsSlug(News $news)
    {
        if (!$news->getUrl()) {
            return false;
        }

        $duplicate = $this->getDoctrine()->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.url = :slug')
            ->andWhere('n.id != :id')
            ->setParameter('slug', $news->getUrl())
            ->setParameter('id', $news->getId() ?: 0)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $duplicate !== null;
    }

    /**
     * Deletes a News entity.
     *
     * @Route("/{id}/delete", methods={"POST"}, name="admin_news_delete")
     */
    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_DELETE');
        $news = $this->findPost($id);

        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_news_index');
        }

        $news->getTags()->clear();

        $em = $this->getDoctrine()->getManager();
        $em->remove($news);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_news_index');
    }

    /**
     * @Route("/disable", name="admin_news_disable", methods={"POST"})
     */
    public function disableAction(Request $request)
    {
        $this->denyAccessUnlessCanPublishOrDelete();

        $em = $this->getDoctrine()->getManager();

        $news = $this->getDoctrine()->getRepository(News::class)->find($request->request->get('newsId'));

        if ($news) {
            $requestedStatus = $request->request->get('status');
            if ($requestedStatus && in_array($requestedStatus, News::VALID_STATUSES, true)) {
                $news->setStatus($requestedStatus);
            }
        }

        $em->persist($news);
        $em->flush();

        return new Response(
            json_encode(
                array(
                    'status'=>'success',
                    'message' => 'Thao tác thành công'
                )
            )
        );
    }

    /**
     * @Route("/bulk", name="admin_news_bulk", methods={"POST"})
     */
    public function bulkAction(Request $request)
    {
        $this->denyAccessUnlessCanPublishOrDelete();

        if (!$this->isCsrfTokenValid('bulk_news', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_news_index');
        }

        $action = $request->request->get('bulk_action');
        $ids = array_filter((array) $request->request->get('ids'), 'is_numeric');

        if (!$ids || !in_array($action, ['publish', 'unpublish', 'archive', 'trash', 'delete'], true)) {
            $this->addFlash('warning', 'Vui lòng chọn bài viết và thao tác hợp lệ.');

            return $this->redirectToRoute('admin_news_index', $request->query->all());
        }

        $em = $this->getDoctrine()->getManager();
        $posts = $em->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.id IN (:ids)')
            ->andWhere('n.postType = :postType')
            ->setParameter('ids', $ids)
            ->setParameter('postType', 'post')
            ->getQuery()
            ->getResult();

        $statusMap = [
            'publish' => News::STATUS_PUBLISHED,
            'unpublish' => News::STATUS_DRAFT,
            'archive' => News::STATUS_ARCHIVED,
            'trash' => News::STATUS_TRASH,
        ];

        foreach ($posts as $post) {
            if ($action === 'delete') {
                $post->getTags()->clear();
                $em->remove($post);
            } elseif (isset($statusMap[$action])) {
                $post->setStatus($statusMap[$action]);
            }
        }

        $em->flush();
        $this->addFlash('success', 'Đã xử lý ' . count($posts) . ' bài viết.');

        return $this->redirectToRoute('admin_news_index', $request->query->all());
    }

    private function denyAccessUnlessCanPublishOrDelete()
    {
        if (!$this->isGranted('CMS_CONTENT_PUBLISH') && !$this->isGranted('CMS_CONTENT_DELETE')) {
            throw $this->createAccessDeniedException();
        }
    }

    private function findPost($id)
    {
        $post = $this->getDoctrine()->getRepository(News::class)->find($id);

        if (!$post || !$post->isPost()) {
            throw $this->createNotFoundException('Bài viết không tồn tại.');
        }

        return $post;
    }
}
