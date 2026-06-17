<?php

namespace AppBundle\Form;

use AppBundle\Entity\MediaFolder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('files', FileType::class, [
                'label' => 'Chọn file',
                'multiple' => true,
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/gif,image/webp,application/pdf',
                ],
            ])
            ->add('folder', EntityType::class, [
                'class' => MediaFolder::class,
                'choice_label' => 'name',
                'placeholder' => 'Không chọn folder',
                'required' => false,
                'mapped' => false,
            ])
            ->add('newFolder', TextType::class, [
                'label' => 'Folder mới',
                'required' => false,
                'mapped' => false,
            ])
            ->add('tagsText', TextType::class, [
                'label' => 'Tags',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'VD: cong-trinh, noi-that'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
