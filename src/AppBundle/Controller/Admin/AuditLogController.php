<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\AuditLog;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/audit-log")
 * @Security("is_granted('CMS_AUDIT_LOG_VIEW')")
 */
class AuditLogController extends Controller
{
    /**
     * @Route("/", name="admin_audit_log_index")
     */
    public function indexAction(Request $request)
    {
        $filters = array(
            'q' => trim((string) $request->query->get('q')),
            'action' => $request->query->get('action'),
            'entityType' => $request->query->get('entityType'),
            'username' => trim((string) $request->query->get('username')),
        );

        $qb = $this->getDoctrine()->getRepository(AuditLog::class)->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($filters['q'] !== '') {
            $qb->andWhere('a.entityLabel LIKE :q OR a.entityId LIKE :q OR a.requestUri LIKE :q OR a.ip LIKE :q')
                ->setParameter('q', '%' . $filters['q'] . '%');
        }

        if ($filters['action']) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $filters['action']);
        }

        if ($filters['entityType']) {
            $qb->andWhere('a.entityType = :entityType')
                ->setParameter('entityType', $filters['entityType']);
        }

        if ($filters['username'] !== '') {
            $qb->andWhere('a.username LIKE :username')
                ->setParameter('username', '%' . $filters['username'] . '%');
        }

        $pagination = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            30
        );

        return $this->render('admin/audit_log/index.html.twig', array(
            'pagination' => $pagination,
            'filters' => $filters,
            'actions' => array('create', 'update', 'delete'),
            'entityTypes' => array('news', 'page', 'newscategory', 'media', 'comment', 'redirect', 'settings', 'user'),
        ));
    }
}
