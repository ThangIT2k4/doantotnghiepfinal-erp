<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateGeoWards2025DistrictCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Cập nhật district_code = '01' cho tất cả các dòng trong bảng geo_wards_2025
     */
    public function run(): void
    {
        echo "Đang cập nhật district_code cho bảng geo_wards_2025...\n";
        
        $updated = DB::table('geo_wards_2025')
            ->update(['district_code' => '01']);
        
        echo "✅ Đã cập nhật {$updated} dòng trong bảng geo_wards_2025.\n";
    }
}

