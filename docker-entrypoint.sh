#!/bin/sh
set -e

PORT="${PORT:-8000}"

# Cache storage links
php artisan storage:link || true

# If SQLite is selected and database file does not exist, create it
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    export DB_CONNECTION=sqlite
    export DB_DATABASE=/var/www/html/database/database.sqlite
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        touch /var/www/html/database/database.sqlite
        chown www-data:www-data /var/www/html/database/database.sqlite
    fi
fi

# Run migrations and demo seeds
php artisan migrate --force || true

# Seed demonstration accounts if database is empty
php artisan db:seed --class=DemoSeeder --force || true

# Cache configurations
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Starting Health Authorization Platform on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
