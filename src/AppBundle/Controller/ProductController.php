<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use AppBundle\Entity\ProductCat;
use AppBundle\Entity\Product;
use AppBundle\Entity\ProductImage;
use AppBundle\Entity\News;
use AppBundle\Entity\Rating;
use AppBundle\Entity\QuickOrder;

use blackknight467\StarRatingBundle\Form\RatingType as RatingType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ProductController extends Controller
{
    /**
     * @var UploaderHelper
     */
    private $helper;

    /**
     * Constructs a new instance of UploaderExtension.
     *
     * @param UploaderHelper $helper
     */
    public function __construct(UploaderHelper $helper)
    {
        $this->helper = $helper;
    }
    
    /**
     * Render the list products of the category
     * 
     * @return Product
     */
    public function listAction($level1 = null, $page = 1)
    {
        if (!empty($level1)) {
            $category = $this->getDoctrine()
                ->getRepository(ProductCat::class)
                ->findOneBy(array('url' => $level1, 'enable' => 1));

            if (!$category) {
                throw $this->createNotFoundException("The item does not exist");
            }

            // Init breadcrum
            $breadcrumbs = $this->buildBreadcrums($category, null, null);

            $products = $this->getDoctrine()
                ->getRepository(Product::class)
                ->createQueryBuilder('n')
                ->innerJoin('n.productCat', 't')
                ->where('t.id = :productcat_id')
                ->andWhere('n.enable = :enable')
                ->setParameter('productcat_id', $category->getId())
                ->setParameter('enable', 1)
                ->orderBy('n.createdAt', 'DESC')
                ->getQuery()->getResult();
        } else {
            // Init breadcrum
            $breadcrumbs = $this->buildBreadcrums(null, null, null);

            $products = $this->getDoctrine()
                ->getRepository(Product::class)
                ->createQueryBuilder('n')
                ->where('n.enable = :enable')
                ->setParameter('enable', 1)
                ->orderBy('n.createdAt', 'DESC')
                ->getQuery()->getResult();
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $products,
            $page,
            $this->get('settings_manager')->get('numberRecordOnPage') ? $this->get('settings_manager')->get('numberRecordOnPage') : 10
        );

        foreach ($pagination as $row) {
            $repositoryRating = $this->getDoctrine()->getManager();
            $queryRating = $repositoryRating->createQuery(
                'SELECT AVG(r.rating) as ratingValue
                FROM AppBundle:Rating r
                WHERE r.product = :product_id'
            )->setParameter('product_id', $row->getId());
            $rating = $queryRating->setMaxResults(1)->getOneOrNullResult();

            $ratingValue = !empty($rating['ratingValue']) ? str_replace('.0', '', number_format($rating['ratingValue'], 1)) : 0;

            $row->ratingNumber = $ratingValue;
        }

        if (!empty($level1)) {
            return $this->render('product/list.html.twig', [
                'category' => $category,
                'pagination' => $pagination
            ]);
        } else {
            return $this->render('product/shop.html.twig', [
                'pagination' => $pagination
            ]);
        }
    }

    /**
     * @Route("san-pham/{slug}.html",
     *      defaults={"_format"="html"},
     *      name="product_show",
     *      requirements={
     *          "slug": "[^/\.]++"
     *      })
     */
    public function showAction($slug, Request $request)
    {
        if ($request->query->get('preview') === false || $request->query->get('preview_id') === null) {
            $product = $this->getDoctrine()
                ->getRepository(Product::class)
                ->findOneBy(
                    array('url' => $slug, 'enable' => 1)
                );
        } else {
            $product = $this->getDoctrine()
                ->getRepository(Product::class)
                ->find($request->query->get('preview_id'));
        }

        if (!$product) {
            throw $this->createNotFoundException("The item does not exist");
        }

        $shippingAndReturn = $this->getDoctrine()
            ->getRepository(News::class)
            ->findOneBy(
                array('url' => 'chinh-sach-giao-hang-va-tra-hang', 'enable' => 1)
            );

        $productImages = $this->getDoctrine()
            ->getRepository(ProductImage::class)
            ->createQueryBuilder('p')
            ->where('p.product = :product_id')
            ->setParameter('product_id', $product->getId())
            ->getQuery()->getResult();

        
        // Render the form product rating
        $formRating = $this->createFormBuilder(null, array(
                'csrf_protection' => false,
            ))
            ->setAction($this->generateUrl('rating'))
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Tên',
            ])
            ->add('email', TextType::class, [
                'required' => true,
                'label' => 'Email',
            ])
            ->add('rating', RatingType::class, [
                'required' => true,
                'label' => 'Rating',
            ])
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'Tiêu đề đánh giá',
            ])
            ->add('contents', TextareaType::class, [
                'required' => true,
                'label' => 'Nội dung đánh giá',
            ])
            ->add('send', ButtonType::class, array('label' => 'label.send'))
            ->getForm();

        // Render the form checkout
        $formCheckout = $this->createFormBuilder(null, array(
            'csrf_protection' => false,
            ))
            ->setAction($this->generateUrl('rating'))
            ->add('customerGender', ChoiceType::class, [
                'required' => true,
                'choices' => array('Name' => '0', 'Nữ' => '1'),
                'data' => 0,
                'choices_as_values' => true,
                'expanded' => true,
                'label' => 'Giới tính'
            ])
            ->add('customerName', TextType::class, [
                'required' => true,
                'label' => 'Tên',
            ])
            ->add('customerPhone', TextType::class, [
                'required' => true,
                'label' => 'Số điện thoại',
            ])
            ->add('customerEmail', TextType::class, [
                'required' => false,
                'label' => 'Địa chị email',
            ])
            ->add('customerAddress', TextareaType::class, [
                'required' => true,
                'label' => 'Địa chỉ',
            ])
            ->add('send', ButtonType::class, array('label' => 'Đặt hàng ngay'))
            ->getForm();

        // Get rating of the post
        $repositoryRating = $this->getDoctrine()->getManager();

        $queryRating = $repositoryRating->createQuery(
            'SELECT AVG(r.rating) as ratingValue, COUNT(r) as ratingCount
            FROM AppBundle:Rating r
            WHERE r.product = :product_id'
        )->setParameter('product_id', $product->getId());

        $rating = $queryRating->setMaxResults(1)->getOneOrNullResult();

        $listOfRating = $this->getDoctrine()
            ->getRepository(Rating::class)
            ->createQueryBuilder('r2')
            ->where('r2.product = :product_id')
            ->setParameter('product_id', $product->getId())
            ->getQuery()->getResult();
        
        // Init breadcrum for the post
        $breadcrumbs = $this->buildBreadcrums($product->getProductCat()[0], $product, null);

        $imagePath = $this->helper->asset($product, 'imageFile');
        $imagePath = substr($imagePath, 1);
        $imageSize = getimagesize($imagePath);

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'shippingAndReturn' => $shippingAndReturn,
            'productImages'     => $productImages,
            'imageSize'         => $imageSize,
            'formRating'        => $formRating->createView(),
            'formCheckout'      => $formCheckout->createView(),
            'rating'            => !empty($rating['ratingValue']) ? str_replace('.0', '', number_format($rating['ratingValue'], 1)) : 0,
            'listOfRating'      => $listOfRating
        ]);
    }

    /**
     * Render the list product hot
     * @return Product
     */
    public function productHotAction()
    {
        $products = $this->getDoctrine()
            ->getRepository(Product::class)
            ->createQueryBuilder('n')
            ->where('n.enable = :enable')
            ->andWhere('n.isHot = :isHot')
            ->setParameter('enable', 1)
            ->setParameter('isHot', 1)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()->getResult();

        foreach ($products as $row) {
            $repositoryRating = $this->getDoctrine()->getManager();
            $queryRating = $repositoryRating->createQuery(
                'SELECT AVG(r.rating) as ratingValue
                FROM AppBundle:Rating r
                WHERE r.product = :product_id'
            )->setParameter('product_id', $row->getId());
            $rating = $queryRating->setMaxResults(1)->getOneOrNullResult();

            $ratingValue = !empty($rating['ratingValue']) ? str_replace('.0', '', number_format($rating['ratingValue'], 1)) : 0;

            $row->ratingNumber = $ratingValue;
        }

        return $this->render('product/productHot.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * @Route("/checkout", name="checkout")
     * 
     * @return JSON
     */
    public function checkoutAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->find($request->request->get('productId'));

        $quickOrder = new QuickOrder();
        $quickOrder->setProduct( $product );
        $quickOrder->setQty( $request->request->get('quantity') );
        $quickOrder->setCustomerGender( $request->request->get('form')["customerGender"] );
        $quickOrder->setCustomerName( $request->request->get('form')["customerName"] );
        $quickOrder->setCustomerPhone( $request->request->get('form')["customerPhone"] );
        $quickOrder->setCustomerEmail( $request->request->get('form')["customerEmail"] );
        $quickOrder->setCustomerAddress( $request->request->get('form')["customerAddress"] );

        $em->persist($quickOrder);

        $em->flush();
        
        return new Response(
            json_encode(
                array(
                    'status'=>'success',
                    'message' => 'Đơn hàng của bạn đã được đặt thành công. Chúng tôi sẽ kiểm tra và liên hệ đến bạn để xác nhận đơn hàng'
                )
            )
        );
    }

    /**
     * Handle the breadcrumb
     * 
     * @return Breadcrums
     **/
    private function buildBreadcrums($productCat = null, $product = null)
    {
        // Init october breadcrum
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        
        // Add home item into first breadcrum.
        $breadcrumbs->addItem("home", $this->generateUrl("homepage"));
        
        // Breadcrum for category page
        if (!empty($productCat)) {
            if ($productCat->getParentcat() === 'root') {
                $breadcrumbs->addItem($productCat->getName(), $this->generateUrl("shop_category", array('level1' => $productCat->getUrl() )));
            } else {
                //$breadcrumbs->addItem($productCat->getParentcat()->getName(), $this->generateUrl("news_category", array('level1' => $productCat->getParentcat()->getUrl() )));
                //$breadcrumbs->addItem($productCat->getName(), $this->generateUrl("list_category", array('level1' => $productCat->getParentcat()->getUrl(), 'level2' => $productCat->getUrl() )));
            }
        } else {
            $breadcrumbs->addItem("Cửa hàng Lily house", $this->generateUrl("shop"));
        }

        // Breadcrum for post page
        if (!empty($product)) {
            $breadcrumbs->addItem($product->getName(), $this->generateUrl('product_show', array('slug' => $product->getUrl())) );
        }

        return $breadcrumbs;
    }
}