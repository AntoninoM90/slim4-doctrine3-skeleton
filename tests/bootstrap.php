<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

require __DIR__ . '/../vendor/autoload.php';

$testDbPath = __DIR__ . '/../var/test.db';

// Remove any database file left over from a previous test run.
if (file_exists($testDbPath)) {
    unlink($testDbPath);
}

// Make the application use the dedicated test database instead of the real one.
putenv('APP_DB_DRIVER=pdo_sqlite');
putenv('APP_DB_PATH=' . $testDbPath);

// Run tests in the "test" environment so logs go to logs/test.log.
putenv('APP_ENV=test');

$doctrineSettings = [
    'dev_mode' => true,
    'metadata_dirs' => [__DIR__ . '/../src/Domain'],
    'proxy_dir' => __DIR__ . '/../var/proxy',
    'connections' => [
        'default' => [
            'driver' => 'pdo_sqlite',
            'path' => $testDbPath,
            'charset' => 'utf8',
        ],
    ],
];

$config = ORMSetup::createAttributeMetadataConfiguration(
    $doctrineSettings['metadata_dirs'],
    $doctrineSettings['dev_mode'],
    $doctrineSettings['proxy_dir'],
    null,
    false
);

$connection = DriverManager::getConnection($doctrineSettings['connections']['default'], $config);

$entityManager = new EntityManager($connection, $config);

(new SchemaTool($entityManager))->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
