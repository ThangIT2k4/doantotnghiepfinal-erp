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
     * Thêm cột organization_id vào bảng services để hỗ trợ multi-tenancy:
     * - organization_id = NULL: Service được tạo bởi System Admin, dùng chung cho tất cả organizations
     * - organization_id != NULL: Service được tạo bởi Manager, chỉ dùng cho organization đó
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Check if organization_id column already exists
            if (!Schema::hasColumn('services', 'organization_id')) {
                // Thêm cột organization_id (nullable để cho phép services global)
                $table->unsignedBigInteger('organization_id')->nullable()->after('id');
                
                // Thêm foreign key constraint
                $table->foreign('organization_id')
                    ->references('id')
                    ->on('organizations')
                    ->onDelete('cascade');
                
                // Thêm index để tăng performance khi filter theo organization
                $table->index('organization_id');
            }
        });
        
        // Drop old unique constraint on key_code if exists (use raw SQL)
        // The constraint name is 'key_code', not 'services_key_code_unique'
        $indexExists = DB::select("SHOW INDEX FROM services WHERE Key_name = 'key_code'");
        if (!empty($indexExists)) {
            DB::statement('ALTER TABLE services DROP INDEX key_code');
        }
        
        Schema::table('services', function (Blueprint $table) {
            // Thêm composite unique: (key_code, organization_id)
            // Điều này cho phép:
            // - Mỗi organization có key_code riêng
            // - System services (organization_id = NULL) có key_code riêng
            $compositeExists = DB::select("SHOW INDEX FROM services WHERE Key_name = 'services_key_code_org_unique'");
            if (empty($compositeExists)) {
                $table->unique(['key_code', 'organization_id'], 'services_key_code_org_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop composite unique constraint if exists
            $compositeExists = DB::select("SHOW INDEX FROM services WHERE Key_name = 'services_key_code_org_unique'");
            if (!empty($compositeExists)) {
                $table->dropUnique('services_key_code_org_unique');
            }
        });
        
        // Restore original unique constraint on key_code
        $indexExists = DB::select("SHOW INDEX FROM services WHERE Key_name = 'key_code'");
        if (empty($indexExists)) {
            DB::statement('ALTER TABLE services ADD UNIQUE INDEX key_code (key_code)');
        }
        
        Schema::table('services', function (Blueprint $table) {
            // Drop foreign key and index if exists
            if (Schema::hasColumn('services', 'organization_id')) {
                $table->dropForeign(['organization_id']);
                $table->dropIndex(['organization_id']);
                
                // Drop column
                $table->dropColumn('organization_id');
            }
        });
    }
};
