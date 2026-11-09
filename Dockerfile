FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev libonig-dev libpng-dev libjpeg62-turbo-dev \
    libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mysqli mbstring curl gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/sigsm.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html/sigsm

EXPOSE 80
CMD ["apache2-foreground"]
