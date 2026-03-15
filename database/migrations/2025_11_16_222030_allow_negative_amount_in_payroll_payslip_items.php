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
     */
    public function up(): void
    {
        // Drop the check constraint that prevents negative amounts
        try {
            DB::statement('ALTER TABLE payroll_payslip_items DROP CHECK chk_payslip_item_amount_positive');
            Log::info('Check constraint chk_payslip_item_amount_positive dropped successfully');
        } catch (\Exception $e) {
            Log::info('Check constraint chk_payslip_item_amount_positive already removed or does not exist: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the check constraint (only non-negative amounts)
        try {
            DB::statement('ALTER TABLE payroll_payslip_items ADD CONSTRAINT chk_payslip_item_amount_positive CHECK (amount >= 0)');
            Log::info('Check constraint chk_payslip_item_amount_positive re-added successfully');
        } catch (\Exception $e) {
            Log::warning('Could not re-add check constraint chk_payslip_item_amount_positive: ' . $e->getMessage());
        }
    }
};
