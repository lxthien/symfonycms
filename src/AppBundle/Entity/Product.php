<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Product
 *
 * @ORM\Table(name="products")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductRepository")
 * @UniqueEntity("url")
 * @Vich\Uploadable
 */
class Product
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
     * One Product has Many Categories
     * @var ProductCat[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\ProductCat", inversedBy="products")
     * @ORM\JoinTable(
     *  name="product_productcat",
     *  joinColumns={
     *      @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="productcat_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $productCat;

    /**
     * Many ProductImage have one (the same) Product
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ProductImage", mappedBy="product", cascade={"persist"}, orphanRemoval=true)
     */
    private $productImages;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 5,
     *      max = 255,
     *      minMessage = "The name must be at least {{ limit }} characters long",
     *      maxMessage = "The name cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     */
    private $url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enable", type="boolean")
     */
    private $enable = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isHot", type="boolean")
     */
    private $isHot = false;

    /**
     * @var string
     *
     * @ORM\Column(name="images", type="string", length=255, nullable=true)
     */
    private $images;

    /**
     * @Vich\UploadableField(mapping="product_images", fileNameProperty="images")
     * @var File
     */
    private $imageFile;

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
     * @ORM\Column(name="contents", type="text")
     */
    private $contents;

    /**
     * @var int
     *
     * @ORM\Column(name="regularPrice", type="integer", nullable=true)
     */
    private $regularPrice;

    /**
     * @var int
     *
     * @ORM\Column(name="salePrice", type="integer", nullable=true)
     */
    private $salePrice;

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
     * @var int
     *
     * @ORM\Column(name="viewCounts", type="integer")
     */
    private $viewCounts = 0;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Rating", mappedBy="product")
     */
    private $rating;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\QuickOrder", mappedBy="product")
     */
    private $quickOrder;

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


    public function __toString()
    {
        return $this->getName();
    }


    public function __construct()
    {
        $this->productCat = new ArrayCollection();
        $this->productImages = new ArrayCollection();
        $this->rating = new ArrayCollection();
        $this->quickOrder = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ProductCat[]
     */
    public function addProductCat(ProductCat $productCat)
    {
        if (!$this->productCat->contains($productCat)) {
            $this->productCat->add($productCat);
        }
    }

    /**
     * @return ProductCat[]
     */
    public function removeProductCat(ProductCat $productCat)
    {
        $this->productCat->removeElement($productCat);
    }

    /**
     * @return \AppBundle\Entity\ProductCat
     */
    public function getProductCat()
    {
        return $this->productCat;
    }

    /**
     * @param \AppBundle\Entity\ProductImage $productImage
     *
     * @return ProductImage
     */
    public function addProductImage(\AppBundle\Entity\ProductImage $productImage)
    {
        $productImage->setProduct($this);

        $this->productImages[] = $productImage;

        return $this;
    }

    /**
     * @param \AppBundle\Entity\ProductImage $productImage
     */
    public function removeProductImage(\AppBundle\Entity\ProductImage $productImage)
    {
        $this->productImages->removeElement($productImage);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductImages()
    {
        return $this->productImages;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Product
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Product
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set enable
     *
     * @param bool $enable
     * @return Product
     */
    public function setEnable($enable)
    {
        $this->enable = $enable;

        return $this;
    }

    /**
     * Get enable
     *
     * @return bool
     */
    public function getEnable()
    {
        return $this->enable;
    }

    /**
     * @param bool $isHot
     * @return Product
     */
    public function setIsHot($isHot)
    {
        $this->isHot = $isHot;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsHot()
    {
        return $this->isHot;
    }

    /**
     * Set imageFile file
     *
     * @param File $imageFile
     * @return Product
     */
    public function setImageFile(File $imageFile = null)
    {
        $this->imageFile = $imageFile;

        if ($imageFile) {
            $this->updatedAt = new \DateTime('now');
        }
    }

    /**
     * @return string
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * Set images
     *
     * @param string $images
     * @return Product
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return string
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param string $description
     * @return Product
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $contents
     * @return Product
     */
    public function setContents($contents)
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param \int $regularPrice
     * @return Product
     */
    public function setRegularPrice($regularPrice)
    {
        $this->regularPrice = $regularPrice;

        return $this;
    }

    /**
     * @return int
     */
    public function getRegularPrice()
    {
        return $this->regularPrice;
    }

    /**
     * @param \int $salePrice
     * @return Product
     */
    public function setSalePrice($salePrice)
    {
        $this->salePrice = $salePrice;

        return $this;
    }

    /**
     * @return int
     */
    public function getSalePrice()
    {
        return $this->salePrice;
    }

    /**
     * Set pageTitle
     *
     * @param string $pageTitle
     * @return Product
     */
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    /**
     * Get pageTitle
     *
     * @return string
     */
    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    /**
     * Set pageDescription
     *
     * @param string $pageDescription
     * @return Product
     */
    public function setPageDescription($pageDescription)
    {
        $this->pageDescription = $pageDescription;

        return $this;
    }

    /**
     * Get pageDescription
     *
     * @return string
     */
    public function getPageDescription()
    {
        return $this->pageDescription;
    }

    /**
     * Set pageKeyword
     *
     * @param string $pageKeyword
     * @return Product
     */
    public function setPageKeyword($pageKeyword)
    {
        $this->pageKeyword = $pageKeyword;

        return $this;
    }

    /**
     * Get pageKeyword
     *
     * @return string
     */
    public function getPageKeyword()
    {
        return $this->pageKeyword;
    }

    /**
     * Set viewCounts
     *
     * @param \int $viewCounts
     * @return Product
     */
    public function setViewCounts($viewCounts)
    {
        $this->viewCounts = $viewCounts;

        return $this;
    }

    /**
     * Get viewCounts
     *
     * @return int
     */
    public function getViewCounts()
    {
        return $this->viewCounts;
    }

    /**
     * Get author
     *
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set author
     *
     * @param User $author
     *
     * @return Product
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    /**
     * @param \AppBundle\Entity\Rating $rating
     */
    public function removeRating(\AppBundle\Entity\Rating $rating)
    {
        $this->rating->removeElement($rating);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Product
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Product
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}