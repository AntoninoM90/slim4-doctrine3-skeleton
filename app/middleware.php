<?php

declare(strict_types=1);

use App\Application\Middleware\CorsMiddleware;
use App\Application\Middleware\RateLimitMiddleware;
use App\Application\Middleware\ResponseCacheMiddleware;
use App\Application\Middleware\SessionMiddleware;
use Slim\App;

return function (App $app) {
    // Slim runs the middleware added last first: CORS is registered last so
    // it wraps every other middleware (and thus also reaches preflight
    // OPTIONS requests before they are routed).
    $app->add(SessionMiddleware::class);
    $app->add(ResponseCacheMiddleware::class);
    $app->add(RateLimitMiddleware::class);
    $app->add(CorsMiddleware::class);
};
