<?php

namespace AppBundle\Form;

use AppBundle\Entity\NewsCategory;
use AppBundle\Form\Type\DateTimePickerType;
use AppBundle\Form\Type\TagsInputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Vich\UploaderBundle\Form\Type\VichFileType;

class NewsCategoryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('parentcat', null, [
                'attr' => ['autofocus' => true],
                'label' => 'label.parentcat',
            ])
            ->add('name', TextType::class, [
                'attr' => ['class' => 'sluggable'],
                'label' => 'label.name',
            ])
            ->add('titleLandingPage', TextType::class, [
                'required' => false,
                'label' => 'Tiêu đề trang Landing',
            ])
            ->add('url', TextType::class, [
                'attr' => ['class' => 'url', 'readonly' => 'readonly'],
                'label' => 'label.url',
            ])
            ->add('imageFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => true,
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'txt-ckeditor', 'data-height' => '300'],
                'label' => 'label.description',
            ])
            ->add('content', TextareaType::class, [
                'attr' => ['class' => 'txt-ckeditor', 'data-height' => '600'],
                'label' => 'Nội dung',
            ])
            ->add('enable', CheckboxType::class, [
                'required' => false,
                'label' => 'label.enable',
            ])
            ->add('sortBy', ChoiceType::class, [
                'required' => false,
                'label' => 'label.sortBy',
                'choices' => ['label.default' => '{"createdAt":"desc"}', 'label.ordering' => '{"ordering":"asc"}'],
                'empty_data' => '"createdAt":"desc"}'
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
            ->add('isIndex', CheckboxType::class, [
                'required' => false,
                'label' => 'Index',
            ])
            ->add('isFollow', CheckboxType::class, [
                'required' => false,
                'label' => 'Follow',
            ])
            ->add('schemaMarkup', TextareaType::class, [
                'attr' => ['rows' => '12'],
                'required' => false,
                'label' => 'Schema Markup',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NewsCategory::class,
        ]);
    }
}
