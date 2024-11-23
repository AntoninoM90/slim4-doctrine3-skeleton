<?php

declare(strict_types=1);

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\ResponseEmitter\ResponseEmitter;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

if (false) { // Should be set to true in production
  $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// Set up settings
$settings = require __DIR__ . '/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$callableResolver = $app->getCallableResolver();

// Register middlewares
$middleware = require __DIR__ . '/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/routes.php';
$routes($app);

// Get settings from container
/** @var SettingsInterface $settings */
$settings = $container->get(SettingsInterface::class);

// Get the logger from the container
/** @var LoggerInterface $logger */
$logger = $container->get(LoggerInterface::class);

// Get the display and error logging settings
/** @var bool $displayErrorDetails */
$displayErrorDetails = $settings->get('displayErrorDetails');

/** @var bool $logError */
$logError = $settings->get('logError');

/** @var bool $logErrorDetails */
$logErrorDetails = $settings->get('logErrorDetails');

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Ger the response factory and create the error handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler(
    $callableResolver,
    $responseFactory,
    $logger
);

// Create and register the shutdown handler
$shutdownHandler = new ShutdownHandler(
    $request,
    $errorHandler,
    $displayErrorDetails,
    $logError,
    $logErrorDetails
);
register_shutdown_function($shutdownHandler);

// Add the Slim routing middleware
$app->addRoutingMiddleware();

// Add the Slim body parsing middleware
$app->addBodyParsingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    $displayErrorDetails,
    $logError,
    $logErrorDetails,
    $logger
);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Run the application and emit the application response
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
