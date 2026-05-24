<?php

namespace App\Breadcrumb;

class BreadcrumbManager
{
    private $items = [];

    public function addItem($text, $url = null, array $translationParameters = [], $translate = false)
    {
        $this->items[] = [
            'text' => $text,
            'url' => $url,
            'translationParameters' => $translationParameters,
            'translate' => $translate,
        ];
    }

    public function all()
    {
        return $this->items;
    }

    public function reset()
    {
        $this->items = [];
    }
}
