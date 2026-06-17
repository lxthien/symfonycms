<?php

namespace AppBundle\Form;

use AppBundle\Entity\SeoRedirect;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeoRedirectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sourcePath', TextType::class, [
                'label' => 'URL cũ',
                'attr' => ['placeholder' => '/url-cu.html'],
            ])
            ->add('targetPath', TextType::class, [
                'label' => 'URL mới',
                'attr' => ['placeholder' => '/url-moi.html'],
            ])
            ->add('statusCode', ChoiceType::class, [
                'label' => 'Mã chuyển hướng',
                'choices' => [
                    '301 - Permanent' => 301,
                    '302 - Temporary' => 302,
                ],
            ])
            ->add('enable', CheckboxType::class, [
                'required' => false,
                'label' => 'Enable',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SeoRedirect::class,
        ]);
    }
}
