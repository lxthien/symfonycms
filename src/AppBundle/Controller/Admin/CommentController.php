<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Comment;
use AppBundle\Form\CommentType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage comment in the backend.
 *
 * @Route("/admin/comment")
 * @Security("is_granted('CMS_COMMENT_MANAGE')")
 */

class CommentController extends Controller
{
    /**
     * Lists all Comment entities.
     *
     * @Route("/", name="admin_comment_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $q = trim((string) $request->query->get('q'));
        $status = $request->query->get('status', '');

        $qb = $em->getRepository(Comment::class)->createQueryBuilder('c')
            ->leftJoin('c.news', 'n')->addSelect('n')
            ->leftJoin('c.parent', 'p')->addSelect('p')
            ->addSelect('COALESCE(p.id, c.id) AS HIDDEN threadId')
            ->addSelect('CASE WHEN p.id IS NULL THEN 0 ELSE 1 END AS HIDDEN threadDepth');

        if ($q !== '') {
            $qb->andWhere('c.author LIKE :q OR c.email LIKE :q OR c.phone LIKE :q OR c.content LIKE :q OR c.ip LIKE :q OR n.title LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($status === 'approved') {
            $qb->andWhere('c.approved = :approved')->setParameter('approved', true);
        } elseif ($status === 'pending') {
            $qb->andWhere('c.approved = :approved')->setParameter('approved', false);
        }

        $qb->orderBy('threadId', 'DESC')
            ->addOrderBy('threadDepth', 'ASC')
            ->addOrderBy('c.createdAt', 'ASC');

        $pagination = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/comment/index.html.twig', [
            'pagination' => $pagination,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Displays a form to edit an existing Comment entity.
     *
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_comment_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Comment $comment)
    {
        //$this->denyAccessUnlessGranted('edit', $category, 'Posts can only be edited by their authors.');

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'action.updated_successfully');

            return $this->redirectToRoute('admin_comment_index');
        }

        return $this->render('admin/comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to reply an existing Comment entity.
     *
     * @Route("/{id}/reply", requirements={"id": "\d+"}, name="admin_comment_reply")
     * @Method({"GET", "POST"})
     */
    public function replyAction(Request $request, Comment $comment)
    {
        $replyComment = new Comment();
        $replyComment->setNews($comment->getNews());
        $replyComment->setParent($comment);
        $replyComment->setEmail( $this->getUser()->getEmail() );
        $replyComment->setPhone( '123456789' ); // Fixed phone
        $replyComment->setApproved( true );
        $replyComment->setAuthor( $this->getUser()->getName() );
        $replyComment->setIp( $this->container->get('request_stack')->getCurrentRequest()->getClientIp() );

        $form = $this->createForm(CommentType::class, $replyComment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($replyComment);
            $em->flush();

            if (!$comment->getApproved()) {
                $comment->setApproved( true );
                
                $em = $this->getDoctrine()->getManager();
                $em->persist($comment);
                $em->flush();
            }

            $this->addFlash('success', 'action.updated_successfully');
            
            return $this->redirectToRoute('admin_comment_index');
        }

        return $this->render('admin/comment/reply.html.twig', [
            'comment' => $replyComment,
            'parentComment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/approve", requirements={"id": "\d+"}, name="admin_comment_approve")
     * @Method("POST")
     */
    public function approveAction(Request $request, Comment $comment)
    {
        if (!$this->isCsrfTokenValid('comment_status_' . $comment->getId(), $request->request->get('token'))) {
            return $this->redirectToRoute('admin_comment_index', $request->query->all());
        }

        $comment->setApproved(true);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Đã duyệt bình luận.');

        return $this->redirectToRoute('admin_comment_index', $request->query->all());
    }

    /**
     * @Route("/{id}/unapprove", requirements={"id": "\d+"}, name="admin_comment_unapprove")
     * @Method("POST")
     */
    public function unapproveAction(Request $request, Comment $comment)
    {
        if (!$this->isCsrfTokenValid('comment_status_' . $comment->getId(), $request->request->get('token'))) {
            return $this->redirectToRoute('admin_comment_index', $request->query->all());
        }

        $comment->setApproved(false);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Đã chuyển bình luận về chờ duyệt.');

        return $this->redirectToRoute('admin_comment_index', $request->query->all());
    }

    /**
     * Deletes a Comment entity.
     *
     * @Route("/{id}/delete", name="admin_comment_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, Comment $comment)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_comment_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_comment_index');
    }

    /**
     * @Route("/bulk", name="admin_comment_bulk")
     * @Method("POST")
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isCsrfTokenValid('bulk_comment', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_comment_index');
        }

        $action = $request->request->get('bulk_action');
        $ids = array_filter((array) $request->request->get('ids'), 'is_numeric');

        if (!$ids || !in_array($action, ['approve', 'unapprove', 'delete'], true)) {
            $this->addFlash('warning', 'Vui lòng chọn bình luận và thao tác hợp lệ.');

            return $this->redirectToRoute('admin_comment_index', $request->query->all());
        }

        $em = $this->getDoctrine()->getManager();
        $comments = $em->getRepository(Comment::class)->createQueryBuilder('c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        foreach ($comments as $comment) {
            if ($action === 'delete') {
                $em->remove($comment);
            } else {
                $comment->setApproved($action === 'approve');
            }
        }

        $em->flush();
        $this->addFlash('success', 'Đã xử lý ' . count($comments) . ' bình luận.');

        return $this->redirectToRoute('admin_comment_index', $request->query->all());
    }
}
