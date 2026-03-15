#!/bin/sh
set -e

echo "🚀 Starting Laravel..."

mkdir -p /var/www/html
echo "📋 Copying app from /opt/app to /var/www/html..."
cp -a /opt/app/. /var/www/html/
sync

echo "✅ Verifying vendor..."
if [ ! -f /var/www/html/vendor/composer/autoload_real.php ]; then
  echo "❌ FATAL: /var/www/html/vendor/composer/autoload_real.php NOT FOUND"
  echo "📁 Contents of /opt/app/vendor/:"
  ls -la /opt/app/vendor 2>&1 | head -30 || echo "(vendor missing)"
  echo ""
  echo "📁 Contents of /opt/app/vendor/composer/:"
  ls -la /opt/app/vendor/composer 2>&1 || echo "(composer folder missing)"
  echo ""
  echo "❌ This means:"
  echo "  1. Docker image build incomplete (composer install failed)"
  echo "  2. Or vendor was excluded from image unexpectedly"
  echo "  3. Check GitHub Actions build logs"
  exit 1
fi

echo "✓ Vendor verified"

if [ ! -f /var/www/html/vendor/thecodingmachine/safe/lib/special_cases.php ]; then
  echo "⚠️  WARNING: thecodingmachine/safe/lib/special_cases.php missing"
  echo "📁 Files in vendor/thecodingmachine/safe/lib/:"
  ls -la /var/www/html/vendor/thecodingmachine/safe/lib/ 2>&1 || echo "(directory missing)"
fi

cd /var/www/html

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