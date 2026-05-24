<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalDateTimeType extends AbstractType
{
    const FORMAT = 'Y-m-d H:i';
    const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                if (!$value instanceof \DateTimeInterface) {
                    return '';
                }

                return $value->format(self::FORMAT);
            },
            function ($value) {
                $value = trim((string) $value);

                if ($value === '') {
                    return null;
                }

                $date = \DateTime::createFromFormat('!' . self::FORMAT, $value, new \DateTimeZone(self::TIMEZONE));
                $errors = \DateTime::getLastErrors();

                $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

                if (!$date || $hasErrors || $date->format(self::FORMAT) !== $value) {
                    throw new TransformationFailedException('Ngày đặt lịch không hợp lệ. Dùng định dạng YYYY-MM-DD HH:mm.');
                }

                return $date;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'invalid_message' => 'Ngày đặt lịch không hợp lệ. Dùng định dạng YYYY-MM-DD HH:mm.',
        ]);
    }

    public function getParent()
    {
        return TextType::class;
    }
}
