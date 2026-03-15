<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_types', function (Blueprint $table) {
            // Drop the old unique constraint on key_code only
            $oldUniqueExists = DB::select("SHOW INDEX FROM property_types WHERE Key_name = 'property_types_key_code_unique'");
            if (!empty($oldUniqueExists)) {
                $table->dropUnique('property_types_key_code_unique');
            }
            
            // Add composite unique constraint on (key_code, organization_id)
            // This allows same key_code in different organizations
            $compositeExists = DB::select("SHOW INDEX FROM property_types WHERE Key_name = 'property_types_key_code_org_unique'");
            if (empty($compositeExists)) {
                $table->unique(['key_code', 'organization_id'], 'property_types_key_code_org_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_types', function (Blueprint $table) {
            // Drop composite unique constraint
            $compositeExists = DB::select("SHOW INDEX FROM property_types WHERE Key_name = 'property_types_key_code_org_unique'");
            if (!empty($compositeExists)) {
                $table->dropUnique('property_types_key_code_org_unique');
            }
            
            // Restore original unique constraint on key_code only
            $oldUniqueExists = DB::select("SHOW INDEX FROM property_types WHERE Key_name = 'property_types_key_code_unique'");
            if (empty($oldUniqueExists)) {
                $table->unique('key_code', 'property_types_key_code_unique');
            }
        });
    }
};
