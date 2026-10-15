FROM php:8.4.26-apache-bookworm@sha256:9e811795a606ca7f8586dee48d32acf444985c19ac252d70d181cd71a4603898 AS base
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql mbstring zip pcntl opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/* \
    && sed -i 's#/var/www/html#/var/www/html/public#g; s/ combined$/ voucher_private/' /etc/apache2/sites-available/000-default.conf \
    && printf 'LogFormat "%%t %%m %%>s %%b" voucher_private\n<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/voucher.conf \
    && a2enconf voucher
COPY --from=composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

FROM base AS test
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts
COPY . .
RUN composer dump-autoload --optimize && chown -R www-data:www-data storage bootstrap/cache
CMD ["php", "artisan", "test"]

FROM node:24.21.0-bookworm-slim@sha256:d6aa754f16b3197301076f047b5def2f02ea1dbbc2ca920407d46d7ec7f87b20 AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js ./
RUN npm run build

FROM base AS production
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --optimize-autoloader
COPY . .
COPY --from=assets /build/public/build public/build
RUN composer dump-autoload --no-dev --optimize && chown -R www-data:www-data storage bootstrap/cache
COPY docker/php.ini /usr/local/etc/php/conf.d/voucher.ini
COPY docker/entrypoint.sh /usr/local/bin/voucher-entrypoint
RUN chmod +x /usr/local/bin/voucher-entrypoint
ENTRYPOINT ["voucher-entrypoint"]
CMD ["apache2-foreground"]
