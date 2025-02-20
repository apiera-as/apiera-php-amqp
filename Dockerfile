FROM php:8.3-cli

WORKDIR /opt/apiera/apiera-php-amqp

RUN apt-get update && apt-get install -y \
    librabbitmq-dev \
    git \
    unzip \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-interaction

COPY . /opt/apiera/apiera-php-amqp/