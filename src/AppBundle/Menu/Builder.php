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

        $menu->addChild('<i class="fa fa-home"></i>', [
            'route' => 'homepage',
            'extras' => ['safe_label' => true]
        ])
        ->setLinkAttribute('class', 'home')
        ->setLinkAttribute('aria-label', 'Xây Dựng Minh Duy');

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

        $menu['Giới thiệu']->addChild('Tuyển dụng', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'tuyen-dung']
        ]);

        $menu->addChild('Bảng giá', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'bang-gia']
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');

        $menu['Bảng giá']->addChild('Xây nhà trọn gói', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'bao-gia-xay-nha-tron-goi']
        ]);

        $menu['Bảng giá']->addChild('Sửa nhà trọn gói', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'bao-gia-sua-nha-tron-goi']
        ]);

        $menu['Bảng giá']->addChild('Xây nhà phần thô', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'bang-gia-xay-dung-nha-phan-tho']
        ]);

        $menu['Bảng giá']->addChild('Sửa chữa căn hộ chung cư', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'dich-vu-bao-gia-sua-chua-can-ho-chung-cu']
        ]);

        $menu['Bảng giá']->addChild('Thiết kế nhà phố, biệt thự', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'bao-gia-thiet-ke-nha-pho']
        ]);
        
        $menu['Bảng giá']->addChild('Thiết kế, thi công quán cafe', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'bao-gia-thiet-ke-thi-cong-quan-cafe-tron-goi-tai-tphcm']
        ]);

        $menu['Bảng giá']->addChild('Thi công nội thất chung cư', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'don-gia-thi-cong-noi-that-chung-cu']
        ]);

        $menu->addChild('Xây dựng', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'xay-dung']
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');

        $menu['Xây dựng']->addChild('Xây dựng nhà tại TP HCM', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'xay-dung', 'level2' => 'xay-nha-tai-tp-hcm']
        ]);

        $menu['Xây dựng']->addChild('Xây dựng nhà phố', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'xay-dung']
        ]);

        $menu['Xây dựng']->addChild('Dự toán chi phí', [
            'route' => 'caculator_cost_construction'
        ]);

        $menu['Xây dựng']->addChild('Chính sách bảo hành', [
            'route' => 'news_show',
            'routeParameters' => ['slug' => 'chinh-sach-bao-hanh']
        ]);

        $menu->addChild('Sửa chữa nhà', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'sua-chua-nha']
        ]);

        $menu->addChild('Thiết kế', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'thiet-ke']
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');

        $menu['Thiết kế']->addChild('Thiết kế nhà phố', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'thiet-ke', 'level2' => 'thiet-ke-nha-pho']
        ]);
        
        $menu['Thiết kế']->addChild('Thiết kế quán cafe', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'thiet-ke', 'level2' => 'thiet-ke-thi-cong-quan-cafe']
        ]);

        $menu->addChild('Phong thủy xây dựng', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'phong-thuy-xay-dung']
        ]);

        $menu->addChild('Dự án thi công', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'du-an']
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');
        
        $menu['Dự án thi công']->addChild('Dự án xây dựng mới', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'du-an', 'level2' => 'xay-moi']
        ]);
        $menu['Dự án thi công']->addChild('Dự án sửa chữa nhà', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'du-an', 'level2' => 'sua-chua']
        ]);
        $menu['Dự án thi công']->addChild('Thi công quán cafe, trà sữa', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'du-an', 'level2' => 'quan-cafe-tra-sua']
        ]);

        $menu->addChild('Tư vấn', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'tu-van']
        ]);

        // Contact us
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