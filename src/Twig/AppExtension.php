<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Utils\Markdown;
use Symfony\Component\Intl\Intl;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * This Twig extension adds a new 'md2html' filter to easily transform Markdown
 * contents into HTML contents inside Twig templates.
 *
 * See https://symfony.com/doc/current/cookbook/templating/twig_extension.html
 *
 * In addition to creating the Twig extension class, before using it you must also
 * register it as a service. See config/services.yml file for details.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Julien ITARD <julienitard@gmail.com>
 */
class AppExtension extends AbstractExtension
{
    /**
     * @var Markdown
     */
    private $parser;

    /**
     * @var array
     */
    private $locales;

    public function __construct(Markdown $parser, $locales)
    {
        $this->parser = $parser;
        $this->locales = $locales;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('md2html', [$this, 'markdownToHtml'], ['is_safe' => ['html']]),
            new TwigFilter('localizeddate', [$this, 'localizedDate']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('locales', [$this, 'getLocales']),
        ];
    }

    /**
     * Transforms the given Markdown content into HTML content.
     *
     *  @param string $content
     *
     * @return string
     */
    public function markdownToHtml($content)
    {
        return $this->parser->toHtml($content);
    }

    /**
     * Compatibility replacement for the abandoned twig/extensions localizeddate filter.
     */
    public function localizedDate($date, $dateFormat = 'medium', $timeFormat = 'medium', $locale = null, $timezone = null, $pattern = null)
    {
        if (!$date) {
            return '';
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $date = new \DateTime((string) $date);
            } catch (\Exception $e) {
                return '';
            }
        }

        $date = \DateTime::createFromFormat('U', (string) $date->getTimestamp());

        if ($timezone) {
            try {
                $date->setTimezone(new \DateTimeZone($timezone));
            } catch (\Exception $e) {
                // Keep the original timezone if the template passes an invalid value.
            }
        }

        $format = $pattern ? $this->convertIcuDatePattern($pattern) : $this->resolveDateFormat($dateFormat, $timeFormat);

        return $date->format($format);
    }

    private function convertIcuDatePattern($pattern)
    {
        $replacements = array(
            'YYYY' => 'Y',
            'yyyy' => 'Y',
            'YY' => 'y',
            'dd' => 'd',
            'd' => 'j',
            'MM' => 'm',
            'M' => 'n',
            'HH' => 'H',
            'H' => 'G',
            'mm' => 'i',
            'ss' => 's',
        );

        return strtr($pattern, $replacements);
    }

    private function resolveDateFormat($dateFormat, $timeFormat)
    {
        $dateFormats = array(
            'none' => '',
            'short' => 'j/n/Y',
            'medium' => 'j/n/Y',
            'long' => 'j/n/Y',
            'full' => 'j/n/Y',
        );
        $timeFormats = array(
            'none' => '',
            'short' => 'H:i',
            'medium' => 'H:i',
            'long' => 'H:i:s',
            'full' => 'H:i:s',
        );

        $parts = array_filter(array(
            isset($dateFormats[$dateFormat]) ? $dateFormats[$dateFormat] : $dateFormats['medium'],
            isset($timeFormats[$timeFormat]) ? $timeFormats[$timeFormat] : $timeFormats['medium'],
        ));

        return implode(' ', $parts);
    }

    /**
     * Takes the list of codes of the locales (languages) enabled in the
     * application and returns an array with the name of each locale written
     * in its own language (e.g. English, Français, Español, etc.).
     *
     * @return array
     */
    public function getLocales()
    {
        $localeCodes = explode('|', $this->locales);

        $locales = [];
        foreach ($localeCodes as $localeCode) {
            $locales[] = ['code' => $localeCode, 'name' => Intl::getLocaleBundle()->getLocaleName($localeCode, $localeCode)];
        }

        return $locales;
    }
}
