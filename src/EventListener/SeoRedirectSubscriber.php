<?php

namespace App\EventListener;

use App\Entity\SeoRedirect;
use App\Seo\RedirectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SeoRedirectSubscriber implements EventSubscriberInterface
{
    private $em;
    private $redirectManager;

    public function __construct(EntityManagerInterface $em, RedirectManager $redirectManager)
    {
        $this->em = $em;
        $this->redirectManager = $redirectManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 40],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        $path = $this->redirectManager->normalizePath($request->getPathInfo());

        if ($path === '' || strpos($path, '/admin') === 0 || strpos($path, '/_') === 0) {
            return;
        }

        $redirect = $this->em->getRepository(SeoRedirect::class)->findEnabledBySourcePath($path);

        if (!$redirect || $redirect->getTargetPath() === $path) {
            return;
        }

        $redirect->incrementHits();
        $this->em->flush($redirect);

        $event->setResponse(new RedirectResponse($redirect->getTargetPath(), $redirect->getStatusCode()));
    }
}
