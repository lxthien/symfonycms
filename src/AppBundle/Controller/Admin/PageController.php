<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\News;
use AppBundle\Form\PageType;
use AppBundle\Media\MediaSelectionManager;
use AppBundle\Media\NewsMediaManager;
use AppBundle\Seo\RedirectManager;
use AppBundle\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage page contents in the backend.
 *
 * @Route("/admin/page")
 * @Security("has_role('ROLE_ADMIN')")
 */

class PageController extends Controller
{
    /**
     * Lists all News entities.
     *
     * @Route("/", name="admin_page_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $q = trim((string) $request->query->get('q'));
        $status = $request->query->get('status', '');

        $qb = $em->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.postType = :postType')
            ->setParameter('postType', 'page');

        if ($q !== '') {
            $qb->andWhere('n.title LIKE :q OR n.url LIKE :q OR n.description LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($status !== '') {
            $qb->andWhere('n.status = :status')->setParameter('status', $status);
        } else {
            // Default to NOT showing trashed items if no status is explicitly requested
            $qb->andWhere('n.status != :trashStatus')->setParameter('trashStatus', News::STATUS_TRASH);
        }

        $qb->orderBy('n.createdAt', 'DESC');

        $pagination = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/page/index.html.twig', [
            'pagination' => $pagination,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Creates a new News entity.
     *
     * @Route("/new", name="admin_page_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, Slugger $slugger, MediaSelectionManager $mediaSelection, NewsMediaManager $newsMediaManager)
    {
        $news = new News();
        $news->setAuthor($this->getUser());
        $news->setPostType('page');
        $news->setPreviewToken(substr(md5(random_bytes(10)), 0, 32));

        $form = $this->createForm(PageType::class, $news)
            ->add('saveAndCreateNew', SubmitType::class);
        $form->get('albumItems')->setData($newsMediaManager->serializeAlbumForForm($news));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news->setUrl($this->normalizeSlug($news->getUrl(), $slugger));

            if ($this->hasDuplicateNewsSlug($news)) {
                $form->get('url')->addError(new FormError('URL này đã được dùng bởi bài viết hoặc page khác.'));

                return $this->render('admin/page/new.html.twig', [
                    'object' => $news,
                    'form' => $form->createView(),
                    'album_json' => $form->get('albumItems')->getData() ?: '[]',
                ]);
            }

            $em = $this->getDoctrine()->getManager();
            $this->applySelectedMedia($form, $news, $mediaSelection);
            $em->persist($news);
            $newsMediaManager->syncAlbumFromJson($news, $form->get('albumItems')->getData());
            $em->flush();

            $this->addFlash('success', 'action.created_successfully');

            if ($form->get('saveAndCreateNew')->isClicked()) {
                return $this->redirectToRoute('admin_page_new');
            }

            return $this->redirectToRoute('admin_page_edit', array(
                'id' => $news->getId()
            ));
        }

        return $this->render('admin/page/new.html.twig', [
            'object' => $news,
            'form' => $form->createView(),
            'album_json' => $form->get('albumItems')->getData() ?: '[]',
        ]);
    }

    /**
     * Displays a form to edit an existing News entity.
     *
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_page_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, News $news, Slugger $slugger, RedirectManager $redirectManager, MediaSelectionManager $mediaSelection, NewsMediaManager $newsMediaManager)
    {
        if (!$news->getPreviewToken()) {
            $news->setPreviewToken(substr(md5(random_bytes(10)), 0, 32));
        }

        $oldPublicPath = $this->generateUrl('news_show', ['slug' => $news->getUrl()]);
        $form = $this->createForm(PageType::class, $news);
        $form->get('albumItems')->setData($newsMediaManager->serializeAlbumForForm($news));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news->setUrl($this->normalizeSlug($news->getUrl(), $slugger));

            if ($this->hasDuplicateNewsSlug($news)) {
                $form->get('url')->addError(new FormError('URL này đã được dùng bởi bài viết hoặc page khác.'));

                return $this->render('admin/page/edit.html.twig', [
                    'object' => $news,
                    'form' => $form->createView(),
                    'album_json' => $form->get('albumItems')->getData() ?: '[]',
                ]);
            }

            $newPublicPath = $this->generateUrl('news_show', ['slug' => $news->getUrl()]);
            $redirectManager->createOrUpdate($oldPublicPath, $newPublicPath, 301);

            $this->applySelectedMedia($form, $news, $mediaSelection);
            $newsMediaManager->syncAlbumFromJson($news, $form->get('albumItems')->getData());
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'action.updated_successfully');

            return $this->redirectToRoute('admin_page_edit', array(
                'id' => $news->getId()
            ));
        }

        return $this->render('admin/page/edit.html.twig', [
            'object' => $news,
            'form' => $form->createView(),
            'album_json' => $form->get('albumItems')->getData() ?: '[]',
        ]);
    }

    private function applySelectedMedia($form, News $news, MediaSelectionManager $mediaSelection)
    {
        $mediaImageId = $form->has('mediaImageId') ? $form->get('mediaImageId')->getData() : null;

        if ($mediaImageId) {
            $mediaSelection->applyToNewsById($news, $mediaImageId);
        }
    }

    /**
     * Deletes a News entity.
     *
     * @Route("/{id}/delete", methods={"POST"}, name="admin_page_delete")
     */
    public function deleteAction(Request $request, $id, News $page)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_page_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($page);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_page_index');
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
     * @Route("/bulk", name="admin_page_bulk")
     * @Method("POST")
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isCsrfTokenValid('bulk_page', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_page_index');
        }

        $action = $request->request->get('bulk_action');
        $ids = array_filter((array) $request->request->get('ids'), 'is_numeric');

        if (!$ids || !in_array($action, ['publish', 'draft', 'trash', 'delete'], true)) {
            $this->addFlash('warning', 'Vui lòng chọn trang và thao tác hợp lệ.');

            return $this->redirectToRoute('admin_page_index', $request->query->all());
        }

        $em = $this->getDoctrine()->getManager();
        $pages = $em->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.id IN (:ids)')
            ->andWhere('n.postType = :postType')
            ->setParameter('ids', $ids)
            ->setParameter('postType', 'page')
            ->getQuery()
            ->getResult();

        foreach ($pages as $page) {
            if ($action === 'delete') {
                $em->remove($page);
            } elseif ($action === 'trash') {
                $page->setStatus(News::STATUS_TRASH);
            } elseif ($action === 'publish') {
                $page->setStatus(News::STATUS_PUBLISHED);
            } elseif ($action === 'draft') {
                $page->setStatus(News::STATUS_DRAFT);
            }
        }

        $em->flush();
        $this->addFlash('success', 'Đã xử lý ' . count($pages) . ' trang.');

        return $this->redirectToRoute('admin_page_index', $request->query->all());
    }
}
