<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Form\TagType;
use App\Seo\RedirectManager;
use App\Utils\Slugger;
use App\Controller\LegacyController as AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage tag contents in the backend.
 *
 * @Route("/admin/tag")
 */

class TagController extends AbstractController
{
    /**
     * Lists all Tag entities.
     *
     * @Route("/", name="admin_tag_index", methods={"GET"})
     */
    public function indexAction()
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_EDIT');

        $em = $this->getDoctrine()->getManager();
        $tags = $em->getRepository(Tag::class)->findAll();

        return $this->render('admin/tag/index.html.twig', ['objects' => $tags]);
    }

    /**
     * Displays a form to edit an existing Tag entity.
     *
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_tag_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, $id, Slugger $slugger, RedirectManager $redirectManager)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_EDIT');
        $tag = $this->findTag($id);

        $oldPublicPath = $this->generateUrl('tags', ['slug' => $tag->getUrl()]);
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPublicPath = $this->generateUrl('tags', ['slug' => $tag->getUrl()]);
            $redirectManager->createOrUpdate($oldPublicPath, $newPublicPath, 301);

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'updated_successfully');
            return $this->redirectToRoute('admin_tag_index');
        }

        return $this->render('admin/tag/edit.html.twig', [
            'object' => $tag,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a Tag entity.
     *
     * @Route("/{id}/delete", name="admin_tag_delete", methods={"POST"})
     */
    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('CMS_CONTENT_EDIT');
        $tag = $this->findTag($id);

        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_tag_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($tag);
        $em->flush();

        $this->addFlash('success', 'deleted_successfully');

        return $this->redirectToRoute('admin_tag_index');
    }

    private function findTag($id)
    {
        $tag = $this->getDoctrine()->getRepository(Tag::class)->find($id);

        if (!$tag) {
            throw $this->createNotFoundException('Tag không tồn tại.');
        }

        return $tag;
    }
}
