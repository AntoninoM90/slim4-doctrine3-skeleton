<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use App\Application\Middleware\RateLimitMiddleware;
use App\Application\Settings\Settings;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    private const USERS_LIST_ROUTE = '/users';

    protected function setUp(): void
    {
        putenv('APP_RATE_LIMIT=1');
        putenv('APP_RATE_LIMIT_MAX=3');
        putenv('APP_RATE_LIMIT_WINDOW=3600');

        $cacheDir = __DIR__ . '/../../../var/cache/rate_limit';

        if (is_dir($cacheDir)) {
            $this->removeDirectory($cacheDir);
        }
    }

    protected function tearDown(): void
    {
        putenv('APP_RATE_LIMIT=0');
        putenv('APP_RATE_LIMIT_MAX=');
        putenv('APP_RATE_LIMIT_WINDOW=');

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() && !$item->isLink()
                ? rmdir($item->getPathname())
                : unlink($item->getPathname());
        }

        rmdir($dir);
    }

    public function testRequestsUnderLimitAreAllowedAndReportHeaders()
    {
        $app = $this->getAppWithErrorHandling();

        $firstResponse = $app->handle($this->createRequest('GET', self::USERS_LIST_ROUTE));
        $secondResponse = $app->handle($this->createRequest('GET', self::USERS_LIST_ROUTE));

        $this->assertEquals(200, $firstResponse->getStatusCode());
        $this->assertSame('3', $firstResponse->getHeaderLine('X-RateLimit-Limit'));
        $this->assertSame('2', $firstResponse->getHeaderLine('X-RateLimit-Remaining'));
        $this->assertSame('1', $secondResponse->getHeaderLine('X-RateLimit-Remaining'));
        $this->assertTrue($firstResponse->hasHeader('X-RateLimit-Reset'));
    }

    public function testRequestsOverLimitReturn429()
    {
        $app = $this->getAppWithErrorHandling();

        for ($i = 0; $i < 3; $i++) {
            $response = $app->handle($this->createRequest('GET', self::USERS_LIST_ROUTE));
            $this->assertEquals(200, $response->getStatusCode());
        }

        $limitedResponse = $app->handle($this->createRequest('GET', self::USERS_LIST_ROUTE));

        $this->assertEquals(429, $limitedResponse->getStatusCode());
        $this->assertSame(self::CONTENT_TYPE_JSON, $limitedResponse->getHeaderLine('Content-Type'));
        $this->assertTrue($limitedResponse->hasHeader('Retry-After'));
        $this->assertSame('Too many requests', json_decode((string) $limitedResponse->getBody(), true)['message']);
    }

    public function testRateLimitIsKeyedPerIp()
    {
        $app = $this->getAppWithErrorHandling();

        $requestFrom = function (string $ip) {
            return $this->createRequest(
                'GET',
                self::USERS_LIST_ROUTE,
                ['HTTP_ACCEPT' => self::CONTENT_TYPE_JSON],
                [],
                ['REMOTE_ADDR' => $ip]
            );
        };

        for ($i = 0; $i < 3; $i++) {
            $app->handle($requestFrom('1.2.3.4'));
        }

        $this->assertEquals(429, $app->handle($requestFrom('1.2.3.4'))->getStatusCode());

        $otherIpResponse = $app->handle($requestFrom('5.6.7.8'));

        $this->assertEquals(200, $otherIpResponse->getStatusCode());
        $this->assertSame('2', $otherIpResponse->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function testRateLimitIsKeyedPerUser()
    {
        $app = $this->getAppWithErrorHandling();

        $requestFrom = function (int $userId) {
            return $this->createRequest('GET', self::USERS_LIST_ROUTE)->withAttribute('user_id', $userId);
        };

        for ($i = 0; $i < 3; $i++) {
            $app->handle($requestFrom(1));
        }

        $this->assertEquals(429, $app->handle($requestFrom(1))->getStatusCode());

        $otherUserResponse = $app->handle($requestFrom(2));

        $this->assertEquals(200, $otherUserResponse->getStatusCode());
        $this->assertSame('2', $otherUserResponse->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function testDisabledRateLimitDoesNotLimit()
    {
        putenv('APP_RATE_LIMIT=0');

        $app = $this->getAppWithErrorHandling();

        for ($i = 0; $i < 3; $i++) {
            $response = $app->handle($this->createRequest('GET', self::USERS_LIST_ROUTE));

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertSame('', $response->getHeaderLine('X-RateLimit-Limit'));
        }
    }

    public function testRateLimitIsKeyedByIpAddressAttribute()
    {
        $middleware = $this->buildMiddleware();

        $request = $this->createRequest('GET', '/')->withAttribute('ip_address', '9.9.9.9');

        $firstResponse = $middleware->process($request, $this->staticHandler());
        $secondResponse = $middleware->process($request, $this->staticHandler());

        $this->assertEquals(200, $firstResponse->getStatusCode());
        $this->assertSame('0', $firstResponse->getHeaderLine('X-RateLimit-Remaining'));
        $this->assertEquals(429, $secondResponse->getStatusCode());
    }

    public function testRateLimitFallsBackToUnknownIpWithoutServerAddr()
    {
        $middleware = $this->buildMiddleware();

        $request = $this->createRequest('GET', '/');

        $firstResponse = $middleware->process($request, $this->staticHandler());
        $secondResponse = $middleware->process($request, $this->staticHandler());

        $this->assertEquals(200, $firstResponse->getStatusCode());
        $this->assertEquals(429, $secondResponse->getStatusCode());
    }

    public function testRateLimitIsKeyedBySessionUserId()
    {
        $middleware = $this->buildMiddleware();

        $request = $this->createRequest('GET', '/')->withAttribute('session', ['user_id' => 7]);

        $firstResponse = $middleware->process($request, $this->staticHandler());
        $secondResponse = $middleware->process($request, $this->staticHandler());

        $this->assertEquals(200, $firstResponse->getStatusCode());
        $this->assertEquals(429, $secondResponse->getStatusCode());
    }

    public function testUserIdAttributeTakesPrecedenceOverSessionUserId()
    {
        $middleware = $this->buildMiddleware();

        $firstRequest = $this->createRequest('GET', '/')
            ->withAttribute('user_id', 1)
            ->withAttribute('session', ['user_id' => 2]);

        $secondRequest = $this->createRequest('GET', '/')
            ->withAttribute('user_id', 1)
            ->withAttribute('session', ['user_id' => 3]);

        $firstResponse = $middleware->process($firstRequest, $this->staticHandler());
        $secondResponse = $middleware->process($secondRequest, $this->staticHandler());

        // Both requests resolve to the same user key (the user_id attribute),
        // regardless of the differing session user ids.
        $this->assertEquals(200, $firstResponse->getStatusCode());
        $this->assertEquals(429, $secondResponse->getStatusCode());
    }

    private function buildMiddleware(): RateLimitMiddleware
    {
        return new RateLimitMiddleware(new ArrayAdapter(), new Settings([
            'rate_limit' => ['enabled' => true, 'limit' => 1, 'window' => 60],
        ]));
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
