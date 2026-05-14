<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * News
 *
 * @ORM\Table(name="news")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NewsRepository")
 * @Vich\Uploadable
 */
class News
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_TRASH = 'trash';

    const VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_REVIEW,
        self::STATUS_SCHEDULED,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
        self::STATUS_TRASH,
    ];

    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Bản nháp',
        self::STATUS_PENDING_REVIEW => 'Chờ duyệt',
        self::STATUS_SCHEDULED => 'Đặt lịch',
        self::STATUS_PUBLISHED => 'Đã xuất bản',
        self::STATUS_ARCHIVED => 'Lưu trữ',
        self::STATUS_TRASH => 'Thùng rác',
    ];
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var AppBundle\Entity\NewsCategory;
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\NewsCategory", inversedBy="news")
     * @ORM\JoinTable(
     *  name="news_newscategory",
     *  joinColumns={
     *      @ORM\JoinColumn(name="news_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="newscategory_id", referencedColumnName="id")
     *  }
     * )
     */
    private $category;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 10,
     *      max = 255,
     *      minMessage = "Your title must be at least {{ limit }} characters long",
     *      maxMessage = "Your title cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     */
    private $url;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var text
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="contents", type="text", columnDefinition="LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL")
     */
    private $contents;

    /**
     * @var string
     *
     * @ORM\Column(name="images", type="string", length=255, nullable=true)
     */
    private $images;

    /**
     * @Vich\UploadableField(mapping="news_images", fileNameProperty="images")
     * @var File
     */
    private $imageFile;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enable", type="boolean")
     */
    private $enable = true;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, options={"default": "draft"})
     */
    private $status = self::STATUS_DRAFT;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="scheduledAt", type="datetime", nullable=true)
     */
    private $scheduledAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="publishedAt", type="datetime", nullable=true)
     */
    private $publishedAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="reviewed_by", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $reviewedBy;

    /**
     * @var string|null
     *
     * @ORM\Column(name="editorialNotes", type="text", nullable=true)
     */
    private $editorialNotes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="previewToken", type="string", length=64, nullable=true, unique=true)
     */
    private $previewToken;

    /**
     * @var boolean
     *
     * @ORM\Column(name="autoFulfillAddress", type="boolean")
     */
    private $autoFulfillAddress = false;

    /**
     * @var string
     *
     * @ORM\Column(name="postType", type="string", length=255)
     */
    private $postType = 'post';

    /**
     * @var string
     *
     * @ORM\Column(name="pageTitle", type="string", length=255, nullable=true)
     */
    private $pageTitle = null;

    /**
     * @var string
     *
     * @ORM\Column(name="pageDescription", type="text", nullable=true)
     */
    private $pageDescription = null;

    /**
     * @var string
     *
     * @ORM\Column(name="pageKeyword", type="string", length=255, nullable=true)
     */
    private $pageKeyword = null;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isIndex", type="boolean", options={"default": true})
     */
    private $isIndex = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isFollow", type="boolean", options={"default": true})
     */
    private $isFollow = true;

    /**
     * @var text
     *
     * @ORM\Column(name="qa", type="text", nullable=true)
     */
    private $qa;

    /**
     * @var int
     *
     * @ORM\Column(name="viewCounts", type="integer")
     */
    private $viewCounts = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="ordering", type="integer", nullable=true)
     */
    private $ordering = null;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=255, nullable=true)
     */
    private $template = null;

    /**
     * @var int
     *
     * @ORM\Column(name="categoryPrimary", type="integer")
     */
    private $categoryPrimary = 0;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="createdAt", type="datetime") 
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @var Tag[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Tag", inversedBy="news", cascade={"persist"})
     * @ORM\OrderBy({"name": "ASC"})
     * @Assert\Count(max="10", maxMessage="news.too_many_tags")
     */
    private $tags;

    public function __toString()
    {
        return (string)$this->getTitle();
    }

    public function __construct()
    {
        $this->category = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addTag(Tag $tag)
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setCategory(\AppBundle\Entity\NewsCategory $category = null)
    {
        $this->category = $category;

        return $this;
    }

    public function addCategory(NewsCategory $newsCategory)
    {
        if (!$this->category->contains($newsCategory)) {
            $this->category->add($newsCategory);
        }
    }

    public function removeCategory(NewsCategory $newsCategory)
    {
        $this->category->removeElement($newsCategory);
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setContents($contents)
    {
        $this->contents = $contents;

        return $this;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function setImageFile(File $images = null)
    {
        $this->imageFile = $images;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($images) {
            $this->updatedAt = new \DateTime('now');
        }
    }

    public function getImageFile()
    {
        return $this->imageFile;
    }

    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    public function getImages()
    {
        return $this->images;
    }

    /**
     * @deprecated Use setStatus() instead. Kept for backward compatibility.
     */
    public function setEnable($enable)
    {
        $this->enable = (bool) $enable;

        // Auto-sync status
        if ($this->enable && $this->status !== self::STATUS_PUBLISHED) {
            $this->status = self::STATUS_PUBLISHED;
            if (!$this->publishedAt) {
                $this->publishedAt = new \DateTime();
            }
        } elseif (!$this->enable && $this->status === self::STATUS_PUBLISHED) {
            $this->status = self::STATUS_DRAFT;
        }

        return $this;
    }

    /**
     * Returns true if status is 'published'. Backward compatible.
     */
    public function getEnable()
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    // ── Publishing Workflow ──────────────────────────────────────

    public function setStatus($status)
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s".', $status));
        }

        $this->status = $status;
        $this->enable = ($status === self::STATUS_PUBLISHED);

        if ($status === self::STATUS_PUBLISHED && !$this->publishedAt) {
            $this->publishedAt = new \DateTime();
        }

        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getStatusLabel()
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isPublished()
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isScheduled()
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function setScheduledAt(\DateTime $scheduledAt = null)
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getScheduledAt()
    {
        return $this->scheduledAt;
    }

    public function setPublishedAt(\DateTime $publishedAt = null)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    public function setReviewedBy(User $reviewedBy = null)
    {
        $this->reviewedBy = $reviewedBy;

        return $this;
    }

    public function getReviewedBy()
    {
        return $this->reviewedBy;
    }

    public function setEditorialNotes($editorialNotes)
    {
        $this->editorialNotes = $editorialNotes;

        return $this;
    }

    public function getEditorialNotes()
    {
        return $this->editorialNotes;
    }

    public function setPreviewToken($previewToken)
    {
        $this->previewToken = $previewToken;

        return $this;
    }

    public function getPreviewToken()
    {
        return $this->previewToken;
    }

    public function generatePreviewToken()
    {
        $this->previewToken = bin2hex(random_bytes(32));

        return $this;
    }

    public function setAutoFulfillAddress($autoFulfillAddress)
    {
        $this->autoFulfillAddress = $autoFulfillAddress;

        return $this;
    }

    public function getAutoFulfillAddress()
    {
        return $this->autoFulfillAddress;
    }

    public function setPostType($postType)
    {
        $this->postType = $postType;

        return $this;
    }

    public function getPostType()
    {
        return $this->postType;
    }

    public function isPage()
    {
        return ($this->postType == 'page') ? true : false;
    }

    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    public function setPageDescription($pageDescription)
    {
        $this->pageDescription = $pageDescription;

        return $this;
    }

    public function getPageDescription()
    {
        return $this->pageDescription;
    }

    public function setPageKeyword($pageKeyword)
    {
        $this->pageKeyword = $pageKeyword;

        return $this;
    }

    public function getPageKeyword()
    {
        return $this->pageKeyword;
    }

    public function setIsIndex($isIndex)
    {
        $this->isIndex = (bool) $isIndex;

        return $this;
    }

    public function getIsIndex()
    {
        return $this->isIndex;
    }

    public function setIsFollow($isFollow)
    {
        $this->isFollow = (bool) $isFollow;

        return $this;
    }

    public function getIsFollow()
    {
        return $this->isFollow;
    }

    public function setQa($qa)
    {
        $this->qa = $qa;

        return $this;
    }

    public function getQa()
    {
        return $this->qa;
    }

    public function setViewCounts($viewCounts)
    {
        $this->viewCounts = $viewCounts;

        return $this;
    }

    public function getViewCounts()
    {
        return $this->viewCounts;
    }

    public function setOrdering($ordering)
    {
        $this->ordering = $ordering;

        return $this;
    }

    public function getOrdering()
    {
        return $this->ordering;
    }

    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function setCategoryPrimary($categoryPrimary)
    {
        $this->categoryPrimary = $categoryPrimary;

        return $this;
    }

    public function getCategoryPrimary()
    {
        return $this->categoryPrimary;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function addComment(Comment $comment)
    {
        $comment->setNews($this);
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }
    }

    public function removeComment(Comment $comment)
    {
        $comment->setNews(null);
        $this->comments->removeElement($comment);
    }
}
