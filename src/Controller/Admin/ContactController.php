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

use App\Entity\Contact;
use App\Controller\LegacyController as AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage blog contents in the backend.
 *
 * @Route("/admin/contact")
 */

class ContactController extends AbstractController
{
    /**
     * Lists all Contact entities.
     *
     * @Route("/", name="admin_contact_index", methods={"GET"})
     */
    public function indexAction()
    {
        $this->denyAccessUnlessGranted('CMS_CONTACT_MANAGE');

        $contacts = $this->getDoctrine()
                ->getRepository(Contact::class)
                ->findBy(
                    array(),
                    array('createdAt' => 'DESC')
                );

        return $this->render('admin/contact/index.html.twig', [
            'objects' => $contacts
        ]);
    }

    /**
     * Deletes a Contact entity.
     *
     * @Route("/{id}/delete", name="admin_contact_delete", methods={"POST"})
     */
    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('CMS_CONTACT_MANAGE');
        $contact = $this->findContact($id);

        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_contact_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($contact);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_contact_index');
    }

    private function findContact($id)
    {
        $contact = $this->getDoctrine()->getRepository(Contact::class)->find($id);

        if (!$contact) {
            throw $this->createNotFoundException('Liên hệ không tồn tại.');
        }

        return $contact;
    }
}
