<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\SeoRedirect;
use AppBundle\Form\SeoRedirectType;
use AppBundle\Seo\RedirectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/redirect")
 * @Security("is_granted('CMS_REDIRECT_MANAGE')")
 */
class SeoRedirectController extends Controller
{
    /**
     * @Route("/", name="admin_redirect_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
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
     * @Route("/new", name="admin_redirect_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, RedirectManager $redirectManager)
    {
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
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_redirect_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, SeoRedirect $redirect, RedirectManager $redirectManager)
    {
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
     * @Route("/{id}/delete", requirements={"id": "\d+"}, name="admin_redirect_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, SeoRedirect $redirect)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_redirect_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($redirect);
        $em->flush();

        $this->addFlash('success', 'Redirect đã được xóa.');

        return $this->redirectToRoute('admin_redirect_index');
    }
}
