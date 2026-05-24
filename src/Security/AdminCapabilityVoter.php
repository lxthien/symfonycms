<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminCapabilityVoter extends Voter
{
    private static $capabilitiesByRole = [
        'ROLE_SUPER_ADMIN' => ['*'],
        'ROLE_ADMIN' => ['*'],
        'ROLE_EDITOR' => [
            AdminCapability::ADMIN_ACCESS,
            AdminCapability::DASHBOARD_VIEW,
            AdminCapability::CONTENT_VIEW,
            AdminCapability::CONTENT_CREATE,
            AdminCapability::CONTENT_EDIT,
            AdminCapability::CONTENT_DELETE,
            AdminCapability::CONTENT_PUBLISH,
            AdminCapability::COMMENT_MANAGE,
            AdminCapability::MEDIA_MANAGE,
            AdminCapability::REDIRECT_MANAGE,
            AdminCapability::CONTENT_DECAY_VIEW,
            AdminCapability::HEALTH_VIEW,
        ],
        'ROLE_AUTHOR' => [
            AdminCapability::ADMIN_ACCESS,
            AdminCapability::DASHBOARD_VIEW,
            AdminCapability::CONTENT_VIEW,
            AdminCapability::CONTENT_CREATE,
            AdminCapability::CONTENT_EDIT,
            AdminCapability::MEDIA_MANAGE,
        ],
        'ROLE_CONTRIBUTOR' => [
            AdminCapability::ADMIN_ACCESS,
            AdminCapability::DASHBOARD_VIEW,
            AdminCapability::CONTENT_VIEW,
            AdminCapability::CONTENT_CREATE,
            AdminCapability::MEDIA_MANAGE,
        ],
        'ROLE_SEO' => [
            AdminCapability::ADMIN_ACCESS,
            AdminCapability::DASHBOARD_VIEW,
            AdminCapability::CONTENT_VIEW,
            AdminCapability::CONTENT_EDIT,
            AdminCapability::MEDIA_MANAGE,
            AdminCapability::REDIRECT_MANAGE,
            AdminCapability::CONTENT_DECAY_VIEW,
            AdminCapability::HEALTH_VIEW,
        ],
        'ROLE_SALES' => [
            AdminCapability::ADMIN_ACCESS,
            AdminCapability::DASHBOARD_VIEW,
            AdminCapability::COMMENT_MANAGE,
            AdminCapability::CONTACT_MANAGE,
        ],
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, AdminCapability::all(), true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        foreach ($user->getRoles() as $role) {
            if (!isset(self::$capabilitiesByRole[$role])) {
                continue;
            }

            $capabilities = self::$capabilitiesByRole[$role];

            if (in_array('*', $capabilities, true) || in_array($attribute, $capabilities, true)) {
                return true;
            }
        }

        return false;
    }
}
