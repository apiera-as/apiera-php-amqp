FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    librabbitmq-dev \
    git \
    unzip

# Install PHP AMQP extension
RUN pecl install amqp \
    && docker-php-ext-enable amqp

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /opt/apiera/apiera-php-amqp