<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="seo_redirect", indexes={
 *     @ORM\Index(name="idx_seo_redirect_source", columns={"sourcePath"}),
 *     @ORM\Index(name="idx_seo_redirect_enable", columns={"enable"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SeoRedirectRepository")
 */
class SeoRedirect
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="sourcePath", type="string", length=255, unique=true)
     */
    private $sourcePath;

    /**
     * @ORM\Column(name="targetPath", type="string", length=255)
     */
    private $targetPath;

    /**
     * @ORM\Column(name="statusCode", type="integer")
     */
    private $statusCode = 301;

    /**
     * @ORM\Column(name="enable", type="boolean", options={"default": true})
     */
    private $enable = true;

    /**
     * @ORM\Column(name="hits", type="integer", options={"default": 0})
     */
    private $hits = 0;

    /**
     * @ORM\Column(name="lastAccessedAt", type="datetime", nullable=true)
     */
    private $lastAccessedAt;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    public function getId() { return $this->id; }
    public function setSourcePath($sourcePath) { $this->sourcePath = $sourcePath; return $this; }
    public function getSourcePath() { return $this->sourcePath; }
    public function setTargetPath($targetPath) { $this->targetPath = $targetPath; return $this; }
    public function getTargetPath() { return $this->targetPath; }
    public function setStatusCode($statusCode) { $this->statusCode = (int) $statusCode; return $this; }
    public function getStatusCode() { return $this->statusCode; }
    public function setEnable($enable) { $this->enable = (bool) $enable; return $this; }
    public function getEnable() { return $this->enable; }
    public function setHits($hits) { $this->hits = (int) $hits; return $this; }
    public function getHits() { return $this->hits; }
    public function incrementHits() { $this->hits++; $this->lastAccessedAt = new \DateTime(); return $this; }
    public function setLastAccessedAt(\DateTime $lastAccessedAt = null) { $this->lastAccessedAt = $lastAccessedAt; return $this; }
    public function getLastAccessedAt() { return $this->lastAccessedAt; }
    public function setCreatedAt(\DateTime $createdAt) { $this->createdAt = $createdAt; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function setUpdatedAt(\DateTime $updatedAt) { $this->updatedAt = $updatedAt; return $this; }
    public function getUpdatedAt() { return $this->updatedAt; }
}
