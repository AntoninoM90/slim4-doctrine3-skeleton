<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\CurrentCommand;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\ListCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

/** @var EntityManager $entityManager */
$entityManager = $container->get(EntityManager::class);

// Build the migrations dependency factory from the shared EntityManager and
// register the migrations commands next to the ORM ones (see app/migrations.php).
$dependencyFactory = DependencyFactory::fromEntityManager(
    new PhpFile(__DIR__ . '/../app/migrations.php'),
    new ExistingEntityManager($entityManager)
);

$migrationCommands = [
    new CurrentCommand($dependencyFactory),
    new DiffCommand($dependencyFactory),
    new DumpSchemaCommand($dependencyFactory),
    new ExecuteCommand($dependencyFactory),
    new GenerateCommand($dependencyFactory),
    new LatestCommand($dependencyFactory),
    new ListCommand($dependencyFactory),
    new MigrateCommand($dependencyFactory),
    new RollupCommand($dependencyFactory),
    new StatusCommand($dependencyFactory),
    new SyncMetadataCommand($dependencyFactory),
    new UpToDateCommand($dependencyFactory),
    new VersionCommand($dependencyFactory),
];

ConsoleRunner::run(
    new SingleManagerProvider($entityManager),
    $migrationCommands
);
