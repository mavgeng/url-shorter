FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath zip intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

ARG HOST_USER_ID=1000
ARG HOST_GROUP_ID=1000
RUN groupmod -o -g ${HOST_GROUP_ID} www-data && \
    usermod -o -u ${HOST_USER_ID} -g www-data www-data

WORKDIR /var/www