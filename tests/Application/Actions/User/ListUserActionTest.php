<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Domain\User\User;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Tests\TestCase;

class ListUserActionTest extends TestCase
{
    public function testActionReturnsFirstPageByDefault()
    {
        $app = $this->getAppInstance();

        /** @var ContainerInterface $container */
        $container = $app->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        $this->seedUsers($entityManager);

        $request = $this->createRequest('GET', '/users');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(200, $payload['statusCode']);
        $this->assertCount(3, $payload['data']['items']);
        $this->assertSame(3, $payload['data']['pagination']['total']);
        $this->assertSame(1, $payload['data']['pagination']['page']);
        $this->assertSame(10, $payload['data']['pagination']['perPage']);
        $this->assertSame(1, $payload['data']['pagination']['totalPages']);
    }

    public function testActionWithPagination()
    {
        $app = $this->getAppInstance();

        /** @var ContainerInterface $container */
        $container = $app->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        $this->seedUsers($entityManager);

        $request = $this->createRequest('GET', '/users', [], [], [], 'page=2&perPage=2');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(3, $payload['data']['pagination']['total']);
        $this->assertSame(2, $payload['data']['pagination']['page']);
        $this->assertSame(2, $payload['data']['pagination']['perPage']);
        $this->assertSame(2, $payload['data']['pagination']['totalPages']);
        $this->assertCount(1, $payload['data']['items']);
        $this->assertSame('Carlo', $payload['data']['items'][0]['firstName']);
    }

    public function testActionFallsBackAndClampsInvalidPaginationParameters()
    {
        $app = $this->getAppInstance();

        /** @var ContainerInterface $container */
        $container = $app->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        $this->seedUsers($entityManager);

        $request = $this->createRequest('GET', '/users', [], [], [], 'page=0&perPage=0');
        $response = $app->handle($request);

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['data']['pagination']['page']);
        $this->assertSame(10, $payload['data']['pagination']['perPage']);
        $this->assertCount(3, $payload['data']['items']);

        $request = $this->createRequest('GET', '/users', [], [], [], 'page=abc&perPage=150');
        $response = $app->handle($request);

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['data']['pagination']['page']);
        $this->assertSame(100, $payload['data']['pagination']['perPage']);
    }

    private function seedUsers(EntityManager $entityManager): void
    {
        $entityManager->createQuery('DELETE FROM ' . User::class . ' u')->execute();

        $users = [
            new User('anna', 'password', 'anna@example.com', 'Anna', 'Bianchi'),
            new User('beppe', 'password', 'beppe@example.com', 'Beppe', 'Verdi'),
            new User('carlo', 'password', 'carlo@example.com', 'Carlo', 'Rossi'),
        ];

        foreach ($users as $user) {
            $entityManager->persist($user);
        }

        $entityManager->flush();
    }
}
