<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(
 *     name="news_view_stat",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uniq_news_view_stat_day", columns={"news_id", "viewDate"})
 *     },
 *     indexes={
 *         @ORM\Index(name="idx_news_view_stat_date", columns={"viewDate"}),
 *         @ORM\Index(name="idx_news_view_stat_news", columns={"news_id"})
 *     }
 * )
 * @ORM\Entity
 */
class NewsViewStat
{
    /**
     * @ORM\Column(name="id", type="integer")
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
     * @ORM\Column(name="viewDate", type="date")
     */
    private $viewDate;

    /**
     * @ORM\Column(name="views", type="integer", options={"default": 0})
     */
    private $views = 0;

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

    public function getId()
    {
        return $this->id;
    }

    public function setNews(News $news)
    {
        $this->news = $news;

        return $this;
    }

    public function getNews()
    {
        return $this->news;
    }

    public function setViewDate(\DateTimeInterface $viewDate)
    {
        $this->viewDate = $viewDate;

        return $this;
    }

    public function getViewDate()
    {
        return $this->viewDate;
    }

    public function setViews($views)
    {
        $this->views = $views;

        return $this;
    }

    public function getViews()
    {
        return $this->views;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
