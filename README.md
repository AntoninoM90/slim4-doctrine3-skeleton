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
After that, open `http://localhost:8080` in your browser (the host port is configurable with `APP_PORT` in `.env`).

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

## Usage Examples

Start the application (or the Docker container), then the endpoints below are
available:

```bash
composer start
# or: docker compose up -d
```

All examples assume the application listens on `http://localhost:8080` and
are formatted with `curl`. Responses are pretty-printed JSON wrapped in an
envelope: successful responses carry the payload under `data`, failures under
`error`:

```json
{ "statusCode": 200, "data": { "id": 1 } }
{ "statusCode": 404, "error": { "type": "RESOURCE_NOT_FOUND", "description": "..." } }
```

### Health check

```bash
curl http://localhost:8080/health
```

```json
{
    "statusCode": 200,
    "data": {
        "status": "ok",
        "checks": {
            "database": "ok",
            "cache": "ok"
        }
    }
}
```

### Root route

```bash
curl http://localhost:8080/
```

```
Hello world!
```

### List users

The skeleton ships with an empty database, so a fresh installation returns an
empty list wrapped in a pagination envelope:

```bash
curl http://localhost:8080/users
```

```json
{
    "statusCode": 200,
    "data": {
        "items": [],
        "pagination": {
            "total": 0,
            "page": 1,
            "perPage": 10,
            "totalPages": 0
        }
    }
}
```

The response is paginated: `items` holds the users of the requested page and
`pagination` carries the total count plus page metadata. The optional `page`
(default 1) and `perPage` (default 10, maximum 100) query parameters select the
page and its size:

```bash
curl "http://localhost:8080/users?page=2&perPage=2"
```

### View a single user

With no users in the database, any id is unknown:

```bash
curl http://localhost:8080/user/1
```

```json
{
    "statusCode": 404,
    "error": {
        "type": "RESOURCE_NOT_FOUND",
        "description": "The user you requested does not exist."
    }
}
```

Once a user exists (persist an `App\Domain\User\User` entity and flush it,
or create it with `POST /user`, see below), the endpoint returns the user
without the `password` field:

```bash
curl http://localhost:8080/user/1
```

```json
{
    "statusCode": 200,
    "data": {
        "id": 1,
        "username": "anna",
        "emailAddress": "anna@example.com",
        "firstName": "Anna",
        "lastName": "Smith"
    }
}
```

### Create a user

```bash
curl -X POST http://localhost:8080/user \
  -H "Content-Type: application/json" \
  -d '{"username":"anna","password":"password123","emailAddress":"anna@example.com","firstName":"Anna","lastName":"Smith"}'
```

```json
{
    "statusCode": 201,
    "data": {
        "id": 1,
        "username": "anna",
        "emailAddress": "anna@example.com",
        "firstName": "Anna",
        "lastName": "Smith"
    }
}
```

The password is stored hashed with bcrypt and never returned. Reusing an
existing `username` or `emailAddress` is a validation error, reported per
field, along with any other invalid field:

```json
{
    "statusCode": 422,
    "error": {
        "type": "VALIDATION_ERROR",
        "description": "The request is invalid.",
        "details": {
            "username": [
                "This username is already in use."
            ],
            "emailAddress": [
                "This email address is already in use."
            ]
        }
    }
}
```

Each field reports its own problems (`NotBlank`, min/max `Length`, `Email`,
uniqueness, ...), grouped by field name under `error.details`.

### Update a user

`PATCH` only changes the fields present in the body:

```bash
curl -X PATCH http://localhost:8080/user/1 \
  -H "Content-Type: application/json" \
  -d '{"lastName":"Brown"}'
```

```json
{
    "statusCode": 200,
    "data": {
        "id": 1,
        "username": "anna",
        "emailAddress": "anna@example.com",
        "firstName": "Anna",
        "lastName": "Brown"
    }
}
```

### Delete a user

```bash
curl -i -X DELETE http://localhost:8080/user/1
```

```
HTTP/1.1 204 No Content
```

