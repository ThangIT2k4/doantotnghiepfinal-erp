#!/bin/bash
# Script tối ưu để tìm lỗi payslip - chỉ tìm trong file log mới nhất

# Tìm file log mới nhất
LATEST_LOG=$(ls -t storage/logs/laravel*.log 2>/dev/null | head -1)

if [ -z "$LATEST_LOG" ]; then
    LATEST_LOG="storage/logs/laravel.log"
fi

echo "Đang kiểm tra file: $LATEST_LOG"
echo ""

# Tìm lỗi trong 500 dòng cuối (cân bằng giữa tốc độ và đầy đủ)
tail -500 "$LATEST_LOG" | grep -E "Error creating payslips from preview|Error creating individual payslip" -A 30 -B 5

echo ""
echo "---"
echo "Để theo dõi real-time, chạy:"
echo "tail -f $LATEST_LOG | grep --line-buffered -E 'Error creating payslips|Error creating individual payslip' -A 30"

