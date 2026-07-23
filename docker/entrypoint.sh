#!/usr/bin/env sh
set -e

cd /var/www/html

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction || true
fi

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate --force --no-interaction
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Waiting for database..."
    php -r '
        $host = getenv("DB_HOST") ?: "mysql";
        $port = (int) (getenv("DB_PORT") ?: 3306);
        $user = getenv("DB_USERNAME") ?: "avatar";
        $pass = getenv("DB_PASSWORD") ?: "";
        $retries = 30;

        for ($i = 1; $i <= $retries; $i++) {
            try {
                new PDO(
                    sprintf("mysql:host=%s;port=%d", $host, $port),
                    $user,
                    $pass,
                    [PDO::ATTR_TIMEOUT => 2]
                );
                echo "Database is ready." . PHP_EOL;
                exit(0);
            } catch (Throwable $e) {
                echo "Database not ready ($i/$retries), retrying..." . PHP_EOL;
                sleep(2);
            }
        }

        fwrite(STDERR, "Database connection failed." . PHP_EOL);
        exit(1);
    '

    echo "Running migrations..."
    php artisan migrate --force --no-interaction
fi

if [ "${APP_ENV}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
else
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

exec "$@"
