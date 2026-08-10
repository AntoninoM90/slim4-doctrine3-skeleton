<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    private const ROOT_ROUTE = '/';

    protected function setUp(): void
    {
        putenv('APP_SECURITY_HEADERS=1');
        putenv('APP_CORS_ENABLED=1');
    }

    protected function tearDown(): void
    {
        putenv('APP_SECURITY_HEADERS=');
        putenv('APP_CORS_ENABLED=');

        parent::tearDown();
    }

    public function testSecurityHeadersAreAddedToResponses()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', self::ROOT_ROUTE));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        $this->assertStringContainsString('geolocation=()', $response->getHeaderLine('Permissions-Policy'));
        $this->assertSame('same-origin', $response->getHeaderLine('Cross-Origin-Opener-Policy'));
        $this->assertSame('same-origin', $response->getHeaderLine('Cross-Origin-Resource-Policy'));

        $csp = $response->getHeaderLine('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' https://unpkg.com 'nonce-", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https://unpkg.com", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString('{nonce}', $csp);
    }

    public function testDisabledMiddlewareAddsNoHeaders()
    {
        putenv('APP_SECURITY_HEADERS=0');

        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', self::ROOT_ROUTE));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('X-Frame-Options'));
    }

    public function testSecurityHeadersAreEnabledByDefault()
    {
        putenv('APP_SECURITY_HEADERS=');

        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', self::ROOT_ROUTE));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
    }

    public function testSecurityHeadersAreAddedToCorsPreflightResponses()
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest(
            'OPTIONS',
            self::ROOT_ROUTE,
            [
                'HTTP_ACCEPT' => self::CONTENT_TYPE_JSON,
                'Origin' => 'http://localhost:3000',
                'Access-Control-Request-Method' => 'GET',
            ]
        ));

        // The preflight is short-circuited by the (inner) CORS middleware;
        // the security headers middleware still wraps its response.
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }
}
