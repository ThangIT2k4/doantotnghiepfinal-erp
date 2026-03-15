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
     * Thay đổi UNIQUE constraints của bảng leads từ global sang scoped theo organization.
     * Điều này cho phép cùng một email/phone có thể là lead của nhiều organizations khác nhau.
     */
    public function up(): void
    {
        // Kiểm tra xem bảng leads có tồn tại không
        if (!Schema::hasTable('leads')) {
            return;
        }

        // Xóa các UNIQUE constraints cũ (global) nếu tồn tại
        // Kiểm tra xem index có tồn tại trước khi xóa
        $indexes = DB::select("
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'leads' 
            AND INDEX_NAME IN ('unique_email', 'unique_phone')
        ");
        
        $indexNames = array_column($indexes, 'INDEX_NAME');
        
        Schema::table('leads', function (Blueprint $table) use ($indexNames) {
            // Xóa các UNIQUE constraints cũ (global) chỉ nếu tồn tại
            if (in_array('unique_email', $indexNames)) {
                $table->dropUnique('unique_email');
            }
            
            if (in_array('unique_phone', $indexNames)) {
                $table->dropUnique('unique_phone');
            }
        });

        // Thêm UNIQUE constraints mới (scoped theo organization) nếu chưa tồn tại
        // Lưu ý: MySQL cho phép nhiều NULL trong UNIQUE constraint
        // Nên constraint này sẽ hoạt động đúng với NULL values
        $existingIndexes = DB::select("
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'leads' 
            AND INDEX_NAME IN ('unique_email_per_org', 'unique_phone_per_org')
        ");
        
        $existingIndexNames = array_column($existingIndexes, 'INDEX_NAME');
        
        Schema::table('leads', function (Blueprint $table) use ($existingIndexNames) {
            // Email unique trong mỗi organization (cho phép NULL)
            // Nếu email là NULL, có thể có nhiều records với cùng organization_id và email=NULL
            if (!in_array('unique_email_per_org', $existingIndexNames)) {
                $table->unique(['organization_id', 'email'], 'unique_email_per_org');
            }
            
            // Phone unique trong mỗi organization (cho phép NULL)
            // Nếu phone là NULL, có thể có nhiều records với cùng organization_id và phone=NULL
            if (!in_array('unique_phone_per_org', $existingIndexNames)) {
                $table->unique(['organization_id', 'phone'], 'unique_phone_per_org');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            // Xóa UNIQUE constraints scoped
            try {
                $table->dropUnique('unique_email_per_org');
            } catch (\Exception $e) {
                // Constraint có thể không tồn tại
            }
            
            try {
                $table->dropUnique('unique_phone_per_org');
            } catch (\Exception $e) {
                // Constraint có thể không tồn tại
            }
        });

        // Khôi phục lại UNIQUE constraints global (nếu cần rollback)
        Schema::table('leads', function (Blueprint $table) {
            try {
                $table->unique('email', 'unique_email');
            } catch (\Exception $e) {
                // Có thể có duplicate data, không thể tạo unique constraint
            }
            
            try {
                $table->unique('phone', 'unique_phone');
            } catch (\Exception $e) {
                // Có thể có duplicate data, không thể tạo unique constraint
            }
        });
    }
};

