<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\User\User;
use App\Domain\User\UserRepository;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Tests\TestCase;

abstract class UserActionTestCase extends TestCase
{
    /** @var list<int> */
    protected array $createdUserIds = [];

    protected function tearDown(): void
    {
        if ($this->createdUserIds !== []) {
            $entityManager = $this->getEntityManager();

            foreach ($this->createdUserIds as $userId) {
                $user = $entityManager->find(User::class, $userId);

                if ($user !== null) {
                    $entityManager->remove($user);
                }
            }

            $entityManager->flush();
        }

        parent::tearDown();
    }

    protected function getEntityManager(): EntityManager
    {
        $app = $this->getAppInstance();

        /** @var ContainerInterface $container */
        $container = $app->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        return $entityManager;
    }

    protected function getUserRepository(): UserRepository
    {
        return new UserRepository($this->getEntityManager());
    }

    /**
     * @param array<string, string> $data
     */
    protected function createUser(array $data = []): User
    {
        $entityManager = $this->getEntityManager();

        $user = new User(
            $data['username'] ?? 'test-' . bin2hex(random_bytes(4)),
            password_hash($data['password'] ?? 'password123', PASSWORD_BCRYPT),
            $data['emailAddress'] ?? 'test-' . bin2hex(random_bytes(4)) . '@example.com',
            $data['firstName'] ?? 'Test',
            $data['lastName'] ?? 'User'
        );

        $entityManager->persist($user);
        $entityManager->flush();

        $id = $user->getId();

        if ($id !== null) {
            $this->createdUserIds[] = $id;
        }

        return $user;
    }

    protected function getNonExistentUserId(): int
    {
        $userRepository = $this->getUserRepository();
        $id = count($userRepository->findAllUsers()) + 1;

        while ($userRepository->findUserOfId($id) !== null) {
            $id++;
        }

        return $id;
    }
}
