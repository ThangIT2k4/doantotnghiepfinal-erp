<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;
use App\Models\Organization;
use App\Models\User;
use App\Models\Role;
use App\Models\OrganizationUser;
use App\Models\PaymentCycle;
use App\Models\LeaseServiceSet;
use App\Models\LeaseServiceSetItem;
use App\Models\Service;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\Amenity;
use App\Models\MasterLease;
use App\Models\Lease;
use App\Models\LeaseResident;
use App\Models\Lead;
use App\Models\BookingDeposit;
use App\Models\Viewing;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketLog;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\CommissionPolicy;
use App\Models\CommissionEvent;
use App\Models\Vendor;
use App\Models\SepayBank;
use App\Models\CompanyInvoice;
use App\Models\CompanyInvoiceItem;
use App\Models\Document;
use App\Models\GeoProvince;
use App\Models\GeoDistrict;
use App\Models\GeoWard;
use App\Helpers\SequenceGenerator;

class DemoDataSeeder extends Seeder
{
    private $faker;
    private $organization;
    private $users = [];
    private $roles = [];
    private $paymentCycles = [];
    private $leaseServiceSets = [];
    private $locations = [];
    private $properties = [];
    private $units = [];
    private $masterLeases = [];
    private $leases = [];
    private $leads = [];
    private $bookingDeposits = [];
    private $invoices = [];
    private $tickets = [];
    private $meters = [];
    private $reviews = [];
    private $commissionPolicies = [];
    private $vendors = [];
    private $documents = [];
    private $paymentMethods = [];

    public function __construct()
    {
        $this->faker = Faker::create('vi_VN');
    }

    /**
     * Helper: Lọc bỏ 5 user đầu tiên (id 1-5) khỏi danh sách users
     */
    private function filterProtectedUsers(array $users): array
    {
        return array_filter($users, function($user) {
            return $user->id > 5;
        });
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting demo data seeding...');
        $this->command->newLine();

        // Thiết lập AUTO_INCREMENT trước khi bắt đầu transaction
        // (vì ALTER TABLE có thể tự động commit transaction)
        $this->ensureAutoIncrementSettings();

        DB::beginTransaction();

        try {
            // 1. Tạo organization demo (thứ 2, không dùng organization đầu tiên)
            $this->seedOrganization();

            // 2. Tạo users demo
            $this->seedUsers();

            // 3. Seed bảng cơ sở
            $this->seedPaymentCycles();
            $this->seedLeaseServiceSets();

            // 4. Seed địa điểm và bất động sản
            $this->seedPropertyTypes();
            $this->seedLocations();
            $this->seedProperties();
            $this->seedUnits();

            // 5. Seed hợp đồng
            $this->seedMasterLeases();
            $this->seedLeases();
            $this->seedLeaseResidents();

            // 6. Seed khách hàng và đặt cọc
            $this->seedLeads();
            $this->seedBookingDeposits();
            $this->seedViewings();

            // 7. Seed hóa đơn và thanh toán
            $this->seedPaymentMethods();
            $this->seedInvoices();
            $this->seedPayments();

            // 8. Seed ticket và hỗ trợ
            $this->seedTickets();

            // 9. Seed đồng hồ
            $this->seedMeters();

            // 10. Seed đánh giá
            $this->seedReviews();

            // 11. Seed hoa hồng (Commission Policies dựa trên CommissionEventService)
            $this->seedCommissionPolicies();
            $this->seedCommissionEvents();

            // 12. Seed nhà cung cấp
            $this->seedVendors();
            $this->seedCompanyInvoices();

            // 13. Seed tài liệu
            $this->seedDocuments();

            // Kiểm tra transaction level trước khi commit
            if (DB::transactionLevel() > 0) {
                DB::commit();
            }

            $this->command->newLine();
            $this->command->info('✅ Demo data seeding completed successfully!');
            $this->command->info("   Organization: {$this->organization->name} (ID: {$this->organization->id})");
            $this->command->info("   Users: " . count($this->users));
            $this->command->info("   Properties: " . count($this->properties));
            $this->command->info("   Units: " . count($this->units));
            $this->command->info("   Leases: " . count($this->leases));
            $this->command->info("   Invoices: " . count($this->invoices));

        } catch (\Exception $e) {
            // Chỉ rollback nếu transaction còn active
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->command->error('❌ Error seeding demo data: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Đảm bảo AUTO_INCREMENT được thiết lập đúng (thực hiện ngoài transaction)
     */
    private function ensureAutoIncrementSettings(): void
    {
        // Kiểm tra và set AUTO_INCREMENT cho organizations
        $maxOrgId = Organization::max('id') ?? 0;
        if ($maxOrgId < 1) {
            DB::statement('ALTER TABLE organizations AUTO_INCREMENT = 2');
            $this->command->info('   ✓ Set organizations AUTO_INCREMENT = 2 để bảo vệ organization ID = 1');
        }

        // Kiểm tra và set AUTO_INCREMENT cho users
        $maxUserId = User::max('id') ?? 0;
        if ($maxUserId < 6) {
            DB::statement('ALTER TABLE users AUTO_INCREMENT = 6');
            $this->command->info('   ✓ Set users AUTO_INCREMENT = 6 để bảo vệ user ID 1-5');
        }
    }

    /**
     * Tạo organization demo (thứ 2, không dùng organization đầu tiên - id = 1)
     */
    private function seedOrganization(): void
    {
        $this->command->info('📦 Creating demo organization...');

        // Kiểm tra organization id = 1 (không được đụng vào)
        $firstOrg = Organization::find(1);
        if (!$firstOrg) {
            $this->command->warn('   ⚠ Organization ID = 1 không tồn tại! Vui lòng chạy DefaultDataSeeder trước.');
        } else {
            $this->command->info("   ✓ Organization ID = 1 đã tồn tại: {$firstOrg->name} (KHÔNG ĐỤNG VÀO)");
        }

        // Tìm organization demo với id >= 2 (KHÔNG BAO GIỜ dùng id = 1)
        $this->organization = Organization::where('id', '>', 1)
            ->where('code', 'ORG-DEMO')
            ->first();

        // Nếu chưa có, tạo mới (Laravel sẽ tự động tạo id >= 2)
        if (!$this->organization) {
            $this->organization = Organization::create([
                'code' => 'ORG-DEMO',
                'name' => 'Demo Organization',
                'phone' => '0900000000',
                'email' => 'demo@organization.com',
                'address' => '123 Đường Demo, Quận Demo, TP. Hà Nội',
                'status' => 1,
            ]);
            $this->command->info("   ✓ Created: {$this->organization->name} (ID: {$this->organization->id})");
        } else {
            $this->command->info("   ✓ Using existing: {$this->organization->name} (ID: {$this->organization->id})");
        }

        // Kiểm tra an toàn cuối cùng: đảm bảo không bao giờ dùng id = 1
        if ($this->organization->id === 1) {
            throw new \Exception('LỖI NGHIÊM TRỌNG: Organization demo không được phép có ID = 1! Vui lòng kiểm tra lại database.');
        }
    }

    /**
     * Tạo users demo
     */
    private function seedUsers(): void
    {
        $this->command->info('👥 Creating demo users...');

        // Kiểm tra và bảo vệ 5 user đầu tiên (id 1-5)
        $protectedUsers = User::whereIn('id', [1, 2, 3, 4, 5])->get();
        if ($protectedUsers->isNotEmpty()) {
            $this->command->info("   ✓ Protected users (ID 1-5) exist: " . $protectedUsers->count() . " users (KHÔNG ĐỤNG VÀO)");
        } else {
            $this->command->warn('   ⚠ Users ID 1-5 không tồn tại! Vui lòng chạy DefaultDataSeeder trước.');
        }

        // Lấy roles
        $this->roles = [
            'admin' => Role::where('key_code', 'admin')->first(),
            'manager' => Role::where('key_code', 'manager')->first(),
            'agent' => Role::where('key_code', 'agent')->first(),
            'landlord' => Role::where('key_code', 'landlord')->first(),
            'tenant' => Role::where('key_code', 'tenant')->first(),
        ];

        // Tạo 5 landlords (KHÔNG dùng user id 1-5)
        for ($i = 1; $i <= 5; $i++) {
            $phone = '09' . str_pad($this->organization->id * 1000 + $i, 8, '0', STR_PAD_LEFT);
            $user = User::updateOrCreate(
                ['email' => "landlord{$i}@demo.com"],
                [
                    'phone' => $phone,
                    'password_hash' => Hash::make('password'),
                    'status' => 1,
                ]
            );

            // Kiểm tra an toàn: đảm bảo không bao giờ dùng user id 1-5
            if ($user->id >= 1 && $user->id <= 5) {
                throw new \Exception("LỖI NGHIÊM TRỌNG: User demo không được phép có ID = {$user->id}! Vui lòng kiểm tra lại database.");
            }

            // Tạo user profile (nếu chưa có)
            DB::table('user_profiles')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'full_name' => $this->faker->name,
                ]
            );

            // Gán vào organization với role landlord (chỉ cho organization demo, không đụng organization id = 1)
            if ($this->roles['landlord'] && $this->organization->id > 1) {
                OrganizationUser::updateOrCreate(
                    [
                        'organization_id' => $this->organization->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role_id' => $this->roles['landlord']->id,
                        'status' => 'active',
                    ]
                );
            }

            $this->users['landlords'][] = $user;
        }

        // Tạo 10 tenants (KHÔNG dùng user id 1-5)
        for ($i = 1; $i <= 10; $i++) {
            $phone = '09' . str_pad($this->organization->id * 1000 + 10 + $i, 8, '0', STR_PAD_LEFT);
            $user = User::updateOrCreate(
                ['email' => "tenant{$i}@demo.com"],
                [
                    'phone' => $phone,
                    'password_hash' => Hash::make('password'),
                    'status' => 1,
                ]
            );

            // Kiểm tra an toàn: đảm bảo không bao giờ dùng user id 1-5
            if ($user->id >= 1 && $user->id <= 5) {
                throw new \Exception("LỖI NGHIÊM TRỌNG: User demo không được phép có ID = {$user->id}! Vui lòng kiểm tra lại database.");
            }

            DB::table('user_profiles')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'full_name' => $this->faker->name,
                ]
            );

            // Chỉ gán vào organization demo (id > 1), không đụng organization id = 1
            if ($this->roles['tenant'] && $this->organization->id > 1) {
                OrganizationUser::updateOrCreate(
                    [
                        'organization_id' => $this->organization->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role_id' => $this->roles['tenant']->id,
                        'status' => 'active',
                    ]
                );
            }

            $this->users['tenants'][] = $user;
        }

