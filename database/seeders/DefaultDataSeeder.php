<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\Organization;
use App\Models\User;
use App\Models\Role;
use App\Models\OrganizationUser;

class DefaultDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting default data seeding...');

        // Seed services
        $this->seedServices();

        // Seed amenities
        $this->seedAmenities();

        // Seed ticket priorities
        $this->seedTicketPriorities();

        // Seed geo data
        $this->seedGeoCountries();
        $this->seedGeoProvinces();
        $this->seedGeoProvinces2025();
        $this->seedGeoDistricts();
        $this->seedGeoWards();
        $this->seedGeoWards2025();

        // Seed subscription plans (before plan_features because of foreign key)
        $this->seedSubscriptionPlans();

        // Seed plan features
        $this->seedPlanFeatures();

        // Seed roles
        $this->seedRoles();

        // Seed property types
        $this->seedPropertyTypes();

        // Seed payment methods
        $this->seedPaymentMethods();

        // Seed notification channels
        $this->seedNotificationChannels();

        // Seed sepay banks
        $this->seedSepayBanks();

        // Seed migrations (if needed)
        $this->seedMigrations();

        // Seed organization (1 default organization)
        $organization = $this->seedOrganization();

        // Seed users (5 users corresponding to 5 roles)
        $users = $this->seedUsers();

        // Seed organization_users (assign 5 users to default organization with 5 roles)
        $this->seedOrganizationUsers($organization, $users);

        // Display summary
        $this->displaySeedingSummary();
        
        $this->command->info('Default data seeding completed!');
    }

    /**
     * Display seeding summary
     */
    private function displaySeedingSummary(): void
    {
        $this->command->info('');
        $this->command->info('=== SEEDING SUMMARY ===');
        $this->command->info('Services: ' . DB::table('services')->count());
        $this->command->info('Amenities: ' . DB::table('amenities')->count());
        $this->command->info('Ticket Priorities: ' . DB::table('ticket_priorities')->count());
        $this->command->info('Geo Countries: ' . DB::table('geo_countries')->count());
        $this->command->info('Geo Provinces: ' . DB::table('geo_provinces')->count());
        $this->command->info('Geo Districts: ' . DB::table('geo_districts')->count());
        $this->command->info('Geo Wards: ' . DB::table('geo_wards')->count());
        $this->command->info('Subscription Plans: ' . DB::table('subscription_plans')->count());
        $this->command->info('Plan Features: ' . DB::table('plan_features')->count());
        $this->command->info('Roles: ' . DB::table('roles')->count());
        $this->command->info('Property Types: ' . DB::table('property_types')->count());
        $this->command->info('Payment Methods: ' . DB::table('payment_methods')->count());
        $this->command->info('Notification Channels: ' . DB::table('notification_channels')->count());
        $this->command->info('Sepay Banks: ' . DB::table('sepay_banks')->count());
        $this->command->info('Organizations: ' . DB::table('organizations')->count());
        $this->command->info('Users: ' . DB::table('users')->count());
        $this->command->info('Organization Users: ' . DB::table('organization_users')->count());
        $this->command->info('========================');
        $this->command->info('');
    }

    /**
     * Seed services
     */
    private function seedServices(): void
    {
        $this->command->info('Seeding services...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping services seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `services` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL columns: id, key_code, name, pricing_type, unit_label, description, created_at, updated_at, deleted_at, deleted_by
            $this->parseAndInsertValues('services', $valuesString, [
                'id', 'key_code', 'name', 'pricing_type', 'unit_label', 'description', 
                'created_at', 'updated_at', 'deleted_at', 'deleted_by'
            ]);
            
            $count = DB::table('services')->count();
            $this->command->info("Services seeded successfully. Total: {$count}");
        }
    }

    /**
     * Seed amenities
     */
    private function seedAmenities(): void
    {
        $this->command->info('Seeding amenities...');
        
        $sqlFile = database_path('sql/11_113.sql');
        $seededFromSql = false;
        
        // Try to seed from SQL file first
        if (File::exists($sqlFile)) {
            $sql = File::get($sqlFile);
            
            if (preg_match('/INSERT INTO `amenities` VALUES (.*?);/s', $sql, $matches)) {
                $valuesString = $matches[1];
                
                // SQL columns: id, key_code, name, category, created_at, updated_at, deleted_at, deleted_by
                $this->parseAndInsertValues('amenities', $valuesString, [
                    'id', 'key_code', 'name', 'category', 'created_at', 'updated_at', 'deleted_at', 'deleted_by'
                ]);
                
                $count = DB::table('amenities')->count();
                $this->command->info("Amenities seeded successfully from SQL file. Total: {$count}");
                $seededFromSql = true;
            }
        }
        
        // If SQL file not found or no data, seed default amenities
        if (!$seededFromSql) {
            $this->command->info('SQL file not found or empty, seeding default amenities...');
            $this->seedDefaultAmenities();
        }
    }

    /**
     * Seed default amenities data
     */
    private function seedDefaultAmenities(): void
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

        $inserted = 0;
        $updated = 0;
        
        foreach ($amenities as $amenity) {
            $result = DB::table('amenities')->updateOrInsert(
                ['key_code' => $amenity['key_code']],
                array_merge($amenity, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
            
            // Check if row was inserted or updated
            $exists = DB::table('amenities')->where('key_code', $amenity['key_code'])->exists();
            if ($exists) {
                $inserted++;
            }
        }
        
        $count = DB::table('amenities')->count();
        $this->command->info("Default amenities seeded successfully. Total: {$count}");
    }

    /**
     * Seed ticket_priorities
     */
    private function seedTicketPriorities(): void
    {
        $this->command->info('Seeding ticket_priorities...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping ticket_priorities seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `ticket_priorities` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL columns: id, key_code, name, created_at, updated_at
            $this->parseAndInsertValues('ticket_priorities', $valuesString, [
                'id', 'key_code', 'name', 'created_at', 'updated_at'
            ]);
            
            $this->command->info('Ticket priorities seeded successfully.');
        }
    }

    /**
     * Seed geo_countries
     */
    private function seedGeoCountries(): void
    {
        $this->command->info('Seeding geo_countries...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping geo_countries seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `geo_countries` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL columns: code, name, name_local, created_at
            $this->parseAndInsertValues('geo_countries', $valuesString, [
                'code', 'name', 'name_local', 'created_at'
            ]);
            
            $this->command->info('Geo countries seeded successfully.');
        }
    }

    /**
     * Seed geo_provinces
     */
    private function seedGeoProvinces(): void
    {
        $this->command->info('Seeding geo_provinces...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping geo_provinces seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `geo_provinces` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL: code, country_code, name_en, name_vi, type, created_at
            // Table: code, country_code, name, name_local, kind, created_at
            $this->parseAndInsertValuesWithMapping('geo_provinces', $valuesString, [
                'code', 'country_code', 'name_en', 'name_vi', 'type', 'created_at'
            ], [
                'name_en' => 'name',
                'name_vi' => 'name_local',
                'type' => 'kind'
            ]);
            
            $this->command->info('Geo provinces seeded successfully.');
        }
    }

    /**
     * Seed geo_provinces_2025
     */
    private function seedGeoProvinces2025(): void
    {
        $this->command->info('Seeding geo_provinces_2025...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping geo_provinces_2025 seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `geo_provinces_2025` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL: code, country_code, name_vi, name_en, type, created_at
            // Table: code, country_code, name, name_local, kind, created_at
            $this->parseAndInsertValuesWithMapping('geo_provinces_2025', $valuesString, [
                'code', 'country_code', 'name_vi', 'name_en', 'type', 'created_at'
            ], [
                'name_vi' => 'name',
                'name_en' => 'name_local',
                'type' => 'kind'
            ]);
            
            $this->command->info('Geo provinces 2025 seeded successfully.');
        }
    }

    /**
     * Seed geo_districts
     */
    private function seedGeoDistricts(): void
    {
        $this->command->info('Seeding geo_districts...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping geo_districts seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `geo_districts` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL: code, province_code, name_vi, name_en, type, created_at
            // Table: code, province_code, name, name_local, kind, created_at
            $this->parseAndInsertValuesWithMapping('geo_districts', $valuesString, [
                'code', 'province_code', 'name_vi', 'name_en', 'type', 'created_at'
            ], [
                'name_vi' => 'name',
                'name_en' => 'name_local',
                'type' => 'kind'
            ]);
            
            $this->command->info('Geo districts seeded successfully.');
        }
    }

    /**
     * Seed geo_wards
     */
    private function seedGeoWards(): void
    {
        $this->command->info('Seeding geo_wards...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping geo_wards seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `geo_wards` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL: code, district_code, name_vi, name_en, type, created_at
            // Table: code, district_code, name, name_local, kind, created_at
            $this->parseAndInsertValuesWithMapping('geo_wards', $valuesString, [
                'code', 'district_code', 'name_vi', 'name_en', 'type', 'created_at'
            ], [
                'name_vi' => 'name',
                'name_en' => 'name_local',
                'type' => 'kind'
            ]);
            
            $this->command->info('Geo wards seeded successfully.');
        }
    }

    /**
     * Seed geo_wards_2025
     */
    private function seedGeoWards2025(): void
    {
        $this->command->info('Seeding geo_wards_2025...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping geo_wards_2025 seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `geo_wards_2025` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL: code, province_code, name_vi, name_en, type, created_at
            // Table: code, district_code, name, name_local, kind, created_at
            // Note: geo_wards_2025 uses district_code in table but province_code in SQL
            $this->parseAndInsertValuesWithMapping('geo_wards_2025', $valuesString, [
                'code', 'province_code', 'name_vi', 'name_en', 'type', 'created_at'
            ], [
                'province_code' => 'district_code',
                'name_vi' => 'name',
                'name_en' => 'name_local',
                'type' => 'kind'
            ]);
            
            $this->command->info('Geo wards 2025 seeded successfully.');
        }
    }

    /**
     * Seed subscription_plans
     */
    private function seedSubscriptionPlans(): void
    {
        $this->command->info('Seeding subscription_plans...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping subscription_plans seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `subscription_plans` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            $this->parseAndInsertValues('subscription_plans', $valuesString, [
                'id', 'code', 'name', 'description', 'price_monthly', 'price_yearly', 
                'currency', 'trial_days', 'is_active', 'is_custom', 'sort_order', 
                'metadata', 'created_at', 'updated_at', 'deleted_at'
            ]);
            
            $this->command->info('Subscription plans seeded successfully.');
        }
    }

    /**
     * Seed plan_features
     */
    private function seedPlanFeatures(): void
    {
        $this->command->info('Seeding plan_features...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping plan_features seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `plan_features` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // Map SQL columns to actual table columns: feature_code -> feature_key, value_type -> feature_type
            $this->parseAndInsertValuesWithMapping('plan_features', $valuesString, [
                'id', 'plan_id', 'feature_code', 'feature_name', 'feature_value', 'value_type', 'created_at', 'updated_at'
            ], [
                'feature_code' => 'feature_key',
                'value_type' => 'feature_type'
            ]);
            
            $this->command->info('Plan features seeded successfully.');
        }
    }

    /**
     * Seed roles
     */
    private function seedRoles(): void
    {
        $this->command->info('Seeding roles...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, creating default roles.');
            $this->createDefaultRoles();
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `roles` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // Map SQL columns to actual table columns: code -> key_code
            $this->parseAndInsertValuesWithMapping('roles', $valuesString, [
                'id', 'code', 'name', 'created_at', 'updated_at'
            ], [
                'code' => 'key_code'
            ]);
            
            $this->command->info('Roles seeded successfully.');
        } else {
            $this->createDefaultRoles();
        }
    }

    /**
     * Create default roles if not found in SQL
     */
    private function createDefaultRoles(): void
    {
        $roles = [
            ['id' => 1, 'key_code' => 'admin', 'name' => 'Quản trị hệ thống'],
            ['id' => 2, 'key_code' => 'manager', 'name' => 'Quản lý'],
            ['id' => 3, 'key_code' => 'agent', 'name' => 'CTV/Nhân viên'],
            ['id' => 4, 'key_code' => 'landlord', 'name' => 'Chủ trọ'],
            ['id' => 5, 'key_code' => 'tenant', 'name' => 'Người thuê'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                array_merge($role, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * Seed organization (1 default organization)
     */
    private function seedOrganization(): Organization
    {
        $this->command->info('Seeding default organization...');
        
        // Try to find existing default organization
        $organization = Organization::where('code', 'ORG-DEFAULT')
            ->orWhere('code', 'ORG-3')
            ->orWhere('name', 'like', '%Default%')
            ->orWhere('name', 'like', '%Organization_default%')
            ->first();

        // If not found, create default
        if (!$organization) {
            $organization = Organization::create([
                'code' => 'ORG-DEFAULT',
                'name' => 'Organization Default',
                'phone' => '0000000000',
                'email' => 'default@organization.com',
                'address' => 'Default Address',
                'status' => 1,
            ]);
        }

        $this->command->info("Default organization: {$organization->name} (ID: {$organization->id})");
        return $organization;
    }

    /**
     * Seed users (5 users corresponding to 5 roles)
     */
    private function seedUsers(): array
    {
        $this->command->info('Seeding default users...');
        
        $users = [];
        $roleCodes = ['admin', 'manager', 'agent', 'landlord', 'tenant'];
        $userEmails = [
            'admin' => 'admin@example.com',
            'manager' => 'manager@example.com',
            'agent' => 'agent@example.com',
            'landlord' => 'landlord@example.com',
            'tenant' => 'tenant@example.com',
        ];

        // Try to find existing users first
        foreach ($roleCodes as $index => $roleCode) {
            $email = $userEmails[$roleCode];
            
            // Try to find user by email or by role pattern
            $user = User::where('email', $email)
                ->orWhere('email', 'like', "%{$roleCode}%")
                ->first();

            if (!$user) {
                // Create default user
                $user = User::create([
                    'email' => $email,
                    'phone' => '090' . str_pad($index + 1, 7, '0', STR_PAD_LEFT),
                    'password_hash' => Hash::make('password'),
                    'status' => 1,
                ]);
            }

            $users[$roleCode] = $user;
            $this->command->info("User: {$user->email} (Role: {$roleCode})");
        }

        return $users;
    }

    /**
     * Seed organization_users (assign 5 users to default organization with 5 roles)
     */
    private function seedOrganizationUsers(Organization $organization, array $users): void
    {
        $this->command->info('Seeding organization_users...');
        
        $roles = Role::all()->keyBy('key_code');
        $roleCodes = ['admin', 'manager', 'agent', 'landlord', 'tenant'];

        foreach ($roleCodes as $roleCode) {
            if (!isset($users[$roleCode])) {
                continue;
            }

            $user = $users[$roleCode];
            $role = $roles->get($roleCode);

            if (!$role) {
                continue;
            }

            // Check if organization_user already exists
            $exists = DB::table('organization_users')
                ->where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->exists();

            if (!$exists) {
                DB::table('organization_users')->insert([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->info("Assigned user {$user->email} to organization {$organization->name} with role {$roleCode}");
            }
        }

        $this->command->info('Organization users seeded successfully.');
    }

    /**
     * Seed property types
     */
    private function seedPropertyTypes(): void
    {
        $this->command->info('Seeding property_types...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping property_types seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `property_types` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL columns: id, key_code, name, icon, description, status, created_at, updated_at, deleted_at, deleted_by
            $this->parseAndInsertValues('property_types', $valuesString, [
                'id', 'key_code', 'name', 'icon', 'description', 'status', 
                'created_at', 'updated_at', 'deleted_at', 'deleted_by'
            ]);
            
            $this->command->info('Property types seeded successfully.');
        }
    }

    /**
     * Seed payment methods
     */
    private function seedPaymentMethods(): void
    {
        $this->command->info('Seeding payment_methods...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping payment_methods seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `payment_methods` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL columns: id, key_code, name
            $this->parseAndInsertValues('payment_methods', $valuesString, [
                'id', 'key_code', 'name'
            ]);
            
            $this->command->info('Payment methods seeded successfully.');
        }
    }

    /**
     * Seed notification channels
     */
    private function seedNotificationChannels(): void
    {
        $this->command->info('Seeding notification_channels...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping notification_channels seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `notification_channels` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL columns: id, key_code, name, active
            $this->parseAndInsertValues('notification_channels', $valuesString, [
                'id', 'key_code', 'name', 'active'
            ]);
            
            $this->command->info('Notification channels seeded successfully.');
        }
    }

    /**
     * Seed sepay banks
     */
    private function seedSepayBanks(): void
    {
        $this->command->info('Seeding sepay_banks...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping sepay_banks seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `sepay_banks` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL columns: id, name, code, bin, short_name, supported, created_at, updated_at
            $this->parseAndInsertValues('sepay_banks', $valuesString, [
                'id', 'name', 'code', 'bin', 'short_name', 'supported', 
                'created_at', 'updated_at'
            ]);
            
            $this->command->info('Sepay banks seeded successfully.');
        }
    }

    /**
     * Seed migrations (if needed)
     */
    private function seedMigrations(): void
    {
        $this->command->info('Seeding migrations...');
        
        $sqlFile = database_path('sql/11_113.sql');
        if (!File::exists($sqlFile)) {
            $this->command->warn('SQL file not found, skipping migrations seeding.');
            return;
        }

        $sql = File::get($sqlFile);
        
        if (preg_match('/INSERT INTO `migrations` VALUES (.*?);/s', $sql, $matches)) {
            $valuesString = $matches[1];
            
            // SQL columns: id, migration, batch
            $this->parseAndInsertValues('migrations', $valuesString, [
                'id', 'migration', 'batch'
            ]);
            
            $this->command->info('Migrations seeded successfully.');
        }
    }

    /**
     * Execute INSERT statement directly from SQL
     */
    private function executeInsertStatement(string $table, string $insertStatement): void
    {
        $beforeCount = DB::table($table)->count();
        
        try {
            // Use DB::unprepared to execute the INSERT statement directly
            // This is safer than parsing complex SQL values
            DB::unprepared($insertStatement);
            
            $afterCount = DB::table($table)->count();
            $inserted = $afterCount - $beforeCount;
            
            if ($inserted > 0) {
                $this->command->info("  ✓ Inserted {$inserted} records into {$table}");
            } else {
                $this->command->warn("  ⚠ No new records inserted into {$table} (may be duplicates)");
            }
        } catch (\Exception $e) {
            // If statement fails, try to execute with IGNORE to skip duplicates
            $insertStatement = str_replace(
                "INSERT INTO `{$table}`",
                "INSERT IGNORE INTO `{$table}`",
                $insertStatement
            );
            try {
                DB::unprepared($insertStatement);
                
                $afterCount = DB::table($table)->count();
                $inserted = $afterCount - $beforeCount;
                
                if ($inserted > 0) {
                    $this->command->info("  ✓ Inserted {$inserted} records into {$table} (with IGNORE)");
                } else {
                    $this->command->warn("  ⚠ No new records inserted into {$table} (duplicates ignored)");
                }
            } catch (\Exception $e2) {
                $this->command->error("  ✗ Failed to insert into {$table}: " . $e2->getMessage());
                throw $e2;
            }
        }
    }

    /**
     * Parse and insert values into a table using direct SQL execution
     */
    private function parseAndInsertValues(string $table, string $valuesString, array $columns): void
    {
        // Build INSERT statement
        $columnsStr = '`' . implode('`, `', $columns) . '`';
        $insertStatement = "INSERT INTO `{$table}` ({$columnsStr}) VALUES {$valuesString};";
        
        $this->executeInsertStatement($table, $insertStatement);
    }

    /**
     * Parse and insert values with column mapping (for cases where SQL column names differ from table column names)
     */
    private function parseAndInsertValuesWithMapping(string $table, string $valuesString, array $sqlColumns, array $columnMapping): void
    {
        // Map SQL column names to actual table column names
        $actualColumns = [];
        foreach ($sqlColumns as $sqlCol) {
            $actualColumns[] = $columnMapping[$sqlCol] ?? $sqlCol;
        }
        
        // Build INSERT statement with mapped column names
        $columnsStr = '`' . implode('`, `', $actualColumns) . '`';
        $insertStatement = "INSERT INTO `{$table}` ({$columnsStr}) VALUES {$valuesString};";
        
        $this->executeInsertStatement($table, $insertStatement);
    }
}
