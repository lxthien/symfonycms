<?php

namespace App\Controller\Admin;

use App\Health\HealthAuditManager;
use App\Controller\LegacyController as AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/health")
 */
class HealthController extends AbstractController
{
    /**
     * @Route("/", name="admin_health_index", methods={"GET"})
     */
    public function indexAction(HealthAuditManager $healthAudit)
    {
        $this->denyAccessUnlessGranted('CMS_HEALTH_VIEW');

        return $this->render('admin/health/index.html.twig', [
            'report' => $healthAudit->buildReport(),
        ]);
    }
}
