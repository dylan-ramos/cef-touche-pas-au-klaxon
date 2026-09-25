# Image d'exécution de l'application : PHP 8.3 + Apache (mod_php).
FROM php:8.3-apache

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# L'utilisateur Apache reçoit l'UID de l'hôte : les fichiers montés restent modifiables des deux côtés.
RUN groupmod -o -g "${GID}" www-data && usermod -o -u "${UID}" -g "${GID}" www-data

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite headers

ENV COMPOSER_HOME=/tmp/composer
WORKDIR /var/www/html
