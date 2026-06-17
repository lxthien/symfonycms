<?php

namespace AppBundle\Form;

use AppBundle\Entity\Media;
use AppBundle\Entity\MediaFolder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('alt', TextType::class, [
                'required' => false,
                'label' => 'Alt text',
            ])
            ->add('caption', TextareaType::class, [
                'required' => false,
                'label' => 'Caption',
                'attr' => ['rows' => 3],
            ])
            ->add('credit', TextType::class, [
                'required' => false,
                'label' => 'Credit',
            ])
            ->add('folder', EntityType::class, [
                'class' => MediaFolder::class,
                'choice_label' => 'name',
                'placeholder' => 'Không chọn folder',
                'required' => false,
                'label' => 'Folder',
            ])
            ->add('newFolder', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Folder mới',
            ])
            ->add('tagsText', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Tags',
                'attr' => ['placeholder' => 'VD: cong-trinh, noi-that'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}
