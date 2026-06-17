<?php

namespace AppBundle\Entity;

use AppBundle\Entity\News;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Gedmo\Mapping\Annotation as Gedmo;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints as Recaptcha;

/**
 * @ORM\Entity
 * @ORM\Table(name="comment", indexes={
 *     @ORM\Index(name="idx_comment_news", columns={"news_id"}),
 *     @ORM\Index(name="idx_comment_parent", columns={"comment_id"}),
 *     @ORM\Index(name="idx_comment_approved", columns={"approved"})
 * })
 */

class Comment
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Comment|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Comment")
     * @ORM\JoinColumn(name="comment_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $parent;

    /**
     * @var News
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\News")
     * @ORM\JoinColumn(name="news_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Assert\NotBlank(message="news.blank")
     */
    private $news;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="content.blank")
     * @Assert\Length(
     *     min=5,
     *     minMessage="content.too_short",
     *     max=10000,
     *     maxMessage="content.too_long"
     * )
     */
    private $content;

    /**
     * @var boolean
     *
     * @ORM\Column(name="approved", type="boolean")
     */
    private $approved = false;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=false)
     */
    private $phone;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="author", type="text")
     */
    private $author;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="ip", type="text")
     */
    private $ip;

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

    public $recaptcha;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @Assert\IsTrue(message="comment.is_spam")
     */
    public function isLegitComment()
    {
        $containsInvalidCharacters = false !== mb_strpos($this->content, '@');

        return !$containsInvalidCharacters;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setNewsId($newsId)
    {
        return $this;
    }

    public function getNewsId()
    {
        return $this->news ? $this->news->getId() : null;
    }

    public function setNews(News $news = null)
    {
        $this->news = $news;

        return $this;
    }

    public function getNews()
    {
        return $this->news;
    }

    public function setCommentId($commentId)
    {
        return $this;
    }

    public function getCommentId()
    {
        return $this->parent ? $this->parent->getId() : null;
    }

    public function setParent(Comment $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent()
    {
        return $this->parent;
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

    public function setApproved($approved)
    {
        $this->approved = $approved;

        return $this;
    }

    public function getApproved()
    {
        return $this->approved;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp()
    {
        return $this->ip;
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

}
