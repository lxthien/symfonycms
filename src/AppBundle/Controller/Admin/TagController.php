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

use AppBundle\Entity\Tag;
use AppBundle\Form\TagType;
use AppBundle\Seo\RedirectManager;
use AppBundle\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage tag contents in the backend.
 *
 * @Route("/admin/tag")
 * @Security("is_granted('CMS_CONTENT_EDIT')")
 */

class TagController extends Controller
{
    /**
     * Lists all Tag entities.
     *
     * @Route("/", name="admin_tag_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $tags = $em->getRepository(Tag::class)->findAll();

        return $this->render('admin/tag/index.html.twig', ['objects' => $tags]);
    }

    /**
     * Displays a form to edit an existing Tag entity.
     *
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_tag_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Tag $tag, Slugger $slugger, RedirectManager $redirectManager)
    {
        //$this->denyAccessUnlessGranted('edit', $category, 'Posts can only be edited by their authors.');

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
     * @Route("/{id}/delete", name="admin_tag_delete")
     * @Method("POST")
     * @Security("is_granted('delete', post)")
     *
     * The Security annotation value is an expression (if it evaluates to false,
     * the authorization mechanism will prevent the user accessing this resource).
     */
    public function deleteAction(Request $request, Tag $tag)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_tag_index');
        }

        // Delete the tags associated with this blog post. This is done automatically
        // by Doctrine, except for SQLite (the database used in this application)
        // because foreign key support is not enabled by default in SQLite

        $em = $this->getDoctrine()->getManager();
        $em->remove($tag);
        $em->flush();

        $this->addFlash('success', 'deleted_successfully');

        return $this->redirectToRoute('admin_tag_index');
    }
}
