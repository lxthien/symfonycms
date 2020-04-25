<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root', array(
            'childrenAttributes' => array (
                'class' => 'nav navbar-nav',
            ),
        ));

        /* $menu->addChild('<i class="fa fa-home"></i>', [
            'route' => 'homepage',
            'extras' => ['safe_label' => true]
        ])
        ->setLinkAttribute('class', 'home'); */

        $menu->addChild('Giới thiệu', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'gioi-thieu']
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');

        $menu['Giới thiệu']->addChild('Về chúng tôi', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'gioi-thieu']
        ]);

        $menu['Giới thiệu']->addChild('Chính sách bảo hành', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'chinh-sach-bao-hanh']
        ]);

        $menu['Giới thiệu']->addChild('Tuyển đại lý, cộng tác viên', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'tuyen-dai-ly-cong-tac-vien']
        ]);

        $menu->addChild('Chăm sóc da', [
            'route' => 'shop_category',
            'routeParameters' => ['level1' => 'cham-soc-da']
        ]);

        $menu->addChild('Chăm sóc tóc', [
            'route' => 'shop_category',
            'routeParameters' => ['level1' => 'cham-soc-toc']
        ]);

        $menu->addChild('Chăm sóc sức khỏe', [
            'route' => 'shop_category',
            'routeParameters' => ['level1' => 'cham-soc-suc-khoe']
        ]);

        $menu->addChild('Mẹ và bé', [
            'route' => 'shop_category',
            'routeParameters' => ['level1' => 'me-va-be']
        ]);

        $menu->addChild('Làm đẹp', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'lam-dep']
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');
        
        $menu['Làm đẹp']->addChild('Chăm sóc da', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'cham-soc-da']
        ]);
        $menu['Làm đẹp']->addChild('Chăm sóc tóc', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'cham-soc-toc']
        ]);

        $menu->addChild('Khuyến mãi', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'khuyen-mai']
        ]);

        $menu->addChild('Liên hệ', [
            'route' => 'contact'
        ]);

        return $menu;
    }

    public function footerMenu(FactoryInterface $factory, array $options)
    {
        $footerMenu = $factory->createItem('root');

        return $footerMenu;
    }
}