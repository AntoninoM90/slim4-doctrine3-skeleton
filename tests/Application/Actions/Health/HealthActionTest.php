<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Health;

use App\Application\Actions\ActionPayload;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\TestCase;

class HealthActionTest extends TestCase
{
    public function testActionReportsHealthyDependencies()
    {
        $app = $this->getAppInstance();

        $request = $this->createRequest('GET', '/health');
        $response = $app->handle($request);

        $expectedPayload = new ActionPayload(
            200,
            [
                'status' => 'ok',
                'checks' => [
                    'database' => 'ok',
                    'cache' => 'ok',
                ],
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            json_encode($expectedPayload, JSON_PRETTY_PRINT),
            (string) $response->getBody()
        );
    }

    public function testActionReportsUnhealthyCache()
    {
        $app = $this->getAppInstance();

        /** @var ContainerInterface $container */
        $container = $app->getContainer();

        $brokenPool = new class implements CacheItemPoolInterface {
            private CacheItemPoolInterface $backend;

            public function __construct()
            {
                $this->backend = new ArrayAdapter();
            }

            public function getItem(string $key): \Psr\Cache\CacheItemInterface
            {
                return $this->backend->getItem($key);
            }

            public function getItems(array $keys = []): iterable
            {
                return $this->backend->getItems($keys);
            }

            public function hasItem(string $key): bool
            {
                return $this->backend->hasItem($key);
            }

            public function clear(): bool
            {
                return $this->backend->clear();
            }

            public function deleteItem(string $key): bool
            {
                return $this->backend->deleteItem($key);
            }

            public function deleteItems(array $keys): bool
            {
                return $this->backend->deleteItems($keys);
            }

            public function save(\Psr\Cache\CacheItemInterface $item): bool
            {
                throw new RuntimeException('Cache is unavailable.');
            }

            public function saveDeferred(\Psr\Cache\CacheItemInterface $item): bool
            {
                throw new RuntimeException('Cache is unavailable.');
            }

            public function commit(): bool
            {
                throw new RuntimeException('Cache is unavailable.');
            }
        };

        $container->set(CacheItemPoolInterface::class, $brokenPool);

        $request = $this->createRequest('GET', '/health');
        $response = $app->handle($request);

        $expectedPayload = new ActionPayload(
            503,
            [
                'status' => 'unhealthy',
                'checks' => [
                    'database' => 'ok',
                    'cache' => 'error',
                ],
            ]
        );

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals(
            json_encode($expectedPayload, JSON_PRETTY_PRINT),
            (string) $response->getBody()
        );
    }
}
