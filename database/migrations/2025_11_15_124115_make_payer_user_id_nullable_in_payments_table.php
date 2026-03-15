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
        Schema::table('payments', function (Blueprint $table) {
            // Cho phép payer_user_id và lead_id nullable
            // Validation ở application level sẽ đảm bảo ít nhất một trong hai phải có giá trị
            $table->unsignedBigInteger('payer_user_id')->nullable()->change();
            $table->unsignedBigInteger('lead_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Revert lại: payer_user_id không được null
            $table->unsignedBigInteger('payer_user_id')->nullable(false)->change();
            // lead_id có thể giữ nullable hoặc không tùy vào yêu cầu ban đầu
        });
    }
};
