<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalaryContract;
use App\Models\User;
use App\Models\Organization;

class SalaryContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first organization
        $organization = Organization::first();
        if (!$organization) {
            $this->command->info('No organization found. Please create an organization first.');
            return;
        }

        // Get users from the organization
        $users = User::whereHas('organizations', function($q) use ($organization) {
            $q->where('organization_id', $organization->id);
        })->get();

        if ($users->isEmpty()) {
            $this->command->info('No users found in the organization. Please create users first.');
            return;
        }

        // Create salary contracts for each user
        foreach ($users as $user) {
            SalaryContract::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'base_salary' => rand(5000000, 15000000), // Random salary between 5M and 15M VND
                'currency' => 'VND',
                'pay_cycle' => 'monthly',
                'pay_day' => 1,
                'allowances_json' => [
                    'transport' => rand(500000, 1000000),
                    'meal' => rand(300000, 800000),
                    'phone' => rand(100000, 300000),
                ],
                'kpi_target_json' => [
                    'sales_target' => rand(10, 50),
                    'commission_rate' => rand(2, 10),
                ],
                'effective_from' => now()->subMonths(6),
                'effective_to' => null,
                'status' => 'active',
            ]);
        }

        $this->command->info('Salary contracts created successfully!');
    }
}
