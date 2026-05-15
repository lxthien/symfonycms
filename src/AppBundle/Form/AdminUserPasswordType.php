<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminUserPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'required' => true,
            'first_options' => ['label' => 'Mật khẩu mới'],
            'second_options' => ['label' => 'Nhập lại mật khẩu mới'],
            'invalid_message' => 'Hai mật khẩu không khớp.',
            'constraints' => [
                new NotBlank(['message' => 'Vui lòng nhập mật khẩu mới.']),
                new Length([
                    'min' => 8,
                    'minMessage' => 'Mật khẩu nên có ít nhất {{ limit }} ký tự.',
                ]),
            ],
        ]);
    }
}
