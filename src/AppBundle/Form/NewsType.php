<?php

namespace AppBundle\Form;

use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\News;
use AppBundle\Form\Type\TagsInputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Vich\UploaderBundle\Form\Type\VichFileType;

class NewsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, [
                'attr' => ['class' => 'sluggable'],
                'label' => 'label.title',
            ])
            ->add('url', TextType::class, [
                'attr' => ['class' => 'url', 'readonly' => 'readonly'],
                'label' => 'label.url',
            ])
            ->add('enable', CheckboxType::class, [
                'required' => false,
                'label' => 'label.enable',
            ])
            ->add('imageFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'label.description',
            ])
            ->add('contents', TextareaType::class, [
                'attr' => ['class' => 'txt-ckeditor', 'data-height' => '500'],
                'label' => 'label.contents',
            ])
            ->add('ordering', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'ordering'],
                'label' => 'label.ordering',
            ])
            ->add('categoryPrimary', HiddenType::class, [
                'required' => false
            ])
            ->add('category', EntityType::class, [
                'required' => false,
                'label' => 'label.category',
                'class' => 'AppBundle:NewsCategory',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('tags', TagsInputType::class, [
                'attr' => ['data-role' => 'tagsinput'],
                'label' => 'label.tags',
                'required' => false,
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
            ->add('autoFulfillAddress', CheckboxType::class, [
                'required' => false,
                'label' => 'Auto Fulfill Address',
                'attr' => ['checked' => 'checked']
            ])
            ->add('qa', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => '10'],
                'label' => 'Q&A',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => News::class,
        ]);
    }
}
