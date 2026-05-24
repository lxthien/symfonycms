<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use App\Entity\Comment;
use App\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Notifies post's author about new comments.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class CommentNotificationSubscriber implements EventSubscriberInterface
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var string
     */
    private $sender;

    /**
     * Constructor.
     *
     * @param MailerInterface       $mailer
     * @param UrlGeneratorInterface $urlGenerator
     * @param TranslatorInterface   $translator
     * @param string                $sender
     */
    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator, $sender)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->sender = $sender;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::COMMENT_CREATED => 'onCommentCreated',
        ];
    }

    /**
     * @param GenericEvent $event
     */
    public function onCommentCreated(GenericEvent $event)
    {
        /** @var Comment $comment */
        $comment = $event->getSubject();
        $post = $comment->getPost();

        $linkToPost = $this->urlGenerator->generate('blog_post', [
            'slug' => $post->getSlug(),
            '_fragment' => 'comment_'.$comment->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $subject = $this->translator->trans('notification.comment_created');
        $body = $this->translator->trans('notification.comment_created.description', [
            '%title%' => $post->getTitle(),
            '%link%' => $linkToPost,
        ]);

        // Symfony Mailer uses the Email class to create and send emails.
        // See https://symfony.com/doc/current/email.html#sending-emails
        $message = (new Email())
            ->subject($subject)
            ->to($post->getAuthor()->getEmail())
            ->from($this->sender)
            ->html($body)
        ;

        // In config/config_dev.yml the development mailer configuration can disable real delivery.
        // That's why in the development environment you won't actually receive any email.
        // However, you can inspect the contents of those unsent emails using the debug toolbar.
        // See https://symfony.com/doc/current/email/dev_environment.html#viewing-from-the-web-debug-toolbar
        $this->mailer->send($message);
    }
}
