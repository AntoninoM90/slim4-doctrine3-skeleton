<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Exception;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;

class TestCase extends PHPUnit_TestCase
{
    use ProphecyTrait;

    /**
     * @return App
     * @throws Exception
     */
    protected function getAppInstance(): App
    {
        // Instantiate PHP-DI ContainerBuilder
        $containerBuilder = new ContainerBuilder();

        // Container intentionally not compiled for tests.

        // Set up settings
        $settings = require __DIR__ . '/../app/settings.php';
        $settings($containerBuilder);

        // Set up dependencies
        $dependencies = require __DIR__ . '/../app/dependencies.php';
        $dependencies($containerBuilder);

        // Build PHP-DI Container instance
        $container = $containerBuilder->build();

        // Instantiate the app
        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // Register middleware
        $middleware = require __DIR__ . '/../app/middleware.php';
        $middleware($app);

        // Register routes
        $routes = require __DIR__ . '/../app/routes.php';
        $routes($app);

        return $app;
    }

    /**
     * Get an app instance wired with routing, body parsing and the project
     * error handler, so handled requests return JSON error payloads instead
     * of throwing exceptions.
     *
     * @return App
     * @throws Exception
     */
    protected function getAppWithErrorHandling(): App
    {
        $app = $this->getAppInstance();

        /** @var ContainerInterface $container */
        $container = $app->getContainer();

        /** @var SettingsInterface $settings */
        $settings = $container->get(SettingsInterface::class);

        /** @var bool $displayErrorDetails */
        $displayErrorDetails = $settings->get('displayErrorDetails');

        /** @var bool $logError */
        $logError = $settings->get('logError');

        /** @var bool $logErrorDetails */
        $logErrorDetails = $settings->get('logErrorDetails');

        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);

        $errorHandler = new HttpErrorHandler(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            $logger
        );

        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();
        $errorMiddleware = $app->addErrorMiddleware(
            $displayErrorDetails,
            $logError,
            $logErrorDetails,
            $logger
        );
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        return $app;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $headers
     * @param array  $cookies
     * @param array  $serverParams
     * @return Request
     */
    protected function createRequest(
        string $method,
        string $path,
        array $headers = ['HTTP_ACCEPT' => 'application/json'],
        array $cookies = [],
        array $serverParams = []
    ): Request {
        $uri = new Uri('', '', 80, $path);
        $handle = fopen('php://temp', 'w+');
        $stream = (new StreamFactory())->createStreamFromResource($handle);

        $h = new Headers();
        foreach ($headers as $name => $value) {
            $h->addHeader($name, $value);
        }

        return new SlimRequest($method, $uri, $h, $cookies, $serverParams, $stream);
    }
}
