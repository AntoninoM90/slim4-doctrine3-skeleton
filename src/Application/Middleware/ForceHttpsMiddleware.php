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

use function strtolower;

/**
 * Redirect every HTTP request to its HTTPS equivalent.
 *
 * When the `https.enabled` setting is false the middleware is a no-op and
 * every request is forwarded untouched. A request is considered HTTPS when
 * the request URI scheme is https or, when `https.trust_forwarded_proto` is
 * enabled (e.g. behind a reverse proxy), when the X-Forwarded-Proto header
 * is https. HTTP requests are answered with a permanent redirect
 * (`https.status_code`, 308 by default so the method and body are kept) to
 * the same host and path over HTTPS.
 */
final class ForceHttpsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SettingsInterface $settings
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $httpsSettings = $this->settings->get('https');

        if (!($httpsSettings['enabled'] ?? false)) {
            return $handler->handle($request);
        }

        if ($this->isHttps($request, $httpsSettings)) {
            return $handler->handle($request);
        }

        return $this->redirectToHttps($request, $httpsSettings);
    }

    /**
     * @param mixed $httpsSettings
     */
    private function isHttps(Request $request, mixed $httpsSettings): bool
    {
        if (strtolower($request->getUri()->getScheme()) === 'https') {
            return true;
        }

        // Behind a trusted reverse proxy the original protocol is forwarded
        // in the X-Forwarded-Proto header.
        if ($this->boolValue($httpsSettings['trust_forwarded_proto'] ?? null, true)) {
            return strtolower($request->getHeaderLine('X-Forwarded-Proto')) === 'https';
        }

        return false;
    }

    /**
     * @param mixed $httpsSettings
     */
    private function redirectToHttps(Request $request, mixed $httpsSettings): Response
    {
        $uri = $request->getUri()->withScheme('https');

        if ($uri->getPort() === 80) {
            $uri = $uri->withPort(null);
        }

        $response = new SlimResponse(
            (int) ($httpsSettings['status_code'] ?? StatusCodeInterface::STATUS_PERMANENT_REDIRECT)
        );

        $response->getBody()->write('The requested resource is only available over HTTPS.');

        return $response
            ->withHeader('Location', (string) $uri)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    private function boolValue(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
    }
}
