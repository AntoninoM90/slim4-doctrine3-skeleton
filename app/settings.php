<?php

declare(strict_types=1);

use App\Application\Settings\Environment;
use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            // Application environment: dev (default), test or prod (see .env.example)
            $appEnv = (string) Environment::get('APP_ENV', 'dev');

            // Database connection settings (see .env.example)
            $dbDriver = Environment::get('APP_DB_DRIVER', 'pdo_sqlite');
            $dbCharset = Environment::get('APP_DB_CHARSET', 'utf8');

            $connection = [
                'driver' => $dbDriver,
                'charset' => $dbCharset,
            ];

            if ($dbDriver === 'pdo_sqlite') {
                $connection['path'] = Environment::get('APP_DB_PATH', __DIR__ . '/../var/data.db');
            } else {
                $connection['host'] = Environment::get('APP_DB_HOST', '127.0.0.1');
                $connection['port'] = (int) Environment::get('APP_DB_PORT', $dbDriver === 'pdo_pgsql' ? 5432 : 3306);
                $connection['dbname'] = Environment::get('APP_DB_NAME', 'slim_app');
                $connection['user'] = Environment::get('APP_DB_USER', 'slim_app');
                $connection['password'] = Environment::get('APP_DB_PASSWORD', '');
            }

            return new Settings([
                'displayErrorDetails' => $appEnv !== 'prod', // Should be set to false in production
                'logError' => false,
                'logErrorDetails' => false,
                'logger' => [
                    'name' => 'slim-app',
                    // Separate log file per environment: logs/dev.log, logs/prod.log
                    'path' => __DIR__ . '/../logs/' . $appEnv . '.log',
                    // Verbose in development, warnings and above in production
                    'level' => $appEnv === 'prod' ? Logger::INFO : Logger::DEBUG,
                    // In Docker, logs are also mirrored to stdout (see dependencies.php)
                    'stdout' => (bool) Environment::get('docker', false),
                ],

                // Doctrine ORM settings
                'doctrine' => [
                    // if true, metadata caching is forcefully disabled
                    'dev_mode' => true,

                    // you should add any other path containing annotated entity classes
                    'metadata_dirs' => [__DIR__ . '/../src/Domain'],

                    'proxy_dir' => __DIR__ . '/../var/proxy',

                    // Directory where the Doctrine cache pools (metadata,
                    // queries, results and hydration) are stored when
                    // dev_mode is false
                    'cache_dir' => __DIR__ . '/../var/cache/doctrine',

                    'connections' => [
                        'default' => $connection,
                    ],
                ],

                // HTTP response cache settings
                'http_cache' => [
                    // Cache HTTP responses only when explicitly enabled via
                    // the APP_HTTP_CACHE environment variable (default: off)
                    'enabled' => (bool) Environment::get('APP_HTTP_CACHE', false),
                    // Time-to-live for cached responses, in seconds
                    'ttl' => 60,
                    // Directory where cached responses are stored
                    'dir' => __DIR__ . '/../var/cache/http',
                ],

                // Request rate limiting settings
                'rate_limit' => [
                    // Limit requests only when explicitly enabled via the
                    // APP_RATE_LIMIT environment variable (default: off)
                    'enabled' => (bool) Environment::get('APP_RATE_LIMIT', false),
                    // Maximum number of requests allowed in a window
                    'limit' => (int) Environment::get('APP_RATE_LIMIT_MAX', 60),
                    // Window size, in seconds
                    'window' => (int) Environment::get('APP_RATE_LIMIT_WINDOW', 60),
                    // Directory where request counters are stored
                    'dir' => __DIR__ . '/../var/cache/rate_limit',
                ],

                // Cross-origin resource sharing (CORS) settings
                'cors' => [
                    // Handle cross-origin requests only when explicitly
                    // enabled via the APP_CORS_ENABLED environment variable
                    // (default: off)
                    'enabled' => (bool) Environment::get('APP_CORS_ENABLED', false),
                    // Origins allowed to make cross-origin requests, as a
                    // comma-separated list (override via APP_CORS_ORIGINS)
                    'allowed_origins' => array_values(array_filter(array_map(
                        'trim',
                        explode(',', (string) Environment::get(
                            'APP_CORS_ORIGINS',
                            'http://localhost:3000,http://localhost:5173,http://localhost:4200,'
                            . 'http://127.0.0.1:3000,http://127.0.0.1:5173,http://127.0.0.1:4200'
                        ))
                    ))),
                    // HTTP methods advertised in preflight responses
                    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                    // Request headers advertised in preflight responses
                    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
                    // Response headers exposed to browser clients
                    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'],
                    // How long a preflight response may be cached by the
                    // browser, in seconds
                    'max_age' => 86400,
                    // Whether Access-Control-Allow-Credentials is sent
                    'allow_credentials' => true,
                ],

                // HTTP security headers settings
                'security_headers' => [
                    // Add security headers to every response when enabled via
                    // the APP_SECURITY_HEADERS environment variable
                    // (default: on)
                    'enabled' => (bool) Environment::get('APP_SECURITY_HEADERS', true),
                    // Headers added to every response. Add or remove entries
                    // as needed.
                    //
                    // The Content-Security-Policy is compatible with the
                    // bundled Swagger UI (served from unpkg.com): the
                    // {nonce} placeholder is replaced per request by the
                    // security headers middleware with a random nonce that
                    // the /docs route also emits on its inline style and
                    // script tags. Strict-Transport-Security is
                    // intentionally omitted: it must only be sent over
                    // HTTPS.
                    'headers' => [
                        'X-Frame-Options' => 'DENY',
                        'X-Content-Type-Options' => 'nosniff',
                        'Referrer-Policy' => 'strict-origin-when-cross-origin',
                        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
                        'Cross-Origin-Opener-Policy' => 'same-origin',
                        'Cross-Origin-Resource-Policy' => 'same-origin',
                        'Content-Security-Policy' =>
                            "default-src 'self'; "
                            . "script-src 'self' https://unpkg.com 'nonce-{nonce}'; "
                            . "style-src 'self' 'unsafe-inline' https://unpkg.com; "
                            . "img-src 'self' data: https://validator.swagger.io; "
                            . "font-src 'self' data:; "
                            . "connect-src 'self' https://validator.swagger.io; "
                            . "object-src 'none'; "
                            . "base-uri 'self'",
                    ],
                ],
            ]);
        }
    ]);
};
