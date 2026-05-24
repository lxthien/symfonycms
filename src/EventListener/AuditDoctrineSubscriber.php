<?php

namespace App\EventListener;

use App\Entity\Comment;
use App\Entity\Media;
use App\Entity\News;
use App\Entity\NewsCategory;
use App\Entity\SeoRedirect;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuditDoctrineSubscriber implements EventSubscriber
{
    private $requestStack;
    private $tokenStorage;
    private $pendingLogs = array();

    private $ignoredFields = array('createdAt', 'updatedAt', 'lastAccessedAt', 'hits');

    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
    }

    public function getSubscribedEvents()
    {
        return array(Events::onFlush, Events::postFlush);
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->isTracked($entity)) {
                continue;
            }

            $this->pendingLogs[] = array(
                'action' => 'create',
                'entity' => $entity,
                'changes' => $this->buildCreateChanges($em->getClassMetadata(get_class($entity)), $entity),
            );
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->isTracked($entity)) {
                continue;
            }

            $changes = $this->normalizeChangeSet($uow->getEntityChangeSet($entity));
            if (empty($changes)) {
                continue;
            }

            $this->pendingLogs[] = array(
                'action' => 'update',
                'entity' => $entity,
                'changes' => $changes,
            );
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$this->isTracked($entity)) {
                continue;
            }

            $this->pendingLogs[] = array(
                'action' => 'delete',
                'entity' => $entity,
                'entityId' => $this->entityId($entity),
                'entityLabel' => $this->entityLabel($entity),
                'changes' => $this->buildDeleteChanges($em->getClassMetadata(get_class($entity)), $entity),
            );
        }

        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            $this->collectCollectionChange($collection);
        }

        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            $this->collectCollectionChange($collection);
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if (empty($this->pendingLogs)) {
            return;
        }

        $logs = $this->pendingLogs;
        $this->pendingLogs = array();
        $connection = $args->getEntityManager()->getConnection();

        foreach ($logs as $log) {
            $entity = $log['entity'];
            $this->insertAuditLog(
                $connection,
                $log['action'],
                $this->entityType($entity),
                isset($log['entityId']) ? $log['entityId'] : $this->entityId($entity),
                isset($log['entityLabel']) ? $log['entityLabel'] : $this->entityLabel($entity),
                $log['changes']
            );
        }
    }

    private function insertAuditLog(Connection $connection, $action, $entityType, $entityId = null, $entityLabel = null, array $changes = array())
    {
        if (empty($changes) && $action === 'update') {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $user = $this->currentUser();

        try {
            $connection->insert('audit_log', array(
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

    private function collectCollectionChange(PersistentCollection $collection)
    {
        $owner = $collection->getOwner();
        if (!$this->isTracked($owner)) {
            return;
        }

        $mapping = $collection->getMapping();
        $field = isset($mapping['fieldName']) ? $mapping['fieldName'] : 'collection';

        $this->pendingLogs[] = array(
            'action' => 'update',
            'entity' => $owner,
            'changes' => array(
                $field => array(
                    'old' => $this->normalizeCollection($collection->getSnapshot()),
                    'new' => $this->normalizeCollection($collection->toArray()),
                ),
            ),
        );
    }

    private function isTracked($entity)
    {
        return $entity instanceof News
            || $entity instanceof NewsCategory
            || $entity instanceof Media
            || $entity instanceof Comment
            || $entity instanceof SeoRedirect
            || $entity instanceof User;
    }

    private function normalizeChangeSet(array $changeSet)
    {
        $changes = array();
        foreach ($changeSet as $field => $values) {
            if (in_array($field, $this->ignoredFields, true)) {
                continue;
            }

            $changes[$field] = array(
                'old' => $this->normalizeValue($values[0]),
                'new' => $this->normalizeValue($values[1]),
            );
        }

        return $changes;
    }

    private function buildCreateChanges($metadata, $entity)
    {
        $changes = array();
        foreach ($metadata->getFieldNames() as $field) {
            if (in_array($field, $this->ignoredFields, true)) {
                continue;
            }
            $value = $metadata->getFieldValue($entity, $field);
            if ($value === null || $value === '') {
                continue;
            }
            $changes[$field] = array('old' => null, 'new' => $this->normalizeValue($value));
        }

        foreach ($metadata->getAssociationNames() as $field) {
            $value = $metadata->getFieldValue($entity, $field);
            if ($value === null) {
                continue;
            }
            $changes[$field] = array('old' => null, 'new' => $this->normalizeValue($value));
        }

        return $changes;
    }

    private function buildDeleteChanges($metadata, $entity)
    {
        $changes = array();
        foreach ($metadata->getFieldNames() as $field) {
            if (in_array($field, $this->ignoredFields, true)) {
                continue;
            }
            $changes[$field] = array(
                'old' => $this->normalizeValue($metadata->getFieldValue($entity, $field)),
                'new' => null,
            );
        }

        return $changes;
    }

    private function normalizeCollection(array $items)
    {
        $values = array();
        foreach ($items as $item) {
            $values[] = $this->normalizeValue($item);
        }

        return $values;
    }

    private function normalizeValue($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format(\DateTime::ATOM);
        }

        if (is_object($value)) {
            if (method_exists($value, 'getId')) {
                return array(
                    'id' => $value->getId(),
                    'label' => $this->entityLabel($value),
                );
            }

            return get_class($value);
        }

        if (is_array($value)) {
            return $this->normalizeCollection($value);
        }

        return $value;
    }

    private function entityId($entity)
    {
        return method_exists($entity, 'getId') ? $entity->getId() : null;
    }

    private function entityType($entity)
    {
        if ($entity instanceof News) {
            return $entity->isPage() ? 'page' : 'news';
        }
        if ($entity instanceof NewsCategory) {
            return 'newscategory';
        }
        if ($entity instanceof Media) {
            return 'media';
        }
        if ($entity instanceof Comment) {
            return 'comment';
        }
        if ($entity instanceof SeoRedirect) {
            return 'redirect';
        }
        if ($entity instanceof User) {
            return 'user';
        }

        return get_class($entity);
    }

    private function entityLabel($entity)
    {
        if ($entity instanceof News) {
            return $entity->getTitle();
        }
        if ($entity instanceof NewsCategory) {
            return $entity->getName();
        }
        if ($entity instanceof Media) {
            return $entity->getOriginalName();
        }
        if ($entity instanceof Comment) {
            return trim($entity->getAuthor() . ' - ' . substr(strip_tags($entity->getContent()), 0, 80));
        }
        if ($entity instanceof SeoRedirect) {
            return $entity->getSourcePath() . ' -> ' . $entity->getTargetPath();
        }
        if ($entity instanceof User) {
            return $entity->getUsername();
        }
        if (method_exists($entity, '__toString')) {
            return (string) $entity;
        }

        return null;
    }

    private function currentUser()
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
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->sanitizeValue($item);
            }

            return $value;
        }

        if ($value instanceof \DateTime) {
            return $value->format(\DateTime::ATOM);
        }

        if (is_string($value)) {
            return $this->truncate($value, 20000);
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
