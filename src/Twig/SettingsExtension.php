<?php

namespace App\Twig;

use App\Settings\SettingsManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
{
    private $settingsManager;

    public function __construct(SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('get_setting', array($this, 'getSetting')),
            new TwigFunction('get_all_settings', array($this, 'getAllSettings')),
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

}
