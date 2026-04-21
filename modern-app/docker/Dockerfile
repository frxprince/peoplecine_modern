FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump --no-dev

FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        default-mysql-client \
        fonts-dejavu-core \
        fonts-noto-core \
        fonts-thai-tlwg \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libsqlite3-dev \
        libwebp-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && a2enmod expires headers rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php-opcache.ini /usr/local/etc/php/conf.d/peoplecine-opcache.ini
COPY docker/entrypoint.sh /usr/local/bin/peoplecine-entrypoint

RUN chmod +x /usr/local/bin/peoplecine-entrypoint

COPY --from=vendor /app /var/www/html

ENTRYPOINT ["peoplecine-entrypoint"]
CMD ["apache2-foreground"]
