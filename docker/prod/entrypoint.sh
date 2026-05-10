#!/bin/sh
set -e

echo "🚀 Starting Laravel..."

cd /var/www/html

echo "✅ App ready at /var/www/html"

echo "✅ Bootstrap complete"
echo ""

# Compose có thể truyền command (serve / queue:work). Nếu không có thì mặc định serve.
if [ "$#" -gt 0 ]; then
  echo "Running: $@"
  exec "$@"
fi

echo "🌐 Starting PHP built-in server..."
exec php artisan serve --host=0.0.0.0 --port=8000