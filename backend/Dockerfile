FROM php:8.4-fpm

# Usa script oficial para baixar extensões pré-compiladas (evita travamento de RAM no Render)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    supervisor \
    nginx \
    gettext-base \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala todas as extensões de uma vez (baixando os binários prontos)
RUN install-php-extensions pdo_pgsql mbstring exif pcntl bcmath gd zip intl redis opcache

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www

# Copia os arquivos do projeto
COPY . .

# Instala dependências do Composer para produção (ignorando versão do PHP do lockfile local)
RUN composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs

# Copia a configuração do Supervisor, Nginx e OPcache
RUN mkdir -p /etc/nginx/templates
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copia script de inicialização
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Ajusta permissões do storage
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expõe a porta padrão (o Render vai injetar a variável PORT dinamicamente)
EXPOSE 8000

# Comando para rodar a aplicação via script de boot (injetar PORT, cache, e supervisor)
CMD ["/usr/local/bin/start.sh"]
