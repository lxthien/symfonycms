<?php

namespace App\Twig;

use App\Entity\News;
use App\Entity\NewsCategory;
use App\Schema\SchemaBuilder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SchemaExtension extends AbstractExtension
{
    private $schemaBuilder;

    public function __construct(SchemaBuilder $schemaBuilder)
    {
        $this->schemaBuilder = $schemaBuilder;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('schema_site', array($this, 'site'), array('is_safe' => array('html'))),
            new TwigFunction('schema_news', array($this, 'news'), array('is_safe' => array('html'))),
            new TwigFunction('schema_category', array($this, 'category'), array('is_safe' => array('html'))),
            new TwigFunction('schema_url', array($this, 'url')),
        );
    }

    public function site()
    {
        return $this->schemaBuilder->renderSiteGraph();
    }

    public function news(News $post, array $context = array())
    {
        return $this->schemaBuilder->renderNews($post, $context);
    }

    public function category(NewsCategory $category, array $context = array())
    {
        return $this->schemaBuilder->renderCategory($category, $context);
    }

    public function url($path)
    {
        return $this->schemaBuilder->canonicalUrl($path);
    }

}
