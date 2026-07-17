#!/bin/sh
set -e


# Injetar a variável $PORT do Render na configuração do Nginx
mkdir -p /etc/nginx/conf.d
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
rm -f /etc/nginx/sites-enabled/default

# Garantir permissões corretas
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Otimizar a aplicação Laravel (Caching)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize

# Iniciar o Supervisor (que irá gerenciar Nginx, PHP-FPM, Horizon, etc.)
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
