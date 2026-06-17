<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\BannerCategory;
use AppBundle\Form\BannerCategoryType;

use AppBundle\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage the banner category in the backend.
 * @Route("/admin/bannercategory")
 * @Security("is_granted('CMS_BANNER_MANAGE')")
 */

class BannerCategoryController extends Controller
{
    /**
     * Lists all the banner categories entities.
     *
     * @Route("/", name="admin_bannercategory_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $bannercategories = $em->getRepository(BannerCategory::class)->findAll();

        return $this->render('admin/bannercategory/index.html.twig', ['objects' => $bannercategories]);
    }

    /**
     * @Route("/new", name="admin_bannercategory_new")
     * @Method({"GET", "POST"})
     */
    public function bannerCategoryNewAction(Request $request, Slugger $slugger)
    {
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
     * @Route("/{id}/edit", name="admin_bannercategory_edit")
     * @Method({"GET", "POST"})
     */
    public function bannerCategoryEditAction(Request $request, BannerCategory $bannerCategory, Slugger $slugger)
    {
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
     * @Route("/{id}/delete", name="admin_bannercategory_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, BannerCategory $bannerCategory)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_bannercategory_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($bannerCategory);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_bannercategory_index');
    }
}
