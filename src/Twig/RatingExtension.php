<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RatingExtension extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter('rating', array($this, 'renderRating'), array('is_safe' => array('html'))),
        );
    }

    public function renderRating($number, $max = 5, $starSize = '')
    {
        $stars = max(0, min((int) $max, (int) $number));
        $max = max(1, (int) $max);
        $html = '<div class="rating">';

        for ($i = 1; $i <= $max; $i++) {
            $class = $i <= $stars ? 'star-full' : 'star-empty';
            $html .= sprintf('<div class="%s %s"></div>', $class, htmlspecialchars((string) $starSize, ENT_QUOTES, 'UTF-8'));
        }

        return $html . '</div>';
    }
}
