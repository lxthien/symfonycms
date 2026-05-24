<?php

namespace Tests\Entity;

use App\Entity\Comment;
use App\Entity\News;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    public function testCommentKeepsDoctrineRelations()
    {
        $news = new News();
        $parent = new Comment();

        $comment = new Comment();
        $comment
            ->setNews($news)
            ->setParent($parent);

        $this->assertSame($news, $comment->getNews());
        $this->assertSame($parent, $comment->getParent());
        $this->assertNull($comment->getNewsId());
        $this->assertNull($comment->getCommentId());
    }

    public function testAddCommentAssignsNewsRelation()
    {
        $news = new News();
        $comment = new Comment();

        $news->addComment($comment);

        $this->assertSame($news, $comment->getNews());
        $this->assertTrue($news->getComments()->contains($comment));
    }

    public function testSpamGuardRejectsAtSign()
    {
        $comment = new Comment();
        $comment->setContent('spam@example.com');

        $this->assertFalse($comment->isLegitComment());

        $comment->setContent('Nội dung bình luận hợp lệ');

        $this->assertTrue($comment->isLegitComment());
    }
}
