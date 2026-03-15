<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommissionPolicy;
use App\Models\CommissionPolicySplit;
use App\Models\CommissionEvent;
use App\Models\Organization;
use App\Models\User;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\Property;

class CommissionDataSeeder extends Seeder
{
    public function run()
    {
        $organization = Organization::first();
        
        if (!$organization) {
            $this->command->error('No organization found. Please run OrganizationSeeder first.');
            return;
        }

        // Create Commission Policies
        $policies = [
            [
                'code' => 'COMM_LEASE_SIGNED',
                'title' => 'Hoa hồng ký hợp đồng',
                'trigger_event' => 'lease_signed',
                'basis' => 'cash',
                'calc_type' => 'percent',
                'percent_value' => 5.00,
                'apply_limit_months' => 12,
                'min_amount' => 500000,
                'cap_amount' => 5000000,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 70.00],
                    ['role_key' => 'manager', 'percent_share' => 30.00]
                ]
            ],
            [
                'code' => 'COMM_DEPOSIT_PAID',
                'title' => 'Hoa hồng thanh toán cọc',
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
                    ['role_key' => 'manager', 'percent_share' => 20.00]
                ]
            ],
            [
                'code' => 'COMM_VIEWING_DONE',
                'title' => 'Hoa hồng xem phòng',
                'trigger_event' => 'viewing_done',
                'basis' => 'cash',
                'calc_type' => 'flat',
                'flat_amount' => 100000,
                'apply_limit_months' => 1,
                'min_amount' => 50000,
                'cap_amount' => 200000,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 100.00]
                ]
            ],
            [
                'code' => 'COMM_LISTING_PUBLISHED',
                'title' => 'Hoa hồng đăng tin',
                'trigger_event' => 'listing_published',
                'basis' => 'cash',
                'calc_type' => 'flat',
                'flat_amount' => 50000,
                'apply_limit_months' => 1,
                'min_amount' => 25000,
                'cap_amount' => 100000,
                'active' => true,
                'splits' => [
                    ['role_key' => 'agent', 'percent_share' => 100.00]
                ]
            ]
        ];

        foreach ($policies as $policyData) {
            $splits = $policyData['splits'];
            unset($policyData['splits']);

            $policy = CommissionPolicy::create([
                'organization_id' => $organization->id,
                ...$policyData
            ]);

            // Create policy splits
            foreach ($splits as $split) {
                CommissionPolicySplit::create([
                    'policy_id' => $policy->id,
                    'role_key' => $split['role_key'],
                    'percent_share' => $split['percent_share']
                ]);
            }

            $this->command->info("Created commission policy: {$policy->title}");
        }

        // Create sample commission events
        $agents = User::whereHas('roles', function($q) {
            $q->whereIn('key_code', ['agent', 'manager']);
        })->get();

        $leases = Lease::with(['tenant', 'unit.property'])->get();
        $units = Unit::with('property')->get();

        if ($agents->count() > 0 && $leases->count() > 0) {
            $leasePolicy = CommissionPolicy::where('code', 'COMM_LEASE_SIGNED')->first();
            $depositPolicy = CommissionPolicy::where('code', 'COMM_DEPOSIT_PAID')->first();

            // Create commission events for leases
            foreach ($leases->take(3) as $lease) {
                $agent = $agents->random();
                
                // Lease signed event
                if ($leasePolicy) {
                    $baseAmount = $lease->rent_amount;
                    $commissionTotal = $leasePolicy->calculateCommission($baseAmount);

                    $event = CommissionEvent::create([
                        'policy_id' => $leasePolicy->id,
                        'organization_id' => $organization->id,
                        'trigger_event' => 'lease_signed',
                        'ref_type' => 'lease',
                        'ref_id' => $lease->id,
                        'lease_id' => $lease->id,
                        'unit_id' => $lease->unit_id,
                        'agent_id' => $agent->id,
                        'occurred_at' => $lease->signed_at ?? now(),
                        'amount_base' => $baseAmount,
                        'commission_total' => $commissionTotal,
                        'status' => 'approved'
                    ]);

                    // Note: Event splits are no longer used

                    $this->command->info("Created commission event for lease #{$lease->id}");
                }

                // Deposit paid event
                if ($depositPolicy && $lease->deposit_amount > 0) {
                    $baseAmount = $lease->deposit_amount;
                    $commissionTotal = $depositPolicy->calculateCommission($baseAmount);

                    $event = CommissionEvent::create([
                        'policy_id' => $depositPolicy->id,
                        'organization_id' => $organization->id,
                        'trigger_event' => 'deposit_paid',
                        'ref_type' => 'lease',
                        'ref_id' => $lease->id,
                        'lease_id' => $lease->id,
                        'unit_id' => $lease->unit_id,
                        'agent_id' => $agent->id,
                        'occurred_at' => $lease->signed_at ?? now(),
                        'amount_base' => $baseAmount,
                        'commission_total' => $commissionTotal,
                        'status' => 'pending'
                    ]);

                    // Note: Event splits are no longer used

                    $this->command->info("Created commission event for deposit #{$lease->id}");
                }
            }
        }

        $this->command->info('Commission data seeded successfully!');
    }
}
