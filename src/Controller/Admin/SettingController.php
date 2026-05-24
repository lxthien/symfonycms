<?php

namespace App\Controller\Admin;

use App\Settings\SettingsDefinitionProvider;
use App\Settings\SettingsManager;
use App\Controller\LegacyController as AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/settings")
 */
class SettingController extends AbstractController
{
    /**
     * @Route("/", name="dmishh_settings_manage_global", methods={"GET", "POST"})
     */
    public function indexAction(Request $request, SettingsManager $settingsManager, SettingsDefinitionProvider $definitionProvider)
    {
        $this->denyAccessUnlessGranted('CMS_SETTINGS_MANAGE');

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
