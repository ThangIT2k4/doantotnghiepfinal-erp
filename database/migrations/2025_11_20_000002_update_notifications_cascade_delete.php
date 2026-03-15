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
     * 
     * Thay đổi foreign key constraint của bảng notifications
     * từ ON DELETE RESTRICT thành ON DELETE CASCADE
     * 
     * Mục đích:
     * - Khi user bị xóa, tự động xóa tất cả notifications của user đó
     * - Bảng notifications không phải bảng trọng yếu, có thể xóa khi user bị xóa
     */
    public function up(): void
    {
        // Kiểm tra xem foreign key constraint có tồn tại không
        $fkExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'notifications'
            AND CONSTRAINT_NAME = 'fk_ntf_user'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        
        if (!empty($fkExists)) {
            // Drop foreign key constraint cũ
            try {
                DB::statement("ALTER TABLE `notifications` DROP FOREIGN KEY `fk_ntf_user`");
            } catch (\Exception $e) {
                Log::warning("Could not drop foreign key fk_ntf_user: " . $e->getMessage());
            }
        }
        
        // Tạo lại foreign key constraint với ON DELETE CASCADE
        try {
            DB::statement("
                ALTER TABLE `notifications`
                ADD CONSTRAINT `fk_ntf_user`
                FOREIGN KEY (`to_user_id`)
                REFERENCES `users` (`id`)
                ON DELETE CASCADE
            ");
        } catch (\Exception $e) {
                Log::warning("Could not add foreign key fk_ntf_user with CASCADE: " . $e->getMessage());
            // Nếu không tạo được, thử tạo lại với RESTRICT (rollback)
            try {
                DB::statement("
                    ALTER TABLE `notifications`
                    ADD CONSTRAINT `fk_ntf_user`
                    FOREIGN KEY (`to_user_id`)
                    REFERENCES `users` (`id`)
                    ON DELETE RESTRICT
                ");
            } catch (\Exception $e2) {
                Log::error("Could not restore foreign key fk_ntf_user: " . $e2->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Khôi phục lại foreign key constraint với ON DELETE RESTRICT
     */
    public function down(): void
    {
        // Kiểm tra xem foreign key constraint có tồn tại không
        $fkExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'notifications'
            AND CONSTRAINT_NAME = 'fk_ntf_user'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        
        if (!empty($fkExists)) {
            // Drop foreign key constraint hiện tại
            try {
                DB::statement("ALTER TABLE `notifications` DROP FOREIGN KEY `fk_ntf_user`");
            } catch (\Exception $e) {
                Log::warning("Could not drop foreign key fk_ntf_user: " . $e->getMessage());
            }
        }
        
        // Tạo lại foreign key constraint với ON DELETE RESTRICT (rollback)
        try {
            DB::statement("
                ALTER TABLE `notifications`
                ADD CONSTRAINT `fk_ntf_user`
                FOREIGN KEY (`to_user_id`)
                REFERENCES `users` (`id`)
                ON DELETE RESTRICT
            ");
        } catch (\Exception $e) {
                Log::warning("Could not restore foreign key fk_ntf_user with RESTRICT: " . $e->getMessage());
        }
    }
};

