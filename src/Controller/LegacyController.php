<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

abstract class LegacyController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), array(
            'doctrine' => '?' . \Doctrine\Persistence\ManagerRegistry::class,
            'kernel' => '?' . \Symfony\Component\HttpKernel\KernelInterface::class,
            'knp_paginator' => '?' . \Knp\Component\Pager\PaginatorInterface::class,
            'request_stack' => '?' . \Symfony\Component\HttpFoundation\RequestStack::class,
            'security.csrf.token_manager' => '?' . \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface::class,
            'settings_manager' => '?' . \App\Settings\SettingsManager::class,
            'translator' => '?' . \Symfony\Contracts\Translation\TranslatorInterface::class,
            'white_october_breadcrumbs' => '?' . \App\Breadcrumb\BreadcrumbManager::class,
        ));
    }

    protected function get($id)
    {
        if (!$this->container->has($id)) {
            throw new ServiceNotFoundException($id);
        }

        return $this->container->get($id);
    }

    protected function getDoctrine()
    {
        return $this->get('doctrine');
    }
}
