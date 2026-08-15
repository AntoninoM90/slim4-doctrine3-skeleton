FROM php:8.3-alpine

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# Xdebug is enabled but idle by default; it is only activated on demand,
# e.g. by "composer test:coverage" (which runs with xdebug.mode=coverage).
RUN printf 'xdebug.mode=off\nxdebug.start_with_request=no\n' > /usr/local/etc/php/conf.d/zz-xdebug.ini

WORKDIR /var/www

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
