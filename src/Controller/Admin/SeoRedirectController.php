<?php

namespace App\Controller\Admin;

use App\Entity\SeoRedirect;
use App\Form\SeoRedirectType;
use App\Seo\RedirectManager;
use App\Controller\LegacyController as AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/redirect")
 */
class SeoRedirectController extends AbstractController
{
    /**
     * @Route("/", name="admin_redirect_index", methods={"GET"})
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('CMS_REDIRECT_MANAGE');

        $q = trim((string) $request->query->get('q'));
        $status = $request->query->get('status', '');

        $qb = $this->getDoctrine()->getRepository(SeoRedirect::class)->createQueryBuilder('r');

        if ($q !== '') {
            $qb->andWhere('r.sourcePath LIKE :q OR r.targetPath LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($status === 'enabled') {
            $qb->andWhere('r.enable = :enable')->setParameter('enable', true);
        } elseif ($status === 'disabled') {
            $qb->andWhere('r.enable = :enable')->setParameter('enable', false);
        }

        $qb->orderBy('r.updatedAt', 'DESC');

        $pagination = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            30
        );

        return $this->render('admin/redirect/index.html.twig', [
            'pagination' => $pagination,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
        ]);
    }

    /**
     * @Route("/new", name="admin_redirect_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, RedirectManager $redirectManager)
    {
        $this->denyAccessUnlessGranted('CMS_REDIRECT_MANAGE');

        $redirect = new SeoRedirect();
        $redirect->setEnable(true);
        $redirect->setStatusCode(301);

        $form = $this->createForm(SeoRedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $redirect->setSourcePath($redirectManager->normalizePath($redirect->getSourcePath()));
            $redirect->setTargetPath($redirectManager->normalizePath($redirect->getTargetPath()));

            $em = $this->getDoctrine()->getManager();
            $em->persist($redirect);
            $em->flush();

            $this->addFlash('success', 'Redirect đã được tạo.');

            return $this->redirectToRoute('admin_redirect_index');
        }

        return $this->render('admin/redirect/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_redirect_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, $id, RedirectManager $redirectManager)
    {
        $this->denyAccessUnlessGranted('CMS_REDIRECT_MANAGE');
        $redirect = $this->findRedirect($id);

        $form = $this->createForm(SeoRedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $redirect->setSourcePath($redirectManager->normalizePath($redirect->getSourcePath()));
            $redirect->setTargetPath($redirectManager->normalizePath($redirect->getTargetPath()));

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Redirect đã được cập nhật.');

            return $this->redirectToRoute('admin_redirect_index');
        }

        return $this->render('admin/redirect/edit.html.twig', [
            'redirect' => $redirect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", requirements={"id": "\d+"}, name="admin_redirect_delete", methods={"POST"})
     */
    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('CMS_REDIRECT_MANAGE');
        $redirect = $this->findRedirect($id);

        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_redirect_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($redirect);
        $em->flush();

        $this->addFlash('success', 'Redirect đã được xóa.');

        return $this->redirectToRoute('admin_redirect_index');
    }

    private function findRedirect($id)
    {
        $redirect = $this->getDoctrine()->getRepository(SeoRedirect::class)->find($id);

        if (!$redirect) {
            throw $this->createNotFoundException('Redirect không tồn tại.');
        }

        return $redirect;
    }
}
