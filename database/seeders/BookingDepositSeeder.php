<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BookingDeposit;
use App\Models\Unit;
use App\Models\User;
use App\Models\Lead;
use App\Models\Organization;

class BookingDepositSeeder extends Seeder
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

        // Get a user (agent)
        $agent = User::whereHas('organizations', function($query) use ($organization) {
            $query->where('organizations.id', $organization->id);
        })->first();

        if (!$agent) {
            $this->command->error('No agent found in organization.');
            return;
        }

        // Get available units
        $units = Unit::where('organization_id', $organization->id)
                    ->where('status', 'available')
                    ->take(3)
                    ->get();

        if ($units->isEmpty()) {
            $this->command->error('No available units found.');
            return;
        }

        // Get leads
        $leads = Lead::where('organization_id', $organization->id)->take(3)->get();

        if ($leads->isEmpty()) {
            $this->command->error('No leads found.');
            return;
        }

        // Create sample booking deposits
        $deposits = [
            [
                'organization_id' => $organization->id,
                'unit_id' => $units[0]->id,
                'lead_id' => $leads[0]->id,
                'agent_id' => $agent->id,
                'amount' => 5000000,
                'deposit_type' => 'booking',
                'payment_status' => 'pending',
                'hold_until' => now()->addDays(7),
                'notes' => 'Đặt cọc phòng trọ - Khách hàng quan tâm',
                'reference_number' => 'BD' . date('Ymd') . '001',
            ],
            [
                'organization_id' => $organization->id,
                'unit_id' => $units->count() > 1 ? $units[1]->id : $units[0]->id,
                'lead_id' => $leads->count() > 1 ? $leads[1]->id : $leads[0]->id,
                'agent_id' => $agent->id,
                'amount' => 3000000,
                'deposit_type' => 'security',
                'payment_status' => 'paid',
                'hold_until' => now()->addDays(5),
                'paid_at' => now()->subDays(1),
                'notes' => 'Cọc an toàn - Đã thanh toán',
                'reference_number' => 'BD' . date('Ymd') . '002',
            ],
            [
                'organization_id' => $organization->id,
                'unit_id' => $units->count() > 2 ? $units[2]->id : $units[0]->id,
                'lead_id' => $leads->count() > 2 ? $leads[2]->id : $leads[0]->id,
                'agent_id' => $agent->id,
                'amount' => 2000000,
                'deposit_type' => 'advance',
                'payment_status' => 'expired',
                'hold_until' => now()->subDays(2),
                'expired_at' => now()->subDays(1),
                'notes' => 'Trả trước - Đã hết hạn',
                'reference_number' => 'BD' . date('Ymd') . '003',
            ],
        ];

        foreach ($deposits as $depositData) {
            BookingDeposit::create($depositData);
        }

        $this->command->info('Created ' . count($deposits) . ' sample booking deposits.');
    }
}
