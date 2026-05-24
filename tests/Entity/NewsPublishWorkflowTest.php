<?php

namespace Tests\Entity;

use App\Entity\News;
use PHPUnit\Framework\TestCase;

class NewsPublishWorkflowTest extends TestCase
{
    public function testNewPostStartsAsDraft()
    {
        $post = new News();

        $this->assertTrue($post->isDraft());
        $this->assertSame(News::STATUS_DRAFT, $post->getStatus());
        $this->assertSame('Bản nháp', $post->getStatusLabel());
    }

    public function testPublishingSetsPublishedAtOnce()
    {
        $post = new News();

        $post->setStatus(News::STATUS_PUBLISHED);
        $firstPublishedAt = $post->getPublishedAt();

        $this->assertTrue($post->isPublished());
        $this->assertInstanceOf(\DateTime::class, $firstPublishedAt);

        $post->setStatus(News::STATUS_PUBLISHED);

        $this->assertSame($firstPublishedAt, $post->getPublishedAt());
    }

    public function testScheduledPostKeepsScheduledAt()
    {
        $scheduledAt = new \DateTime('2026-05-18 13:15:00');
        $post = new News();

        $post
            ->setStatus(News::STATUS_SCHEDULED)
            ->setScheduledAt($scheduledAt);

        $this->assertTrue($post->isScheduled());
        $this->assertSame($scheduledAt, $post->getScheduledAt());
        $this->assertNull($post->getPublishedAt());
    }

    public function testInvalidStatusIsRejected()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new News())->setStatus('invalid-status');
    }
}