### Change a user password

An admin can reset a user's password without knowing the current one. The new
password is stored hashed with bcrypt and never returned:

```bash
curl -X PATCH http://localhost:8080/user/1/password \
  -H "Content-Type: application/json" \
  -d '{"newPassword":"new-password-123"}'
```

```json
{
    "statusCode": 200,
    "data": {
        "id": 1,
        "username": "anna",
        "emailAddress": "anna@example.com",
        "firstName": "Anna",
        "lastName": "Smith"
    }
}
```

A password shorter than 8 characters is a validation error (`422`).

### API documentation

```bash
curl http://localhost:8080/docs.json   # the OpenAPI specification (JSON)
# then open http://localhost:8080/docs in your browser for the Swagger UI
```

### Rate limiting

When rate limiting is enabled (see "Rate Limiting"), every allowed response
carries the `X-RateLimit-*` headers and requests beyond the limit return
`429 Too Many Requests`:

```bash
curl -i http://localhost:8080/users
```

```
HTTP/1.1 200 OK
...
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1753876200
```

In production, HTTP requests are redirected to HTTPS with a `308` (see
"Force HTTPS"), and every response carries the security headers described in
"Security Headers".

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

The application can rate-limit requests with a **fixed-window counter**, keyed
per client IP address (or per user id when the request carries one), backed by
a Symfony Cache pool. It is **disabled by default**; to enable it, set
`APP_RATE_LIMIT=1` in your `.env` file (or the `rate_limit.enabled` value in
`app/settings.php`):

```bash
APP_RATE_LIMIT=1
```

The behaviour is configured under the `rate_limit` key in
`app/settings.php`, each option with an environment variable counterpart:

- `enabled` (`APP_RATE_LIMIT`, default `false`) — when false the middleware
  is a no-op and no `X-RateLimit-*` headers are added;
- `limit` (`APP_RATE_LIMIT_MAX`, default `60`) — maximum number of requests
  allowed in a window;
- `window` (`APP_RATE_LIMIT_WINDOW`, default `60`) — window size in seconds;
- `dir` — directory where the counters are stored (`var/cache/rate_limit`).

### How the fixed window works

Windows are aligned to **absolute time**, not to each client's first request:
with `window = 60` a window always spans `HH:MM:00`–`HH:MM:59`. A request at
`10:00:59` and one at `10:01:01` therefore fall in **different** windows even
though they are only two seconds apart, while a burst at `10:00:01` and one at
`10:00:59` share the same window. Each window starts over as soon as the clock
passes its end; `X-RateLimit-Reset` reports the exact moment the current
window ends.

### How a request is counted

- the requestor key is the **user id** when the request carries one (the
  `user_id` request attribute or the `user_id` session key), otherwise the
  **client IP** (the `ip_address` request attribute, or `REMOTE_ADDR`); an
  auth middleware can set these attributes to rate-limit per account instead
  of per IP;
- every request increments the counter for the current window; the request
  that would exceed the limit is rejected with `429 Too Many Requests`;
- allowed responses carry `X-RateLimit-Limit` (the window limit),
  `X-RateLimit-Remaining` (requests left in the window, down to `0`) and
  `X-RateLimit-Reset` (unix timestamp of the window end);
- limited responses carry a JSON body `{"message": "Too many requests"}` plus
  `X-RateLimit-Limit`, `X-RateLimit-Reset` and `Retry-After` (seconds until
  the window resets).

### Example

With `APP_RATE_LIMIT_MAX=3` and `APP_RATE_LIMIT_WINDOW=60`, three requests
from the same IP within a minute are allowed (the third reports
`X-RateLimit-Remaining: 0`); the fourth gets a `429` with `Retry-After`
counting down to the next window boundary.

### Storage

Counters are stored in a Symfony `FilesystemAdapter` (PSR-6) pool in the
`rate_limit.dir` directory; they expire `window` seconds after they are
written. Item keys are sha256-hashed, so the client IP never appears in the
cache directory. When HTTP response caching is also enabled, both features
share a single filesystem pool (see `app/dependencies.php`), so the counters
are then persisted inside the HTTP cache directory instead.

