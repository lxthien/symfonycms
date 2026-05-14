<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="content_revision", indexes={
 *     @ORM\Index(name="idx_content_revision_news_created", columns={"news_id", "createdAt"}),
 *     @ORM\Index(name="idx_content_revision_type", columns={"revisionType"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContentRevisionRepository")
 */
class ContentRevision
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\News")
     * @ORM\JoinColumn(name="news_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $news;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $author;

    /** @ORM\Column(name="revisionType", type="string", length=32) */
    private $revisionType = 'manual';

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private $title;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private $url;

    /** @ORM\Column(type="text", nullable=true) */
    private $description;

    /** @ORM\Column(type="text", nullable=true, columnDefinition="LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") */
    private $contents;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private $pageTitle;

    /** @ORM\Column(type="text", nullable=true) */
    private $pageDescription;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private $pageKeyword;

    /** @ORM\Column(type="text", nullable=true) */
    private $qa;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private $template;

    /** @ORM\Column(type="text", nullable=true) */
    private $diffSummary;

    /** @ORM\Column(name="createdAt", type="datetime") */
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function setNews(News $news) { $this->news = $news; return $this; }
    public function getNews() { return $this->news; }
    public function setAuthor(User $author = null) { $this->author = $author; return $this; }
    public function getAuthor() { return $this->author; }
    public function setRevisionType($revisionType) { $this->revisionType = $revisionType; return $this; }
    public function getRevisionType() { return $this->revisionType; }
    public function setTitle($title) { $this->title = $title; return $this; }
    public function getTitle() { return $this->title; }
    public function setUrl($url) { $this->url = $url; return $this; }
    public function getUrl() { return $this->url; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function getDescription() { return $this->description; }
    public function setContents($contents) { $this->contents = $contents; return $this; }
    public function getContents() { return $this->contents; }
    public function setPageTitle($pageTitle) { $this->pageTitle = $pageTitle; return $this; }
    public function getPageTitle() { return $this->pageTitle; }
    public function setPageDescription($pageDescription) { $this->pageDescription = $pageDescription; return $this; }
    public function getPageDescription() { return $this->pageDescription; }
    public function setPageKeyword($pageKeyword) { $this->pageKeyword = $pageKeyword; return $this; }
    public function getPageKeyword() { return $this->pageKeyword; }
    public function setQa($qa) { $this->qa = $qa; return $this; }
    public function getQa() { return $this->qa; }
    public function setTemplate($template) { $this->template = $template; return $this; }
    public function getTemplate() { return $this->template; }
    public function setDiffSummary($diffSummary) { $this->diffSummary = $diffSummary; return $this; }
    public function getDiffSummary() { return $this->diffSummary; }
    public function setCreatedAt(\DateTime $createdAt) { $this->createdAt = $createdAt; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}
