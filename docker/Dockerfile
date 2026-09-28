FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y unzip libzip-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
    
WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www/html

RUN composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

RUN printf '%s\n' \
'<VirtualHost *:80>' \
'    DocumentRoot /var/www/html/public' \
'    <Directory /var/www/html/public>' \
'        AllowOverride All' \
'        Require all granted' \
'        Options FollowSymLinks' \
'    </Directory>' \
'</VirtualHost>' \
> /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]