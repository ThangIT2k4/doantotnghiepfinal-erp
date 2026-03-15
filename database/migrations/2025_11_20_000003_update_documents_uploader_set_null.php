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
     * Thay đổi foreign key constraint của bảng documents
     * từ ON DELETE RESTRICT thành ON DELETE SET NULL
     * 
     * Mục đích:
     * - Khi user bị xóa, set uploaded_by thành NULL thay vì chặn việc xóa
     * - Documents vẫn được giữ lại nhưng không còn thông tin về người upload
     */
    public function up(): void
    {
        // Kiểm tra xem foreign key constraint có tồn tại không
        $fkExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'documents'
            AND CONSTRAINT_NAME = 'fk_docs_uploader'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        
        if (!empty($fkExists)) {
            // Drop foreign key constraint cũ
            try {
                DB::statement("ALTER TABLE `documents` DROP FOREIGN KEY `fk_docs_uploader`");
            } catch (\Exception $e) {
                Log::warning("Could not drop foreign key fk_docs_uploader: " . $e->getMessage());
            }
        }
        
        // Tạo lại foreign key constraint với ON DELETE SET NULL
        try {
            DB::statement("
                ALTER TABLE `documents`
                ADD CONSTRAINT `fk_docs_uploader`
                FOREIGN KEY (`uploaded_by`)
                REFERENCES `users` (`id`)
                ON DELETE SET NULL
            ");
        } catch (\Exception $e) {
            Log::warning("Could not add foreign key fk_docs_uploader with SET NULL: " . $e->getMessage());
            // Nếu không tạo được, thử tạo lại với RESTRICT (rollback)
            try {
                DB::statement("
                    ALTER TABLE `documents`
                    ADD CONSTRAINT `fk_docs_uploader`
                    FOREIGN KEY (`uploaded_by`)
                    REFERENCES `users` (`id`)
                    ON DELETE RESTRICT
                ");
            } catch (\Exception $e2) {
                Log::error("Could not restore foreign key fk_docs_uploader: " . $e2->getMessage());
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
            AND TABLE_NAME = 'documents'
            AND CONSTRAINT_NAME = 'fk_docs_uploader'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        
        if (!empty($fkExists)) {
            // Drop foreign key constraint hiện tại
            try {
                DB::statement("ALTER TABLE `documents` DROP FOREIGN KEY `fk_docs_uploader`");
            } catch (\Exception $e) {
                Log::warning("Could not drop foreign key fk_docs_uploader: " . $e->getMessage());
            }
        }
        
        // Tạo lại foreign key constraint với ON DELETE RESTRICT (rollback)
        try {
            DB::statement("
                ALTER TABLE `documents`
                ADD CONSTRAINT `fk_docs_uploader`
                FOREIGN KEY (`uploaded_by`)
                REFERENCES `users` (`id`)
                ON DELETE RESTRICT
            ");
        } catch (\Exception $e) {
            Log::warning("Could not restore foreign key fk_docs_uploader with RESTRICT: " . $e->getMessage());
        }
    }
};

