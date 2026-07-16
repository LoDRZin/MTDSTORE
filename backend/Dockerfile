FROM php:8.3-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev

# Limpa o cache apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Instala a extensão Redis via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www

# Ajusta permissões do usuário (opcional, útil para ambientes de dev local)
RUN usermod -u 1000 www-data
USER www-data

# O código fonte e a pasta vendor serão montados via volume em dev.
# Em produção, você faria COPY . . e RUN composer install