`429` responses are produced inside the middleware stack, so they carry the
same security headers as every other response (see "Security Headers").

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

A `SecurityHeadersMiddleware` hardens every response produced inside the
middleware stack (including CORS preflight, rate-limit and HTTPS redirect
responses): it adds the configured security headers, substitutes the
`{nonce}` placeholder with a fresh per-request nonce, and strips the headers
listed under `security_headers.remove`. It is **enabled by default**; to
disable it, set `APP_SECURITY_HEADERS=0` in your `.env` file (or the
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
'Content-Security-Policy' =>
    "default-src 'self'; "
    . "script-src 'self' https://unpkg.com 'nonce-{nonce}'; "
    . "style-src 'self' 'unsafe-inline' https://unpkg.com; "
    . "img-src 'self' data: https://validator.swagger.io; "
    . "font-src 'self' data:; "
    . "connect-src 'self' https://validator.swagger.io; "
    . "object-src 'none'; "
    . "base-uri 'self'",
```

### Content-Security-Policy and nonces

The `Content-Security-Policy` is compatible with the bundled Swagger UI
(`/docs`), which loads its assets from `unpkg.com` and relies on inline
styles plus a small inline initializer script. To allow those inline `style`
and `script` elements without weakening the policy with `'unsafe-inline'`,
the middleware:

- generates a random **nonce** for every request and substitutes it into the
  `{nonce}` placeholder of the policy;
- exposes the same nonce as the `cspNonce` request attribute, which the
  `/docs` route renders on its inline `<style nonce="...">` and
  `<script nonce="...">` elements.

`'unsafe-inline'` is only present in `style-src` (Swagger UI sets dynamic
inline `style` attributes on its elements); `script-src` allows neither
inline scripts nor `eval`. To tighten the policy, edit the value in
`app/settings.php`; the `{nonce}` placeholder is optional.

### Headers removed from every response

The `security_headers.remove` setting lists headers stripped from every
response (default: `Server` and `X-Powered-By`), as defense in depth for
headers that reach the response object. Note that the `X-Powered-By` header
injected by PHP itself is added by the SAPI when the response is sent and
**cannot** be removed from the response object: disable it with
`expose_php=Off` in your PHP configuration. The skeleton's `composer start`
script, the `docker-compose.yml` command and `public/.htaccess` already pass
`expose_php=0`.

One header is intentionally **not** set by default:

- `Strict-Transport-Security` — it must only be sent over HTTPS. Send it in
  production, ideally together with the force-HTTPS middleware below.

Note that responses produced by the error handler (404/405/500) are created
outside the middleware stack and therefore do not carry these headers; add
them in the error handler if you need them there.

## Force HTTPS

A `ForceHttpsMiddleware` redirects every HTTP request to its HTTPS equivalent
with a `308` permanent redirect (which preserves the request method and
body). It is **enabled only in production** (`APP_ENV=prod`); override it
with `APP_FORCE_HTTPS=1` (or `0`) in your `.env` file, or edit the `https`
section in `app/settings.php`:

- `enabled` (`APP_FORCE_HTTPS`) — enable the redirect (default: on in
  `prod`, off elsewhere);
- `status_code` — `308` by default; `301` is a common alternative;
- `trust_forwarded_proto` — when behind a trusted reverse proxy (nginx,
  Caddy, ...), a request is also considered HTTPS when it carries an
  `X-Forwarded-Proto: https` header (default `true`).

The middleware is registered directly inside `SecurityHeadersMiddleware`, so
the redirect responses carry the security headers too.

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
        return $this->id ?? null;
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

Note that `getId()` reads the identifier with the null coalescing operator
(`?? null`): the `id` property is uninitialized until Doctrine hydrates the
entity, so a plain `return $this->id;` would throw a
`Typed property ... must not be accessed before initialization` error on a
newly constructed (not yet persisted) entity.

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
