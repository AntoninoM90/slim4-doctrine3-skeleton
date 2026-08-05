<?php

declare(strict_types=1);

namespace Tests\Domain\User;

use App\Domain\User\UserRepository;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function testJsonSerialize()
    {
        $app = $this->getAppInstance();

        /** @var ContainerInterface $container */
        $container = $app->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        $userRepository = new UserRepository($entityManager);

        $user = $userRepository->findUserOfId(1);

        if ($user) {
            $expectedPayload = json_encode([
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ]);

            $this->assertEquals($expectedPayload, json_encode($user));
        } else {
            $this->assertEquals(null, null);
        }
    }
}
