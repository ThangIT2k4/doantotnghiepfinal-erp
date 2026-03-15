<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Tracking để ngăn chặn lợi dụng trial khi chuyển gói liên tục
            // Only add columns if they don't exist
            if (!Schema::hasColumn('organizations', 'first_trial_at')) {
                $table->timestamp('first_trial_at')->nullable()->after('status')
                    ->comment('Thời điểm đầu tiên sử dụng trial period');
            }
            
            if (!Schema::hasColumn('organizations', 'has_ever_paid')) {
                $table->boolean('has_ever_paid')->default(false)->after('first_trial_at')
                    ->comment('Đã từng thanh toán thành công ít nhất 1 lần hay chưa');
            }
            
            if (!Schema::hasColumn('organizations', 'paid_subscriptions_count')) {
                $table->integer('paid_subscriptions_count')->default(0)->after('has_ever_paid')
                    ->comment('Số lần đã thanh toán subscription thành công');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['first_trial_at', 'has_ever_paid', 'paid_subscriptions_count']);
        });
    }
};
