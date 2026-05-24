<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->andWhere('u.username = :identifier OR u.email = :identifier OR u.usernameCanonical = :canonical OR u.emailCanonical = :canonical')
            ->setParameter('identifier', $identifier)
            ->setParameter('canonical', mb_strtolower(trim($identifier), 'UTF-8'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('User "%s" was not found.', $identifier));
        }

        return $user;
    }

    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier((string) $username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $refreshedUser = $this->entityManager->getRepository(User::class)->find($user->getId());

        if (!$refreshedUser instanceof User) {
            throw new UserNotFoundException(sprintf('User with id "%s" was not found.', $user->getId()));
        }

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
