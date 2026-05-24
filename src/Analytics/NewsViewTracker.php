<?php

namespace App\Analytics;

use App\Entity\News;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class NewsViewTracker
{
    private const COOKIE_PREFIX = 'md_viewed_news_';
    private const COOKIE_TTL = 1800;

    private $connection;
    private $timezone;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->timezone = new \DateTimeZone('Asia/Ho_Chi_Minh');
    }

    public function track(News $post, Request $request)
    {
        if (!$this->shouldTrack($post, $request)) {
            return null;
        }

        $now = new \DateTime('now', $this->timezone);
        $viewDate = $now->format('Y-m-d');
        $nowValue = $now->format('Y-m-d H:i:s');

        $this->connection->executeUpdate(
            'UPDATE news SET viewCounts = viewCounts + 1 WHERE id = :id',
            ['id' => $post->getId()]
        );

        $this->connection->executeUpdate(
            'INSERT INTO news_view_stat (news_id, viewDate, views, createdAt, updatedAt)
             VALUES (:newsId, :viewDate, 1, :createdAt, :updatedAt)
             ON DUPLICATE KEY UPDATE views = views + 1, updatedAt = VALUES(updatedAt)',
            [
                'newsId' => $post->getId(),
                'viewDate' => $viewDate,
                'createdAt' => $nowValue,
                'updatedAt' => $nowValue,
            ]
        );

        return new Cookie(
            $this->getCookieName($post),
            '1',
            time() + self::COOKIE_TTL,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_LAX
        );
    }

    private function shouldTrack(News $post, Request $request)
    {
        if (!$post->getId() || !$post->isPublished()) {
            return false;
        }

        if (!$request->isMethod('GET') || $request->isXmlHttpRequest()) {
            return false;
        }

        if ($request->query->get('preview') || $request->query->get('preview_id')) {
            return false;
        }

        if ($request->cookies->has($this->getCookieName($post))) {
            return false;
        }

        if ($request->headers->has('DNT') && $request->headers->get('DNT') === '1') {
            return false;
        }

        if ($this->isBot($request)) {
            return false;
        }

        return true;
    }

    private function getCookieName(News $post)
    {
        return self::COOKIE_PREFIX . $post->getId();
    }

    private function isBot(Request $request)
    {
        $userAgent = strtolower((string) $request->headers->get('User-Agent'));

        if ($userAgent === '') {
            return true;
        }

        $patterns = [
            'bot',
            'crawl',
            'spider',
            'slurp',
            'google',
            'google-extended',
            'bingpreview',
            'bingbot',
            'duckduckbot',
            'baiduspider',
            'yandex',
            'sogou',
            'exabot',
            'facebot',
            'facebookexternalhit',
            'facebookbot',
            'twitterbot',
            'linkedinbot',
            'pinterest',
            'telegrambot',
            'whatsapp',
            'discordbot',
            'embedly',
            'quora link preview',
            'ahrefs',
            'semrush',
            'mj12bot',
            'dotbot',
            'petalbot',
            'bytespider',
            'gptbot',
            'chatgpt',
            'chatgpt-user',
            'oai-searchbot',
            'openai',
            'claudebot',
            'claude-web',
            'anthropic',
            'perplexitybot',
            'perplexity-user',
            'gemini',
            'bard',
            'ccbot',
            'commoncrawl',
            'meta-externalagent',
            'meta-externalfetcher',
            'amazonbot',
            'applebot',
            'tiktokspider',
            'uptime',
            'monitor',
            'headless',
            'phantom',
            'curl',
            'wget',
            'python',
            'java/',
            'go-http-client',
            'libwww',
            'php/',
        ];

        foreach ($patterns as $pattern) {
            if (strpos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
