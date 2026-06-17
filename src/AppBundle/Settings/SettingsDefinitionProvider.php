<?php

namespace AppBundle\Settings;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SettingsDefinitionProvider
{
    public function all()
    {
        return array(
            'siteName' => array('type' => TextType::class, 'options' => array('required' => false)),
            'listCategoryOnHomepage' => array('type' => TextareaType::class, 'options' => array('required' => false, 'attr' => array('rows' => 7))),
            'pageTitle' => array('type' => TextType::class, 'options' => array('required' => false)),
            'pageDescription' => array('type' => TextareaType::class, 'options' => array('required' => false)),
            'pageKeyword' => array('type' => TextType::class, 'options' => array('required' => false)),
            'isShowSortOnCategory' => array('type' => CheckboxType::class, 'options' => array('required' => false)),
            'isShowCommentOnPost' => array('type' => CheckboxType::class, 'options' => array('required' => false)),
            'numberRecordOnPage' => array('type' => NumberType::class, 'options' => array('required' => true)),
            'companyAddress' => array('type' => TextType::class, 'options' => array('required' => false)),
            'companyWebsite' => array('type' => TextType::class, 'options' => array('required' => false)),
            'emailContact' => array('type' => EmailType::class, 'options' => array('required' => false)),
            'hotLine1' => array('type' => TextType::class, 'options' => array('required' => false)),
            'hotLine2' => array('type' => TextType::class, 'options' => array('required' => false)),
            'marqueeHomepage' => array('type' => TextareaType::class, 'options' => array('required' => false, 'attr' => array('rows' => 7))),
            'googleAnalytic' => array('type' => TextareaType::class, 'options' => array('required' => false, 'attr' => array('rows' => 7))),
            'localBusiness' => array('type' => TextareaType::class, 'options' => array('required' => false, 'attr' => array('rows' => 7))),
            'topKeyword' => array('type' => TextareaType::class, 'options' => array('required' => false, 'attr' => array('rows' => 10))),
            'facebookMessenger' => array('type' => TextareaType::class, 'options' => array('required' => false, 'attr' => array('rows' => 10))),
            'displayNameCommentAs' => array('type' => TextType::class, 'options' => array('required' => false)),
            'linkToFacebook' => array('type' => TextType::class, 'options' => array('required' => false)),
            'linkToGooglePlus' => array('type' => TextType::class, 'options' => array('required' => false)),
            'linkToTwitter' => array('type' => TextType::class, 'options' => array('required' => false)),
            'linkToYoutube' => array('type' => TextType::class, 'options' => array('required' => false)),
            'linkToLinkedin' => array('type' => TextType::class, 'options' => array('required' => false)),
            'dmcaScript' => array('type' => TextareaType::class, 'options' => array('required' => false, 'attr' => array('rows' => 5))),
            'siteNameDescription' => array('type' => TextareaType::class, 'options' => array('required' => false, 'attr' => array('rows' => 5))),
            'addressInPost' => array('type' => TextareaType::class, 'options' => array('required' => false, 'attr' => array('rows' => 7))),
        );
    }

    public function groups()
    {
        return array(
            'general' => array(
                'siteName',
                'marqueeHomepage',
                'googleAnalytic',
                'topKeyword',
                'facebookMessenger',
                'listCategoryOnHomepage',
                'numberRecordOnPage',
                'displayNameCommentAs',
                'isShowSortOnCategory',
                'isShowCommentOnPost',
                'dmcaScript',
                'siteNameDescription',
                'addressInPost',
                'localBusiness',
            ),
            'seo' => array('pageTitle', 'pageDescription', 'pageKeyword'),
            'contact' => array('emailContact', 'hotLine1', 'hotLine2', 'companyAddress', 'companyWebsite'),
            'social_networks' => array('linkToFacebook', 'linkToGooglePlus', 'linkToTwitter', 'linkToYoutube', 'linkToLinkedin'),
        );
    }
}
