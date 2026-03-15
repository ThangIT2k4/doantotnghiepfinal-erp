<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Xóa CHECK constraint cũ không tương thích với MySQL
     */
    public function up(): void
    {
        // Kiểm tra xem bảng có tồn tại không
        if (!Schema::hasTable('email_otps')) {
            return;
        }
        
        // Xóa CHECK constraint cũ nếu tồn tại
        // MySQL không hỗ trợ regexp_like() trong CHECK constraint
        try {
            // Tìm tất cả CHECK constraints trên bảng email_otps
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'email_otps' 
                AND CONSTRAINT_TYPE = 'CHECK'
            ");
            
            foreach ($constraints as $constraint) {
                try {
                    // Xóa từng constraint
                    DB::statement("ALTER TABLE `email_otps` DROP CONSTRAINT `{$constraint->CONSTRAINT_NAME}`");
                } catch (\Exception $e) {
                    // Bỏ qua nếu không xóa được (có thể đã được xóa)
                }
            }
        } catch (\Exception $e) {
            // Nếu không query được hoặc không có constraint, bỏ qua
            // Có thể MySQL version không hỗ trợ CHECK constraints hoặc đã được xóa
        }
        
        // Thử xóa với tên cụ thể (nếu biết chắc tên)
        try {
            DB::statement("ALTER TABLE `email_otps` DROP CONSTRAINT `chk_otp_code_format`");
        } catch (\Exception $e) {
            // Constraint không tồn tại hoặc đã được xóa, bỏ qua
        }
    }

    /**
     * Reverse the migrations.
     * Không khôi phục CHECK constraint vì nó không tương thích
     */
    public function down(): void
    {
        // Không khôi phục CHECK constraint vì nó không tương thích với MySQL
        // Validation sẽ được xử lý ở application level (Model)
    }
};
