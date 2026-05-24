<?php

namespace App\EventListener;

use App\Breadcrumb\BreadcrumbManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BreadcrumbResetSubscriber implements EventSubscriberInterface
{
    private $breadcrumbs;

    public function __construct(BreadcrumbManager $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['resetBreadcrumbs', 256],
        ];
    }

    public function resetBreadcrumbs(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->breadcrumbs->reset();
    }
}
