<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Settings\SettingsInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Handle cross-origin requests in a secure, configurable way.
 *
 * When the `cors.enabled` setting is false the middleware is a no-op and
 * every request is forwarded untouched. When enabled, only requests that
 * carry an Origin header are treated as cross-origin; everything else is
 * forwarded untouched. The origin must match one of the
 * `cors.allowed_origins` settings: preflight requests (OPTIONS carrying an
 * `Access-Control-Request-Method` header) from unknown origins get a 403 and
 * actual requests are forwarded without CORS headers, so the browser blocks
 * the response client-side.
 *
 * Preflight requests from allowed origins are answered directly with a 204
 * and never reach the routing layer. Allowed actual requests receive the
 * `Access-Control-Allow-Origin` header echoing the concrete origin (never
 * `*`), so the response can safely be combined with
 * `Access-Control-Allow-Credentials`.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    private const DEFAULT_MAX_AGE = 86400;

    public function __construct(
        private readonly SettingsInterface $settings
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $corsSettings = $this->settings->get('cors');

        // When CORS is disabled the middleware is a no-op: every request is
        // forwarded untouched.
        if (!($corsSettings['enabled'] ?? true)) {
            return $handler->handle($request);
        }

        $origin = $request->getHeaderLine('Origin');

        // Same-origin and non-browser requests carry no Origin header: they
        // are forwarded untouched.
        if ($origin === '') {
            return $handler->handle($request);
        }

        $allowedOrigins = $this->stringList($corsSettings['allowed_origins'] ?? []);

        if (!$this->isOriginAllowed($origin, $allowedOrigins)) {
            if ($this->isPreflight($request)) {
                return $this->buildForbiddenResponse();
            }

            // Actual requests from unknown origins are forwarded without CORS
            // headers; the browser blocks the response client-side.
            return $handler->handle($request);
        }

        if ($this->isPreflight($request)) {
            return $this->buildPreflightResponse($origin, $corsSettings);
        }

        return $this->withCorsHeaders($handler->handle($request), $origin, $corsSettings);
    }

    /**
     * @param mixed $settings
     */
    private function withCorsHeaders(Response $response, string $origin, mixed $settings): Response
    {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withAddedHeader('Vary', 'Origin');

        if ($this->boolValue($settings['allow_credentials'] ?? null, true)) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        $exposedHeaders = $this->stringList($settings['exposed_headers'] ?? []);

        if ($exposedHeaders !== []) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $exposedHeaders));
        }

        return $response;
    }

    /**
     * @param mixed $settings
     */
    private function buildPreflightResponse(string $origin, mixed $settings): Response
    {
        $response = new SlimResponse(StatusCodeInterface::STATUS_NO_CONTENT);

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader(
                'Access-Control-Allow-Methods',
                implode(', ', $this->stringList($settings['allowed_methods'] ?? []))
            )
            ->withHeader(
                'Access-Control-Allow-Headers',
                implode(', ', $this->stringList($settings['allowed_headers'] ?? []))
            )
            ->withHeader('Access-Control-Max-Age', (string) ($settings['max_age'] ?? self::DEFAULT_MAX_AGE))
            ->withAddedHeader('Vary', 'Origin')
            ->withAddedHeader('Vary', 'Access-Control-Request-Method')
            ->withAddedHeader('Vary', 'Access-Control-Request-Headers');

        if ($this->boolValue($settings['allow_credentials'] ?? null, true)) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    private function buildForbiddenResponse(): Response
    {
        $response = new SlimResponse(StatusCodeInterface::STATUS_FORBIDDEN);

        $response->getBody()->write(
            json_encode(['message' => 'Origin not allowed'], JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withAddedHeader('Vary', 'Origin');
    }

    private function isPreflight(Request $request): bool
    {
        return strtoupper($request->getMethod()) === 'OPTIONS'
            && $request->hasHeader('Access-Control-Request-Method');
    }

    /**
     * @param list<string> $allowedOrigins
     */
    private function isOriginAllowed(string $origin, array $allowedOrigins): bool
    {
        return in_array($origin, $allowedOrigins, true);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        return array_values(array_map('strval', is_array($value) ? $value : []));
    }

    private function boolValue(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
    }
}
