<?php

namespace App\Twig;

use App\Breadcrumb\BreadcrumbManager;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

class BreadcrumbExtension extends AbstractExtension
{
    private $breadcrumbs;
    private $twig;

    public function __construct(BreadcrumbManager $breadcrumbs, Environment $twig)
    {
        $this->breadcrumbs = $breadcrumbs;
        $this->twig = $twig;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('wo_breadcrumbs', [$this, 'getBreadcrumbs']),
            new TwigFunction('wo_render_breadcrumbs', [$this, 'renderBreadcrumbs']),
        ];
    }

    public function getBreadcrumbs()
    {
        return $this->breadcrumbs->all();
    }

    public function renderBreadcrumbs(array $options = [])
    {
        $html = $this->twig->render('breadcrumbs/microdata.html.twig', array_merge([
            'breadcrumbs' => $this->getBreadcrumbs(),
            'listId' => 'wo-breadcrumbs',
            'listClass' => 'breadcrumb',
            'itemClass' => '',
            'separator' => '',
            'separatorClass' => 'separator',
            'linkRel' => '',
            'translation_domain' => 'messages',
            'locale' => null,
        ], $options));

        return new Markup($html, 'UTF-8');
    }
}
