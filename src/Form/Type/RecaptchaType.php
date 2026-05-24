<?php

namespace App\Form\Type;

use App\Validator\Constraints\Recaptcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecaptchaType extends AbstractType
{
    private $publicKey;
    private $locale;

    public function __construct($publicKey, $locale)
    {
        $this->publicKey = $publicKey;
        $this->locale = $locale;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'constraints' => [
                new Recaptcha(),
            ],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['public_key'] = $this->publicKey;
        $view->vars['locale'] = $this->locale;
    }

    public function getParent()
    {
        return HiddenType::class;
    }

    public function getBlockPrefix()
    {
        return 'app_recaptcha';
    }
}
