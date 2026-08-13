<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use App\Application\Middleware\CorsMiddleware;
use App\Application\Settings\Settings;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Tests\TestCase;

class CorsMiddlewareTest extends TestCase
{
    private const ROOT_ROUTE = '/';
    private const ALLOWED_ORIGIN = 'https://app.example.com';
    private const UNKNOWN_ORIGIN = 'https://evil.example.com';

    protected function setUp(): void
    {
        putenv('APP_CORS_ORIGINS=' . self::ALLOWED_ORIGIN);
        putenv('APP_CORS_ENABLED=1');
    }

    protected function tearDown(): void
    {
        putenv('APP_CORS_ORIGINS=');
        putenv('APP_CORS_ENABLED=');

        parent::tearDown();
    }

    public function testActualRequestFromAllowedOriginGetsCorsHeaders()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest(
            'GET',
            self::ROOT_ROUTE,
            [
                'HTTP_ACCEPT' => self::CONTENT_TYPE_JSON,
                'Origin' => self::ALLOWED_ORIGIN,
            ]
        ));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(self::ALLOWED_ORIGIN, $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('Origin', $response->getHeaderLine('Vary'));
        $this->assertSame('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertStringContainsString(
            'X-RateLimit-Limit',
            $response->getHeaderLine('Access-Control-Expose-Headers')
        );
    }

    public function testPreflightFromAllowedOriginIsShortCircuitedWith204()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest(
            'OPTIONS',
            self::ROOT_ROUTE,
            [
                'HTTP_ACCEPT' => self::CONTENT_TYPE_JSON,
                'Origin' => self::ALLOWED_ORIGIN,
                'Access-Control-Request-Method' => 'GET',
                'Access-Control-Request-Headers' => 'Content-Type',
            ]
        ));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame(self::ALLOWED_ORIGIN, $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('GET', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('Content-Type', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame('86400', $response->getHeaderLine('Access-Control-Max-Age'));
        $this->assertSame('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertStringContainsString('Origin', $response->getHeaderLine('Vary'));
    }

    public function testPreflightFromUnknownOriginReturns403()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest(
            'OPTIONS',
            self::ROOT_ROUTE,
            [
                'HTTP_ACCEPT' => self::CONTENT_TYPE_JSON,
                'Origin' => self::UNKNOWN_ORIGIN,
                'Access-Control-Request-Method' => 'GET',
            ]
        ));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('Origin not allowed', json_decode((string) $response->getBody(), true)['message']);
    }

    public function testActualRequestFromUnknownOriginIsForwardedWithoutCorsHeaders()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest(
            'GET',
            self::ROOT_ROUTE,
            [
                'HTTP_ACCEPT' => self::CONTENT_TYPE_JSON,
                'Origin' => self::UNKNOWN_ORIGIN,
            ]
        ));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('Hello world!', (string) $response->getBody());
        $this->assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testRequestWithoutOriginIsNotAffected()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', self::ROOT_ROUTE));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testPlainOptionsWithoutOriginStillReachesRoutes()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('OPTIONS', self::ROOT_ROUTE));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testDisabledMiddlewarePassesRequestsThrough()
    {
        putenv('APP_CORS_ENABLED=0');

        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest(
            'GET',
            self::ROOT_ROUTE,
            [
                'HTTP_ACCEPT' => self::CONTENT_TYPE_JSON,
                'Origin' => self::ALLOWED_ORIGIN,
            ]
        ));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));

        $preflight = $app->handle($this->createRequest(
            'OPTIONS',
            self::ROOT_ROUTE,
            [
                'HTTP_ACCEPT' => self::CONTENT_TYPE_JSON,
                'Origin' => self::ALLOWED_ORIGIN,
                'Access-Control-Request-Method' => 'GET',
            ]
        ));

        // The middleware does not short-circuit: the catch-all OPTIONS route
        // answers with a plain 200.
        $this->assertEquals(200, $preflight->getStatusCode());
        $this->assertSame('', $preflight->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testMiddlewareIsDisabledByDefault()
    {
        putenv('APP_CORS_ENABLED=');

        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest(
            'GET',
            self::ROOT_ROUTE,
            [
                'HTTP_ACCEPT' => self::CONTENT_TYPE_JSON,
                'Origin' => self::ALLOWED_ORIGIN,
            ]
        ));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testDisabledAllowCredentialsOmitsCredentialsHeader()
    {
        $middleware = $this->buildMiddleware([
            'enabled' => true,
            'allowed_origins' => [self::ALLOWED_ORIGIN],
            'allow_credentials' => false,
        ]);

        $response = $middleware->process(
            $this->createRequest('GET', self::ROOT_ROUTE, ['Origin' => self::ALLOWED_ORIGIN]),
            $this->staticHandler()
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(self::ALLOWED_ORIGIN, $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('', $response->getHeaderLine('Access-Control-Allow-Credentials'));
    }

    public function testMissingExposedHeadersOmitsExposeHeadersHeader()
    {
        $middleware = $this->buildMiddleware([
            'enabled' => true,
            'allowed_origins' => [self::ALLOWED_ORIGIN],
        ]);

        $response = $middleware->process(
            $this->createRequest('GET', self::ROOT_ROUTE, ['Origin' => self::ALLOWED_ORIGIN]),
            $this->staticHandler()
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Access-Control-Expose-Headers'));
    }

    public function testCustomMaxAgeIsReflectedInPreflight()
    {
        $middleware = $this->buildMiddleware([
            'enabled' => true,
            'allowed_origins' => [self::ALLOWED_ORIGIN],
            'max_age' => 3600,
        ]);

        $response = $middleware->process(
            $this->createPreflightRequest(),
            $this->staticHandler()
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame('3600', $response->getHeaderLine('Access-Control-Max-Age'));
    }

    public function testCustomMethodsAndHeadersAreReflectedInPreflight()
    {
        $middleware = $this->buildMiddleware([
            'enabled' => true,
            'allowed_origins' => [self::ALLOWED_ORIGIN],
            'allowed_methods' => ['PATCH', 'DELETE'],
            'allowed_headers' => ['X-Custom', 'X-Other'],
        ]);

        $response = $middleware->process(
            $this->createPreflightRequest(),
            $this->staticHandler()
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame('PATCH, DELETE', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertSame('X-Custom, X-Other', $response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    public function testPreflightVariesOnRequestMethodAndHeaders()
    {
        $middleware = $this->buildMiddleware([
            'enabled' => true,
            'allowed_origins' => [self::ALLOWED_ORIGIN],
        ]);

        $response = $middleware->process(
            $this->createPreflightRequest(),
            $this->staticHandler()
        );

        $vary = $response->getHeaderLine('Vary');

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertStringContainsString('Origin', $vary);
        $this->assertStringContainsString('Access-Control-Request-Method', $vary);
        $this->assertStringContainsString('Access-Control-Request-Headers', $vary);
    }

    private function buildMiddleware(array $corsSettings): CorsMiddleware
    {
        return new CorsMiddleware(new Settings(['cors' => $corsSettings]));
    }

    private function createPreflightRequest(): Request
    {
        return $this->createRequest(
            'OPTIONS',
            self::ROOT_ROUTE,
            [
                'Origin' => self::ALLOWED_ORIGIN,
                'Access-Control-Request-Method' => 'GET',
            ]
        );
    }

    private function staticHandler(): RequestHandler
    {
        return new class implements RequestHandler {
            public function handle(Request $request): Response
            {
                return new SlimResponse(200);
            }
        };
    }
}
