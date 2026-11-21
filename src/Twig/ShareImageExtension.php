<?php

namespace App\Twig;

use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ShareImageExtension extends AbstractExtension
{
    private FilterService $filterService;
    private string $publicDir;

    public function __construct(FilterService $filterService, KernelInterface $kernel)
    {
        $this->filterService = $filterService;
        $this->publicDir = rtrim($kernel->getProjectDir(), '/\\') . '/public';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('share_image', [$this, 'shareImage']),
        ];
    }

    /**
     * Tạo bản JPEG cắt giữa theo filter (mặc định 1200x630) cho og:image,
     * trả về URL tĩnh đã cache kèm kích thước thật và mime type.
     *
     * @return array{url: string, width: int, height: int, type: string}|null
     */
    public function shareImage(?string $path, string $filter = 'news_share_1200_630'): ?array
    {
        $relative = $this->localPath($path);
        if ($relative === null) {
            return null;
        }

        try {
            $url = $this->filterService->getUrlOfFilteredImage($relative, $filter);
        } catch (\Throwable $e) {
            return null;
        }

        $urlPath = (string) parse_url($url, PHP_URL_PATH);
        $file = $this->publicDir . '/' . ltrim($urlPath, '/');
        $info = is_file($file) ? @getimagesize($file) : false;

        $width = is_array($info) && !empty($info[0]) ? (int) $info[0] : 1200;
        $height = is_array($info) && !empty($info[1]) ? (int) $info[1] : 630;
        $mime = is_array($info) && !empty($info['mime']) ? (string) $info['mime'] : 'image/jpeg';

        return [
            'url' => $urlPath !== '' ? $urlPath : $url,
            'width' => $width,
            'height' => $height,
            'type' => $mime,
        ];
    }

    private function localPath(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }

        $relative = ltrim(rawurldecode($path), '/');
        if ($relative === '' || strpos($relative, '..') !== false) {
            return null;
        }

        return is_file($this->publicDir . '/' . $relative) ? $relative : null;
    }
}
