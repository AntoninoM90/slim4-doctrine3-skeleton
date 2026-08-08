# Slim Framework 4 Skeleton Application with Doctrine ORM 3

[![Build Status](https://github.com/AntoninoM90/slim4-doctrine-skeleton/workflows/Tests/badge.svg)](https://github.com/AntoninoM90/slim4-doctrine-skeleton/actions)

Use this skeleton application to quickly setup and start working on a new Slim Framework 4 application. This application uses the latest Slim 4 with Slim PSR-7 implementation and PHP-DI container implementation. It also uses the Monolog logger.

This skeleton application was built for Composer. This makes setting up a new Slim Framework application quick and easy.

## Install the Application

Run this command from the directory in which you want to clone the Slim Framework application skeleton. You will require PHP 8.3 or newer.

```bash
git clone https://github.com/AntoninoM90/slim4-doctrine3-skeleton.git
```

You'll want to:

* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` is web writable.

To run the application in development, you can run these commands

```bash
cd [my-app-name]
composer start
```

Or you can use `docker-compose` to run the app with `docker`, so you can run these commands:
```bash
cd [my-app-name]
docker-compose up -d
```
After that, open `http://localhost:8080` in your browser.

The `docker-compose.yml` runs with SQLite out of the box: the database file `var/data.db` persists on the host through the bind-mounted project directory. To use PostgreSQL or MySQL instead, follow the commented examples in `docker-compose.yml` (they include a database service with a named volume so the data survives container restarts) and set the matching values in `.env` (e.g. `APP_DB_DRIVER=pdo_pgsql` and `APP_DB_HOST=postgres`).

Run this command in the application directory to run the test suite

```bash
composer test
```

That's it! Now go build something cool.

## Environment and Logging

Set `APP_ENV` in `.env` to select the environment: `dev` (default), `test` or `prod`.

Monolog writes to a separate file per environment inside `logs/`, e.g. `logs/dev.log`,
`logs/prod.log` and `logs/test.log`. In `dev` (and `test`) the log level is `DEBUG` and
error details are displayed; in `prod` the level is `INFO` and error details are hidden.

When running under Docker Compose, log lines are also mirrored to stdout, so they are
visible with `docker compose logs slim`.

## Database Configuration

The database connection is configured in `app/settings.php` under the `doctrine` key. The skeleton ships with SQLite out of the box:

```php
'doctrine' => [
    // if true, metadata caching is forcefully disabled
    'dev_mode' => true,

    // paths containing entity classes
    'metadata_dirs' => [__DIR__ . '/../src/Domain'],

    'proxy_dir' => __DIR__ . '/../var/proxy',

    'connections' => [
        'default' => [
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/../var/data.db',
            'charset' => 'utf8'
        ],
    ],
],
```

To switch to MySQL, replace the `default` connection with:

```php
'default' => [
    'driver' => 'pdo_mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'slim_skeleton',
    'user' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
],
```

For PostgreSQL:

```php
'default' => [
    'driver' => 'pdo_pgsql',
    'host' => '127.0.0.1',
    'port' => 5432,
    'dbname' => 'slim_skeleton',
    'user' => 'postgres',
    'password' => '',
    'charset' => 'utf8',
],
```

Make sure the corresponding PDO extension is installed and enabled in your `php.ini` (`pdo_mysql`, `pdo_pgsql`, ...).

## Creating a New Entity

Entities are plain PHP classes annotated with Doctrine attributes. They must live in a directory listed in `metadata_dirs` (default: `src/Domain`).

Example `src/Domain/Category/Category.php`:

```php
<?php

declare(strict_types=1);

namespace App\Domain\Category;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'category')]
class Category
{
    #[Id, Column(type: 'integer'), GeneratedValue('IDENTITY')]
    private ?int $id;

    #[Column(type: 'string', length: 100)]
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
```

Then update the database schema to create the `category` table (see below).

## Doctrine Commands

This skeleton ships a Doctrine ORM console at `bin/doctrine.php` (the equivalent of Symfony's `bin/console`):

```bash
# Show the SQL needed to bring the schema in sync with the entities
php bin/doctrine.php orm:schema-tool:update --dump-sql

# Apply the schema changes to the database
php bin/doctrine.php orm:schema-tool:update --force

# Create the schema from scratch
php bin/doctrine.php orm:schema-tool:create

# Validate that the entity mappings are correct
php bin/doctrine.php orm:validate-schema

# Show basic information about all mapped entities
php bin/doctrine.php orm:info

# Generate proxy classes for entities
php bin/doctrine.php orm:generate-proxies
```

In a Symfony application the same commands are available as `bin/console doctrine:schema:update --dump-sql` / `--force`, `bin/console doctrine:schema:create`, `bin/console doctrine:validate-schema`, etc.
