#!/bin/bash
# Script để kiểm tra lỗi tạo payslip trên Linux

echo "=== Kiểm tra lỗi tạo payslip ==="
echo ""

# Cách 1: Tìm lỗi gần nhất (nhanh nhất)
echo "1. Lỗi gần nhất (100 dòng cuối):"
tail -100 storage/logs/laravel.log | grep -i "error creating payslips" -A 20

echo ""
echo "---"
echo ""

# Cách 2: Tìm tất cả lỗi trong file (có thể chậm nếu file lớn)
echo "2. Tất cả lỗi tạo payslip (có thể chậm):"
grep -i "error creating payslips from preview\|error creating individual payslip" storage/logs/laravel.log | tail -5

echo ""
echo "---"
echo ""

# Cách 3: Tìm lỗi theo thời gian (nhanh hơn)
echo "3. Lỗi trong 1 giờ qua:"
find storage/logs -name "laravel-*.log" -mtime -1 -exec grep -l "error creating payslips" {} \; | xargs tail -50 | grep -i "error creating payslips" -A 20

echo ""
echo "---"
echo ""

# Cách 4: Real-time monitoring (chỉ xem lỗi mới)
echo "4. Theo dõi lỗi real-time (Ctrl+C để dừng):"
tail -f storage/logs/laravel.log | grep --line-buffered -i "error creating payslips" -A 20

