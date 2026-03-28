<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command: UpdateGeoWards2025DistrictCodeCommand
 * 
 * MỤC ĐÍCH:
 * Cập nhật district_code cho tất cả các bản ghi trong bảng geo_wards_2025 về một giá trị cụ thể.
 * Command này được dùng để chuẩn hóa hoặc sửa lỗi dữ liệu địa lý.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận tham số: district_code (mặc định: '01')
 * 2. Đếm tổng số bản ghi trong bảng geo_wards_2025
 * 3. Cập nhật tất cả bản ghi: district_code = giá trị được chỉ định
 * 4. Verify: Kiểm tra tất cả bản ghi đã được cập nhật đúng chưa
 * 5. Hiển thị kết quả
 * 
 * CÁCH CHẠY:
 * php artisan geo:update-wards-2025-district-code [district_code]
 * 
 * Ví dụ:
 * php artisan geo:update-wards-2025-district-code 01
 * php artisan geo:update-wards-2025-district-code 02
 * 
 * LƯU Ý:
 * - Command này CẬP NHẬT TẤT CẢ bản ghi trong bảng
 * - Không thể hoàn tác sau khi chạy
 * - Chỉ nên dùng khi cần chuẩn hóa dữ liệu
 */
class UpdateGeoWards2025DistrictCodeCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Tham số:
     * - {district_code}: District code để set cho tất cả wards (mặc định: '01')
     * 
     * @var string
     */
    protected $signature = 'geo:update-wards-2025-district-code {district_code=01 : The district code to set for all wards}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Update all district_code in geo_wards_2025 table to a specific value (default: 01)';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy district_code từ tham số (mặc định: '01')
     * 2. Đếm tổng số bản ghi trong bảng geo_wards_2025
     * 3. Nếu không có bản ghi: Hiển thị cảnh báo và return
     * 4. Cập nhật tất cả bản ghi: district_code = giá trị được chỉ định
     * 5. Verify: Kiểm tra tất cả bản ghi đã được cập nhật đúng chưa
     * 6. Hiển thị kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng geo_wards_2025: Đếm và verify
     * 
     * DỮ LIỆU GHI VÀO:
     * - Cập nhật district_code cho tất cả bản ghi trong bảng geo_wards_2025
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle()
    {
        $districtCode = $this->argument('district_code');
        
        $this->info("Updating district_code to '{$districtCode}' for all records in geo_wards_2025 table...");
        
        try {
            // Get total count before update
            $totalCount = DB::table('geo_wards_2025')->count();
            
            if ($totalCount == 0) {
                $this->warn("No records found in geo_wards_2025 table.");
                return Command::SUCCESS;
            }
            
            $this->info("Found {$totalCount} records to update.");
            
            // Update all records
            $updated = DB::table('geo_wards_2025')
                ->update(['district_code' => $districtCode]);
            
            $this->info("✓ Successfully updated {$updated} records.");
            
            // Verify the update
            $verifyCount = DB::table('geo_wards_2025')
                ->where('district_code', $districtCode)
                ->count();
            
            if ($verifyCount == $totalCount) {
                $this->info("✓ Verification: All {$verifyCount} records now have district_code = '{$districtCode}'");
            } else {
                $this->warn("⚠ Warning: Expected {$totalCount} records with district_code = '{$districtCode}', but found {$verifyCount}");
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

