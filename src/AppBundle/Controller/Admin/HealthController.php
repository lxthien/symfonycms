<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Health\HealthAuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/admin/health")
 * @Security("is_granted('CMS_HEALTH_VIEW')")
 */
class HealthController extends Controller
{
    /**
     * @Route("/", name="admin_health_index")
     * @Method("GET")
     */
    public function indexAction(HealthAuditManager $healthAudit)
    {
        return $this->render('admin/health/index.html.twig', [
            'report' => $healthAudit->buildReport(),
        ]);
    }
}
