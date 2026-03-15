<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommissionPolicy;
use App\Models\Organization;

class CommissionPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultOrg = Organization::where('code', 'ORG_MAIN')->first();

        $policies = [
            [
                'organization_id' => $defaultOrg?->id,
                'code' => 'COMM_LEASE_SIGNED',
                'title' => 'Hoa hồng ký hợp đồng',
                'trigger_event' => 'lease_signed',
                'basis' => 'cash',
                'calc_type' => 'percent',
                'percent_value' => 5.00, // 5%
                'apply_limit_months' => 12,
                'min_amount' => 1000000, // 1 million VND
                'active' => 1,
            ],
            [
                'organization_id' => $defaultOrg?->id,
                'code' => 'COMM_DEPOSIT_PAID',
                'title' => 'Hoa hồng đặt cọc',
                'trigger_event' => 'deposit_paid',
                'basis' => 'cash',
                'calc_type' => 'percent',
                'percent_value' => 2.00, // 2%
                'apply_limit_months' => null,
                'min_amount' => 500000, // 500k VND
                'active' => 1,
            ],
            [
                'organization_id' => $defaultOrg?->id,
                'code' => 'COMM_VIEWING_DONE',
                'title' => 'Hoa hồng xem phòng',
                'trigger_event' => 'viewing_done',
                'basis' => 'cash',
                'calc_type' => 'flat',
                'flat_amount' => 100000, // 100k VND
                'apply_limit_months' => null,
                'min_amount' => null,
                'active' => 1,
            ],
        ];

        foreach ($policies as $policy) {
            CommissionPolicy::updateOrCreate(
                ['code' => $policy['code']],
                $policy
            );
        }
    }
}