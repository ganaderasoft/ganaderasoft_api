#!/bin/bash
set -e

echo "========================================================="
if [ "$APP_ENV" = "production" ]; then
    echo "🚀 Iniciando Backend en modo: PRODUCCIÓN"
else
    echo "🛠️  Iniciando Backend en modo: DESARROLLO ($APP_ENV)"
fi
echo "========================================================="
# Asegurar que las dependencias estén instaladas (principalmente para Dev, en Prod ya vienen instaladas)
if [ ! -f "vendor/autoload.php" ]; then
    echo "Instalando dependencias de Composer..."
    composer install --no-interaction
fi

# Esperar a la base de datos si DB_HOST está configurado y no estamos en producción
if [ "$APP_ENV" != "production" ] && [ -n "$DB_HOST" ]; then
    echo "Esperando a la base de datos..."
    DB_H="${DB_HOST}"
    DB_P="${DB_PORT:-3306}"
    DB_D="${DB_DATABASE:-ganaderasoft}"
    DB_U="${DB_USERNAME:-ganaderasoft_user}"
    DB_PW="${DB_PASSWORD:-ganaderasoft_pass}"

    until php -r "try { new PDO(\"mysql:host=$DB_H;port=$DB_P;dbname=$DB_D\", \"$DB_U\", \"$DB_PW\"); echo 'ok'; } catch(Exception \$e) { exit(1); }" 2>/dev/null; do
        echo "Base de datos no lista, reintentando en 3s..."
        sleep 3
    done
    echo "¡Base de datos lista!"
fi

# Arreglar permisos para el almacenamiento de Laravel
# Asegurar que las carpetas de framework existan (por si el .dockerignore las omitió)
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/bootstrap/cache

# Enlace simbólico de storage público
if [ ! -L "/var/www/html/public/storage" ] && [ ! -d "/var/www/html/public/storage" ]; then
    ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage 2>/dev/null || true
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

if [ "$APP_ENV" = "production" ]; then
    # Optimizar Laravel para producción
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
else
    # Limpiar todas las cachés en desarrollo (rutas, vistas, config, cache)
    php artisan optimize:clear || true
fi

# Ejecutar migraciones automáticamente (Opcional, elimina si prefieres migración manual)
# php artisan migrate --force

# Iniciar Supervisor (inicia Nginx y PHP)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
