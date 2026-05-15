<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="media", indexes={
 *     @ORM\Index(name="idx_media_mime", columns={"mimeType"}),
 *     @ORM\Index(name="idx_media_hash", columns={"hash"}),
 *     @ORM\Index(name="idx_media_created", columns={"createdAt"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MediaRepository")
 */
class Media
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\Column(name="filename", type="string", length=255) */
    private $filename;

    /** @ORM\Column(name="originalName", type="string", length=255) */
    private $originalName;

    /** @ORM\Column(name="path", type="string", length=255) */
    private $path;

    /** @ORM\Column(name="thumbnailPath", type="string", length=255, nullable=true) */
    private $thumbnailPath;

    /** @ORM\Column(name="webpPath", type="string", length=255, nullable=true) */
    private $webpPath;

    /** @ORM\Column(name="mimeType", type="string", length=120) */
    private $mimeType;

    /** @ORM\Column(name="size", type="integer") */
    private $size = 0;

    /** @ORM\Column(name="width", type="integer", nullable=true) */
    private $width;

    /** @ORM\Column(name="height", type="integer", nullable=true) */
    private $height;

    /** @ORM\Column(name="alt", type="string", length=255, nullable=true) */
    private $alt;

    /** @ORM\Column(name="caption", type="text", nullable=true) */
    private $caption;

    /** @ORM\Column(name="credit", type="string", length=255, nullable=true) */
    private $credit;

    /** @ORM\Column(name="hash", type="string", length=64, nullable=true) */
    private $hash;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\MediaFolder", inversedBy="media")
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $folder;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="uploadedBy_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $uploadedBy;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\MediaTag", inversedBy="media", cascade={"persist"})
     * @ORM\JoinTable(name="media_media_tag")
     */
    private $tags;

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

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId() { return $this->id; }
    public function setFilename($filename) { $this->filename = $filename; return $this; }
    public function getFilename() { return $this->filename; }
    public function setOriginalName($originalName) { $this->originalName = $originalName; return $this; }
    public function getOriginalName() { return $this->originalName; }
    public function setPath($path) { $this->path = $path; return $this; }
    public function getPath() { return $this->path; }
    public function setThumbnailPath($thumbnailPath) { $this->thumbnailPath = $thumbnailPath; return $this; }
    public function getThumbnailPath() { return $this->thumbnailPath; }
    public function setWebpPath($webpPath) { $this->webpPath = $webpPath; return $this; }
    public function getWebpPath() { return $this->webpPath; }
    public function setMimeType($mimeType) { $this->mimeType = $mimeType; return $this; }
    public function getMimeType() { return $this->mimeType; }
    public function setSize($size) { $this->size = (int) $size; return $this; }
    public function getSize() { return $this->size; }
    public function setWidth($width) { $this->width = $width !== null ? (int) $width : null; return $this; }
    public function getWidth() { return $this->width; }
    public function setHeight($height) { $this->height = $height !== null ? (int) $height : null; return $this; }
    public function getHeight() { return $this->height; }
    public function setAlt($alt) { $this->alt = trim((string) $alt) ?: null; return $this; }
    public function getAlt() { return $this->alt; }
    public function setCaption($caption) { $this->caption = trim((string) $caption) ?: null; return $this; }
    public function getCaption() { return $this->caption; }
    public function setCredit($credit) { $this->credit = trim((string) $credit) ?: null; return $this; }
    public function getCredit() { return $this->credit; }
    public function setHash($hash) { $this->hash = $hash; return $this; }
    public function getHash() { return $this->hash; }
    public function setFolder(MediaFolder $folder = null) { $this->folder = $folder; return $this; }
    public function getFolder() { return $this->folder; }
    public function setUploadedBy(User $uploadedBy = null) { $this->uploadedBy = $uploadedBy; return $this; }
    public function getUploadedBy() { return $this->uploadedBy; }
    public function getTags() { return $this->tags; }
    public function addTag(MediaTag $tag) { if (!$this->tags->contains($tag)) { $this->tags->add($tag); } return $this; }
    public function removeTag(MediaTag $tag) { $this->tags->removeElement($tag); return $this; }
    public function clearTags() { $this->tags->clear(); return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    public function isImage()
    {
        return strpos((string) $this->mimeType, 'image/') === 0;
    }
}
