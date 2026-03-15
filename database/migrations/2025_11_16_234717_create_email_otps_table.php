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
        // Kiểm tra xem bảng đã tồn tại chưa
        if (Schema::hasTable('email_otps')) {
            // Bảng đã tồn tại, bỏ qua migration này
            return;
        }
        
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            
            // User relationship
            $table->unsignedBigInteger('user_id');
            
            // Email and OTP
            $table->string('email');
            $table->string('otp_code', 6);
            $table->string('type')->default('email_verification');
            
            // Expiry and verification
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_used')->default(false);
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Indexes
            $table->index(['user_id', 'type'], 'email_otps_user_id_type_index');
            $table->index(['email', 'otp_code'], 'email_otps_email_otp_code_index');
            $table->index('expires_at', 'email_otps_expires_at_index');
            
            // Foreign key
            $table->foreign('user_id', 'email_otps_user_id_foreign')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
        
        // Lưu ý: CHECK constraint với REGEXP không được hỗ trợ tốt trong MySQL
        // Validation otp_code (6 digits) sẽ được xử lý ở application level (Model)
        // Pattern: ^[0-9]{6}$ - 6 chữ số từ 0-9
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
