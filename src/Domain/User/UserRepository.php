<?php

declare(strict_types=1);

namespace App\Domain\User;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class UserRepository
{
    protected EntityManagerInterface $entityManager;

    /** @var EntityRepository<User> */
    protected EntityRepository $repository;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        /** @var EntityRepository<User> $repository */
        $repository = $this->entityManager->getRepository(User::class);
        $this->repository = $repository;
    }

    /**
     * @return User[]
     */
    public function findAllUsers(): array
    {
        return $this->repository->findAll();
    }

    /**
     * @throws UserAlreadyExistsException
     */
    public function createUser(User $user): void
    {
        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new UserAlreadyExistsException($e);
        }
    }

    /**
     * @throws UserAlreadyExistsException
     */
    public function updateUser(User $user): void
    {
        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new UserAlreadyExistsException($e);
        }
    }

    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * Find users with pagination, ordered by id.
     *
     * @return array{users: list<User>, total: int}
     */
    public function findUsersWithPagination(int $limit = 10, int $offset = 0): array
    {
        $queryBuilder = $this->repository->createQueryBuilder('u');

        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->select('COUNT(u.id)');
        $total = (int) $countQueryBuilder->getQuery()->getSingleScalarResult();

        $queryBuilder
            ->orderBy('u.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        /** @var list<User> $users */
        $users = $queryBuilder->getQuery()->getResult();

        return [
            'users' => $users,
            'total' => $total,
        ];
    }

    /**
     * @param int $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     *
     * @return User|null
     */
    public function findUserOfId(
        int $id,
        ?int $lockMode = null,
        ?int $lockVersion = null
    ): ?User {
        return $this->repository->find($id, $lockMode, $lockVersion);
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, 'ASC'|'asc'|'DESC'|'desc'>|null $orderBy
     *
     * @return User|null
     */
    public function findOneUserBy(
        array $criteria,
        ?array $orderBy = null
    ): ?User {
        return $this->repository->findOneBy($criteria, $orderBy);
    }
}
