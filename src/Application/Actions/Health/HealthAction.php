<?php

declare(strict_types=1);

namespace App\Application\Actions\Health;

use App\Application\Actions\Action;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Throwable;

class HealthAction extends Action
{
    private CacheItemPoolInterface $cache;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->cache = $container->get(CacheItemPoolInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $database = $this->checkDatabase();
        $cache = $this->checkCache();

        foreach (['database' => $database, 'cache' => $cache] as $name => $check) {
            if (!$check['ok']) {
                $this->logger->error("Health check `{$name}` failed: {$check['error']}");
            }
        }

        $healthy = $database['ok'] && $cache['ok'];

        return $this->respondWithData(
            [
                'status' => $healthy ? 'ok' : 'unhealthy',
                'checks' => [
                    'database' => $database['ok'] ? 'ok' : 'error',
                    'cache' => $cache['ok'] ? 'ok' : 'error',
                ],
            ],
            $healthy ? StatusCodeInterface::STATUS_OK : StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE
        );
    }

    /**
     * Probe the database connection with a trivial query.
     *
     * @return array{ok: bool, error: ?string}
     */
    private function checkDatabase(): array
    {
        try {
            $result = $this->entityManager->getConnection()->executeQuery('SELECT 1');

            if ((int) $result->fetchOne() !== 1) {
                return ['ok' => false, 'error' => 'Unexpected database response.'];
            }

            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Store and read back a probe value in the shared cache pool.
     *
     * @return array{ok: bool, error: ?string}
     */
    private function checkCache(): array
    {
        try {
            $item = $this->cache->getItem('health_check');
            $item->set('ok');
            $this->cache->save($item);

            $probe = $this->cache->getItem('health_check');

            if (!$probe->isHit() || $probe->get() !== 'ok') {
                return ['ok' => false, 'error' => 'Cache probe was not stored or read back.'];
            }

            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
