<?php

declare(strict_types=1);

namespace Tests\Application\Routes;

use App\Domain\User\User;
use App\Domain\User\UserRepository;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Slim\App;
use Tests\TestCase;

class AppRoutesTest extends TestCase
{
    private ?int $createdUserId = null;

    protected function tearDown(): void
    {
        if ($this->createdUserId !== null) {
            $app = $this->getAppInstance();

            /** @var ContainerInterface $container */
            $container = $app->getContainer();

            /** @var EntityManager $entityManager */
            $entityManager = $container->get(EntityManager::class);

            $user = $entityManager->find(User::class, $this->createdUserId);

            if ($user !== null) {
                $entityManager->remove($user);
                $entityManager->flush();
            }
        }

        parent::tearDown();
    }

    public function testRootRouteReturnsHelloWorld()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello world!', (string) $response->getBody());
    }

    public function testOptionsRouteHandlesCorsPreflight()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('OPTIONS', '/users'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUsersRouteRespondsWithJsonPayload()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', '/users'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(200, $payload['statusCode']);
        $this->assertArrayHasKey('data', $payload);
    }

    public function testUserRouteRespondsWithPersistedUser()
    {
        $app = $this->getAppWithErrorHandling();

        /** @var ContainerInterface $container */
        $container = $app->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        $username = 'route-test-' . bin2hex(random_bytes(4));
        $user = new User($username, 'password', $username . '@example.com', 'Route', 'Test');
        $entityManager->persist($user);
        $entityManager->flush();

        $userId = $user->getId();
        $this->createdUserId = $userId;

        $this->assertNotNull($userId);

        $response = $app->handle($this->createRequest('GET', '/user/' . $userId));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(200, $payload['statusCode']);
        $this->assertSame($userId, $payload['data']['id']);
        $this->assertSame($username, $payload['data']['username']);
        $this->assertSame('Route', $payload['data']['firstName']);
        $this->assertSame('Test', $payload['data']['lastName']);
    }

    public function testUserRouteReturnsNotFoundForUnknownUser()
    {
        $app = $this->getAppWithErrorHandling();

        /** @var ContainerInterface $container */
        $container = $app->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        $userRepository = new UserRepository($entityManager);
        $userId = count($userRepository->findAllUsers()) + 1;

        while ($userRepository->findUserOfId($userId) !== null) {
            $userId++;
        }

        $response = $app->handle($this->createRequest('GET', '/user/' . $userId));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(404, $payload['statusCode']);
        $this->assertSame('RESOURCE_NOT_FOUND', $payload['error']['type']);
    }

    public function testUnknownRouteReturnsMethodNotAllowed()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', '/unknown-route'));

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(405, $payload['statusCode']);
        $this->assertSame('NOT_ALLOWED', $payload['error']['type']);
    }

    public function testDocsJsonRouteRespondsWithOpenApiSpec()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', '/docs.json'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame('3.0.0', $payload['openapi']);
        $this->assertSame('Slim 4 Doctrine 3 Skeleton API', $payload['info']['title']);
        $this->assertArrayHasKey('/health', $payload['paths']);
        $this->assertArrayHasKey('/users', $payload['paths']);
        $this->assertArrayHasKey('/user/{id}', $payload['paths']);
        $this->assertArrayHasKey('User', $payload['components']['schemas']);
    }

    public function testDocsRouteServesSwaggerUiHtml()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', '/docs'));

        $this->assertEquals(200, $response->getStatusCode());

        $body = (string) $response->getBody();

        $this->assertStringContainsString('swagger-ui', $body);
        $this->assertStringContainsString('/docs.json', $body);

        // The inline style and script carry the same nonce that the security
        // headers middleware embeds into the Content-Security-Policy header,
        // so the browser accepts them without 'unsafe-inline'.
        $this->assertSame(1, preg_match('/nonce="([a-f0-9]{32})"/', $body, $matches));
        $this->assertStringContainsString(
            "'nonce-" . $matches[1] . "'",
            $response->getHeaderLine('Content-Security-Policy')
        );
    }
}
