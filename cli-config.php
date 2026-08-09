<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManager;

require __DIR__ . '/vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$settings = require __DIR__ . '/app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/app/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

/** @var EntityManager $entityManager */
$entityManager = $container->get(EntityManager::class);

// Doctrine Migrations convention: this file is discovered by
// "vendor/bin/doctrine-migrations" and must return a DependencyFactory.
return DependencyFactory::fromEntityManager(
    new PhpFile(__DIR__ . '/app/migrations.php'),
    new ExistingEntityManager($entityManager)
);
