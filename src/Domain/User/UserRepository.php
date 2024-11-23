<?php

declare(strict_types=1);

namespace App\Domain\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class UserRepository
{
    protected EntityManagerInterface $entityManager;

    protected EntityRepository $repository;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository(User::class);
    }

    /**
     * @return User[]
     */
    public function findAllUsers(): array
    {
        return $this->repository->findAll();
    }

    /**
     * @param int $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     *
     * @return User|null
     * @throws UserNotFoundException
     */
    public function findUserOfId(
        int $id,
        int $lockMode = null,
        int $lockVersion = null
    ): ?User {
        return $this->repository->find($id, $lockMode, $lockVersion);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param mixed $limit
     * @param mixed $offset
     *
     * @return User[]
     * @throws UserNotFoundException
     */
    public function findUsersBy(
        array $criteria,
        ?array $orderBy = null,
        $limit = null,
        $offset = null
    ): array {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param mixed $limit
     * @param mixed $offset
     *
     * @return User|null
     * @throws UserNotFoundException
     */
    public function findOneUserBy(
        array $criteria,
        ?array $orderBy = null,
        $limit = null,
        $offset = null
    ): ?User {
        return $this->repository->findOneBy($criteria, $orderBy);
    }
}
