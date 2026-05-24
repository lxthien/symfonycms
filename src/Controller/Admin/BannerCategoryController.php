<?php

namespace App\Controller\Admin;

use App\Entity\BannerCategory;
use App\Form\BannerCategoryType;

use App\Utils\Slugger;
use App\Controller\LegacyController as AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage the banner category in the backend.
 *
 * @Route("/admin/bannercategory")
 */

class BannerCategoryController extends AbstractController
{
    /**
     * Lists all the banner categories entities.
     *
     * @Route("/", name="admin_bannercategory_index", methods={"GET"})
     */
    public function indexAction()
    {
        $this->denyAccessUnlessGranted('CMS_BANNER_MANAGE');

        $em = $this->getDoctrine()->getManager();
        $bannercategories = $em->getRepository(BannerCategory::class)->findAll();

        return $this->render('admin/bannercategory/index.html.twig', ['objects' => $bannercategories]);
    }

    /**
     * @Route("/new", name="admin_bannercategory_new", methods={"GET", "POST"})
     */
    public function bannerCategoryNewAction(Request $request, Slugger $slugger)
    {
        $this->denyAccessUnlessGranted('CMS_BANNER_MANAGE');

        $bannerCategory = new BannerCategory();

        $form = $this->createForm(BannerCategoryType::class, $bannerCategory);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($bannerCategory);
            $em->flush();

            $this->addFlash('success', 'action.created_successfully');

            return $this->redirectToRoute('admin_bannercategory_index');
        }

        return $this->render('admin/bannercategory/new.html.twig', [
            'object' => $bannerCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_bannercategory_edit", methods={"GET", "POST"})
     */
    public function bannerCategoryEditAction(Request $request, $id, Slugger $slugger)
    {
        $this->denyAccessUnlessGranted('CMS_BANNER_MANAGE');
        $bannerCategory = $this->findBannerCategory($id);

        $form = $this->createForm(BannerCategoryType::class, $bannerCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'action.updated_successfully');

            return $this->redirectToRoute('admin_bannercategory_index');
        }

        return $this->render('admin/bannercategory/edit.html.twig', [
            'object' => $bannerCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a banner category entity.
     *
     * @Route("/{id}/delete", name="admin_bannercategory_delete", methods={"POST"})
     */
    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('CMS_BANNER_MANAGE');
        $bannerCategory = $this->findBannerCategory($id);

        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_bannercategory_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($bannerCategory);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_bannercategory_index');
    }

    private function findBannerCategory($id)
    {
        $bannerCategory = $this->getDoctrine()->getRepository(BannerCategory::class)->find($id);

        if (!$bannerCategory) {
            throw $this->createNotFoundException('Danh mục banner không tồn tại.');
        }

        return $bannerCategory;
    }
}
