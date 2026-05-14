<?php

namespace AppBundle\Seo;

use AppBundle\Entity\SeoRedirect;
use Doctrine\ORM\EntityManagerInterface;

class RedirectManager
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function normalizePath($path)
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        $parts = parse_url($path);

        if (isset($parts['path'])) {
            $path = $parts['path'];
        }

        $path = '/' . ltrim($path, '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    public function createOrUpdate($sourcePath, $targetPath, $statusCode = 301)
    {
        $sourcePath = $this->normalizePath($sourcePath);
        $targetPath = $this->normalizePath($targetPath);

        if ($sourcePath === '' || $targetPath === '' || $sourcePath === $targetPath) {
            return null;
        }

        $redirect = $this->em->getRepository(SeoRedirect::class)->findOneBy(['sourcePath' => $sourcePath]);

        if (!$redirect) {
            $redirect = new SeoRedirect();
            $redirect->setSourcePath($sourcePath);
            $this->em->persist($redirect);
        }

        $redirect
            ->setTargetPath($targetPath)
            ->setStatusCode($statusCode)
            ->setEnable(true);

        return $redirect;
    }
}
