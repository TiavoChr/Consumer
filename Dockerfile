FROM php:7.4-cli

WORKDIR /var/www/html

COPY . /var/www/html

RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
    && docker-php-ext-install pdo pdo_mysql
