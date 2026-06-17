<?php

namespace AppBundle\Audit;

use AppBundle\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuditLogManager
{
    private $connection;
    private $requestStack;
    private $tokenStorage;

    public function __construct(Connection $connection, RequestStack $requestStack, TokenStorageInterface $tokenStorage)
    {
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
    }

    public function log($action, $entityType, $entityId = null, $entityLabel = null, array $changes = array())
    {
        if (empty($changes) && $action === 'update') {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $user = $this->getUser();

        try {
            $this->connection->insert('audit_log', array(
                'action' => $action,
                'entityType' => $entityType,
                'entityId' => $entityId !== null ? (string) $entityId : null,
                'entityLabel' => $entityLabel !== null ? $this->truncate((string) $entityLabel, 255) : null,
                'changes' => json_encode($this->sanitizeChanges($changes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'user_id' => $user ? $user->getId() : null,
                'username' => $user ? $user->getUsername() : null,
                'ip' => $request ? $request->getClientIp() : null,
                'userAgent' => $request ? $this->truncate($request->headers->get('User-Agent'), 1000) : null,
                'route' => $request ? $request->attributes->get('_route') : null,
                'requestMethod' => $request ? $request->getMethod() : null,
                'requestUri' => $request ? $this->truncate($request->getRequestUri(), 1000) : null,
                'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            ));
        } catch (\Exception $e) {
            // Audit logging must never block the primary CMS action.
        }
    }

    public function logSettings(array $before, array $after)
    {
        $changes = array();
        foreach ($after as $name => $value) {
            $oldValue = array_key_exists($name, $before) ? $before[$name] : null;
            if ($oldValue === $value) {
                continue;
            }

            $changes[$name] = array(
                'old' => $oldValue,
                'new' => $value,
            );
        }

        $this->log('update', 'settings', null, 'Global settings', $changes);
    }

    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            return null;
        }

        return $token->getUser();
    }

    private function sanitizeChanges(array $changes)
    {
        foreach ($changes as $field => $change) {
            if ($this->isSensitiveField($field)) {
                $changes[$field] = array('old' => '[redacted]', 'new' => '[redacted]');
                continue;
            }

            if (is_array($change)) {
                if (array_key_exists('old', $change)) {
                    $change['old'] = $this->sanitizeValue($change['old']);
                }
                if (array_key_exists('new', $change)) {
                    $change['new'] = $this->sanitizeValue($change['new']);
                }
                $changes[$field] = $change;
            }
        }

        return $changes;
    }

    private function sanitizeValue($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format(\DateTime::ATOM);
        }

        if (is_scalar($value) || $value === null) {
            return is_string($value) ? $this->truncate($value, 20000) : $value;
        }

        return $value;
    }

    private function isSensitiveField($field)
    {
        return (bool) preg_match('/password|salt|token|secret|credential/i', (string) $field);
    }

    private function truncate($value, $max)
    {
        $value = (string) $value;
        if (function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > $max) {
            return mb_substr($value, 0, $max, 'UTF-8') . '...[truncated]';
        }

        return strlen($value) > $max ? substr($value, 0, $max) . '...[truncated]' : $value;
    }
}
