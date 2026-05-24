<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="news_media", indexes={
 *     @ORM\Index(name="idx_news_media_ordering", columns={"ordering"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\NewsMediaRepository")
 */
class NewsMedia
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\News", inversedBy="mediaItems")
     * @ORM\JoinColumn(name="news_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $news;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Media")
     * @ORM\JoinColumn(name="media_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $media;

    /** @ORM\Column(name="ordering", type="integer") */
    private $ordering = 0;

    /** @ORM\Column(name="altOverride", type="string", length=255, nullable=true) */
    private $altOverride;

    /** @ORM\Column(name="captionOverride", type="text", nullable=true) */
    private $captionOverride;

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
    public function setNews(News $news) { $this->news = $news; return $this; }
    public function getNews() { return $this->news; }
    public function setMedia(Media $media) { $this->media = $media; return $this; }
    public function getMedia() { return $this->media; }
    public function setOrdering($ordering) { $this->ordering = (int) $ordering; return $this; }
    public function getOrdering() { return $this->ordering; }
    public function setAltOverride($altOverride) { $this->altOverride = trim((string) $altOverride) ?: null; return $this; }
    public function getAltOverride() { return $this->altOverride; }
    public function setCaptionOverride($captionOverride) { $this->captionOverride = trim((string) $captionOverride) ?: null; return $this; }
    public function getCaptionOverride() { return $this->captionOverride; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    public function getEffectiveAlt()
    {
        return $this->altOverride ?: ($this->media ? ($this->media->getAlt() ?: $this->media->getOriginalName()) : '');
    }

    public function getEffectiveCaption()
    {
        return $this->captionOverride ?: ($this->media ? $this->media->getCaption() : null);
    }
}
