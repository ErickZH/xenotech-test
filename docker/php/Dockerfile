FROM php:8.4-fpm

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    git zip unzip curl libzip-dev libpq-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establece directorio de trabajo
WORKDIR /var/www

CMD ["php-fpm"]
