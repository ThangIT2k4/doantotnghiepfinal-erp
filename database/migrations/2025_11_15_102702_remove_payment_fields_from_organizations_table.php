<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Xóa các trường payment_due_hours, invoice_timing, invoice_payment_days 
     * khỏi bảng organizations vì đã chuyển sang bảng payment_cycles
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Drop payment-related columns that are now in payment_cycles table
            // Only drop if they exist
            $columnsToDrop = [];
            
            if (Schema::hasColumn('organizations', 'payment_due_hours')) {
                $columnsToDrop[] = 'payment_due_hours';
            }
            if (Schema::hasColumn('organizations', 'invoice_timing')) {
                $columnsToDrop[] = 'invoice_timing';
            }
            if (Schema::hasColumn('organizations', 'invoice_payment_days')) {
                $columnsToDrop[] = 'invoice_payment_days';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Restore payment-related columns for rollback
            $table->integer('payment_due_hours')->default(4320)->after('status')->comment('Thời gian chờ thanh toán (phút) - đã chuyển sang payment_cycles');
            $table->enum('invoice_timing', ['start_of_cycle', 'end_of_cycle'])->default('end_of_cycle')->after('payment_due_hours')->comment('Thời điểm tạo hóa đơn - đã chuyển sang payment_cycles');
            $table->integer('invoice_payment_days')->default(30)->after('invoice_timing')->comment('Số ngày từ issue_date đến due_date - đã chuyển sang payment_cycles');
        });
    }
};
