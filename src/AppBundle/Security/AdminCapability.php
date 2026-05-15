<?php

namespace AppBundle\Security;

final class AdminCapability
{
    const ADMIN_ACCESS = 'CMS_ADMIN_ACCESS';
    const DASHBOARD_VIEW = 'CMS_DASHBOARD_VIEW';
    const CONTENT_VIEW = 'CMS_CONTENT_VIEW';
    const CONTENT_CREATE = 'CMS_CONTENT_CREATE';
    const CONTENT_EDIT = 'CMS_CONTENT_EDIT';
    const CONTENT_DELETE = 'CMS_CONTENT_DELETE';
    const CONTENT_PUBLISH = 'CMS_CONTENT_PUBLISH';
    const COMMENT_MANAGE = 'CMS_COMMENT_MANAGE';
    const MEDIA_MANAGE = 'CMS_MEDIA_MANAGE';
    const REDIRECT_MANAGE = 'CMS_REDIRECT_MANAGE';
    const CONTENT_DECAY_VIEW = 'CMS_CONTENT_DECAY_VIEW';
    const BANNER_MANAGE = 'CMS_BANNER_MANAGE';
    const CONTACT_MANAGE = 'CMS_CONTACT_MANAGE';
    const SETTINGS_MANAGE = 'CMS_SETTINGS_MANAGE';
    const USER_MANAGE = 'CMS_USER_MANAGE';

    public static function all()
    {
        return [
            self::ADMIN_ACCESS,
            self::DASHBOARD_VIEW,
            self::CONTENT_VIEW,
            self::CONTENT_CREATE,
            self::CONTENT_EDIT,
            self::CONTENT_DELETE,
            self::CONTENT_PUBLISH,
            self::COMMENT_MANAGE,
            self::MEDIA_MANAGE,
            self::REDIRECT_MANAGE,
            self::CONTENT_DECAY_VIEW,
            self::BANNER_MANAGE,
            self::CONTACT_MANAGE,
            self::SETTINGS_MANAGE,
            self::USER_MANAGE,
        ];
    }

    public static function roleLabels()
    {
        return [
            'Super Admin' => 'ROLE_SUPER_ADMIN',
            'Admin' => 'ROLE_ADMIN',
            'Editor' => 'ROLE_EDITOR',
            'Author' => 'ROLE_AUTHOR',
            'Contributor' => 'ROLE_CONTRIBUTOR',
            'SEO' => 'ROLE_SEO',
            'Sales' => 'ROLE_SALES',
        ];
    }
}
