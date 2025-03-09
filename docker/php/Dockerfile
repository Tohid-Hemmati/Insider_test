FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    zip \
    vim \
    supervisor

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html

COPY src /var/www/html

RUN composer install --optimize-autoloader --no-dev --verbose

RUN npm install

COPY ./docker/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY ./docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000 5173

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
