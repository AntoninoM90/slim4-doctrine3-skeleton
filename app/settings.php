<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

$env = function (string $key, $default = null) {
    $value = getenv($key);

    return $value === false || $value === '' ? $default : $value;
};

// Database connection settings (see .env.example)
$dbDriver = $env('APP_DB_DRIVER', 'pdo_sqlite');
$dbCharset = $env('APP_DB_CHARSET', 'utf8');

$connection = [
    'driver' => $dbDriver,
    'charset' => $dbCharset,
];

if ($dbDriver === 'pdo_sqlite') {
    $connection['path'] = $env('APP_DB_PATH', __DIR__ . '/../var/data.db');
} else {
    $connection['host'] = $env('APP_DB_HOST', '127.0.0.1');
    $connection['port'] = (int) $env('APP_DB_PORT', $dbDriver === 'pdo_pgsql' ? 5432 : 3306);
    $connection['dbname'] = $env('APP_DB_NAME', 'slim_app');
    $connection['user'] = $env('APP_DB_USER', 'slim_app');
    $connection['password'] = $env('APP_DB_PASSWORD', '');
}

return function (ContainerBuilder $containerBuilder) use ($connection) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () use ($connection) {
            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError' => false,
                'logErrorDetails' => false,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],

                // Doctrine ORM settings
                'doctrine' => [
                    // if true, metadata caching is forcefully disabled
                    'dev_mode' => true,

                    // you should add any other path containing annotated entity classes
                    'metadata_dirs' => [__DIR__ . '/../src/Domain'],

                    'proxy_dir' => __DIR__ . '/../var/proxy',

                    'connections' => [
                        'default' => $connection,
                    ],
                ],
            ]);
        }
    ]);
};
