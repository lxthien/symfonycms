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
            'routeParameters' => ['slug' => 'thiet-ke-thi-cong-tron-goi-quan-cafe']
        ]);

        $menu->addChild('Xây dựng', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'xay-dung']
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');

        $menu['Xây dựng']->addChild('Xây nhà trọn gói', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'xay-dung', 'level2' => 'xay-nha-tron-goi']
        ]);

        $menu['Xây dựng']->addChild('Xây nhà phần thô', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'xay-dung', 'level2' => 'xay-nha-phan-tho']
        ]);

        $menu['Xây dựng']->addChild('Xây nhà cấp 4', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'xay-dung', 'level2' => 'xay-nha-cap-4']
        ]);

        $menu['Xây dựng']->addChild('Thiết kế, thi công quán Cafe', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'xay-dung', 'level2' => 'thiet-ke-va-thi-cong-quan-cafe']
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
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');

        $menu['Sửa chữa nhà']->addChild('Sửa nhà trọn gói', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'sua-chua-nha', 'level2' => 'sua-nha-tron-goi']
        ]);

        $menu['Sửa chữa nhà']->addChild('Sửa nhà chung cư', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'sua-chua-nha', 'level2' => 'sua-nha-chung-cu']
        ]);

        $menu['Sửa chữa nhà']->addChild('Sửa nhà nâng tầng', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'sua-chua-nha', 'level2' => 'sua-nha-nang-tang']
        ]);

        $menu['Sửa chữa nhà']->addChild('Dự án sửa chữa nhà', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'sua-chua-nha', 'level2' => 'sua-chua']
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

        $menu->addChild('Dịch vụ khác', [
            'uri' => '#'
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');

        $menu['Dịch vụ khác']->addChild('Sơn nhà trọn gói', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'dich-vu-son-nha-tron-goi']
        ]);

        $menu['Dịch vụ khác']->addChild('Phá dỡ nhà cũ', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'bao-gia-dich-vu-pha-do-nha-cu']
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
        $menu['Dự án thi công']->addChild('Thi công quán cafe, trà sữa', [
            'route' => 'list_category',
            'routeParameters' => ['level1' => 'du-an', 'level2' => 'quan-cafe-tra-sua']
        ]);

        $menu->addChild('Tin tức', [
            'uri' => '#'
        ])
        ->setAttribute('class', 'dropdown')
        ->setLinkAttribute('class', 'dropdown-toggle')
        ->setLinkAttribute('data-toggle', 'dropdown')
        ->setChildrenAttribute('class', 'dropdown-menu');

        $menu['Tin tức']->addChild('Tư vấn', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'tu-van']
        ]);

        $menu['Tin tức']->addChild('Phong thủy xây dựng', [
            'route' => 'news_category',
            'routeParameters' => ['level1' => 'phong-thuy-xay-dung']
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