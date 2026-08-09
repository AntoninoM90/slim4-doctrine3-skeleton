<?php

declare(strict_types=1);

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;

$configuration = new Configuration();

// Migration classes live in the migrations/ directory, under the
// App\Migration namespace (loaded by doctrine/migrations itself, so no
// composer autoload entry is required).
$configuration->addMigrationsDirectory('App\Migration', __DIR__ . '/../migrations');

// Table where the executed migration versions are recorded.
$storage = new TableMetadataStorageConfiguration();
$storage->setTableName('doctrine_migration_versions');
$configuration->setMetadataStorageConfiguration($storage);

// Each migration either applies completely or not at all.
$configuration->setAllOrNothing(true);

return $configuration;
