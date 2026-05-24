<?php

namespace App\Controller\Admin;

use App\Entity\Banner;
use App\Form\BannerType;

use App\Utils\Slugger;
use App\Controller\LegacyController as AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage banner in the backend.
 *
 * @Route("/admin/banner")
 */

class BannerController extends AbstractController
{
    /**
     * Lists all the banner entities.
     *
     * @Route("/", name="admin_banner_index", methods={"GET"})
     */
    public function indexAction()
    {
        $this->denyAccessUnlessGranted('CMS_BANNER_MANAGE');

        $em = $this->getDoctrine()->getManager();
        $banners = $em->getRepository(Banner::class)->findAll();

        return $this->render('admin/banner/index.html.twig', ['objects' => $banners]);
    }

    /**
     * @Route("/new", name="admin_banner_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, Slugger $slugger)
    {
        $this->denyAccessUnlessGranted('CMS_BANNER_MANAGE');

        $banner = new Banner();

        $form = $this->createForm(BannerType::class, $banner);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($banner);
            $em->flush();

            $this->addFlash('success', 'action.created_successfully');

            return $this->redirectToRoute('admin_banner_index');
        }

        return $this->render('admin/banner/new.html.twig', [
            'object' => $banner,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_banner_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, $id, Slugger $slugger)
    {
        $this->denyAccessUnlessGranted('CMS_BANNER_MANAGE');
        $banner = $this->findBanner($id);

        $form = $this->createForm(BannerType::class, $banner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'action.updated_successfully');

            return $this->redirectToRoute('admin_banner_index');
        }

        return $this->render('admin/banner/edit.html.twig', [
            'object' => $banner,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a banner entity.
     *
     * @Route("/{id}/delete", name="admin_banner_delete", methods={"POST"})
     */
    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('CMS_BANNER_MANAGE');
        $banner = $this->findBanner($id);

        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_banner_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($banner);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_banner_index');
    }

    private function findBanner($id)
    {
        $banner = $this->getDoctrine()->getRepository(Banner::class)->find($id);

        if (!$banner) {
            throw $this->createNotFoundException('Banner không tồn tại.');
        }

        return $banner;
    }
}
