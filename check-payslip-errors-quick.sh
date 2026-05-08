#!/bin/bash
# Script nhanh để tìm lỗi payslip

# Tìm lỗi trong 200 dòng cuối (nhanh nhất)
tail -200 storage/logs/laravel.log | grep -E "(Error creating payslips|Error creating individual payslip)" -A 25 -B 5

