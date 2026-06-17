<?php

namespace AppBundle\Twig;

use AppBundle\Entity\News;
use AppBundle\Entity\NewsCategory;
use AppBundle\Schema\SchemaBuilder;

class SchemaExtension extends \Twig_Extension
{
    private $schemaBuilder;

    public function __construct(SchemaBuilder $schemaBuilder)
    {
        $this->schemaBuilder = $schemaBuilder;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('schema_site', array($this, 'site'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('schema_news', array($this, 'news'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('schema_category', array($this, 'category'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('schema_url', array($this, 'url')),
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

    public function getName()
    {
        return 'app_schema_extension';
    }
}
