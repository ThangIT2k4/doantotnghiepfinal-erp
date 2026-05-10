#!/bin/sh
set -e

echo "🚀 Starting Laravel..."

mkdir -p /var/www/html
echo "📋 Copying app from /opt/app to /var/www/html..."
# Xóa file cũ để đảm bảo code mới nhất được cập nhật
rm -f /var/www/html/.copy_finished
cp -a /opt/app/. /var/www/html/
sync

# Tạo file đánh dấu đã copy xong
touch /var/www/html/.copy_finished
echo "✓ Code sync complete"

cd /var/www/html

echo "✅ Bootstrap complete"
echo ""

# Compose có thể truyền command (serve / queue:work). Nếu không có thì mặc định serve.
if [ "$#" -gt 0 ]; then
  echo "Running: $@"
  exec "$@"
fi

echo "🌐 Starting PHP built-in server..."
exec php artisan serve --host=0.0.0.0 --port=8000