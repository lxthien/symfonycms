<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\File;
use Gedmo\Mapping\Annotation as Gedmo;

use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * NewsCategory
 *
 * @ORM\Table(name="newscategory")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NewsCategoryRepository")
 * @Vich\Uploadable
 */
class NewsCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * One Category has Many Categories.
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\NewsCategory", mappedBy="parentcat")
     */
    protected $children;

    /**
     * Many Categories have One Category.
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\NewsCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parentcat_id", referencedColumnName="id", nullable=true)
     */
    private $parentcat;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="titleLandingPage", type="string", length=255, nullable=true)
     */
    private $titleLandingPage;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description = null;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true, columnDefinition="LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
     */
    private $content = null;

    /**
     * @var string
     *
     * @ORM\Column(name="schemaMarkup", type="text", nullable=true)
     */
    private $schemaMarkup = null;

    /**
     * @var string
     *
     * @ORM\Column(name="images", type="string", length=255, nullable=true)
     */
    private $images;

    /**
     * @Vich\UploadableField(mapping="newscategory_images", fileNameProperty="images")
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
     * @var string
     *
     * @ORM\Column(name="sortBy", type="string", length=255, nullable=true)
     */
    private $sortBy = null;

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
     * @var News[]|ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\News", mappedBy="category")
     */
    private $news;


    public function __construct()
    {
        $this->parentcat = new ArrayCollection();
        $this->news = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTitleLandingPage($titleLandingPage)
    {
        $this->titleLandingPage = $titleLandingPage;

        return $this;
    }

    public function getTitleLandingPage()
    {
        return $this->titleLandingPage;
    }

    public function setParentcat(\AppBundle\Entity\NewsCategory $parent = null) {
        $this->parentcat = $parent;

        return $this;
    }

    public function getParentcat() {
        return $this->parentcat != null ? $this->parentcat : 'root';
    }

    public function getChildren() {
        return $this->children;
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

    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setSchemaMarkup($schemaMarkup)
    {
        $this->schemaMarkup = $schemaMarkup;

        return $this;
    }

    public function getSchemaMarkup()
    {
        return $this->schemaMarkup;
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

    public function setEnable($enable)
    {
        $this->enable = (bool) $enable;

        return $this;
    }

    public function getEnable()
    {
        return $this->enable;
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

    public function setSortBy($sortBy)
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    public function getSortBy()
    {
        return $this->sortBy;
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

    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }
}
