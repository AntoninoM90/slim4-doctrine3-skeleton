<?php

declare(strict_types=1);

use App\Application\Middleware\CorsMiddleware;
use App\Application\Middleware\ForceHttpsMiddleware;
use App\Application\Middleware\RateLimitMiddleware;
use App\Application\Middleware\ResponseCacheMiddleware;
use App\Application\Middleware\SecurityHeadersMiddleware;
use App\Application\Middleware\SessionMiddleware;
use Slim\App;

return function (App $app) {
    // Slim runs the middleware added last first: security headers, force
    // HTTPS, CORS and rate limiting are registered last so they wrap every
    // other middleware (and thus also reach preflight OPTIONS requests,
    // rate-limit responses and HTTPS redirects before they are emitted).
    $app->add(SessionMiddleware::class);
    $app->add(ResponseCacheMiddleware::class);
    $app->add(RateLimitMiddleware::class);
    $app->add(CorsMiddleware::class);
    $app->add(ForceHttpsMiddleware::class);
    $app->add(SecurityHeadersMiddleware::class);
};
