<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="user")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, \Serializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=180)
     */
    protected $username;

    /**
     * @ORM\Column(name="username_canonical", type="string", length=180, unique=true)
     */
    protected $usernameCanonical;

    /**
     * @ORM\Column(type="string", length=180)
     */
    protected $email;

    /**
     * @ORM\Column(name="email_canonical", type="string", length=180, unique=true)
     */
    protected $emailCanonical;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $enabled = true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $salt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $password;

    /**
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\Column(name="confirmation_token", type="string", length=180, nullable=true, unique=true)
     */
    protected $confirmationToken;

    /**
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    protected $passwordRequestedAt;

    /**
     * @ORM\Column(type="array")
     */
    protected $roles = array();

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank(message="Please enter your name.", groups={"Registration", "Profile"})
     * @Assert\Length(
     *     min=3,
     *     max=255,
     *     minMessage="The name is too short.",
     *     maxMessage="The name is too long.",
     *     groups={"Registration", "Profile"}
     * )
     */
    protected $name;

    public function __construct()
    {
        $this->enabled = true;
        $this->roles = array('ROLE_USER');
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        $this->usernameCanonical = $this->canonicalize($username);

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function setUsernameCanonical($usernameCanonical)
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function getUsernameCanonical()
    {
        return $this->usernameCanonical;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        $this->emailCanonical = $this->canonicalize($email);

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmailCanonical($emailCanonical)
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;

        return $this;
    }

    public function isEnabled()
    {
        return (bool) $this->enabled;
    }

    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setLastLogin(\DateTimeInterface $lastLogin = null)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    public function setPasswordRequestedAt(\DateTimeInterface $passwordRequestedAt = null)
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    public function addRole($role)
    {
        $role = strtoupper($role);

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole($role)
    {
        $this->roles = array_values(array_diff($this->roles, array(strtoupper($role))));

        return $this;
    }

    public function setRoles(array $roles)
    {
        $this->roles = array_values(array_unique(array_map('strtoupper', $roles)));

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles ?: array();
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setFullName($name)
    {
        return $this->setName($name);
    }

    public function getFullName()
    {
        return $this->getName();
    }

    public function eraseCredentials(): void
    {
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function serialize(): string
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            $this->enabled,
        ));
    }

    public function unserialize($serialized): void
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            $this->enabled
        ) = unserialize($serialized);
    }

    private function canonicalize($value)
    {
        return mb_strtolower(trim((string) $value), 'UTF-8');
    }
}
