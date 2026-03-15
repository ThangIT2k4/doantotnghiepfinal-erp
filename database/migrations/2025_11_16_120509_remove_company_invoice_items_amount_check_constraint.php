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
        // This allows deposit refund deductions (negative amounts) in company invoice items
        try {
            DB::statement('ALTER TABLE company_invoice_items DROP CHECK chk_company_invoice_item_amount');
        } catch (\Exception $e) {
            // Constraint might not exist or already dropped, continue
            Log::info('Check constraint chk_company_invoice_item_amount already removed or does not exist: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the check constraint (only non-negative amounts)
        // Note: This will fail if there are existing negative amounts in the table
        try {
            DB::statement('ALTER TABLE company_invoice_items ADD CONSTRAINT chk_company_invoice_item_amount CHECK (amount = quantity * unit_price AND amount >= 0 AND quantity > 0)');
        } catch (\Exception $e) {
            Log::info('Cannot add check constraint: ' . $e->getMessage());
        }
    }
};