        // Tạo 3 agents (KHÔNG dùng user id 1-5)
        for ($i = 1; $i <= 3; $i++) {
            $phone = '09' . str_pad($this->organization->id * 1000 + 20 + $i, 8, '0', STR_PAD_LEFT);
            $user = User::updateOrCreate(
                ['email' => "agent{$i}@demo.com"],
                [
                    'phone' => $phone,
                    'password_hash' => Hash::make('password'),
                    'status' => 1,
                ]
            );

            // Kiểm tra an toàn: đảm bảo không bao giờ dùng user id 1-5
            if ($user->id >= 1 && $user->id <= 5) {
                throw new \Exception("LỖI NGHIÊM TRỌNG: User demo không được phép có ID = {$user->id}! Vui lòng kiểm tra lại database.");
            }

            DB::table('user_profiles')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'full_name' => $this->faker->name,
                ]
            );

            // Chỉ gán vào organization demo (id > 1), không đụng organization id = 1
            if ($this->roles['agent'] && $this->organization->id > 1) {
                OrganizationUser::updateOrCreate(
                    [
                        'organization_id' => $this->organization->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role_id' => $this->roles['agent']->id,
                        'status' => 'active',
                    ]
                );
            }

            $this->users['agents'][] = $user;
        }

        // Tạo 2 managers (KHÔNG dùng user id 1-5)
        for ($i = 1; $i <= 2; $i++) {
            $phone = '09' . str_pad($this->organization->id * 1000 + 30 + $i, 8, '0', STR_PAD_LEFT);
            $user = User::updateOrCreate(
                ['email' => "manager{$i}@demo.com"],
                [
                    'phone' => $phone,
                    'password_hash' => Hash::make('password'),
                    'status' => 1,
                ]
            );

            // Kiểm tra an toàn: đảm bảo không bao giờ dùng user id 1-5
            if ($user->id >= 1 && $user->id <= 5) {
                throw new \Exception("LỖI NGHIÊM TRỌNG: User demo không được phép có ID = {$user->id}! Vui lòng kiểm tra lại database.");
            }

            DB::table('user_profiles')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'full_name' => $this->faker->name,
                ]
            );

            // Chỉ gán vào organization demo (id > 1), không đụng organization id = 1
            if ($this->roles['manager'] && $this->organization->id > 1) {
                OrganizationUser::updateOrCreate(
                    [
                        'organization_id' => $this->organization->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role_id' => $this->roles['manager']->id,
                        'status' => 'active',
                    ]
                );
            }

            $this->users['managers'][] = $user;
        }

        $this->command->info("   ✓ Created " . (count($this->users['landlords']) + count($this->users['tenants']) + count($this->users['agents']) + count($this->users['managers'])) . " users");
    }

    /**
     * Seed Payment Cycles
     */
    private function seedPaymentCycles(): void
    {
        $this->command->info('💳 Seeding payment cycles...');

        $cycleTypes = ['monthly', 'quarterly', 'yearly', 'custom'];
        $defaultCycle = null;

        for ($i = 1; $i <= 15; $i++) {
            $cycleType = $cycleTypes[array_rand($cycleTypes)];
            $isDefault = ($i === 1);

            $cycle = PaymentCycle::create([
                'organization_id' => $this->organization->id,
                'cycle_type' => $cycleType,
                'billing_day' => rand(1, 28),
                'custom_months' => $cycleType === 'custom' ? rand(2, 6) : null,
                'name' => "Chu kỳ {$i}",
                'is_default' => $isDefault,
                'payment_due_hours' => rand(24, 168), // 1-7 ngày
                'invoice_timing' => ['start_of_cycle', 'end_of_cycle'][array_rand(['start_of_cycle', 'end_of_cycle'])],
                'invoice_payment_days' => rand(1, 30),
            ]);

            $this->paymentCycles[] = $cycle;
            if ($isDefault) {
                $defaultCycle = $cycle;
            }
        }

        $this->command->info("   ✓ Created " . count($this->paymentCycles) . " payment cycles");
    }

    /**
     * Seed Lease Service Sets
     */
    private function seedLeaseServiceSets(): void
    {
        $this->command->info('🔧 Seeding lease service sets...');

        $services = Service::take(10)->get();
        if ($services->isEmpty()) {
            $this->command->warn('   ⚠ No services found, skipping lease service sets');
            return;
        }

        for ($i = 1; $i <= 15; $i++) {
            $isDefault = ($i === 1);

            $serviceSet = LeaseServiceSet::create([
                'organization_id' => $this->organization->id,
                'name' => "Bộ dịch vụ {$i}",
                'description' => $this->faker->sentence,
                'is_default' => $isDefault,
            ]);

            // Thêm 3-5 services vào mỗi set
            $selectedServices = $services->random(rand(3, 5));
            $sortOrder = 1;
            foreach ($selectedServices as $service) {
                LeaseServiceSetItem::create([
                    'lease_service_set_id' => $serviceSet->id,
                    'service_id' => $service->id,
                    'price' => rand(50000, 500000),
                    'sort_order' => $sortOrder++,
                ]);
            }

            $this->leaseServiceSets[] = $serviceSet;
        }

        $this->command->info("   ✓ Created " . count($this->leaseServiceSets) . " lease service sets");
    }

    /**
     * Seed Property Types
     */
    private function seedPropertyTypes(): void
    {
        $this->command->info('🏗️ Seeding property types...');

        $propertyTypes = [
            ['key_code' => 'apartment', 'name' => 'Chung cư', 'icon' => 'fa-building', 'description' => 'Chung cư cao cấp'],
            ['key_code' => 'house', 'name' => 'Nhà riêng', 'icon' => 'fa-home', 'description' => 'Nhà riêng biệt lập'],
            ['key_code' => 'villa', 'name' => 'Biệt thự', 'icon' => 'fa-home', 'description' => 'Biệt thự sang trọng'],
            ['key_code' => 'studio', 'name' => 'Studio', 'icon' => 'fa-door-open', 'description' => 'Phòng studio'],
            ['key_code' => 'dormitory', 'name' => 'Ký túc xá', 'icon' => 'fa-bed', 'description' => 'Ký túc xá'],
        ];

        foreach ($propertyTypes as $typeData) {
            PropertyType::updateOrCreate(
                ['key_code' => $typeData['key_code']],
                $typeData
            );
        }

        $this->command->info("   ✓ Created/Updated " . count($propertyTypes) . " property types");
    }

    /**
     * Seed Locations
     */
    private function seedLocations(): void
    {
        $this->command->info('📍 Seeding locations...');

        // Lấy một số tỉnh/thành phố
        $provinces = GeoProvince::take(5)->get();
        if ($provinces->isEmpty()) {
            $this->command->warn('   ⚠ No provinces found, creating locations without geo data');
        }

        for ($i = 1; $i <= 15; $i++) {
            $province = $provinces->isNotEmpty() ? $provinces->random() : null;
            $district = $province ? $province->districts()->first() : null;
            $ward = $district ? $district->wards()->first() : null;

            $location = Location::create([
                'country_code' => 'VN',
                'province_code' => $province ? $province->code : null,
                'district_code' => $district ? $district->code : null,
                'ward_code' => $ward ? $ward->code : null,
                'street' => $this->faker->streetAddress,
                'country' => 'Vietnam',
                'city' => $province ? $province->name : 'Hà Nội',
                'district' => $district ? $district->name : 'Quận 1',
                'ward' => $ward ? $ward->name : 'Phường 1',
                'lat' => $this->faker->latitude(20.5, 21.5),
                'lng' => $this->faker->longitude(105.5, 106.5),
            ]);

            $this->locations[] = $location;
        }

        $this->command->info("   ✓ Created " . count($this->locations) . " locations");
    }

    /**
     * Seed Properties
     */
    private function seedProperties(): void
    {
        $this->command->info('🏢 Seeding properties...');

        $propertyTypes = PropertyType::take(5)->get();
        if ($propertyTypes->isEmpty()) {
            $this->command->warn('   ⚠ No property types found');
        }

        for ($i = 1; $i <= 15; $i++) {
            $property = Property::create([
                'organization_id' => $this->organization->id,
                'property_type_id' => $propertyTypes->isNotEmpty() ? $propertyTypes->random()->id : null,
                'name' => "Bất động sản {$i}",
                'location_id' => $this->locations[array_rand($this->locations)]->id,
                'description' => $this->faker->paragraph,
                'total_floors' => rand(3, 20),
                'status' => rand(0, 1),
                'payment_cycle_id' => $this->paymentCycles[array_rand($this->paymentCycles)]->id,
                'lease_services_id' => $this->leaseServiceSets[array_rand($this->leaseServiceSets)]->id,
            ]);

            $this->properties[] = $property;
        }

        $this->command->info("   ✓ Created " . count($this->properties) . " properties");
    }

    /**
     * Seed Units
     */
    private function seedUnits(): void
    {
        $this->command->info('🏠 Seeding units...');

        // unit_type là enum: 'room', 'apartment', 'dorm', 'shared'
        $unitTypes = ['room', 'apartment', 'dorm', 'shared'];
        $amenities = Amenity::take(10)->get();

        foreach ($this->properties as $property) {
            $unitsPerProperty = rand(5, 10);
            for ($i = 1; $i <= $unitsPerProperty; $i++) {
                // Tạo code unique: P{org_id}-{property_id}-{unit_index}
                $unitCode = "P{$this->organization->id}-{$property->id}-" . str_pad($i, 3, '0', STR_PAD_LEFT);
                $unit = Unit::create([
                    'property_id' => $property->id,
                    'code' => $unitCode,
                    'floor' => rand(1, $property->total_floors),
                    'area_m2' => rand(20, 80),
                    'unit_type' => $unitTypes[array_rand($unitTypes)],
                    'base_rent' => rand(2000000, 10000000),
                    'deposit_amount' => rand(2000000, 5000000),
                    'max_occupancy' => rand(1, 4),
                    'status' => ['available', 'reserved', 'occupied', 'maintenance'][array_rand(['available', 'reserved', 'occupied', 'maintenance'])],
                    'note' => $this->faker->sentence,
                ]);

                // Gán amenities cho unit
                if ($amenities->isNotEmpty()) {
                    $selectedAmenities = $amenities->random(rand(3, 6));
                    $unit->amenities()->attach($selectedAmenities->pluck('id')->toArray());
                }

                $this->units[] = $unit;
            }
        }

        $this->command->info("   ✓ Created " . count($this->units) . " units");
    }

    /**
     * Seed Master Leases
     */
    private function seedMasterLeases(): void
    {
        $this->command->info('📋 Seeding master leases...');

        $landlords = $this->users['landlords'] ?? [];

        for ($i = 1; $i <= 15; $i++) {
            $property = $this->properties[array_rand($this->properties)];
            $landlord = !empty($landlords) ? $landlords[array_rand($landlords)] : null;

            $startDate = Carbon::now()->subMonths(rand(1, 12));
            $endDate = $startDate->copy()->addMonths(rand(12, 36));

            $masterLease = new MasterLease([
                'organization_id' => $this->organization->id,
                'landlord_user_id' => $landlord ? $landlord->id : null,
                'property_id' => $property->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'base_rent' => rand(50000000, 200000000),
                'rent_currency' => 'VND',
                'deposit_amount' => rand(10000000, 50000000),
                'billing_cycle' => rand(1, 3),
                'billing_day' => rand(1, 28),
                'due_in_days' => rand(1, 30),
                'revenue_share_pct' => rand(0, 30),
                'status' => ['draft', 'active', 'terminated', 'expired'][array_rand(['draft', 'active', 'terminated', 'expired'])],
                'note' => $this->faker->sentence,
            ]);
            
            // Generate contract number using model method
            $masterLease->contract_no = $masterLease->generateContractNumber();
            $masterLease->save();

            $this->masterLeases[] = $masterLease;
        }

        $this->command->info("   ✓ Created " . count($this->masterLeases) . " master leases");
    }

    /**
     * Seed Leases
     */
    private function seedLeases(): void
    {
        $this->command->info('📝 Seeding leases...');

        $tenants = $this->users['tenants'] ?? [];
        $agents = $this->users['agents'] ?? [];

        for ($i = 1; $i <= 15; $i++) {
            $unit = $this->units[array_rand($this->units)];
            $tenant = !empty($tenants) ? $tenants[array_rand($tenants)] : null;
            $agent = !empty($agents) ? $agents[array_rand($agents)] : null;

            $startDate = Carbon::now()->subMonths(rand(1, 6));
            $endDate = $startDate->copy()->addMonths(rand(6, 24));

            // Generate contract_no using SequenceGenerator
            $year = (int) date('Y');
            $month = (int) date('m');
            $sequenceKey = SequenceGenerator::buildKey('lease', $this->organization->id, $year, $month);
            $newSequence = SequenceGenerator::getNext($sequenceKey, function() use ($year, $month) {
                $existingLeases = Lease::withTrashed()
                    ->where('organization_id', $this->organization->id)
                    ->where(function($query) use ($year, $month) {
                        $query->where('contract_no', 'like', "HD-{$this->organization->id}-{$year}-{$month}-%");
                    })
                    ->pluck('contract_no')
                    ->toArray();
                
                $maxNumber = 0;
                foreach ($existingLeases as $contractNo) {
                    $parts = explode('-', $contractNo);
                    if (count($parts) >= 5) {
                        $number = (int) preg_replace('/[^0-9]/', '', $parts[4]);
                        if ($number > $maxNumber) {
                            $maxNumber = $number;
                        }
                    }
                }
                return $maxNumber;
            });
            
            $contractNo = "HD-{$this->organization->id}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
            
            $lease = Lease::create([
                'organization_id' => $this->organization->id,
                'unit_id' => $unit->id,
                'tenant_id' => $tenant ? $tenant->id : null,
                'agent_id' => $agent ? $agent->id : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'rent_amount' => $unit->base_rent,
                'deposit_amount' => $unit->deposit_amount,
                'payment_cycle_id' => $this->paymentCycles[array_rand($this->paymentCycles)]->id,
                'lease_services_id' => $this->leaseServiceSets[array_rand($this->leaseServiceSets)]->id,
                'status' => ['draft', 'active', 'terminated', 'expired'][array_rand(['draft', 'active', 'terminated', 'expired'])],
                'contract_no' => $contractNo,
                'signed_at' => $startDate->copy()->addDays(rand(0, 7)),
            ]);

            $this->leases[] = $lease;
        }

        $this->command->info("   ✓ Created " . count($this->leases) . " leases");
    }

    /**
     * Seed Lease Residents
     */
    private function seedLeaseResidents(): void
    {
        $this->command->info('👨‍👩‍👧‍👦 Seeding lease residents...');

        $tenants = $this->users['tenants'] ?? [];

        foreach ($this->leases as $lease) {
            if ($lease->status === 'active' && !empty($tenants)) {
                $numResidents = rand(1, 3);
                for ($i = 0; $i < $numResidents; $i++) {
                    LeaseResident::create([
                        'lease_id' => $lease->id,
                        'user_id' => $tenants[array_rand($tenants)]->id,
                        'name' => $this->faker->name,
                        'phone' => $this->faker->phoneNumber,
                        'id_number' => $this->faker->numerify('##########'),
                        'note' => $this->faker->sentence,
                    ]);
                }
            }
        }

        $count = LeaseResident::whereIn('lease_id', collect($this->leases)->pluck('id'))->count();
        $this->command->info("   ✓ Created {$count} lease residents");
    }

    /**
     * Seed Leads
     */
    private function seedLeads(): void
    {
        $this->command->info('📞 Seeding leads...');

        $tenants = $this->users['tenants'] ?? [];

        for ($i = 1; $i <= 15; $i++) {
            $tenant = !empty($tenants) && rand(0, 1) ? $tenants[array_rand($tenants)] : null;

            $lead = Lead::create([
                'organization_id' => $this->organization->id,
                'tenant_id' => $tenant ? $tenant->id : null,
                'source' => ['Google Ads', 'Facebook', 'Zalo', 'Referral', 'Website'][array_rand(['Google Ads', 'Facebook', 'Zalo', 'Referral', 'Website'])],
                'name' => $this->faker->name,
                'phone' => $this->faker->phoneNumber,
                'email' => $this->faker->email,
                'desired_city' => ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng'][array_rand(['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng'])],
                'budget_min' => rand(2000000, 5000000),
                'budget_max' => rand(5000000, 10000000),
                'note' => $this->faker->sentence,
                'status' => ['new', 'contacted', 'qualified', 'lost', 'converted'][array_rand(['new', 'contacted', 'qualified', 'lost', 'converted'])],
            ]);

            $this->leads[] = $lead;
        }

        $this->command->info("   ✓ Created " . count($this->leads) . " leads");
    }

    /**
     * Seed Booking Deposits
     */
    private function seedBookingDeposits(): void
    {
        $this->command->info('💰 Seeding booking deposits...');

        $tenants = $this->users['tenants'] ?? [];
        $agents = $this->users['agents'] ?? [];

        for ($i = 1; $i <= 15; $i++) {
            $unit = $this->units[array_rand($this->units)];
            $tenant = !empty($tenants) ? $tenants[array_rand($tenants)] : null;
            $lead = !empty($this->leads) && rand(0, 1) ? $this->leads[array_rand($this->leads)] : null;
            $agent = !empty($agents) ? $agents[array_rand($agents)] : null;

            $bookingDeposit = BookingDeposit::create([
                'organization_id' => $this->organization->id,
                'unit_id' => $unit->id,
                'tenant_user_id' => $tenant ? $tenant->id : null,
                'lead_id' => $lead ? $lead->id : null,
                'agent_id' => $agent ? $agent->id : null,
                'amount' => rand(1000000, 5000000),
                'payment_status' => ['pending', 'pending_approval', 'paid', 'refunded', 'expired', 'cancelled'][array_rand(['pending', 'pending_approval', 'paid', 'refunded', 'expired', 'cancelled'])],
                'deposit_type' => ['booking', 'security', 'advance'][array_rand(['booking', 'security', 'advance'])],
                'hold_until' => Carbon::now()->addDays(rand(7, 30)),
                'payment_due_date' => Carbon::now()->addDays(rand(1, 7)),
                'paid_at' => rand(0, 1) ? Carbon::now()->subDays(rand(1, 30)) : null,
                'approved_at' => rand(0, 1) ? Carbon::now()->subDays(rand(1, 30)) : null,
                'approved_by' => !empty($agents) ? $agents[array_rand($agents)]->id : null,
                'notes' => $this->faker->sentence,
            ]);

            $this->bookingDeposits[] = $bookingDeposit;
        }

        $this->command->info("   ✓ Created " . count($this->bookingDeposits) . " booking deposits");
    }

    /**
     * Seed Viewings
     */
    private function seedViewings(): void
    {
        $this->command->info('👀 Seeding viewings...');

        $tenants = $this->users['tenants'] ?? [];
        $agents = $this->users['agents'] ?? [];

        for ($i = 1; $i <= 15; $i++) {
            $property = $this->properties[array_rand($this->properties)];
            $unit = $property->units()->first();
            $tenant = !empty($tenants) ? $tenants[array_rand($tenants)] : null;
            $agent = !empty($agents) ? $agents[array_rand($agents)] : null;

            Viewing::create([
                'organization_id' => $this->organization->id,
                'property_id' => $property->id,
                'unit_id' => $unit ? $unit->id : null,
                'tenant_id' => $tenant ? $tenant->id : null,
                'agent_id' => $agent ? $agent->id : null,
                'schedule_at' => Carbon::now()->subDays(rand(0, 60)),
                'status' => ['requested', 'confirmed', 'done', 'cancelled', 'no_show'][array_rand(['requested', 'confirmed', 'done', 'cancelled', 'no_show'])],
                'note' => $this->faker->sentence,
            ]);
        }

        $this->command->info("   ✓ Created 15 viewings");
    }

    /**
     * Seed Invoices
     */
    private function seedInvoices(): void
    {
        $this->command->info('🧾 Seeding invoices...');

        $managers = $this->users['managers'] ?? [];

        foreach ($this->leases as $lease) {
            if ($lease->status === 'active') {
                // Tạo 2-4 invoices cho mỗi lease
                $numInvoices = rand(2, 4);
                for ($i = 0; $i < $numInvoices; $i++) {
                    $issueDate = Carbon::now()->subMonths(rand(0, 6))->startOfMonth();
                    $dueDate = $issueDate->copy()->addDays(30);

                    $invoice = Invoice::create([
                        'organization_id' => $this->organization->id,
                        'lease_id' => $lease->id,
                        'invoice_no' => Invoice::generateInvoiceNumber($this->organization->id, null),
                        'invoice_type' => $i === 0 ? 'first_invoice' : 'monthly_rent',
                        'issue_date' => $issueDate,
                        'due_date' => $dueDate,
                        'status' => ['draft', 'issued', 'paid', 'overdue', 'cancelled'][array_rand(['draft', 'issued', 'paid', 'overdue', 'cancelled'])],
                        'subtotal' => $lease->rent_amount,
                        'tax_amount' => 0,
                        'discount_amount' => rand(0, 500000),
                        'total_amount' => $lease->rent_amount - rand(0, 500000),
                        'currency' => 'VND',
                        'note' => "Hóa đơn tiền thuê tháng {$issueDate->format('m/Y')}",
                        'created_by' => !empty($managers) ? $managers[array_rand($managers)]->id : null,
                    ]);

                    // Tạo invoice items
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'item_type' => 'rent',
                        'description' => 'Tiền thuê phòng',
                        'quantity' => 1,
                        'unit_price' => $lease->rent_amount,
                        'amount' => $lease->rent_amount,
                    ]);

                    $this->invoices[] = $invoice;
                }
            }
        }

        // Tạo invoices cho booking deposits
        foreach ($this->bookingDeposits as $bookingDeposit) {
            if ($bookingDeposit->payment_status === 'paid') {
                $invoice = Invoice::create([
                    'organization_id' => $this->organization->id,
                    'booking_deposit_id' => $bookingDeposit->id,
                    'invoice_no' => Invoice::generateInvoiceNumber($this->organization->id, null),
                    'invoice_type' => 'booking_deposit',
                    'issue_date' => $bookingDeposit->paid_at ?? now(),
                    'due_date' => $bookingDeposit->payment_due_date ?? now(),
                    'status' => 'paid',
                    'subtotal' => $bookingDeposit->amount,
                    'total_amount' => $bookingDeposit->amount,
                    'currency' => 'VND',
                    'note' => 'Hóa đơn đặt cọc',
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'deposit',
                    'description' => 'Tiền đặt cọc',
                    'quantity' => 1,
                    'unit_price' => $bookingDeposit->amount,
                    'amount' => $bookingDeposit->amount,
                ]);

                $this->invoices[] = $invoice;
            }
        }

        $this->command->info("   ✓ Created " . count($this->invoices) . " invoices");
    }

    /**
     * Seed Payment Methods
     */
    private function seedPaymentMethods(): void
    {
        $this->command->info('💳 Seeding payment methods...');

        $methods = [
            ['key_code' => 'cash', 'name' => 'Tiền mặt'],
            ['key_code' => 'bank_transfer', 'name' => 'Chuyển khoản ngân hàng'],
            ['key_code' => 'momo', 'name' => 'Ví MoMo'],
            ['key_code' => 'zalopay', 'name' => 'ZaloPay'],
            ['key_code' => 'vnpay', 'name' => 'VNPay'],
            ['key_code' => 'credit_card', 'name' => 'Thẻ tín dụng'],
            ['key_code' => 'debit_card', 'name' => 'Thẻ ghi nợ'],
        ];

        foreach ($methods as $method) {
            $paymentMethod = PaymentMethod::updateOrCreate(
                ['key_code' => $method['key_code']],
                ['name' => $method['name']]
            );
            $this->paymentMethods[] = $paymentMethod;
        }

        $this->command->info("   ✓ Created/Updated " . count($this->paymentMethods) . " payment methods");
    }

    /**
     * Seed Payments
     */
    private function seedPayments(): void
    {
        $this->command->info('💵 Seeding payments...');

        if (empty($this->paymentMethods)) {
            $this->command->warn('   ⚠ No payment methods found');
            return;
        }

        $tenants = $this->users['tenants'] ?? [];

        foreach ($this->invoices as $invoice) {
            if (in_array($invoice->status, ['paid', 'issued']) && rand(0, 1)) {
                $paymentMethod = $this->paymentMethods[array_rand($this->paymentMethods)];
                $tenant = !empty($tenants) ? $tenants[array_rand($tenants)] : null;

                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'method_id' => $paymentMethod->id,
                    'amount' => $invoice->total_amount,
                    'paid_at' => $invoice->issue_date->copy()->addDays(rand(1, 30)),
                    'txn_ref' => 'TXN-' . strtoupper(Str::random(10)),
                    'status' => 'success',
                    'payer_user_id' => $tenant ? $tenant->id : null,
                    'note' => $this->faker->sentence,
                ]);

                // Generate txn_ref nếu có method
                try {
                    $payment->txn_ref = $payment->generateTxnRef($this->organization->id, $paymentMethod->id);
                    $payment->save();
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }

        $count = Payment::whereIn('invoice_id', collect($this->invoices)->pluck('id'))->count();
        $this->command->info("   ✓ Created {$count} payments");
    }

    /**
     * Seed Tickets
     */
    private function seedTickets(): void
    {
        $this->command->info('🎫 Seeding tickets...');

        $priorities = TicketPriority::take(3)->get();
        $tenants = $this->users['tenants'] ?? [];
        $agents = $this->users['agents'] ?? [];
        $managers = $this->users['managers'] ?? [];

        for ($i = 1; $i <= 15; $i++) {
            $property = $this->properties[array_rand($this->properties)];
            $unit = $property->units()->first();
            $lease = !empty($this->leases) && rand(0, 1) ? $this->leases[array_rand($this->leases)] : null;
            $createdBy = !empty($tenants) ? $tenants[array_rand($tenants)] : null;
            $assignedTo = !empty($agents) ? $agents[array_rand($agents)] : null;

            $ticket = Ticket::create([
                'organization_id' => $this->organization->id,
                'property_id' => $property->id,
                'unit_id' => $unit ? $unit->id : null,
                'lease_id' => $lease ? $lease->id : null,
                'created_by' => $createdBy ? $createdBy->id : null,
                'assigned_to' => $assignedTo ? $assignedTo->id : null,
                'title' => $this->faker->sentence,
                'description' => $this->faker->paragraph,
                'status' => ['open', 'in_progress', 'resolved', 'closed', 'cancelled'][array_rand(['open', 'in_progress', 'resolved', 'closed', 'cancelled'])],
                'priority_id' => $priorities->isNotEmpty() ? $priorities->random()->id : null,
            ]);

            // Tạo ticket logs
            if (rand(0, 1)) {
                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'actor_id' => $assignedTo ? $assignedTo->id : (!empty($managers) ? $managers[array_rand($managers)]->id : null),
                    'action' => 'comment',
                    'detail' => $this->faker->sentence,
                ]);
            }

            $this->tickets[] = $ticket;
        }

        $this->command->info("   ✓ Created " . count($this->tickets) . " tickets");
    }

    /**
     * Seed Meters
     */
    private function seedMeters(): void
    {
        $this->command->info('🔢 Seeding meters...');

        $services = Service::whereIn('key_code', ['electricity', 'water', 'internet'])->get();
        if ($services->isEmpty()) {
            $services = Service::take(3)->get();
        }

        foreach ($this->properties as $property) {
            foreach ($property->units()->take(3)->get() as $unit) {
                foreach ($services as $service) {
                    $meter = Meter::create([
                        'property_id' => $property->id,
                        'unit_id' => $unit->id,
                        'service_id' => $service->id,
                        'serial_no' => 'MTR-' . strtoupper(Str::random(8)),
                        'installed_at' => Carbon::now()->subMonths(rand(1, 12)),
                        'status' => rand(0, 1) == 1, // boolean
                    ]);

                    $this->meters[] = $meter;

                    // Tạo meter readings
                    for ($i = 0; $i < 3; $i++) {
                        MeterReading::create([
                            'meter_id' => $meter->id,
                            'value' => rand(100, 10000),
                            'reading_date' => Carbon::now()->subMonths($i)->startOfMonth(),
                            'taken_by' => !empty($this->users['agents']) ? $this->users['agents'][array_rand($this->users['agents'])]->id : null,
                            'note' => $this->faker->sentence,
                        ]);
                    }
                }
            }
        }

        $this->command->info("   ✓ Created " . count($this->meters) . " meters");
    }

    /**
     * Seed Reviews
     */
    private function seedReviews(): void
    {
        $this->command->info('⭐ Seeding reviews...');

        $tenants = $this->users['tenants'] ?? [];

        foreach ($this->leases as $lease) {
            if ($lease->status === 'active' && !empty($tenants) && rand(0, 1)) {
                $rating = rand(3, 5);
                $review = Review::create([
                    'organization_id' => $this->organization->id,
                    'unit_id' => $lease->unit_id,
                    'lease_id' => $lease->id,
                    'tenant_id' => $lease->tenant_id ?? $tenants[array_rand($tenants)]->id,
                    'overall_rating' => $rating,
                    'location_rating' => rand(3, 5),
                    'quality_rating' => rand(3, 5),
                    'service_rating' => rand(3, 5),
                    'price_rating' => rand(3, 5),
                    'title' => $this->faker->sentence,
                    'content' => $this->faker->paragraph,
                    'status' => 'published',
                ]);

                $this->reviews[] = $review;

                // Tạo review reply
                if (rand(0, 1) && !empty($this->users['managers'])) {
                    ReviewReply::create([
                        'review_id' => $review->id,
                        'user_id' => $this->users['managers'][array_rand($this->users['managers'])]->id,
                        'user_type' => 'manager',
                        'content' => $this->faker->sentence,
                    ]);
                }
            }
        }

        $this->command->info("   ✓ Created " . count($this->reviews) . " reviews");
    }

    /**
     * Seed Commission Policies dựa trên CommissionEventService
     */
    private function seedCommissionPolicies(): void
    {
        $this->command->info('💼 Seeding commission policies...');

        // Dựa trên CommissionEventService.php, các trigger events là:
        // - lease_signed: Khi ký hợp đồng
        // - deposit_paid: Khi thanh toán cọc (từ lease hoặc booking deposit)
        // - viewing_done: Khi xem phòng hoàn thành
        // - invoice_paid: Khi hóa đơn được thanh toán

        $policies = [
            // Lease Signed Policies
            [
                'code' => 'COMM_LEASE_SIGNED_1',
                'title' => 'Hoa hồng ký hợp đồng - Chính sách 1',
                'trigger_event' => 'lease_signed',
                'basis' => 'cash',
                'calc_type' => 'percent',
                'percent_value' => 5.00,
                'apply_limit_months' => null,
                'min_amount' => 500000,
                'cap_amount' => 5000000,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 70.00],
                    ['role_key' => 'manager', 'percent_share' => 30.00],
                ],
            ],
            [
                'code' => 'COMM_LEASE_SIGNED_2',
                'title' => 'Hoa hồng ký hợp đồng - Chính sách 2',
                'trigger_event' => 'lease_signed',
                'basis' => 'cash',
                'calc_type' => 'percent',
                'percent_value' => 3.00,
                'apply_limit_months' => null,
                'min_amount' => 300000,
                'cap_amount' => 3000000,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 80.00],
                    ['role_key' => 'manager', 'percent_share' => 20.00],
                ],
            ],
            [
                'code' => 'COMM_LEASE_SIGNED_FLAT',
                'title' => 'Hoa hồng ký hợp đồng - Cố định',
                'trigger_event' => 'lease_signed',
                'basis' => 'cash',
                'calc_type' => 'flat',
                'flat_amount' => 1000000,
                'apply_limit_months' => null,
                'min_amount' => null,
                'cap_amount' => null,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 100.00],
                ],
            ],

            // Deposit Paid Policies
            [
                'code' => 'COMM_DEPOSIT_PAID_1',
                'title' => 'Hoa hồng thanh toán cọc - Chính sách 1',
                'trigger_event' => 'deposit_paid',
                'basis' => 'cash',
                'calc_type' => 'percent',
                'percent_value' => 2.00,
                'apply_limit_months' => 1,
                'min_amount' => 100000,
                'cap_amount' => 1000000,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 80.00],
                    ['role_key' => 'manager', 'percent_share' => 20.00],
                ],
            ],
            [
                'code' => 'COMM_DEPOSIT_PAID_2',
                'title' => 'Hoa hồng thanh toán cọc - Chính sách 2',
                'trigger_event' => 'deposit_paid',
                'basis' => 'cash',
                'calc_type' => 'flat',
                'flat_amount' => 500000,
                'apply_limit_months' => null,
                'min_amount' => null,
                'cap_amount' => null,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 100.00],
                ],
            ],

            // Viewing Done Policies
            [
                'code' => 'COMM_VIEWING_DONE_1',
                'title' => 'Hoa hồng xem phòng - Chính sách 1',
                'trigger_event' => 'viewing_done',
                'basis' => 'cash',
                'calc_type' => 'flat',
                'flat_amount' => 200000,
                'apply_limit_months' => null,
                'min_amount' => null,
                'cap_amount' => null,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 100.00],
                ],
            ],
            [
                'code' => 'COMM_VIEWING_DONE_2',
                'title' => 'Hoa hồng xem phòng - Chính sách 2',
                'trigger_event' => 'viewing_done',
                'basis' => 'cash',
                'calc_type' => 'percent',
                'percent_value' => 0.5,
                'apply_limit_months' => null,
                'min_amount' => 50000,
                'cap_amount' => 500000,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 90.00],
                    ['role_key' => 'manager', 'percent_share' => 10.00],
                ],
            ],

            // Invoice Paid Policies
            [
                'code' => 'COMM_INVOICE_PAID_1',
                'title' => 'Hoa hồng thanh toán hóa đơn - Chính sách 1',
                'trigger_event' => 'invoice_paid',
                'basis' => 'cash',
                'calc_type' => 'percent',
                'percent_value' => 1.00,
                'apply_limit_months' => 12,
                'min_amount' => 50000,
                'cap_amount' => 1000000,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 60.00],
                    ['role_key' => 'manager', 'percent_share' => 40.00],
                ],
            ],
            [
                'code' => 'COMM_INVOICE_PAID_2',
                'title' => 'Hoa hồng thanh toán hóa đơn - Chính sách 2',
                'trigger_event' => 'invoice_paid',
                'basis' => 'cash',
                'calc_type' => 'percent',
                'percent_value' => 0.5,
                'apply_limit_months' => 6,
                'min_amount' => 25000,
                'cap_amount' => 500000,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 70.00],
                    ['role_key' => 'manager', 'percent_share' => 30.00],
                ],
            ],
            [
                'code' => 'COMM_INVOICE_PAID_3',
                'title' => 'Hoa hồng thanh toán hóa đơn - Chính sách 3',
                'trigger_event' => 'invoice_paid',
                'basis' => 'accrual',
                'calc_type' => 'flat',
                'flat_amount' => 100000,
                'apply_limit_months' => 3,
                'min_amount' => null,
                'cap_amount' => null,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 100.00],
                ],
            ],
        ];

        // Tạo thêm các policies ngẫu nhiên để đủ 15+
        $triggerEvents = ['lease_signed', 'deposit_paid', 'viewing_done', 'invoice_paid'];
        $calcTypes = ['percent', 'flat'];

        for ($i = count($policies) + 1; $i <= 15; $i++) {
            $triggerEvent = $triggerEvents[array_rand($triggerEvents)];
            $calcType = $calcTypes[array_rand($calcTypes)];

            $policyData = [
                'code' => "COMM_{$triggerEvent}_{$this->organization->id}_{$i}",
                'title' => "Hoa hồng {$triggerEvent} - Chính sách {$i}",
                'trigger_event' => $triggerEvent,
                'basis' => 'cash',
                'calc_type' => $calcType,
                'apply_limit_months' => $triggerEvent === 'invoice_paid' ? rand(1, 12) : null,
                'min_amount' => rand(50000, 500000),
                'cap_amount' => rand(500000, 5000000),
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => rand(60, 90)],
                    ['role_key' => 'manager', 'percent_share' => rand(10, 40)],
                ],
            ];

            if ($calcType === 'percent') {
                $policyData['percent_value'] = rand(1, 10);
            } else {
                $policyData['flat_amount'] = rand(100000, 2000000);
            }

            $policies[] = $policyData;
        }

        // Tạo policies trong database
        foreach ($policies as $policyData) {
            // Bỏ splits vì bảng commission_policy_splits không tồn tại
            unset($policyData['splits']);

            // Đảm bảo code là unique cho organization
            $code = $policyData['code'] . '-' . $this->organization->id;
            
            $policy = CommissionPolicy::updateOrCreate(
                [
                    'code' => $code,
                    'organization_id' => $this->organization->id,
                ],
                array_merge($policyData, [
                    'code' => $code,
                    'organization_id' => $this->organization->id,
                ])
            );

            $this->commissionPolicies[] = $policy;
        }

        $this->command->info("   ✓ Created " . count($this->commissionPolicies) . " commission policies");
    }

    /**
     * Seed Commission Events
     */
    private function seedCommissionEvents(): void
    {
        $this->command->info('📊 Seeding commission events...');

        $agents = $this->users['agents'] ?? [];

        // Tạo events từ leases
        foreach ($this->leases as $lease) {
            if ($lease->status === 'active' && $lease->agent_id && !empty($agents)) {
                $policies = CommissionPolicy::where('organization_id', $this->organization->id)
                    ->where('trigger_event', 'lease_signed')
                    ->where('active', true)
                    ->get();

                foreach ($policies as $policy) {
                    if (rand(0, 1)) {
                        $baseAmount = $lease->rent_amount;
                        $commission = $policy->calculateCommission($baseAmount);

                        if ($commission > 0) {
                            CommissionEvent::create([
                                'policy_id' => $policy->id,
                                'organization_id' => $this->organization->id,
                                'trigger_event' => 'lease_signed',
                                'ref_type' => 'lease',
                                'ref_id' => $lease->id,
                                'lease_id' => $lease->id,
                                'unit_id' => $lease->unit_id,
                                'agent_id' => $lease->agent_id,
                                'occurred_at' => $lease->signed_at ?? $lease->start_date,
                                'amount_base' => $baseAmount,
                                'commission_total' => $commission,
                                'status' => ['pending', 'approved', 'paid', 'reversed', 'cancelled'][array_rand(['pending', 'approved', 'paid', 'reversed', 'cancelled'])],
                            ]);
                        }
                    }
                }
            }
        }

        // Tạo events từ invoices
        foreach ($this->invoices as $invoice) {
            if ($invoice->status === 'paid' && $invoice->lease_id) {
                $lease = $invoice->lease;
                if ($lease && $lease->agent_id) {
                    $policies = CommissionPolicy::where('organization_id', $this->organization->id)
                        ->where('trigger_event', 'invoice_paid')
                        ->where('active', true)
                        ->get();

                    foreach ($policies as $policy) {
                        if (rand(0, 1)) {
                            $baseAmount = $invoice->total_amount;
                            $commission = $policy->calculateCommission($baseAmount);

                            if ($commission > 0) {
                                CommissionEvent::create([
                                    'policy_id' => $policy->id,
                                    'organization_id' => $this->organization->id,
                                    'trigger_event' => 'invoice_paid',
                                    'ref_type' => 'invoice',
                                    'ref_id' => $invoice->id,
                                    'lease_id' => $lease->id,
                                    'unit_id' => $lease->unit_id,
                                    'agent_id' => $lease->agent_id,
                                    'occurred_at' => $invoice->updated_at ?? now(),
                                    'amount_base' => $baseAmount,
                                    'commission_total' => $commission,
                                    'status' => ['pending', 'approved', 'paid', 'reversed', 'cancelled'][array_rand(['pending', 'approved', 'paid', 'reversed', 'cancelled'])],
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $count = CommissionEvent::where('organization_id', $this->organization->id)->count();
        $this->command->info("   ✓ Created {$count} commission events");
    }

    /**
     * Seed Vendors
     */
    private function seedVendors(): void
    {
        $this->command->info('🏪 Seeding vendors...');

        $banks = SepayBank::take(5)->get();

        for ($i = 1; $i <= 15; $i++) {
            $bank = $banks->isNotEmpty() && rand(0, 1) ? $banks->random() : null;
            
            $vendor = Vendor::create([
                'organization_id' => $this->organization->id,
                'name' => "Nhà cung cấp {$i}",
                'vendor_type' => ['individual', 'company'][array_rand(['individual', 'company'])],
                'phone' => $this->faker->phoneNumber,
                'email' => $this->faker->email,
                'address' => $this->faker->address,
                'tax_code' => $this->faker->numerify('##########'),
                'sepay_bank_id' => $bank ? $bank->id : null,
                'account_number' => $bank && rand(0, 1) ? $this->faker->numerify('############') : null,
                'account_holder_name' => $bank && rand(0, 1) ? $this->faker->name : null,
                'contact_person' => $this->faker->name,
                'contact_phone' => $this->faker->phoneNumber,
                'contact_email' => $this->faker->email,
                'business_license' => rand(0, 1) ? $this->faker->numerify('##########') : null,
                'status' => ['active', 'inactive', 'suspended'][array_rand(['active', 'inactive', 'suspended'])],
            ]);

            $this->vendors[] = $vendor;
        }

        $this->command->info("   ✓ Created " . count($this->vendors) . " vendors");
    }

    /**
     * Seed Company Invoices
     */
    private function seedCompanyInvoices(): void
    {
        $this->command->info('🏢 Seeding company invoices...');

        $managers = $this->users['managers'] ?? [];
        $agents = $this->users['agents'] ?? [];
        
        // Đảm bảo có ít nhất một user để làm created_by
        $createdByUser = !empty($managers) ? $managers[array_rand($managers)] : 
                        (!empty($agents) ? $agents[array_rand($agents)] : 
                        (!empty($this->users['landlords']) ? $this->users['landlords'][array_rand($this->users['landlords'])] : null));
        
        if (!$createdByUser) {
            $this->command->warn('   ⚠ No users found for created_by, skipping company invoices');
            return;
        }

        for ($i = 1; $i <= 15; $i++) {
            $vendor = $this->vendors[array_rand($this->vendors)];
            $masterLease = !empty($this->masterLeases) && rand(0, 1) ? $this->masterLeases[array_rand($this->masterLeases)] : null;

            // Xác định invoice_type dựa trên master_lease_id
            $invoiceType = $masterLease ? 'master_lease' : ['utility', 'maintenance', 'service', 'supply', 'other'][array_rand(['utility', 'maintenance', 'service', 'supply', 'other'])];
            
            $companyInvoice = new CompanyInvoice([
                'organization_id' => $this->organization->id,
                'vendor_id' => $vendor->id,
                'master_lease_id' => $masterLease ? $masterLease->id : null,
                'invoice_type' => $invoiceType,
                'issue_date' => Carbon::now()->subDays(rand(0, 90)),
                'due_date' => Carbon::now()->addDays(rand(1, 30)),
                'status' => ['draft', 'pending', 'approved', 'paid', 'overdue', 'cancelled'][array_rand(['draft', 'pending', 'approved', 'paid', 'overdue', 'cancelled'])],
                'subtotal' => rand(1000000, 10000000),
                'tax_amount' => rand(0, 1000000),
                'discount_amount' => rand(0, 500000),
                'total_amount' => rand(1000000, 11000000),
                'currency' => 'VND',
                'description' => $this->faker->sentence,
                'note' => $this->faker->sentence,
                'created_by' => $createdByUser->id,
            ]);
            
            // Generate invoice number using model method
            $companyInvoice->invoice_no = $companyInvoice->generateInvoiceNumber();
            $companyInvoice->save();

            // Tạo invoice items
            $numItems = rand(1, 3);
            for ($j = 0; $j < $numItems; $j++) {
                $unitPrice = rand(100000, 1000000);
                $quantity = rand(1, 10);
                CompanyInvoiceItem::create([
                    'company_invoice_id' => $companyInvoice->id,
                    'item_type' => 'other',
                    'description' => $this->faker->words(3, true),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'amount' => $unitPrice * $quantity,
                ]);
            }
        }

        $this->command->info("   ✓ Created 15 company invoices");
    }

    /**
     * Seed Documents
     */
    private function seedDocuments(): void
    {
        $this->command->info('📄 Seeding documents...');

        $allUsers = array_merge(
            $this->users['landlords'] ?? [],
            $this->users['tenants'] ?? [],
            $this->users['agents'] ?? [],
            $this->users['managers'] ?? []
        );

        for ($i = 1; $i <= 15; $i++) {
            $user = !empty($allUsers) ? $allUsers[array_rand($allUsers)] : null;

            $document = Document::create([
                'uploaded_by' => $user ? $user->id : null,
                'file_name' => "document_{$i}.pdf",
                'file_url' => "https://example.com/documents/document_{$i}.pdf",
                'file_size' => rand(100000, 5000000),
                'mime_type' => 'application/pdf',
                'document_type' => ['image', 'document', 'avatar', 'photo', 'attachment'][array_rand(['image', 'document', 'avatar', 'photo', 'attachment'])],
                'description' => $this->faker->sentence,
            ]);

            $this->documents[] = $document;
        }

        $this->command->info("   ✓ Created " . count($this->documents) . " documents");
    }
}

