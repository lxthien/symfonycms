<?php

namespace Tests\Seo;

use App\Entity\SeoRedirect;
use App\Seo\RedirectManager;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class RedirectManagerTest extends TestCase
{
    public function testNormalizePathKeepsOnlyCanonicalPath()
    {
        $manager = new RedirectManager($this->createMock(EntityManagerInterface::class));

        $this->assertSame('/old-url', $manager->normalizePath('https://example.com/old-url/?utm=test'));
        $this->assertSame('/old-url', $manager->normalizePath('old-url/'));
        $this->assertSame('/', $manager->normalizePath('/'));
        $this->assertSame('', $manager->normalizePath(''));
    }

    public function testCreateOrUpdateCreatesEnabledRedirect()
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('sourcePath' => '/old-url'))
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(SeoRedirect::class)
            ->willReturn($repository);
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(SeoRedirect::class));

        $redirect = (new RedirectManager($em))->createOrUpdate('https://site.test/old-url?x=1', '/new-url/', 302);

        $this->assertInstanceOf(SeoRedirect::class, $redirect);
        $this->assertSame('/old-url', $redirect->getSourcePath());
        $this->assertSame('/new-url', $redirect->getTargetPath());
        $this->assertSame(302, $redirect->getStatusCode());
        $this->assertTrue($redirect->getEnable());
    }

    public function testCreateOrUpdateSkipsEmptyOrSamePath()
    {
        $manager = new RedirectManager($this->createMock(EntityManagerInterface::class));

        $this->assertNull($manager->createOrUpdate('', '/target'));
        $this->assertNull($manager->createOrUpdate('/same', '/same/'));
    }
}
