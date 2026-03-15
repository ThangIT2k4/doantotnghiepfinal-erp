<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;
use App\Models\PlanFeature;
use Illuminate\Support\Facades\DB;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing data (in correct order due to foreign keys)
        // Do this outside transaction as ALTER TABLE doesn't work in transactions
        $this->command->info('Deleting existing subscription data...');
        
        // Delete organization_subscriptions first (has foreign key to subscription_plans with restrict)
        $deletedSubscriptions = DB::table('organization_subscriptions')->delete();
        $this->command->info("Deleted {$deletedSubscriptions} organization_subscriptions");
        
        // Delete plan_features
        $deletedFeatures = DB::table('plan_features')->delete();
        $this->command->info("Deleted {$deletedFeatures} plan_features");
        
        // Delete subscription_plans
        $deletedPlans = DB::table('subscription_plans')->delete();
        $this->command->info("Deleted {$deletedPlans} subscription_plans");
        
        // Reset auto increment
        DB::statement('ALTER TABLE subscription_plans AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE plan_features AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE organization_subscriptions AUTO_INCREMENT = 1');
        
        // Now start transaction for seeding
        DB::beginTransaction();

        try {
            $this->command->info('Starting to seed subscription plans...');
            
            // Free Plan
            $freePlan = SubscriptionPlan::create([
                'code' => 'FREE',
                'name' => 'Gói Miễn Phí',
                'description' => 'Gói miễn phí dành cho cá nhân và doanh nghiệp nhỏ mới bắt đầu',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'VND',
                'trial_days' => 30,
                'is_active' => true,
                'is_custom' => false,
                'sort_order' => 1,
            ]);

            $this->createFeatures($freePlan->id, [
                ['max_properties', 'Số lượng bất động sản tối đa', 'limit', 2],
                ['max_units', 'Số lượng đơn vị tối đa', 'limit', 10],
                ['max_users', 'Số lượng người dùng tối đa', 'limit', 2],
                ['max_leases', 'Số lượng hợp đồng thuê tối đa', 'limit', 10],
                ['enable_reports', 'Báo cáo nâng cao', 'boolean', false],
                ['enable_webhooks', 'Webhooks', 'boolean', false],
                ['enable_data_export', 'Xuất dữ liệu', 'boolean', false],
                ['enable_chat', 'Chat với AI', 'boolean', false],
            ]);

            // Starter Plan
            $starterPlan = SubscriptionPlan::create([
                'code' => 'STARTER',
                'name' => 'Gói Khởi Đầu',
                'description' => 'Dành cho doanh nghiệp nhỏ với nhu cầu quản lý vừa phải',
                'price_monthly' => 500000,
                'price_yearly' => 5000000,
                'currency' => 'VND',
                'trial_days' => 14,
                'is_active' => true,
                'is_custom' => false,
                'sort_order' => 2,
            ]);

            $this->createFeatures($starterPlan->id, [
                ['max_properties', 'Số lượng bất động sản tối đa', 'limit', 10],
                ['max_units', 'Số lượng đơn vị tối đa', 'limit', 50],
                ['max_users', 'Số lượng người dùng tối đa', 'limit', 5],
                ['max_leases', 'Số lượng hợp đồng thuê tối đa', 'limit', 50],
                ['enable_reports', 'Báo cáo nâng cao', 'boolean', true],
                ['enable_webhooks', 'Webhooks', 'boolean', false],
                ['enable_data_export', 'Xuất dữ liệu', 'boolean', true],
                ['enable_chat', 'Chat với AI', 'boolean', false],
            ]);

            // Professional Plan
            $proPlan = SubscriptionPlan::create([
                'code' => 'PROFESSIONAL',
                'name' => 'Gói Chuyên Nghiệp',
                'description' => 'Dành cho doanh nghiệp vừa với nhu cầu quản lý chuyên nghiệp',
                'price_monthly' => 1500000,
                'price_yearly' => 15000000,
                'currency' => 'VND',
                'trial_days' => 14,
                'is_active' => true,
                'is_custom' => false,
                'sort_order' => 3,
            ]);

            $this->createFeatures($proPlan->id, [
                ['max_properties', 'Số lượng bất động sản tối đa', 'limit', 50],
                ['max_units', 'Số lượng đơn vị tối đa', 'limit', 200],
                ['max_users', 'Số lượng người dùng tối đa', 'limit', 15],
                ['max_leases', 'Số lượng hợp đồng thuê tối đa', 'limit', 200],
                ['enable_reports', 'Báo cáo nâng cao', 'boolean', true],
                ['enable_webhooks', 'Webhooks', 'boolean', true],
                ['enable_advanced_permissions', 'Phân quyền nâng cao', 'boolean', true],
                ['enable_data_export', 'Xuất dữ liệu', 'boolean', true],
                ['enable_chat', 'Chat với AI', 'boolean', true],
            ]);

            // Enterprise Plan
            $enterprisePlan = SubscriptionPlan::create([
                'code' => 'ENTERPRISE',
                'name' => 'Gói Doanh Nghiệp',
                'description' => 'Dành cho doanh nghiệp lớn với nhu cầu không giới hạn',
                'price_monthly' => 5000000,
                'price_yearly' => 50000000,
                'currency' => 'VND',
                'trial_days' => 30,
                'is_active' => true,
                'is_custom' => false,
                'sort_order' => 4,
            ]);

            $this->createFeatures($enterprisePlan->id, [
                ['max_properties', 'Số lượng bất động sản tối đa', 'limit', -1], // -1 = unlimited
                ['max_units', 'Số lượng đơn vị tối đa', 'limit', -1],
                ['max_users', 'Số lượng người dùng tối đa', 'limit', -1],
                ['max_leases', 'Số lượng hợp đồng thuê tối đa', 'limit', -1],
                ['enable_reports', 'Báo cáo nâng cao', 'boolean', true],
                ['enable_webhooks', 'Webhooks', 'boolean', true],
                ['enable_advanced_permissions', 'Phân quyền nâng cao', 'boolean', true],
                ['enable_priority_support', 'Hỗ trợ ưu tiên', 'boolean', true],
                ['enable_data_export', 'Xuất dữ liệu', 'boolean', true],
                ['enable_chat', 'Chat với AI', 'boolean', true],
            ]);

            DB::commit();

            $this->command->info('Subscription plans seeded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding subscription plans: ' . $e->getMessage());
        }
    }

    /**
     * Create features for a plan.
     */
    protected function createFeatures(int $planId, array $features): void
    {
        foreach ($features as $feature) {
            [$key, $name, $type, $value] = $feature;

            $featureValue = match($type) {
                'limit' => ['limit' => $value],
                'boolean' => ['enabled' => $value],
                default => ['value' => $value],
            };

            PlanFeature::create([
                'plan_id' => $planId,
                'feature_key' => $key,
                'feature_name' => $name,
                'feature_type' => $type,
                'feature_value' => $featureValue,
            ]);
        }
    }
}
