<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the check constraint that prevents negative amounts
        // This allows booking deposit deductions (negative amounts) in invoice items
        try {
            DB::statement('ALTER TABLE invoice_items DROP CHECK chk_invoice_item_amount');
        } catch (\Exception $e) {
            // Constraint might not exist or already dropped, continue
            \Log::info('Check constraint chk_invoice_item_amount already removed or does not exist: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the check constraint (only non-negative amounts)
        try {
            DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT chk_invoice_item_amount CHECK (amount >= 0)');
        } catch (\Exception $e) {
            \Log::info('Cannot add check constraint: ' . $e->getMessage());
        }
    }
};
