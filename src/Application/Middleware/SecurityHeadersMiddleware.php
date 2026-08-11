<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Settings\SettingsInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use function bin2hex;
use function random_bytes;
use function str_replace;

/**
 * Add HTTP security headers to every response produced inside the middleware
 * stack (including CORS preflight and rate-limit responses) and strip
 * configured headers (e.g. Server, X-Powered-By) that reach the response.
 *
 * The headers are configured under the `security_headers.headers` setting,
 * the headers to remove under `security_headers.remove`. When the
 * `security_headers.enabled` setting is false the middleware is a no-op and
 * the request is forwarded untouched. When no headers are configured the
 * response is left unchanged as well.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
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
        $securitySettings = $this->settings->get('security_headers');

        if (!($securitySettings['enabled'] ?? true)) {
            return $handler->handle($request);
        }

        $headers = $this->headerMap($securitySettings['headers'] ?? []);

        $remove = (array) ($securitySettings['remove'] ?? []);

        if ($headers === [] && $remove === []) {
            return $handler->handle($request);
        }

        // Per-request nonce, exposed as a request attribute so routes can
        // mark their inline scripts and styles as trusted (Content-Security-
        // Policy) and substituted into any header value containing the
        // {nonce} placeholder.
        $nonce = bin2hex(random_bytes(16));
        $request = $request->withAttribute('cspNonce', $nonce);

        $response = $handler->handle($request);

        foreach ($remove as $name) {
            $response = $response->withoutHeader((string) $name);
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, str_replace('{nonce}', $nonce, $value));
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function headerMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $headers = [];

        foreach ($value as $name => $headerValue) {
            $headers[(string) $name] = (string) $headerValue;
        }

        return $headers;
    }
}
