<?php

namespace AppBundle\Twig;

use AppBundle\Settings\SettingsManager;

class SettingsExtension extends \Twig_Extension
{
    private $settingsManager;

    public function __construct(SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_setting', array($this, 'getSetting')),
            new \Twig_SimpleFunction('get_all_settings', array($this, 'getAllSettings')),
        );
    }

    public function getSetting($name, $owner = null, $default = null)
    {
        return $this->settingsManager->get($name, $owner, $default);
    }

    public function getAllSettings($owner = null)
    {
        return $this->settingsManager->all($owner);
    }

    public function getName()
    {
        return 'app_settings_extension';
    }
}
