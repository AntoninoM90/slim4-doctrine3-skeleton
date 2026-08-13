<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use App\Application\Middleware\SessionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Tests\TestCase;

class SessionMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_abort();
        }

        parent::tearDown();
    }

    public function testSessionIsStartedAndAttachedWhenAuthorizationIsPresent()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token';

        $middleware = new SessionMiddleware();

        $handler = $this->capturingHandler();

        $response = $middleware->process($this->createRequest('GET', '/'), $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertSame($_SESSION, $handler->captured->getAttribute('session'));
    }

    public function testRequestWithoutAuthorizationIsForwardedWithoutSession()
    {
        $middleware = new SessionMiddleware();

        $handler = $this->capturingHandler();

        $response = $middleware->process($this->createRequest('GET', '/'), $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(PHP_SESSION_NONE, session_status());
        $this->assertNull($handler->captured->getAttribute('session'));
    }

    /**
     * @return class RequestHandler&object{captured: ?Request}
     */
    private function capturingHandler(): RequestHandler
    {
        return new class implements RequestHandler {
            public ?Request $captured = null;

            public function handle(Request $request): Response
            {
                $this->captured = $request;

                return new SlimResponse(200);
            }
        };
    }
}
