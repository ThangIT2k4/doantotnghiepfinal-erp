<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Lead;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first organization
        $organization = Organization::first();
        if (!$organization) {
            $this->command->error('No organization found. Please create an organization first.');
            return;
        }

        // Create tenant users
        $tenantUsers = [
            [
                'name' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@example.com',
                'phone' => '0123456789',
                'role' => 'tenant',
            ],
            [
                'name' => 'Trần Thị B',
                'email' => 'tranthib@example.com',
                'phone' => '0987654321',
                'role' => 'tenant',
            ],
            [
                'name' => 'Lê Văn C',
                'email' => 'levanc@example.com',
                'phone' => '0369852147',
                'role' => 'tenant',
            ],
        ];

        foreach ($tenantUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $userData['phone'],
                    'role' => $userData['role'],
                ]
            );

            // Attach to organization if not already attached
            if (!$user->organizations()->where('organizations.id', $organization->id)->exists()) {
                $user->organizations()->attach($organization->id, ['role_id' => 1]);
            }
        }

        // Create properties if they don't exist
        $properties = [
            [
                'name' => 'Chung cư ABC',
                'description' => 'Chung cư cao cấp tại quận 1',
                'total_floors' => 20,
                'total_rooms' => 100,
                'status' => 1,
            ],
            [
                'name' => 'Nhà trọ XYZ',
                'description' => 'Nhà trọ giá rẻ tại quận 7',
                'total_floors' => 5,
                'total_rooms' => 50,
                'status' => 1,
            ],
        ];

        foreach ($properties as $propertyData) {
            $property = Property::firstOrCreate(
                ['name' => $propertyData['name']],
                array_merge($propertyData, [
                    'organization_id' => $organization->id,
                    'owner_id' => User::where('role', 'admin')->first()->id ?? 1,
                    'property_type_id' => 1,
                    'location_id' => 1,
                ])
            );

            // Create units for each property
            $unitTypes = ['Phòng đơn', 'Phòng đôi', 'Căn hộ 1PN', 'Căn hộ 2PN'];
            $baseRents = [2000000, 3000000, 5000000, 7000000];

            for ($i = 1; $i <= 10; $i++) {
                $unitType = $unitTypes[array_rand($unitTypes)];
                $baseRent = $baseRents[array_rand($baseRents)];

                Unit::firstOrCreate(
                    [
                        'property_id' => $property->id,
                        'code' => $property->name . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    ],
                    [
                        'organization_id' => $organization->id,
                        'unit_type' => $unitType,
                        'base_rent' => $baseRent,
                        'area' => rand(20, 60),
                        'status' => 'available',
                        'description' => 'Phòng/căn hộ đầy đủ tiện nghi',
                    ]
                );
            }
        }

        // Create additional leads
        $additionalLeads = [
            [
                'name' => 'Phạm Thị D',
                'phone' => '0912345678',
                'email' => 'phamthid@email.com',
                'source' => 'Google Ads',
                'status' => 'active',
                'note' => 'Tìm phòng gần trường đại học',
            ],
            [
                'name' => 'Hoàng Văn E',
                'phone' => '0923456789',
                'email' => 'hoangvane@email.com',
                'source' => 'Facebook',
                'status' => 'active',
                'note' => 'Ngân sách 4-6 triệu',
            ],
        ];

        foreach ($additionalLeads as $leadData) {
            Lead::firstOrCreate(
                ['phone' => $leadData['phone']],
                array_merge($leadData, [
                    'organization_id' => $organization->id,
                ])
            );
        }

        $this->command->info('Sample data created successfully!');
        $this->command->info('- Tenant users: ' . count($tenantUsers));
        $this->command->info('- Properties: ' . count($properties));
        $this->command->info('- Units: ' . (count($properties) * 10));
        $this->command->info('- Leads: ' . count($additionalLeads));
    }
}
