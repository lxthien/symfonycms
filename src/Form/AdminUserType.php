<?php

namespace App\Form;

use App\Entity\User;
use App\Security\AdminCapability;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roles = AdminCapability::roleLabels();
        $roles['User only'] = 'ROLE_USER';

        if (!$options['allow_super_admin']) {
            unset($roles['Super Admin']);
        }

        $builder
            ->add('username', TextType::class, [
                'label' => 'label.username',
            ])
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ])
            ->add('email', EmailType::class, [
                'label' => 'label.email',
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $roles,
                'expanded' => true,
                'multiple' => true,
                'label' => 'label.role',
            ])
        ;

        if ($options['allow_super_admin']) {
            $builder->add('enabled', CheckboxType::class, [
                'required' => false,
                'label' => 'Hoạt động',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'allow_super_admin' => false,
        ]);

        $resolver->setAllowedTypes('allow_super_admin', 'bool');
    }
}
