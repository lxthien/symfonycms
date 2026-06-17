<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Settings\SettingsDefinitionProvider;
use AppBundle\Settings\SettingsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/settings")
 * @Security("is_granted('CMS_SETTINGS_MANAGE')")
 */
class SettingController extends Controller
{
    /**
     * @Route("/", name="dmishh_settings_manage_global")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request, SettingsManager $settingsManager, SettingsDefinitionProvider $definitionProvider)
    {
        $formBuilder = $this->createFormBuilder($settingsManager->all());

        foreach ($definitionProvider->all() as $name => $definition) {
            $formBuilder->add($name, $definition['type'], $definition['options']);
        }

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $settingsManager->setMany($form->getData());
            $this->addFlash('success', $this->get('translator')->trans('settings_updated', array(), 'settings'));

            return $this->redirectToRoute('dmishh_settings_manage_global');
        }

        return $this->render('admin/settings/index.html.twig', array(
            'settings_form' => $form->createView(),
            'settings_groups' => $definitionProvider->groups(),
        ));
    }
}
