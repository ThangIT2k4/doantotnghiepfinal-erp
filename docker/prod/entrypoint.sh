#!/bin/sh
set -e

echo "🚀 Starting Laravel..."

cd /var/www/html

echo "✅ App ready at /var/www/html"

echo "🔄 Running migrations..."
php artisan migrate --force || true

echo "📦 Publishing Sanctum migrations..."
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag=sanctum-migrations --force || true

echo "🔄 Running migrations again..."
php artisan migrate --force || true

echo "⚙️  Caching config..."
php artisan config:cache || true

echo "✅ Bootstrap complete"
echo ""

# Compose có thể truyền command (serve / queue:work). Nếu không có thì mặc định serve.
if [ "$#" -gt 0 ]; then
  echo "Running: $@"
  exec "$@"
fi

echo "🌐 Starting PHP built-in server..."
exec php artisan serve --host=0.0.0.0 --port=8000