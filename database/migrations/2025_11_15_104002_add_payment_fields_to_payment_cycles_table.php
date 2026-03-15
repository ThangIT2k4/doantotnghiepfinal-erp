<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Thêm các cột payment_due_hours, invoice_timing, invoice_payment_days vào payment_cycles
     * để chuyển settings từ organizations sang payment_cycles
     */
    public function up(): void
    {
        Schema::table('payment_cycles', function (Blueprint $table) {
            // Kiểm tra và thêm cột payment_due_hours nếu chưa có
            if (!Schema::hasColumn('payment_cycles', 'payment_due_hours')) {
                $table->integer('payment_due_hours')
                    ->nullable()
                    ->default(4320)
                    ->after('is_default')
                    ->comment('Thời gian chờ thanh toán cho booking deposit (đơn vị: phút). Mặc định: 4320 phút = 72 giờ = 3 ngày');
            }

            // Kiểm tra và thêm cột invoice_timing nếu chưa có
            if (!Schema::hasColumn('payment_cycles', 'invoice_timing')) {
                $table->enum('invoice_timing', ['start_of_cycle', 'end_of_cycle'])
                    ->nullable()
                    ->default('end_of_cycle')
                    ->after('payment_due_hours')
                    ->comment('Thời điểm tạo hóa đơn: start_of_cycle = đầu chu kỳ (cộng vào hóa đơn tạo hợp đồng), end_of_cycle = cuối chu kỳ (không cộng)');
            }

            // Kiểm tra và thêm cột invoice_payment_days nếu chưa có
            if (!Schema::hasColumn('payment_cycles', 'invoice_payment_days')) {
                $table->integer('invoice_payment_days')
                    ->nullable()
                    ->default(30)
                    ->after('invoice_timing')
                    ->comment('Số ngày từ issue_date đến due_date cho hóa đơn. Mặc định: 30 ngày');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_cycles', function (Blueprint $table) {
            // Xóa các cột nếu rollback
            if (Schema::hasColumn('payment_cycles', 'payment_due_hours')) {
                $table->dropColumn('payment_due_hours');
            }
            
            if (Schema::hasColumn('payment_cycles', 'invoice_timing')) {
                $table->dropColumn('invoice_timing');
            }
            
            if (Schema::hasColumn('payment_cycles', 'invoice_payment_days')) {
                $table->dropColumn('invoice_payment_days');
            }
        });
    }
};
