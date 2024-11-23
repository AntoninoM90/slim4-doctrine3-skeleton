# Slim Framework 4 Skeleton Application with Doctrine ORM 2

[![Build Status](https://github.com/AntoninoM90/slim4-doctrine-skeleton/workflows/Tests/badge.svg)](https://github.com/AntoninoM90/slim4-doctrine-skeleton/actions)

Use this skeleton application to quickly setup and start working on a new Slim Framework 4 application. This application uses the latest Slim 4 with Slim PSR-7 implementation and PHP-DI container implementation. It also uses the Monolog logger.

This skeleton application was built for Composer. This makes setting up a new Slim Framework application quick and easy.

## Install the Application

Run this command from the directory in which you want to clone the Slim Framework application skeleton. You will require PHP 7.4 or newer.

```bash
git clone https://github.com/AntoninoM90/slim4-doctrine-skeleton.git
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

Run this command in the application directory to run the test suite

```bash
composer test
```

That's it! Now go build something cool.
