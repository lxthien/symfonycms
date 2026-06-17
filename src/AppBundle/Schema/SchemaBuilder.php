<?php

namespace AppBundle\Schema;

use AppBundle\Entity\News;
use AppBundle\Entity\NewsCategory;
use AppBundle\Settings\SettingsManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class SchemaBuilder
{
    private $router;
    private $settings;
    private $uploaderHelper;

    public function __construct(
        UrlGeneratorInterface $router,
        SettingsManager $settings,
        UploaderHelper $uploaderHelper
    ) {
        $this->router = $router;
        $this->settings = $settings;
        $this->uploaderHelper = $uploaderHelper;
    }

    public function renderSiteGraph()
    {
        $siteUrl = $this->siteUrl();
        $siteName = $this->setting('siteName', 'Xây Dựng Minh Duy');
        $phone = $this->setting('hotLine2', '0982969379');
        $email = $this->setting('emailContact', 'ldminh1988@gmail.com');
        $address = $this->setting('companyAddress', '95/121 Đường Lê Văn Lương, P. Tân Hưng, TP.HCM');
        $customLocalBusiness = $this->setting('localBusiness');

        $sameAs = array_values(array_filter(array(
            $this->setting('linkToFacebook', 'https://www.facebook.com/minhduyconstruction/'),
            $this->setting('linkToTwitter', 'https://twitter.com/xaydungminhduy'),
            $this->setting('linkToYoutube', 'https://www.youtube.com/channel/UCwYmkaoSIRgpZo2Us4FzleQ'),
            $this->setting('linkToLinkedin', 'https://www.linkedin.com/in/xaydungminhduy/'),
            'https://www.pinterest.com/xaydungminhduy/',
            'https://xaydungminhduy.business.site/',
        )));

        $graph = array(
            array(
                '@type' => 'Organization',
                '@id' => $siteUrl . '/#organization',
                'url' => $siteUrl . '/',
                'name' => $siteName,
                'alternateName' => array(
                    'Xây Dựng Minh Duy',
                    'công ty xây dựng minh duy',
                    'Xây nhà trọn gói Xây Dựng Minh Duy',
                    'Sửa nhà trọn gói Xây Dựng Minh Duy',
                ),
                'logo' => array(
                    '@type' => 'ImageObject',
                    '@id' => $siteUrl . '/assets/images/logo.png',
                    'url' => $siteUrl . '/assets/images/logo.png',
                    'width' => 354,
                    'height' => 72,
                ),
                'sameAs' => $sameAs,
            ),
            array(
                '@type' => 'WebSite',
                '@id' => $siteUrl . '/#website',
                'url' => $siteUrl . '/',
                'name' => $siteName,
                'inLanguage' => 'vi-VN',
                'publisher' => array('@id' => $siteUrl . '/#organization'),
                'potentialAction' => array(
                    '@type' => 'SearchAction',
                    'target' => $siteUrl . '/search?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ),
            ),
            array(
                '@type' => 'Person',
                '@id' => $this->routeUrl('author', array('slug' => 'webmaster')) . '#person',
                'url' => $this->routeUrl('author', array('slug' => 'webmaster')),
                'name' => 'Lê Xuân Minh',
                'alternateName' => array('Lê Xuân Thúy', 'Minh Le'),
                'familyName' => 'Lê',
                'additionalName' => 'Xuân',
                'givenName' => 'Minh',
                'birthDate' => '1988-05-21',
                'description' => 'Lê Xuân Minh là CEO công ty Xây Dựng Minh Duy - là một đơn vị chuyên cung cấp dịch vụ thiết kế nhà, thi công xây dựng, sửa chữa nhà ở dân dụng, nhà công nghiệp.',
                'jobTitle' => array('CEO', 'Founder'),
                'email' => $email,
                'image' => $siteUrl . '/uploads/ckfinder/userfiles/files/leduyminh.jpg',
                'sameAs' => array_values(array_filter(array(
                    'https://www.facebook.com/profile.php?id=100011397406701',
                    'https://www.linkedin.com/in/lexuanminh/',
                    'https://www.pinterest.com/lexuanminhxaydungminhduy/',
                    'https://twitter.com/lexuanminh88',
                ))),
                'worksFor' => array('@id' => $siteUrl . '/#organization'),
            ),
        );

        if ($customLocalBusiness) {
            return $this->renderGraph($graph) . $this->renderRawJsonLd($customLocalBusiness);
        }

        $graph[] = array(
                '@type' => array('HomeAndConstructionBusiness', 'LocalBusiness'),
                '@id' => $siteUrl . '/#business',
                'name' => $siteName,
                'url' => $siteUrl,
                'telephone' => $phone,
                'email' => $email,
                'description' => $this->setting('siteNameDescription', 'Đơn vị thi công xây dựng, sửa chữa và cải tạo nhà ở tại TP.HCM.'),
                'priceRange' => '10000VND-100000000VND',
                'image' => $siteUrl . '/assets/images/logo.png',
                'logo' => $siteUrl . '/assets/images/logo.png',
                'hasMap' => 'https://www.google.com/maps?cid=12698937955444482750',
                'address' => array(
                    '@type' => 'PostalAddress',
                    'streetAddress' => $address,
                    'addressLocality' => 'Thành phố Hồ Chí Minh',
                    'addressRegion' => 'TP.HCM',
                    'addressCountry' => 'VN',
                    'postalCode' => '70000',
                ),
                'geo' => array(
                    '@type' => 'GeoCoordinates',
                    'latitude' => 10.7359837,
                    'longitude' => 106.7010309,
                ),
                'openingHoursSpecification' => array(
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                    'opens' => '07:30',
                    'closes' => '17:30',
                ),
                'contactPoint' => array(
                    '@type' => 'ContactPoint',
                    'url' => $siteUrl . '/lien-he',
                    'telephone' => $phone,
                    'contactType' => 'customer service',
                    'availableLanguage' => array('Vietnamese'),
                ),
                'founder' => array('@id' => $this->routeUrl('author', array('slug' => 'webmaster')) . '#person'),
                'sameAs' => $sameAs,
                'areaServed' => array('TP.HCM', 'Bình Dương', 'Đồng Nai', 'Long An', 'Bà Rịa - Vũng Tàu', 'Tây Ninh', 'Bình Phước', 'Tiền Giang'),
                'hasOfferCatalog' => $this->siteOfferCatalog(),
        );

        return $this->renderGraph($graph);
    }

    public function renderNews(News $post, array $context = array())
    {
        $url = $this->routeUrl('news_show', array('slug' => $post->getUrl()));
        $title = $this->first($post->getPageTitle(), $post->getTitle());
        $description = $this->first($post->getPageDescription(), $post->getDescription());
        $image = isset($context['image']) ? $context['image'] : $this->imageUrl($post, 'imageFile');
        $siteUrl = $this->siteUrl();

        $webPage = array(
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $title,
            'description' => $this->clean($description),
            'datePublished' => $this->date($this->firstDate($post->getPublishedAt(), $post->getCreatedAt())),
            'dateModified' => $this->date($post->getUpdatedAt()),
            'isPartOf' => array('@id' => $siteUrl . '/#website'),
            'inLanguage' => 'vi-VN',
        );

        if ($image) {
            $webPage['primaryImageOfPage'] = array('@id' => $image);
        }

        $graph = array($webPage);

        if (!$post->isPage()) {
            $article = array(
                '@type' => 'BlogPosting',
                '@id' => $url . '#blogposting',
                'mainEntityOfPage' => array('@id' => $url . '#webpage'),
                'headline' => $this->clean($post->getTitle()),
                'name' => $title,
                'description' => $this->clean($description),
                'datePublished' => $this->date($this->firstDate($post->getPublishedAt(), $post->getCreatedAt())),
                'dateModified' => $this->date($post->getUpdatedAt()),
                'author' => $this->author($post),
                'publisher' => array('@id' => $siteUrl . '/#organization'),
                'url' => $url,
                'inLanguage' => 'vi-VN',
                'wordCount' => isset($context['wordCount']) ? (int) $context['wordCount'] : $this->wordCount($post->getContents()),
                'commentCount' => isset($context['comments']) ? count($context['comments']) : 0,
            );

            if (isset($context['category']) && $context['category'] instanceof NewsCategory) {
                $article['articleSection'] = $context['category']->getName();
            }

            if ($image) {
                $article['image'] = array(
                    '@type' => 'ImageObject',
                    '@id' => $image,
                    'url' => $image,
                );
            }

            $keywords = $this->keywords($post);
            if (!empty($keywords)) {
                $article['keywords'] = $keywords;
            }

            $graph[] = $article;
        }

        return $this->renderGraph($graph) . $this->renderRawJsonLd($post->getQa());
    }

    public function renderCategory(NewsCategory $category, array $context = array())
    {
        if ($category->getSchemaMarkup()) {
            return $this->renderRawJsonLd($category->getSchemaMarkup());
        }

        $url = isset($context['baseUrl'])
            ? $context['baseUrl']
            : $this->categoryUrl($category);
        $title = $this->replaceYear($this->first($category->getPageTitle(), $category->getName()));
        $description = $this->replaceYear($this->first($category->getPageDescription(), $category->getDescription(), $category->getName()));
        $image = isset($context['image']) ? $context['image'] : $this->imageUrl($category, 'imageFile');

        $collection = array(
            '@type' => 'CollectionPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $title,
            'description' => $this->clean($description),
            'isPartOf' => array('@id' => $this->siteUrl() . '/#website'),
            'inLanguage' => 'vi-VN',
        );

        if ($image) {
            $collection['primaryImageOfPage'] = array('@id' => $image);
        }

        $items = array();
        if (isset($context['pagination'])) {
            $position = 1;
            foreach ($context['pagination'] as $post) {
                if (!$post instanceof News) {
                    continue;
                }

                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'url' => $this->routeUrl('news_show', array('slug' => $post->getUrl())),
                    'name' => $post->getTitle(),
                );
            }
        }

        if (!empty($items)) {
            $collection['mainEntity'] = array(
                '@type' => 'ItemList',
                'itemListElement' => $items,
            );
        }

        $graph = array($collection);

        if ($category->getContent()) {
            $graph[] = $this->serviceForCategory($category, $url, $image);
        }

        return $this->renderGraph($graph);
    }

    public function canonicalUrl($path)
    {
        return $this->absoluteAsset($path);
    }

    private function renderGraph(array $graph)
    {
        return $this->renderJsonLd(array(
            '@context' => 'https://schema.org',
            '@graph' => $this->withoutEmptyValues($graph),
        ));
    }

    private function renderJsonLd(array $schema)
    {
        return '<script type="application/ld+json">' . "\n"
            . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            . "\n" . '</script>' . "\n";
    }

    private function renderRawJsonLd($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }

        if (stripos($raw, '<script') !== false) {
            return "\n" . $raw . "\n";
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->renderJsonLd($decoded);
        }

        return '<script type="application/ld+json">' . "\n" . $raw . "\n" . '</script>' . "\n";
    }

    private function author(News $post)
    {
        $author = $post->getAuthor();
        $name = $author && method_exists($author, 'getName') ? $author->getName() : 'Lê Xuân Minh';
        $username = $author && method_exists($author, 'getUsername') ? $author->getUsername() : 'webmaster';

        return array(
            '@type' => 'Person',
            '@id' => $this->routeUrl('author', array('slug' => $username)) . '#person',
            'name' => $name,
        );
    }

    private function serviceForCategory(NewsCategory $category, $url, $image = null)
    {
        $title = $this->replaceYear($this->first($category->getPageTitle(), $category->getTitleLandingPage(), $category->getName()));
        $description = $this->replaceYear($this->first($category->getPageDescription(), $category->getDescription(), $category->getName()));

        $schema = array(
            '@type' => 'Service',
            '@id' => $url . '#service',
            'name' => $title,
            'url' => $url,
            'mainEntityOfPage' => array('@id' => $url . '#webpage'),
            'description' => $this->clean($description),
            'serviceType' => $category->getName(),
            'provider' => array('@id' => $this->siteUrl() . '/#business'),
            'brand' => array('@id' => $this->siteUrl() . '/#organization'),
            'category' => 'Home Improvement',
            'areaServed' => array(
                array('@type' => 'City', 'name' => 'Thành phố Hồ Chí Minh'),
                array('@type' => 'Country', 'name' => 'Vietnam'),
            ),
            'hasOfferCatalog' => $this->siteOfferCatalog(),
            'potentialAction' => array(
                array(
                    '@type' => 'TradeAction',
                    'url' => $this->routeUrl('contact'),
                    'name' => 'Nhận báo giá',
                ),
            ),
        );

        if ($image) {
            $schema['image'] = array($image);
        }

        return $schema;
    }

    private function siteOfferCatalog()
    {
        return array(
            '@type' => 'OfferCatalog',
            'name' => 'Dịch vụ của Xây Dựng Minh Duy',
            'itemListElement' => array(
                array(
                    '@type' => 'OfferCatalog',
                    'name' => 'Dịch vụ thi công xây dựng',
                    'itemListElement' => array(
                        $this->serviceOffer('Xây nhà trọn gói'),
                        $this->serviceOffer('Xây nhà phần thô'),
                        $this->serviceOffer('Thi công hoàn thiện'),
                        $this->serviceOffer('Xây nhà cấp 4'),
                        $this->serviceOffer('Thi công quán Cafe'),
                        $this->serviceOffer('Ép cọc bê tông'),
                        $this->serviceOffer('Thi công nhà xưởng'),
                    ),
                ),
                array(
                    '@type' => 'OfferCatalog',
                    'name' => 'Dịch vụ sửa chữa cải tạo nhà',
                    'itemListElement' => array(
                        $this->serviceOffer('Sửa nhà trọn gói'),
                        $this->serviceOffer('Sửa nhà nâng tầng'),
                        $this->serviceOffer('Cải tạo nhà chung cư'),
                        $this->serviceOffer('Sửa nhà cấp 4'),
                        $this->serviceOffer('Cải tạo mặt tiền nhà'),
                        $this->serviceOffer('Chống thấm chống dột'),
                        $this->serviceOffer('Sửa chữa điện nước'),
                    ),
                ),
            ),
        );
    }

    private function serviceOffer($name)
    {
        return array(
            '@type' => 'Offer',
            'itemOffered' => array(
                '@type' => 'Service',
                'name' => $name,
            ),
        );
    }

    private function imageUrl($entity, $field)
    {
        $asset = $this->uploaderHelper->asset($entity, $field);
        if (!$asset) {
            return $this->siteUrl() . '/assets/images/no-image.png';
        }

        return $this->absoluteAsset($asset);
    }

    private function categoryUrl(NewsCategory $category)
    {
        $parent = $category->getParentcat();
        if ($parent instanceof NewsCategory) {
            return $this->routeUrl('list_category', array(
                'level1' => $parent->getUrl(),
                'level2' => $category->getUrl(),
            ));
        }

        return $this->routeUrl('news_category', array('level1' => $category->getUrl()));
    }

    private function keywords(News $post)
    {
        $keywords = array();
        if ($post->getPageKeyword()) {
            $keywords = array_map('trim', explode(',', $post->getPageKeyword()));
        }

        foreach ($post->getTags() as $tag) {
            $keywords[] = $tag->getName();
        }

        return array_values(array_unique(array_filter($keywords)));
    }

    private function setting($name, $default = null)
    {
        try {
            return $this->settings->get($name, null, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }

    private function siteUrl()
    {
        $url = rtrim($this->setting('companyWebsite', 'https://xaydungminhduy.com'), '/');
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return preg_replace('#^(https?://)www\.#i', '$1', $url);
    }

    private function routeUrl($route, array $parameters = array())
    {
        return $this->siteUrl() . $this->router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    private function absoluteAsset($asset)
    {
        if (preg_match('#^https?://#i', $asset)) {
            return preg_replace('#^(https?://)www\.#i', '$1', $asset);
        }

        if (strpos($asset, '//') === 0) {
            return 'https:' . $asset;
        }

        return $this->siteUrl() . '/' . ltrim($asset, '/');
    }

    private function clean($value)
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    private function truncate($value, $length)
    {
        if (function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > $length) {
            return mb_substr($value, 0, $length, 'UTF-8');
        }

        return strlen($value) > $length ? substr($value, 0, $length) : $value;
    }

    private function wordCount($value)
    {
        $clean = $this->clean($value);
        return $clean === '' ? 0 : str_word_count($clean);
    }

    private function date(\DateTime $date = null)
    {
        return $date ? $date->format(\DateTime::ATOM) : null;
    }

    private function firstDate(\DateTime $primary = null, \DateTime $fallback = null)
    {
        return $primary ?: $fallback;
    }

    private function first()
    {
        foreach (func_get_args() as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function replaceYear($value)
    {
        return str_replace('%YEAR%', date('Y'), (string) $value);
    }

    private function withoutEmptyValues($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $filtered = array();
        foreach ($value as $key => $item) {
            $item = $this->withoutEmptyValues($item);
            if ($item === null || $item === '' || $item === array()) {
                continue;
            }
            $filtered[$key] = $item;
        }

        return $filtered;
    }
}
