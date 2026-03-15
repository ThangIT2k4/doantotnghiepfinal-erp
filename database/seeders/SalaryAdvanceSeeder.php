<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalaryAdvance;
use App\Models\Organization;
use App\Models\User;

class SalaryAdvanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::first();
        if (!$organization) {
            $this->command->info('No organization found. Please create an organization first.');
            return;
        }

        $users = User::whereHas('organizations', function($q) use ($organization) {
            $q->where('organization_id', $organization->id);
        })->get();

        if ($users->isEmpty()) {
            $this->command->info('No users found in the organization. Please create users first.');
            return;
        }

        // Create sample salary advances
        $reasons = [
            'Ứng lương để chi trả chi phí y tế',
            'Ứng lương để mua sắm đồ dùng cần thiết',
            'Ứng lương để chi trả học phí',
            'Ứng lương để sửa chữa nhà cửa',
            'Ứng lương để chi trả nợ cũ',
            'Ứng lương để đầu tư kinh doanh nhỏ',
            'Ứng lương để chi trả tiền thuê nhà',
            'Ứng lương để mua xe máy'
        ];

        $repaymentMethods = ['payroll_deduction', 'direct_payment', 'installment'];
        $statuses = ['pending', 'approved', 'rejected', 'partially_repaid'];

        foreach ($users as $user) {
            // Create 1-3 salary advances per user
            $advanceCount = rand(1, 3);
            
            for ($i = 0; $i < $advanceCount; $i++) {
                $amount = rand(1000000, 5000000); // 1M to 5M VND
                $advanceDate = now()->subDays(rand(1, 30));
                $expectedRepaymentDate = $advanceDate->copy()->addMonths(rand(1, 6));
                $repaymentMethod = $repaymentMethods[array_rand($repaymentMethods)];
                $status = $statuses[array_rand($statuses)];

                $salaryAdvance = SalaryAdvance::create([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => 'VND',
                    'advance_date' => $advanceDate,
                    'expected_repayment_date' => $expectedRepaymentDate,
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => $status,
                    'repayment_method' => $repaymentMethod,
                    'remaining_amount' => $amount, // Set initial remaining amount
                    'note' => 'Dữ liệu mẫu từ seeder',
                ]);

                // Set remaining amount based on status
                if ($status === 'approved' || $status === 'partially_repaid') {
                    $repaidAmount = $status === 'partially_repaid' ? rand(100000, $amount - 100000) : 0;
                    $remainingAmount = $amount - $repaidAmount;
                    
                    $salaryAdvance->update([
                        'repaid_amount' => $repaidAmount,
                        'remaining_amount' => $remainingAmount,
                    ]);

                    // Set approval info if approved
                    if ($status === 'approved') {
                        $salaryAdvance->update([
                            'approved_by' => $users->random()->id,
                            'approved_at' => $advanceDate->copy()->addDays(rand(1, 3)),
                        ]);
                    }
                } else {
                    $salaryAdvance->update([
                        'repaid_amount' => 0,
                        'remaining_amount' => $amount,
                    ]);
                }

                // Set rejection info if rejected
                if ($status === 'rejected') {
                    $salaryAdvance->update([
                        'rejected_by' => $users->random()->id,
                        'rejected_at' => $advanceDate->copy()->addDays(rand(1, 3)),
                        'rejection_reason' => 'Không đủ điều kiện ứng lương theo quy định công ty.',
                    ]);
                }

                // Set installment info if installment method
                if ($repaymentMethod === 'installment') {
                    $salaryAdvance->update([
                        'installment_months' => rand(2, 6),
                    ]);
                }

                // Set monthly deduction if payroll deduction method
                if ($repaymentMethod === 'payroll_deduction') {
                    $monthlyDeduction = $amount / rand(2, 6); // Divide by 2-6 months
                    $salaryAdvance->update([
                        'monthly_deduction' => round($monthlyDeduction, -3), // Round to nearest 1000
                    ]);
                }
            }
        }

        $this->command->info('Salary advances created successfully!');
    }
}