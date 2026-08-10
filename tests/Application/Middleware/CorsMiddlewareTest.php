<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

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
}
