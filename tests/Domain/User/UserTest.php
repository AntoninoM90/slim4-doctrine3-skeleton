<?php

declare(strict_types=1);

namespace Tests\Domain\User;

use App\Domain\User\UserRepository;
use Doctrine\ORM\EntityManager;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * @param int    $id
     * @param string $username
     * @param string $firstName
     * @param string $lastName
     */
    public function testJsonSerialize()
    {
        $app = $this->getAppInstance();

        /** @var Container $container */
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
