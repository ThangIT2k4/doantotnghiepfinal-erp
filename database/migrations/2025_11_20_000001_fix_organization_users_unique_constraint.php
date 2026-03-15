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
     * Thay đổi unique constraint từ (organization_id, user_id, role_id) 
     * thành (organization_id, user_id)
     * 
     * Mục đích:
     * - Không cho phép một user có 2 role khác nhau trong cùng một tổ chức
     * - Cho phép một user thuộc nhiều tổ chức (với role khác nhau hoặc giống nhau)
     */
    public function up(): void
    {
        // Kiểm tra xem unique constraint có tồn tại không
        $oldUniqueExists = DB::select("SHOW INDEX FROM organization_users WHERE Key_name = 'uq_org_user_role'");
        
        if (!empty($oldUniqueExists)) {
            // Tìm foreign key constraints có thể đang sử dụng index này
            // Kiểm tra foreign keys trong chính bảng organization_users
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'organization_users'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");
            
            // Lưu danh sách foreign keys để tạo lại sau (nếu cần)
            $fkNames = array_column($foreignKeys, 'CONSTRAINT_NAME');
            
            // Drop foreign keys tạm thời (nếu có)
            foreach ($fkNames as $fkName) {
                try {
                    DB::statement("ALTER TABLE `organization_users` DROP FOREIGN KEY `{$fkName}`");
                } catch (\Exception $e) {
                    // Ignore nếu không drop được
                }
            }
            
            // Bây giờ có thể drop unique index
            try {
                DB::statement("ALTER TABLE `organization_users` DROP INDEX `uq_org_user_role`");
            } catch (\Exception $e) {
                // Nếu vẫn lỗi, thử với disable foreign key checks
                DB::statement("SET FOREIGN_KEY_CHECKS = 0");
                DB::statement("ALTER TABLE `organization_users` DROP INDEX `uq_org_user_role`");
                DB::statement("SET FOREIGN_KEY_CHECKS = 1");
            }
            
            // Tạo lại foreign keys (nếu đã drop)
            // Các foreign keys trong organization_users thường là:
            // - fk_ou_org: organization_id -> organizations.id
            // - fk_ou_user: user_id -> users.id  
            // - fk_ou_role: role_id -> roles.id
            $fkDefinitions = [
                'fk_ou_org' => "FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE",
                'fk_ou_user' => "FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE",
                'fk_ou_role' => "FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT",
            ];
            
            foreach ($fkDefinitions as $fkName => $fkDef) {
                if (in_array($fkName, $fkNames)) {
                    try {
                        // Kiểm tra xem foreign key đã tồn tại chưa
                        $exists = DB::select("
                            SELECT CONSTRAINT_NAME
                            FROM information_schema.TABLE_CONSTRAINTS
                            WHERE TABLE_SCHEMA = DATABASE()
                            AND TABLE_NAME = 'organization_users'
                            AND CONSTRAINT_NAME = ?
                        ", [$fkName]);
                        
                        if (empty($exists)) {
                            DB::statement("ALTER TABLE `organization_users` ADD CONSTRAINT `{$fkName}` {$fkDef}");
                        }
                    } catch (\Exception $e) {
                        // Log nhưng không fail migration
                        \Log::warning("Could not recreate foreign key {$fkName}: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Add new unique constraint (organization_id, user_id)
        $newUniqueExists = DB::select("SHOW INDEX FROM organization_users WHERE Key_name = 'uq_org_user'");
        if (empty($newUniqueExists)) {
            try {
                DB::statement("ALTER TABLE `organization_users` ADD UNIQUE KEY `uq_org_user` (`organization_id`, `user_id`)");
            } catch (\Exception $e) {
                // Nếu lỗi duplicate, có thể constraint đã tồn tại
                \Log::warning("Could not add unique constraint uq_org_user: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_users', function (Blueprint $table) {
            // Drop new unique constraint
            $newUniqueExists = DB::select("SHOW INDEX FROM organization_users WHERE Key_name = 'uq_org_user'");
            if (!empty($newUniqueExists)) {
                $table->dropUnique('uq_org_user');
            }
            
            // Restore old unique constraint (organization_id, user_id, role_id)
            $oldUniqueExists = DB::select("SHOW INDEX FROM organization_users WHERE Key_name = 'uq_org_user_role'");
            if (empty($oldUniqueExists)) {
                $table->unique(['organization_id', 'user_id', 'role_id'], 'uq_org_user_role');
            }
        });
    }
};

