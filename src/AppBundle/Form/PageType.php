<?php

namespace AppBundle\Form;

use AppBundle\Entity\News;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PageType extends AbstractType
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
                'attr' => ['class' => 'url slug-field'],
                'label' => 'label.url',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Bản nháp' => News::STATUS_DRAFT,
                    'Chờ duyệt' => News::STATUS_PENDING_REVIEW,
                    'Đặt lịch' => News::STATUS_SCHEDULED,
                    'Đã xuất bản' => News::STATUS_PUBLISHED,
                    'Lưu trữ' => News::STATUS_ARCHIVED,
                ],
                'label' => 'Trạng thái',
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Ngày đặt lịch',
                'attr' => ['class' => 'js-scheduled-at'],
            ])
            ->add('editorialNotes', TextareaType::class, [
                'required' => false,
                'label' => 'Ghi chú biên tập',
                'attr' => ['rows' => 3],
            ])
            ->add('imageFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => true,
            ])
            ->add('mediaImageId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('albumItems', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'label.description',
            ])
            ->add('contents', TextareaType::class, [
                'attr' => ['class' => 'txt-ckeditor', 'data-height' => '500'],
                'label' => 'label.contents',
            ])
            ->add('postType', HiddenType::class, [
                'label' => 'label.postType',
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
