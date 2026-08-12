# Contributing

Thanks for considering contributing to this skeleton! These guidelines cover
reporting issues and submitting changes.

## Repository layout

- `main` is the stable branch: pull requests are merged into it.
- `develop` is the integration branch where active development happens.
- Dependency updates from Dependabot are merged directly into `main`.

## Prerequisites

- PHP 8.3 or newer
- [Composer](https://getcomposer.org/)
- Optionally [Docker](https://www.docker.com/) / Docker Compose

## Setting up a development environment

```bash
git clone https://github.com/AntoninoM90/slim4-doctrine3-skeleton.git
cd slim4-doctrine3-skeleton
composer install
cp .env.example .env
composer start
```

The application is now reachable at `http://localhost:8080`; see the README
for the available endpoints and settings.

## Running the checks

Before submitting a change, make sure the whole suite passes:

```bash
vendor/bin/phpcs      # coding standards (PSR-12, applied to src/ and tests/)
vendor/bin/phpstan    # static analysis (level 8, applied to src/)
vendor/bin/phpunit    # the test suite
```

A coverage report can be generated with `composer test:coverage` (requires
Xdebug or PCOV; the Docker image ships Xdebug, so
`docker compose run --rm slim composer test:coverage` works out of the box).

The Doctrine schema and migrations are also validated in CI, so check them
locally when you touch entities:

```bash
php bin/doctrine.php migrations:migrate --no-interaction
php bin/doctrine.php orm:validate-schema --skip-sync
```

## Submitting changes

1. Fork the repository and create a feature branch for your change.
2. Keep the change focused: one pull request per feature or fix.
3. Add or update tests for the behaviour you changed and make sure the checks
   above pass.
4. Open a pull request targeting `main` and describe what the change does and
   why.
5. The GitHub Actions workflow runs the checks on every pull request; address
   any failing check before asking for review.

## Style guide

- Code follows [PSR-12](https://www.php-fig.org/psr/psr-12/), enforced by
  PHPCS.
- Static analysis is run with PHPStan at level 8 (config:
  `phpstan.neon.dist`).
- Write descriptive commit messages in the imperative mood ("Add the health
  check endpoint", "Fix the rate limit window reset", ...).
- New configuration is added to `app/settings.php` with an environment
  variable counterpart and is documented in the README.

## Reporting issues

Please open an issue describing the problem, the expected behaviour and the
steps to reproduce it, including the PHP version and, when relevant, the
operating system and web server.
