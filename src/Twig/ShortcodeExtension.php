<?php

namespace App\Twig;

use App\Service\PostShortcodeRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ShortcodeExtension extends AbstractExtension
{
    private $renderer;

    public function __construct(PostShortcodeRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('render_shortcodes', [$this, 'renderShortcodes'], ['is_safe' => ['html']]),
        ];
    }

    public function renderShortcodes(?string $content, ?int $excludePostId = null): string
    {
        return $this->renderer->render($content, $excludePostId);
    }
}

