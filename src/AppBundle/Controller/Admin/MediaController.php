<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Media;
use AppBundle\Form\MediaType;
use AppBundle\Form\MediaUploadType;
use AppBundle\Media\MediaLibraryManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/admin/media")
 * @Security("has_role('ROLE_ADMIN')")
 */
class MediaController extends Controller
{
    /**
     * @Route("/", name="admin_media_index")
     * @Method("GET")
     */
    public function indexAction(Request $request, MediaLibraryManager $mediaLibrary)
    {
        $uploadForm = $this->createForm(MediaUploadType::class, null, [
            'action' => $this->generateUrl('admin_media_upload'),
        ]);

        $filters = $mediaLibrary->normalizeFilters($request->query->all());

        $pagination = $this->get('knp_paginator')->paginate(
            $mediaLibrary->createFilteredQueryBuilder($filters)->getQuery(),
            $request->query->getInt('page', 1),
            24
        );

        return $this->render('admin/media/index.html.twig', [
            'pagination' => $pagination,
            'folders' => $mediaLibrary->getFolders(),
            'tags' => $mediaLibrary->getTags(),
            'filters' => $filters,
            'upload_form' => $uploadForm->createView(),
        ]);
    }

    /**
     * @Route("/picker", name="admin_media_picker")
     * @Method("GET")
     */
    public function pickerAction(Request $request, MediaLibraryManager $mediaLibrary)
    {
        $uploadForm = $this->createForm(MediaUploadType::class, null, [
            'action' => $this->generateUrl('admin_media_upload'),
        ]);
        $filters = $mediaLibrary->normalizeFilters($request->query->all());
        $filters['type'] = $filters['type'] ?: 'image';

        $pagination = $this->get('knp_paginator')->paginate(
            $mediaLibrary->createFilteredQueryBuilder($filters)->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/media/_picker.html.twig', [
            'pagination' => $pagination,
            'folders' => $mediaLibrary->getFolders(),
            'tags' => $mediaLibrary->getTags(),
            'filters' => $filters,
            'upload_form' => $uploadForm->createView(),
        ]);
    }

    /**
     * @Route("/upload", name="admin_media_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadAction(Request $request, MediaLibraryManager $mediaLibrary)
    {
        $form = $this->createForm(MediaUploadType::class, null, [
            'action' => $this->generateUrl('admin_media_upload'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $mediaLibrary->uploadBatch(
                (array) $form->get('files')->getData(),
                $form->get('folder')->getData(),
                $form->get('newFolder')->getData(),
                $form->get('tagsText')->getData(),
                $this->getUser()
            );
            $uploaded = $result['uploaded'];
            $errors = $result['errors'];

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => $uploaded > 0,
                    'uploaded' => $uploaded,
                    'errors' => $errors,
                    'message' => $uploaded > 0
                        ? 'Đã upload ' . $uploaded . ' file media.'
                        : 'Không upload được file nào.',
                ], $uploaded > 0 ? 200 : 400);
            }

            if ($uploaded > 0 && count($errors) === 0) {
                $this->addFlash('success', 'Đã upload ' . $uploaded . ' file media.');

                return $this->redirectToRoute('admin_media_index');
            }

            if ($uploaded > 0) {
                $this->addFlash('warning', 'Một số file đã upload, một số file bị bỏ qua.');

                return $this->redirectToRoute('admin_media_index');
            }

            if (count($errors) > 0) {
                $this->addFlash('danger', implode(' ', $errors));
            }
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'uploaded' => 0,
                'errors' => $this->getFormErrors($form),
                'message' => 'Dữ liệu upload chưa hợp lệ.',
            ], 400);
        }

        return $this->render('admin/media/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_media_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Media $media, MediaLibraryManager $mediaLibrary)
    {
        $form = $this->createForm(MediaType::class, $media, [
            'action' => $this->generateUrl('admin_media_edit', ['id' => $media->getId()]),
        ]);
        $form->get('tagsText')->setData($mediaLibrary->getTagsText($media));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mediaLibrary->updateMetadata($media, $form->get('newFolder')->getData(), $form->get('tagsText')->getData());

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Media đã được cập nhật.',
                ]);
            }

            $this->addFlash('success', 'Media đã được cập nhật.');

            return $this->redirectToRoute('admin_media_index');
        }

        if ($request->isXmlHttpRequest() || $request->query->get('modal')) {
            $statusCode = $form->isSubmitted() && !$form->isValid() ? 422 : 200;

            return $this->render('admin/media/_edit_modal_content.html.twig', [
                'media' => $media,
                'form' => $form->createView(),
            ], new Response('', $statusCode));
        }

        return $this->render('admin/media/edit.html.twig', [
            'media' => $media,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", requirements={"id": "\d+"}, name="admin_media_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, Media $media, MediaLibraryManager $mediaLibrary)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_media_index');
        }

        $mediaLibrary->delete($media);

        $this->addFlash('success', 'Media đã được xóa.');

        return $this->redirectToRoute('admin_media_index');
    }
    private function getFormErrors($form)
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}
