<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="audit_log", indexes={
 *     @ORM\Index(name="idx_audit_created", columns={"createdAt"}),
 *     @ORM\Index(name="idx_audit_entity", columns={"entityType", "entityId"}),
 *     @ORM\Index(name="idx_audit_action", columns={"action"}),
 *     @ORM\Index(name="idx_audit_user", columns={"user_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\AuditLogRepository")
 */
class AuditLog
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\Column(name="action", type="string", length=20) */
    private $action;

    /** @ORM\Column(name="entityType", type="string", length=120) */
    private $entityType;

    /** @ORM\Column(name="entityId", type="string", length=64, nullable=true) */
    private $entityId;

    /** @ORM\Column(name="entityLabel", type="string", length=255, nullable=true) */
    private $entityLabel;

    /** @ORM\Column(name="changes", type="text", nullable=true, columnDefinition="LONGTEXT") */
    private $changes;

    /** @ORM\ManyToOne(targetEntity="App\Entity\User") @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL") */
    private $user;

    /** @ORM\Column(name="username", type="string", length=180, nullable=true) */
    private $username;

    /** @ORM\Column(name="ip", type="string", length=45, nullable=true) */
    private $ip;

    /** @ORM\Column(name="userAgent", type="text", nullable=true) */
    private $userAgent;

    /** @ORM\Column(name="route", type="string", length=120, nullable=true) */
    private $route;

    /** @ORM\Column(name="requestMethod", type="string", length=12, nullable=true) */
    private $requestMethod;

    /** @ORM\Column(name="requestUri", type="text", nullable=true) */
    private $requestUri;

    /** @ORM\Column(name="createdAt", type="datetime") */
    private $createdAt;

    public function getId() { return $this->id; }
    public function getAction() { return $this->action; }
    public function setAction($action) { $this->action = $action; return $this; }
    public function getEntityType() { return $this->entityType; }
    public function setEntityType($entityType) { $this->entityType = $entityType; return $this; }
    public function getEntityId() { return $this->entityId; }
    public function setEntityId($entityId) { $this->entityId = $entityId; return $this; }
    public function getEntityLabel() { return $this->entityLabel; }
    public function setEntityLabel($entityLabel) { $this->entityLabel = $entityLabel; return $this; }
    public function getChanges() { return $this->changes; }
    public function setChanges($changes) { $this->changes = $changes; return $this; }
    public function getUser() { return $this->user; }
    public function setUser(User $user = null) { $this->user = $user; return $this; }
    public function getUsername() { return $this->username; }
    public function setUsername($username) { $this->username = $username; return $this; }
    public function getIp() { return $this->ip; }
    public function setIp($ip) { $this->ip = $ip; return $this; }
    public function getUserAgent() { return $this->userAgent; }
    public function setUserAgent($userAgent) { $this->userAgent = $userAgent; return $this; }
    public function getRoute() { return $this->route; }
    public function setRoute($route) { $this->route = $route; return $this; }
    public function getRequestMethod() { return $this->requestMethod; }
    public function setRequestMethod($requestMethod) { $this->requestMethod = $requestMethod; return $this; }
    public function getRequestUri() { return $this->requestUri; }
    public function setRequestUri($requestUri) { $this->requestUri = $requestUri; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt) { $this->createdAt = $createdAt; return $this; }

    public function getChangesData()
    {
        $decoded = json_decode((string) $this->changes, true);

        return is_array($decoded) ? $decoded : array();
    }
}
