FROM php:8.3-fpm-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
    bash \
    curl \
    fcgi \
    git \
    postgresql-client \
    supervisor \
    su-exec \
    unzip \
    zip \
    && install-php-extensions \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
        redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-wellbeing.ini
COPY docker/php/zz-fpm-timeouts.conf /usr/local/etc/php-fpm.d/zz-fpm-timeouts.conf
COPY docker/php/php-fpm-healthcheck /usr/local/bin/php-fpm-healthcheck
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/php-fpm-healthcheck /usr/local/bin/entrypoint.sh \
    && addgroup -g ${GID} wellbeing \
    && adduser -D -G wellbeing -u ${UID} wellbeing \
    && mkdir -p /var/www/backend/storage /var/www/backend/bootstrap/cache \
    && chown -R wellbeing:wellbeing /var/www

WORKDIR /var/www/backend

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
