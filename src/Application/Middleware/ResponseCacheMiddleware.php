<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Settings\SettingsInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Cache HTTP responses (GET/HEAD) in a Symfony Cache pool.
 *
 * Disabled by default: when the `http_cache.enabled` setting is false the
 * request is forwarded untouched (no cache lookup, no X-Cache header). When
 * enabled, responses are keyed by the full request URI, so identical requests
 * within the TTL are served from the cache instead of re-executing the
 * application. Requests that carry authentication or cookie state are never
 * cached, and neither are error responses or responses that set cookies.
 */
final class ResponseCacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $cachePool,
        private readonly SettingsInterface $settings
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $httpCacheSettings = $this->settings->get('http_cache');

        if (!($httpCacheSettings['enabled'] ?? false)) {
            return $handler->handle($request);
        }

        if (!$this->isCacheableRequest($request)) {
            return $handler->handle($request)->withHeader('X-Cache', 'SKIP');
        }

        $cacheItem = $this->cachePool->getItem($this->getCacheKey($request));

        if ($cacheItem->isHit()) {
            return $this->restoreResponse($cacheItem->get())
                ->withHeader('X-Cache', 'HIT');
        }

        $response = $handler->handle($request);

        if ($this->isCacheableResponse($response)) {
            $ttl = (int) $this->settings->get('http_cache')['ttl'];

            $cacheItem
                ->set($this->serializeResponse($response))
                ->expiresAfter($ttl);

            $this->cachePool->save($cacheItem);
        }

        return $response->withHeader('X-Cache', 'MISS');
    }

    private function isCacheableRequest(Request $request): bool
    {
        $method = strtoupper($request->getMethod());

        if (!in_array($method, ['GET', 'HEAD'], true)) {
            return false;
        }

        // The response may depend on the authenticated user or session state.
        if ($request->hasHeader('Authorization') || $request->hasHeader('Cookie')) {
            return false;
        }

        return true;
    }

    private function isCacheableResponse(Response $response): bool
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            return false;
        }

        // Never cache responses that set cookies (user-specific state).
        if ($response->hasHeader('Set-Cookie')) {
            return false;
        }

        return true;
    }

    private function getCacheKey(Request $request): string
    {
        return hash('sha256', strtoupper($request->getMethod()) . ':' . (string) $request->getUri());
    }

    /**
     * @return array{status: int, headers: array<string, list<string>>, body: string}
     */
    private function serializeResponse(Response $response): array
    {
        $headers = [];

        foreach ($response->getHeaders() as $name => $values) {
            if (in_array(strtolower($name), ['set-cookie', 'x-cache'], true)) {
                continue;
            }

            $headers[$name] = array_values($values);
        }

        return [
            'status' => $response->getStatusCode(),
            'headers' => $headers,
            'body' => (string) $response->getBody(),
        ];
    }

    /**
     * @param array{status: int, headers: array<string, list<string>>, body: string} $data
     */
    private function restoreResponse(array $data): Response
    {
        $response = new SlimResponse($data['status']);

        foreach ($data['headers'] as $name => $values) {
            $response = $response->withAddedHeader($name, $values);
        }

        $response->getBody()->write($data['body']);

        return $response;
    }
}
