<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use App\Application\Middleware\ForceHttpsMiddleware;
use App\Application\Settings\Settings;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response as SlimResponse;
use Slim\Psr7\Uri;
use Tests\TestCase;

class ForceHttpsMiddlewareTest extends TestCase
{
    public function testHttpRequestIsRedirectedToHttps()
    {
        $settings = new Settings([
            'https' => [
                'enabled' => true,
                'status_code' => StatusCodeInterface::STATUS_PERMANENT_REDIRECT,
                'trust_forwarded_proto' => true,
            ],
        ]);

        $middleware = new ForceHttpsMiddleware($settings);

        $response = $middleware->process(
            $this->buildRequest('http', 'api.example.com', '/user/1', [], null, 'page=2'),
            $this->createHandler()
        );

        $this->assertSame(StatusCodeInterface::STATUS_PERMANENT_REDIRECT, $response->getStatusCode());
        $this->assertSame('https://api.example.com/user/1?page=2', $response->getHeaderLine('Location'));
    }

    public function testHttpRequestOnDefaultPortDropsItFromRedirect()
    {
        $settings = new Settings([
            'https' => ['enabled' => true],
        ]);

        $middleware = new ForceHttpsMiddleware($settings);

        $response = $middleware->process(
            $this->buildRequest('http', 'api.example.com', '/docs', [], 80),
            $this->createHandler()
        );

        $this->assertSame('https://api.example.com/docs', $response->getHeaderLine('Location'));
    }

    public function testHttpsRequestIsForwarded()
    {
        $settings = new Settings([
            'https' => ['enabled' => true],
        ]);

        $middleware = new ForceHttpsMiddleware($settings);

        $response = $middleware->process(
            $this->buildRequest('https', 'api.example.com', '/'),
            $this->createHandler()
        );

        $this->assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
    }

    public function testXForwardedProtoHttpsRequestIsForwarded()
    {
        $settings = new Settings([
            'https' => ['enabled' => true],
        ]);

        $middleware = new ForceHttpsMiddleware($settings);

        $response = $middleware->process(
            $this->buildRequest('http', 'api.example.com', '/', ['X-Forwarded-Proto' => 'https']),
            $this->createHandler()
        );

        $this->assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
    }

    public function testUntrustedXForwardedProtoDoesNotSkipRedirect()
    {
        $settings = new Settings([
            'https' => [
                'enabled' => true,
                'trust_forwarded_proto' => false,
            ],
        ]);

        $middleware = new ForceHttpsMiddleware($settings);

        $response = $middleware->process(
            $this->buildRequest('http', 'api.example.com', '/', ['X-Forwarded-Proto' => 'https']),
            $this->createHandler()
        );

        $this->assertSame(StatusCodeInterface::STATUS_PERMANENT_REDIRECT, $response->getStatusCode());
    }

    public function testDisabledMiddlewareForwardsRequest()
    {
        $settings = new Settings([
            'https' => ['enabled' => false],
        ]);

        $middleware = new ForceHttpsMiddleware($settings);

        $response = $middleware->process(
            $this->buildRequest('http', 'api.example.com', '/'),
            $this->createHandler()
        );

        $this->assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
    }

    private function createHandler(): RequestHandler
    {
        return new class implements RequestHandler {
            public function handle(Request $request): Response
            {
                return new SlimResponse(StatusCodeInterface::STATUS_OK);
            }
        };
    }

    /**
     * @param array<string, string> $headers
     */
    private function buildRequest(
        string $scheme,
        string $host,
        string $path,
        array $headers = [],
        ?int $port = null,
        string $query = ''
    ): Request {
        $uri = new Uri($scheme, $host, $port, $path, $query);

        $handle = fopen('php://temp', 'w+');
        $stream = (new StreamFactory())->createStreamFromResource($handle);

        $requestHeaders = new Headers();

        foreach ($headers as $name => $value) {
            $requestHeaders->addHeader($name, $value);
        }

        return new SlimRequest('GET', $uri, $requestHeaders, [], [], $stream);
    }
}
