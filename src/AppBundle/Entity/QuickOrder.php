<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Product
 *
 * @ORM\Table(name="quickorders")
 * @ORM\Entity()
 */
class QuickOrder
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
     * @var AppBundle\Entity\Product;
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Product", inversedBy="product", cascade={"persist"})
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $product;

    /**
     * @var int
     *
     * @ORM\Column(name="qty", type="integer")
     */
    private $qty = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="customerGender", type="integer")
     */
    private $customerGender = 1;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="customerName", type="string", length=255)
     */
    private $customerName;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="customerPhone", type="string", length=255)
     */
    private $customerPhone;

    /**
     * @var string
     *
     * @ORM\Column(name="customerEmail", type="text", nullable=true)
     */
    private $customerEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="customerAddress", type="text", nullable=true)
     */
    private $customerAddress;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="createdAt", type="datetime") 
     */
    private $createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \AppBundle\Entity\Product $product
     * @return QuickOrder
     */
    public function setProduct(\AppBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return \AppBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param \int $qty
     * @return QuickOrder
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * @return int
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param \int $customerGender
     * @return QuickOrder
     */
    public function setCustomerGender($customerGender)
    {
        $this->customerGender = $customerGender;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerGender()
    {
        return $this->customerGender == 0 ? 'Nam' : 'Nữ';
    }
    
    /**
     * @param \int $string
     * @return QuickOrder
     */
    public function setCustomerName($customerName)
    {
        $this->customerName = $customerName;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * @param \string $customerPhone
     * @return QuickOrder
     */
    public function setCustomerPhone($customerPhone)
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerPhone()
    {
        return $this->customerPhone;
    }

    /**
     * @param \string $customerEmail
     * @return QuickOrder
     */
    public function setCustomerEmail($customerEmail)
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->customerEmail;
    }

    /**
     * @param \string $customerAddress
     * @return QuickOrder
     */
    public function setCustomerAddress($customerAddress)
    {
        $this->customerAddress = $customerAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerAddress()
    {
        return $this->customerAddress;
    }

    /**
     * @param \DateTime $createdAt
     * @return QuickOrder
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}