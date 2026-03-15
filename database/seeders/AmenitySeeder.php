<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Amenity;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            // Basic amenities
            [
                'key_code' => 'air_conditioner',
                'name' => 'Điều hòa',
                'category' => 'basic',
            ],
            [
                'key_code' => 'wifi',
                'name' => 'WiFi',
                'category' => 'basic',
            ],
            [
                'key_code' => 'hot_water',
                'name' => 'Nước nóng',
                'category' => 'basic',
            ],
            [
                'key_code' => 'refrigerator',
                'name' => 'Tủ lạnh',
                'category' => 'basic',
            ],
            [
                'key_code' => 'washing_machine',
                'name' => 'Máy giặt',
                'category' => 'basic',
            ],
            // Kitchen amenities
            [
                'key_code' => 'kitchen',
                'name' => 'Bếp',
                'category' => 'kitchen',
            ],
            [
                'key_code' => 'gas_stove',
                'name' => 'Bếp gas',
                'category' => 'kitchen',
            ],
            [
                'key_code' => 'microwave',
                'name' => 'Lò vi sóng',
                'category' => 'kitchen',
            ],
            // Bathroom amenities
            [
                'key_code' => 'private_bathroom',
                'name' => 'WC riêng',
                'category' => 'bathroom',
            ],
            [
                'key_code' => 'bathtub',
                'name' => 'Bồn tắm',
                'category' => 'bathroom',
            ],
            // Security amenities
            [
                'key_code' => 'security_camera',
                'name' => 'Camera an ninh',
                'category' => 'security',
            ],
            [
                'key_code' => 'card_access',
                'name' => 'Thẻ từ',
                'category' => 'security',
            ],
            [
                'key_code' => 'guard_24h',
                'name' => 'Bảo vệ 24/7',
                'category' => 'security',
            ],
            // Parking amenities
            [
                'key_code' => 'motorbike_parking',
                'name' => 'Chỗ để xe máy',
                'category' => 'parking',
            ],
            [
                'key_code' => 'car_parking',
                'name' => 'Chỗ để ô tô',
                'category' => 'parking',
            ],
            // Other amenities
            [
                'key_code' => 'balcony',
                'name' => 'Ban công',
                'category' => 'other',
            ],
            [
                'key_code' => 'elevator',
                'name' => 'Thang máy',
                'category' => 'other',
            ],
            [
                'key_code' => 'gym',
                'name' => 'Phòng gym',
                'category' => 'other',
            ],
            [
                'key_code' => 'swimming_pool',
                'name' => 'Hồ bơi',
                'category' => 'other',
            ],
        ];

        foreach ($amenities as $amenity) {
            Amenity::updateOrCreate(
                ['key_code' => $amenity['key_code']],
                $amenity
            );
        }
    }
}