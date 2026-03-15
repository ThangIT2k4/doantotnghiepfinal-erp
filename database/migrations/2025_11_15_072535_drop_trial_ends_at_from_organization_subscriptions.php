<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop column trial_ends_at vì đã được tính toán trong current_period_end
     */
    public function up(): void
    {
        if (Schema::hasTable('organization_subscriptions')) {
            Schema::table('organization_subscriptions', function (Blueprint $table) {
                // Drop column (index sẽ tự động bị xóa khi drop column)
                if (Schema::hasColumn('organization_subscriptions', 'trial_ends_at')) {
                    $table->dropColumn('trial_ends_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('organization_subscriptions')) {
            Schema::table('organization_subscriptions', function (Blueprint $table) {
                $table->timestamp('trial_ends_at')->nullable()->comment('Kết thúc trial')->after('status');
                $table->index('trial_ends_at');
            });
        }
    }
};
