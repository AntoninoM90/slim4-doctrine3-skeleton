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
 * Rate-limit requests with a fixed-window counter stored in a Symfony Cache
 * pool, keyed by client IP address (or by user id when the request exposes
 * one).
 *
 * Disabled by default: when the `rate_limit.enabled` setting is false the
 * request is forwarded untouched and no X-RateLimit-* headers are added.
 * When enabled, every request counts against the current window; once the
 * limit is reached the client receives a 429 response with a Retry-After
 * header. The requestor key is the client IP address, unless the request
 * carries a user id (the `user_id` request attribute or the `user_id`
 * session key), in which case the limit applies to that user instead.
 */
final class RateLimitMiddleware implements MiddlewareInterface
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
        $rateLimitSettings = $this->settings->get('rate_limit');

        if (!($rateLimitSettings['enabled'] ?? false)) {
            return $handler->handle($request);
        }

        $limit = max(1, (int) ($rateLimitSettings['limit'] ?? 60));
        $window = max(1, (int) ($rateLimitSettings['window'] ?? 60));

        $windowStart = intdiv(time(), $window) * $window;
        $resetAt = $windowStart + $window;

        // FilesystemAdapter reserves characters such as ":" in item keys, so
        // the requestor key is hashed before it reaches the cache pool.
        $cacheKey = hash('sha256', 'rate_limit:' . $this->getRequestKey($request) . ':' . $windowStart);

        $cacheItem = $this->cachePool->getItem($cacheKey);
        $count = $cacheItem->isHit() ? (int) $cacheItem->get() : 0;

        if ($count >= $limit) {
            return $this->buildTooManyRequestsResponse($limit, $resetAt);
        }

        $cacheItem
            ->set($count + 1)
            ->expiresAfter($window);

        $this->cachePool->save($cacheItem);

        return $handler->handle($request)
            ->withHeader('X-RateLimit-Limit', (string) $limit)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $limit - $count - 1))
            ->withHeader('X-RateLimit-Reset', (string) $resetAt);
    }

    private function getRequestKey(Request $request): string
    {
        $userId = $request->getAttribute('user_id');

        if ($userId === null) {
            $session = $request->getAttribute('session');
            $userId = is_array($session) ? ($session['user_id'] ?? null) : null;
        }

        if ($userId !== null) {
            return 'user:' . (string) $userId;
        }

        $ip = $request->getAttribute('ip_address');

        if ($ip === null) {
            $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        }

        return 'ip:' . (string) $ip;
    }

    private function buildTooManyRequestsResponse(int $limit, int $resetAt): Response
    {
        $response = new SlimResponse(429);

        $response->getBody()->write(
            json_encode(['message' => 'Too many requests'], JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-RateLimit-Limit', (string) $limit)
            ->withHeader('X-RateLimit-Reset', (string) $resetAt)
            ->withHeader('Retry-After', (string) max(1, $resetAt - time()));
    }
}
