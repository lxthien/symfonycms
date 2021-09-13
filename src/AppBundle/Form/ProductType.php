<?php

namespace AppBundle\Form;

use AppBundle\Entity\ProductCat;
use AppBundle\Entity\Product;
use AppBundle\Form\Type\TagsInputType;
use AppBundle\Form\ProductImageType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Vich\UploaderBundle\Form\Type\VichFileType;

class ProductType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'attr' => ['class' => 'sluggable'],
                'label' => 'label.name',
            ])
            ->add('url', TextType::class, [
                'attr' => ['class' => 'url', 'readonly' => 'readonly'],
                'label' => 'label.url',
            ])
            ->add('enable', CheckboxType::class, [
                'required' => false,
                'label' => 'label.enable',
            ])
            ->add('isHot', CheckboxType::class, [
                'required' => false,
                'label' => 'Sản phẩm nổi bật',
            ])
            ->add('productCat', null, [
                'required' => false,
                'label' => 'label.category',
            ])
            ->add('imageFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'label.description',
            ])
            ->add('contents', TextareaType::class, [
                'required' => true,
                'attr' => ['class' => 'txt-ckeditor', 'data-height' => '500'],
                'label' => 'label.contents',
            ])
            ->add('productImages', CollectionType::class, array(
                'entry_type'   		=> ProductImageType::class,
                'prototype'			=> true,
                'allow_add'			=> true,
                'allow_delete'		=> true,
                'by_reference' 		=> false,
                'required'			=> false,
                'label'				=> true,
            ))
            ->add('regularPrice', null, [
                'required' => false,
                'label' => 'Giá',
            ])
            ->add('salePrice', null, [
                'required' => false,
                'label' => 'Giá sau giảm',
            ])
            ->add('pageTitle', TextType::class, [
                'required' => false,
                'label' => 'label.pageTitle',
            ])
            ->add('pageDescription', TextareaType::class, [
                'required' => false,
                'label' => 'label.pageDescription',
            ])
            ->add('pageKeyword', TextType::class, [
                'required' => false,
                'label' => 'label.pageKeyword',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
