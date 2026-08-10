# Slim Framework 4 Skeleton Application with Doctrine ORM 3

[![Build Status](https://github.com/AntoninoM90/slim4-doctrine-skeleton/workflows/Tests/badge.svg)](https://github.com/AntoninoM90/slim4-doctrine-skeleton/actions)
[![codecov](https://codecov.io/gh/AntoninoM90/slim4-doctrine-skeleton/graph/badge.svg)](https://codecov.io/gh/AntoninoM90/slim4-doctrine-skeleton)

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

To run the test suite with a code coverage report:

```bash
composer test:coverage
```

This requires Xdebug (or PCOV). The Docker image built from the `Dockerfile` ships
Xdebug, so inside the container the command works out of the box. The HTML report is
written to `var/coverage/` (open `var/coverage/index.html` in your browser) and a text
summary is printed to the console; a machine-readable `var/coverage/clover.xml` is also
generated. On every push to `main` the GitHub Actions workflow uploads the coverage to
Codecov, which feeds the badge at the top of this file.

That's it! Now go build something cool.

## Environment and Logging

Set `APP_ENV` in `.env` to select the environment: `dev` (default), `test` or `prod`.

Monolog writes to a separate file per environment inside `logs/`, e.g. `logs/dev.log`,
`logs/prod.log` and `logs/test.log`. In `dev` (and `test`) the log level is `DEBUG` and
error details are displayed; in `prod` the level is `INFO` and error details are hidden.

When running under Docker Compose, log lines are also mirrored to stdout, so they are
visible with `docker compose logs slim`.

## HTTP Response Caching

The application can cache HTTP `GET`/`HEAD` responses in a Symfony Cache pool.
It is **disabled by default**; to enable it, set `APP_HTTP_CACHE=1` in your
`.env` file (or change the `http_cache.enabled` value in `app/settings.php`):

```bash
APP_HTTP_CACHE=1
```

When enabled:

- only `GET`/`HEAD` requests are cached, keyed by the full request URI;
- requests carrying an `Authorization` or `Cookie` header are never cached;
- only successful (2xx) responses without a `Set-Cookie` header are stored;
- cached responses expire after the `http_cache.ttl` (60 seconds by default)
  and are stored in the `http_cache.dir` directory (`var/cache/http`);
- responses handled by the cache layer carry an `X-Cache` header with one of
  the values `HIT` (served from cache), `MISS` (stored on first request) or
  `SKIP` (the request is not cacheable).

## Rate Limiting

The application can rate-limit requests per client IP address (or per user id,
when the request carries one) with a fixed-window counter backed by a Symfony
Cache pool.

It is **disabled by default**; to enable it, set `APP_RATE_LIMIT=1`
in your `.env` file (or change the `rate_limit.enabled` value in
`app/settings.php`):

```bash
APP_RATE_LIMIT=1
```

When enabled:

- every request counts against a fixed window of `rate_limit.window` seconds
  (60 by default); once `rate_limit.limit` requests (60 by default) are used
  up, the client receives a `429 Too Many Requests` response;
- the requestor key is the client IP address, unless the request carries a
  user id (via the `user_id` request attribute or the `user_id` session key),
  in which case the limit applies to that user instead;
- allowed responses carry `X-RateLimit-Limit`, `X-RateLimit-Remaining` and
  `X-RateLimit-Reset` headers; limited responses also carry a `Retry-After`
  header;
- counters expire at the end of each window and are stored in the
  `rate_limit.dir` directory (`var/cache/rate_limit`);
- the limits can be tuned with the `APP_RATE_LIMIT_MAX` and
  `APP_RATE_LIMIT_WINDOW` environment variables.

## Cross-Origin Resource Sharing (CORS)

The CORS middleware is **disabled by default**. To enable it, set
`APP_CORS_ENABLED=1` in your `.env` file (or the `cors.enabled` value in
`app/settings.php`); when disabled it is a no-op and every request is
forwarded without any CORS handling.

When enabled, the application accepts cross-origin requests from the origins
listed under `cors.allowed_origins` in `app/settings.php`. By default these
are common local development origins (`http://localhost:3000`,
`http://localhost:5173`, `http://localhost:4200` and their `127.0.0.1`
counterparts). To change the allow-list, set the `APP_CORS_ORIGINS`
environment variable to a comma-separated list of origins:

```bash
APP_CORS_ORIGINS=https://app.example.com,https://admin.example.com
```

How the `CorsMiddleware` behaves:

- requests without an `Origin` header (same-origin or non-browser clients)
  are forwarded untouched;
- preflight requests (an `OPTIONS` request carrying an
  `Access-Control-Request-Method` header) from allowed origins are answered
  directly with a `204` that advertises the allowed methods, headers and
  `Access-Control-Max-Age`, and never reach the routing layer;
- preflights from unknown origins get a `403 Forbidden`;
- actual requests from allowed origins are processed normally and the
  response receives `Access-Control-Allow-Origin` (echoing the concrete
  origin, never `*`), `Access-Control-Allow-Credentials`,
  `Access-Control-Expose-Headers` and a `Vary: Origin` header;
- actual requests from unknown origins are still processed, but without CORS
  headers, so the browser blocks the response client-side.

Because the allowed origins are exact matches and the middleware echoes the
requesting origin, the responses are safe to combine with
`Access-Control-Allow-Credentials: true`, which is sent by default. The other
CORS settings (`allowed_methods`, `allowed_headers`, `exposed_headers`,
`max_age` and `allow_credentials`) can be tuned in `app/settings.php`. The
`exposed_headers` list includes the rate limit headers, so browser clients can
read them.

## Security Headers

A `SecurityHeadersMiddleware` adds HTTP security headers to every response
produced inside the middleware stack (including CORS preflight and rate-limit
responses). It is **enabled by default**; to disable it, set
`APP_SECURITY_HEADERS=0` in your `.env` file (or the
`security_headers.enabled` value in `app/settings.php`).

The headers are configured under `security_headers.headers` in
`app/settings.php` and default to:

```php
'X-Frame-Options' => 'DENY',
'X-Content-Type-Options' => 'nosniff',
'Referrer-Policy' => 'strict-origin-when-cross-origin',
'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
'Cross-Origin-Opener-Policy' => 'same-origin',
'Cross-Origin-Resource-Policy' => 'same-origin',
```

Two headers are intentionally **not** set by default:

- `Content-Security-Policy` — the bundled Swagger UI (`/docs`) loads its
  assets from a CDN and uses inline styles, so a policy would break it. Add a
  policy that covers those sources if you want to enforce one.
- `Strict-Transport-Security` — it must only be sent over HTTPS. Enable it in
  production once the API is served over TLS.

Note that responses produced by the error handler (404/405/500) are created
outside the middleware stack and therefore do not carry these headers; add
them in the error handler if you need them there.

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

Then generate a migration for the new table and apply it (see "Database Migrations" below).

## Database Migrations

Database changes are managed with Doctrine Migrations (`doctrine/migrations`).
Migrations are plain PHP classes stored in the `migrations/` directory
(namespace `App\Migration`) and executed through the Doctrine console:

```bash
# Show the status of the migrations
php bin/doctrine.php migrations:status

# Apply all pending migrations
php bin/doctrine.php migrations:migrate

# Revert the last applied migration
php bin/doctrine.php migrations:migrate prev
```

The same commands are available through `composer doctrine` (e.g.
`composer doctrine -- migrations:migrate`). The standalone
`vendor/bin/doctrine-migrations` binary works too, since `cli-config.php`
returns the migrations `DependencyFactory`.

The migration configuration lives in `app/migrations.php`:

- migration classes are stored in `migrations/` under the `App\Migration`
  namespace (loaded by doctrine/migrations itself, no composer autoload entry
  needed);
- executed versions are recorded in the `doctrine_migration_versions` table;
- each migration runs inside a transaction where the driver supports it
  (`all_or_nothing`).

The skeleton ships one initial migration (`migrations/Version20260809122208.php`)
that creates the `user` table. It was generated with `migrations:diff` against
an empty database, so the SQL it contains is SQLite-specific. When you switch
to PostgreSQL or MySQL, drop the database and regenerate a platform-specific
migration instead:

```bash
# with APP_DB_DRIVER set to the target platform
php bin/doctrine.php migrations:diff
php bin/doctrine.php migrations:migrate
```

After changing an entity, generate the migration for the change with:

```bash
php bin/doctrine.php migrations:diff
php bin/doctrine.php migrations:migrate
```

To adopt migrations on a database that already contains the schema (for
example an existing project), mark the current migration as already executed
instead of applying it:

```bash
php bin/doctrine.php migrations:version --add App\Migration\Version20260809122208
```

> **Note:** `orm:validate-schema` reports the `doctrine_migration_versions`
> table as a schema difference. Use `orm:validate-schema --skip-sync` (as the
> CI workflow does) to skip that check.

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

# List the available migrations and their status
php bin/doctrine.php migrations:list

# Generate a new empty migration
php bin/doctrine.php migrations:generate

# Generate a migration for the differences between the entities and the database
php bin/doctrine.php migrations:diff
```

In a Symfony application the same commands are available as `bin/console doctrine:schema:update --dump-sql` / `--force`, `bin/console doctrine:schema:create`, `bin/console doctrine:validate-schema`, etc.

## API Documentation (Swagger/OpenAPI)

The API is documented with [`zircote/swagger-php`](https://github.com/zircote/swagger-php) and exposed through Swagger UI.

Start the application, then open:

- `http://localhost:8080/docs` — interactive Swagger UI
- `http://localhost:8080/docs.json` — the raw OpenAPI specification (JSON)

Global metadata (API info, servers, schemas, tags) lives in `src/Application/Documentation/OpenApiDocumentation.php`. Endpoints are documented with `#[OA\*]` attributes directly on the action classes, for example `src/Application/Actions/User/ListUsersAction.php`:

```php
use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/users',
    tags: ['Users'],
    summary: 'List all users',
    operationId: 'listUsers',
    responses: [
        new OA\Response(
            response: 200,
            description: 'List of users',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/User')
            ),
        ),
    ],
)]
class ListUsersAction extends UserAction
{
    // ...
}
```

Whenever you add or modify an endpoint, just add the corresponding `#[OA\*]` attributes and the documentation updates automatically.
