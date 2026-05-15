<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\NewsCategory;
use AppBundle\Form\NewsCategoryType;
use AppBundle\Media\MediaSelectionManager;
use AppBundle\Seo\RedirectManager;
use AppBundle\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage post category contents in the backend.
 *
 * @Route("/admin/newscategory")
 * @Security("is_granted('CMS_CONTENT_EDIT')")
 */

class NewsCategoryController extends Controller
{
    /**
     * Lists all NewsCategory entities.
     *
     * @Route("/", name="admin_newscategory_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $categories = $em->getRepository(NewsCategory::class)->findAll();

        return $this->render('admin/newscategory/index.html.twig', [
            'objects' => $categories
        ]);
    }

    /**
     * Creates a new NewsCategory entity.
     *
     * @Route("/new", name="admin_newscategory_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, Slugger $slugger, MediaSelectionManager $mediaSelection)
    {
        $category = new NewsCategory();
        $category->setAuthor($this->getUser());

        // See https://symfony.com/doc/current/book/forms.html#submitting-forms-with-multiple-buttons
        $form = $this->createForm(NewsCategoryType::class, $category)
            ->add('saveAndCreateNew', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->applySelectedMedia($form, $category, $mediaSelection);
            } catch (\InvalidArgumentException $exception) {
                $form->addError(new FormError($exception->getMessage()));

                return $this->render('admin/newscategory/new.html.twig', [
                    'category' => $category,
                    'form' => $form->createView(),
                ]);
            } catch (\RuntimeException $exception) {
                $form->addError(new FormError('Không gán được ảnh từ Media Library.'));

                return $this->render('admin/newscategory/new.html.twig', [
                    'category' => $category,
                    'form' => $form->createView(),
                ]);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'action.created_successfully');

            if ($form->get('saveAndCreateNew')->isClicked()) {
                return $this->redirectToRoute('admin_newscategory_new');
            }

            return $this->redirectToRoute('admin_newscategory_edit', array(
                'id' => $category->getId()
            ));
        }

        return $this->render('admin/newscategory/new.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing NewsCategory entity.
     *
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_newscategory_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, NewsCategory $category, Slugger $slugger, RedirectManager $redirectManager, MediaSelectionManager $mediaSelection)
    {
        $oldPublicPath = $this->getCategoryPublicPath($category);
        $form = $this->createForm(NewsCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->applySelectedMedia($form, $category, $mediaSelection);
            } catch (\InvalidArgumentException $exception) {
                $form->addError(new FormError($exception->getMessage()));

                return $this->render('admin/newscategory/edit.html.twig', [
                    'category' => $category,
                    'form' => $form->createView(),
                ]);
            } catch (\RuntimeException $exception) {
                $form->addError(new FormError('Không gán được ảnh từ Media Library.'));

                return $this->render('admin/newscategory/edit.html.twig', [
                    'category' => $category,
                    'form' => $form->createView(),
                ]);
            }

            $newPublicPath = $this->getCategoryPublicPath($category);
            $redirectManager->createOrUpdate($oldPublicPath, $newPublicPath, 301);

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'action.updated_successfully');

            return $this->redirectToRoute('admin_newscategory_edit', array(
                'id' => $category->getId()
            ));
        }

        return $this->render('admin/newscategory/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    private function getCategoryPublicPath(NewsCategory $category)
    {
        if ($category->getParentcat() === 'root') {
            return $this->generateUrl('news_category', ['level1' => $category->getUrl()]);
        }

        return $this->generateUrl('list_category', [
            'level1' => $category->getParentcat()->getUrl(),
            'level2' => $category->getUrl(),
        ]);
    }

    private function applySelectedMedia($form, NewsCategory $category, MediaSelectionManager $mediaSelection)
    {
        $mediaImageId = $form->has('mediaImageId') ? $form->get('mediaImageId')->getData() : null;

        if (!$mediaImageId) {
            return;
        }

        $mediaSelection->applyToNewsCategoryById($category, $mediaImageId);
    }

    /**
     * Deletes a NewsCategory entity.
     *
     * @Route("/{id}/delete", methods={"POST"}, name="admin_newscategory_delete")
     */
    public function deleteAction(Request $request, NewsCategory $category)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_newscategory_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($category);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_newscategory_index');
    }
}
