<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Mục đích:
     * 1. Thêm UNIQUE constraint cho leases (contract_no, organization_id)
     * 2. Thêm UNIQUE constraint cho master_leases (contract_no, organization_id)
     * 3. Thêm UNIQUE constraint cho deposit_refunds (lease_id, organization_id, refund_reference)
     * 4. Thêm UNIQUE constraint cho booking_deposits (reference_number)
     * 
     * Lưu ý: Xử lý dữ liệu trùng lặp trước khi thêm constraint
     */
    public function up(): void
    {
        // ============================================
        // 1. Xử lý bảng leases
        // ============================================
        if (Schema::hasTable('leases')) {
            // Kiểm tra và xử lý dữ liệu trùng lặp contract_no trong cùng organization_id
            $duplicateLeases = DB::table('leases')
                ->select('contract_no', 'organization_id', DB::raw('COUNT(*) as count'))
                ->whereNotNull('contract_no')
                ->whereNotNull('organization_id')
                ->groupBy('contract_no', 'organization_id')
                ->having('count', '>', 1)
                ->get();

            if ($duplicateLeases->isNotEmpty()) {
                Log::warning('Found duplicate contract_no in leases table', [
                    'duplicates' => $duplicateLeases->toArray(),
                ]);

                // Xử lý từng nhóm trùng lặp: giữ lại bản ghi đầu tiên, sửa các bản ghi còn lại
                foreach ($duplicateLeases as $duplicate) {
                    $leases = DB::table('leases')
                        ->where('contract_no', $duplicate->contract_no)
                        ->where('organization_id', $duplicate->organization_id)
                        ->orderBy('id')
                        ->get();

                    // Giữ lại bản ghi đầu tiên, sửa các bản ghi còn lại
                    $keepId = $leases->first()->id;
                    $updateIds = $leases->skip(1)->pluck('id');

                    foreach ($updateIds as $id) {
                        // Tạo contract_no mới bằng cách thêm suffix
                        $newContractNo = $duplicate->contract_no . '-DUP-' . $id;
                        DB::table('leases')
                            ->where('id', $id)
                            ->update(['contract_no' => $newContractNo]);

                        Log::info('Updated duplicate lease contract_no', [
                            'lease_id' => $id,
                            'old_contract_no' => $duplicate->contract_no,
                            'new_contract_no' => $newContractNo,
                        ]);
                    }
                }
            }

            // Thêm UNIQUE constraint
            try {
                Schema::table('leases', function (Blueprint $table) {
                    $table->unique(['contract_no', 'organization_id'], 'leases_contract_no_organization_id_unique');
                });
                Log::info('Added UNIQUE constraint to leases (contract_no, organization_id)');
            } catch (\Exception $e) {
                Log::error('Error adding UNIQUE constraint to leases: ' . $e->getMessage());
            }
        }

        // ============================================
        // 2. Xử lý bảng master_leases
        // ============================================
        if (Schema::hasTable('master_leases')) {
            // Kiểm tra và xử lý dữ liệu trùng lặp contract_no trong cùng organization_id
            $duplicateMasterLeases = DB::table('master_leases')
                ->select('contract_no', 'organization_id', DB::raw('COUNT(*) as count'))
                ->whereNotNull('contract_no')
                ->whereNotNull('organization_id')
                ->groupBy('contract_no', 'organization_id')
                ->having('count', '>', 1)
                ->get();

            if ($duplicateMasterLeases->isNotEmpty()) {
                Log::warning('Found duplicate contract_no in master_leases table', [
                    'duplicates' => $duplicateMasterLeases->toArray(),
                ]);

                // Xử lý từng nhóm trùng lặp
                foreach ($duplicateMasterLeases as $duplicate) {
                    $masterLeases = DB::table('master_leases')
                        ->where('contract_no', $duplicate->contract_no)
                        ->where('organization_id', $duplicate->organization_id)
                        ->orderBy('id')
                        ->get();

                    $keepId = $masterLeases->first()->id;
                    $updateIds = $masterLeases->skip(1)->pluck('id');

                    foreach ($updateIds as $id) {
                        $newContractNo = $duplicate->contract_no . '-DUP-' . $id;
                        DB::table('master_leases')
                            ->where('id', $id)
                            ->update(['contract_no' => $newContractNo]);

                        Log::info('Updated duplicate master_lease contract_no', [
                            'master_lease_id' => $id,
                            'old_contract_no' => $duplicate->contract_no,
                            'new_contract_no' => $newContractNo,
                        ]);
                    }
                }
            }

            // Thêm UNIQUE constraint
            try {
                Schema::table('master_leases', function (Blueprint $table) {
                    $table->unique(['contract_no', 'organization_id'], 'master_leases_contract_no_organization_id_unique');
                });
                Log::info('Added UNIQUE constraint to master_leases (contract_no, organization_id)');
            } catch (\Exception $e) {
                Log::error('Error adding UNIQUE constraint to master_leases: ' . $e->getMessage());
            }
        }

        // ============================================
        // 3. Xử lý bảng deposit_refunds
        // ============================================
        if (Schema::hasTable('deposit_refunds')) {
            // Kiểm tra và xử lý dữ liệu trùng lặp (lease_id, organization_id, refund_reference)
            $duplicateDepositRefunds = DB::table('deposit_refunds')
                ->select('lease_id', 'organization_id', 'refund_reference', DB::raw('COUNT(*) as count'))
                ->whereNotNull('lease_id')
                ->whereNotNull('organization_id')
                ->whereNotNull('refund_reference')
                ->groupBy('lease_id', 'organization_id', 'refund_reference')
                ->having('count', '>', 1)
                ->get();

            if ($duplicateDepositRefunds->isNotEmpty()) {
                Log::warning('Found duplicate refund_reference in deposit_refunds table', [
                    'duplicates' => $duplicateDepositRefunds->toArray(),
                ]);

                // Xử lý từng nhóm trùng lặp
                foreach ($duplicateDepositRefunds as $duplicate) {
                    $refunds = DB::table('deposit_refunds')
                        ->where('lease_id', $duplicate->lease_id)
                        ->where('organization_id', $duplicate->organization_id)
                        ->where('refund_reference', $duplicate->refund_reference)
                        ->orderBy('id')
                        ->get();

                    $keepId = $refunds->first()->id;
                    $updateIds = $refunds->skip(1)->pluck('id');

                    foreach ($updateIds as $id) {
                        $newRefundReference = $duplicate->refund_reference . '-DUP-' . $id;
                        DB::table('deposit_refunds')
                            ->where('id', $id)
                            ->update(['refund_reference' => $newRefundReference]);

                        Log::info('Updated duplicate deposit_refund refund_reference', [
                            'deposit_refund_id' => $id,
                            'old_refund_reference' => $duplicate->refund_reference,
                            'new_refund_reference' => $newRefundReference,
                        ]);
                    }
                }
            }

            // Thêm UNIQUE constraint
            try {
                Schema::table('deposit_refunds', function (Blueprint $table) {
                    $table->unique(['lease_id', 'organization_id', 'refund_reference'], 'deposit_refunds_lease_org_refund_unique');
                });
                Log::info('Added UNIQUE constraint to deposit_refunds (lease_id, organization_id, refund_reference)');
            } catch (\Exception $e) {
                Log::error('Error adding UNIQUE constraint to deposit_refunds: ' . $e->getMessage());
            }
        }

        // ============================================
        // 4. Xử lý bảng booking_deposits
        // ============================================
        if (Schema::hasTable('booking_deposits')) {
            // Kiểm tra và xử lý dữ liệu trùng lặp reference_number
            $duplicateBookingDeposits = DB::table('booking_deposits')
                ->select('reference_number', DB::raw('COUNT(*) as count'))
                ->whereNotNull('reference_number')
                ->groupBy('reference_number')
                ->having('count', '>', 1)
                ->get();

            if ($duplicateBookingDeposits->isNotEmpty()) {
                Log::warning('Found duplicate reference_number in booking_deposits table', [
                    'duplicates' => $duplicateBookingDeposits->toArray(),
                ]);

                // Xử lý từng nhóm trùng lặp
                foreach ($duplicateBookingDeposits as $duplicate) {
                    $bookingDeposits = DB::table('booking_deposits')
                        ->where('reference_number', $duplicate->reference_number)
                        ->orderBy('id')
                        ->get();

                    $keepId = $bookingDeposits->first()->id;
                    $updateIds = $bookingDeposits->skip(1)->pluck('id');

                    foreach ($updateIds as $id) {
                        $newReferenceNumber = $duplicate->reference_number . '-DUP-' . $id;
                        DB::table('booking_deposits')
                            ->where('id', $id)
                            ->update(['reference_number' => $newReferenceNumber]);

                        Log::info('Updated duplicate booking_deposit reference_number', [
                            'booking_deposit_id' => $id,
                            'old_reference_number' => $duplicate->reference_number,
                            'new_reference_number' => $newReferenceNumber,
                        ]);
                    }
                }
            }

            // Thêm UNIQUE constraint
            try {
                Schema::table('booking_deposits', function (Blueprint $table) {
                    $table->unique('reference_number', 'booking_deposits_reference_number_unique');
                });
                Log::info('Added UNIQUE constraint to booking_deposits (reference_number)');
            } catch (\Exception $e) {
                Log::error('Error adding UNIQUE constraint to booking_deposits: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa các UNIQUE constraints
        if (Schema::hasTable('leases')) {
            try {
                Schema::table('leases', function (Blueprint $table) {
                    $table->dropUnique('leases_contract_no_organization_id_unique');
                });
            } catch (\Exception $e) {
                Log::warning('Error dropping UNIQUE constraint from leases: ' . $e->getMessage());
            }
        }

        if (Schema::hasTable('master_leases')) {
            try {
                Schema::table('master_leases', function (Blueprint $table) {
                    $table->dropUnique('master_leases_contract_no_organization_id_unique');
                });
            } catch (\Exception $e) {
                Log::warning('Error dropping UNIQUE constraint from master_leases: ' . $e->getMessage());
            }
        }

        if (Schema::hasTable('deposit_refunds')) {
            try {
                Schema::table('deposit_refunds', function (Blueprint $table) {
                    $table->dropUnique('deposit_refunds_lease_org_refund_unique');
                });
            } catch (\Exception $e) {
                Log::warning('Error dropping UNIQUE constraint from deposit_refunds: ' . $e->getMessage());
            }
        }

        if (Schema::hasTable('booking_deposits')) {
            try {
                Schema::table('booking_deposits', function (Blueprint $table) {
                    $table->dropUnique('booking_deposits_reference_number_unique');
                });
            } catch (\Exception $e) {
                Log::warning('Error dropping UNIQUE constraint from booking_deposits: ' . $e->getMessage());
            }
        }
    }
};
