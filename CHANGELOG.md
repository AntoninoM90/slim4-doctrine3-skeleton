# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Security headers middleware (`SecurityHeadersMiddleware`): configurable
  headers applied to every response, a `Content-Security-Policy` that uses a
  per-request nonce so the bundled Swagger UI keeps working without
  `'unsafe-inline'`/`'unsafe-eval'` in `script-src`, and a `remove` list that
  strips `Server` and `X-Powered-By` from responses.
- Force HTTPS middleware (`ForceHttpsMiddleware`): redirects HTTP requests to
  HTTPS with a `308` in production (`APP_ENV=prod`), with `X-Forwarded-Proto`
  support for trusted reverse proxies.
- Configurable CORS middleware (`CorsMiddleware`), disabled by default.
- Fixed-window rate limiting middleware (`RateLimitMiddleware`) with
  per-IP/per-user keys and `X-RateLimit-*` headers, disabled by default.
- HTTP response caching middleware (`ResponseCacheMiddleware`) with `X-Cache`
  headers, disabled by default.
- OpenAPI documentation with `zircote/swagger-php`: `/docs.json` serves the
  specification and `/docs` the interactive Swagger UI.
- Health check endpoint (`GET /health`) that probes the database and the
  cache.
- Doctrine Migrations support (`bin/doctrine.php migrations:*`).
- Request validation through Symfony Validator (`Action::validateRequest()`
  and `HttpValidationException`).
- User CRUD endpoints: `POST /user`, `PATCH /user/{id}` and `DELETE /user/{id}`
  with validated request DTOs and bcrypt-hashed passwords. All request errors
  are reported per field under `error.details` with a `422` status, including
  uniqueness of the `username` and `emailAddress` (a user may keep their own
  values when updating).
- Environment-aware logging: per-environment log files in `logs/` and the
  `APP_ENV` setting.
- Docker support: `Dockerfile` with Xdebug and `docker-compose.yml` (SQLite by
  default, with PostgreSQL/MySQL examples).
- GitHub Actions workflow testing PHP 8.3, 8.4 and 8.5 with PHPStan, PHPCS
  and Codecov coverage upload, plus Dependabot configuration.

### Changed

- Router bootstrapping now runs through `public/index.php`.
- Doctrine ORM 3 / DBAL 4, Slim 4.15, PHP-DI 7, Monolog 3, swagger-php 6,
  PHPUnit 12 and Symfony Cache/Validator 7.
- Minimum PHP version raised to 8.3.
- The `start` script, the Docker command and `.htaccess` disable `expose_php`
  so PHP version headers are not sent.

### Fixed

- Default implicit nullable values on PHP 8.4+.
- Removed the unused `doctrine/annotations` dependency.
- `User::getId()` and `User::jsonSerialize()` no longer throw on a newly
  constructed (not yet persisted) entity: the `id` property is read through
  the null coalescing operator, which does not fail on uninitialized typed
  properties.
