<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Set dependencies of the application.
 *
 * @param ContainerBuilder $containerBuilder The container builder
 *
 * @return void
 */
return function (
    ContainerBuilder $containerBuilder
) {
    $containerBuilder->addDefinitions([
        // Logger
        LoggerInterface::class => function (ContainerInterface $c): LoggerInterface {
            $settings = $c->get(SettingsInterface::class);

            // Get Logger settings
            $loggerSettings = $settings->get('logger');

            // Initialize the Logger
            $logger = new Logger($loggerSettings['name']);

            // Push Uid processor
            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            // Push stream handler: environment-specific log file (logs/dev.log, ...)
            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            // In Docker, also mirror logs to stdout so they show up in
            // "docker compose logs"
            if (!empty($loggerSettings['stdout'])) {
                $logger->pushHandler(new StreamHandler('php://stdout', $loggerSettings['level']));
            }

            // Return the logger
            return $logger;
        },

        // Doctrine Entity Manager
        EntityManager::class => function (ContainerInterface $container): EntityManager {
            /** @var SettingsInterface $settings */
            $settings = $container->get(SettingsInterface::class);

            /** @var array $doctrineSettings */
            $doctrineSettings = $settings->get('doctrine');

            // Create the configuration for annotation metadata
            $config = ORMSetup::createAttributeMetadataConfiguration(
                $doctrineSettings['metadata_dirs'],
                $doctrineSettings['dev_mode'],
                $doctrineSettings['proxy_dir'],
                null,
                false
            );

            // Set cache
            if ($doctrineSettings['dev_mode']) {
                $metadataCache = new ArrayAdapter();
                $queryCache = new ArrayAdapter();
                $resultCache = new ArrayAdapter();
            } else {
                $metadataCache = new PhpFilesAdapter('doctrine_metadata');
                $queryCache = new PhpFilesAdapter('doctrine_queries');
                $resultCache = new PhpFilesAdapter('doctrine_cache');
            }

            // Set metadata cache
            $config->setMetadataCache(
                $metadataCache
            );

            // Set query cache
            $config->setQueryCache(
                $queryCache
            );

            // Set result cache
            $config->setResultCache(
                $resultCache
            );

            // configuring the database connection
            $connection = DriverManager::getConnection(
                $doctrineSettings['connections']['default'],
                $config
            );

            // obtaining the entity manager
            $entityManager = new EntityManager($connection, $config);

            // Return the new entity manager created
            return $entityManager;
        },

        // Symfony Validator (attribute mapping)
        ValidatorInterface::class => function (): ValidatorInterface {
            return Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator();
        },

        // Shared Symfony Cache pool. While neither feature is enabled the
        // pool is an ephemeral ArrayAdapter. When HTTP response caching is
        // enabled a FilesystemAdapter persists responses in var/cache/http;
        // otherwise, when rate limiting is enabled, a FilesystemAdapter
        // persists request counters in var/cache/rate_limit. Both features
        // namespace their keys, so a single pool can back both at once.
        CacheItemPoolInterface::class => function (ContainerInterface $c): CacheItemPoolInterface {
            /** @var SettingsInterface $settings */
            $settings = $c->get(SettingsInterface::class);

            /** @var array<string, mixed> $httpCacheSettings */
            $httpCacheSettings = $settings->get('http_cache');

            /** @var array<string, mixed> $rateLimitSettings */
            $rateLimitSettings = $settings->get('rate_limit');

            if (!empty($httpCacheSettings['enabled'])) {
                /** @var string $namespace */
                $namespace = 'http_cache';

                /** @var int $ttl */
                $ttl = $httpCacheSettings['ttl'];

                /** @var string $dir */
                $dir = $httpCacheSettings['dir'];

                return new FilesystemAdapter($namespace, $ttl, $dir);
            }

            if (!empty($rateLimitSettings['enabled'])) {
                /** @var string $rateLimitDir */
                $rateLimitDir = $rateLimitSettings['dir'];

                return new FilesystemAdapter('rate_limit', 0, $rateLimitDir);
            }

            return new ArrayAdapter();
        },
    ]);
};
