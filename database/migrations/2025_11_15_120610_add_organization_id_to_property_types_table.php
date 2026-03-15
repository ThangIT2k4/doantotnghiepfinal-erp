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
        Schema::table('property_types', function (Blueprint $table) {
            // Add organization_id column (nullable for global property types) only if it doesn't exist
            if (!Schema::hasColumn('property_types', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id');
            }
            
            // Add foreign key constraint only if it doesn't exist
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'property_types' 
                AND COLUMN_NAME = 'organization_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (empty($foreignKeys)) {
                $table->foreign('organization_id')
                    ->references('id')
                    ->on('organizations')
                    ->onDelete('cascade');
            }
            
            // Add index for better query performance only if it doesn't exist
            $indexes = DB::select("
                SELECT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'property_types' 
                AND COLUMN_NAME = 'organization_id' 
                AND INDEX_NAME != 'PRIMARY'
            ");
            
            if (empty($indexes)) {
                $table->index('organization_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_types', function (Blueprint $table) {
            // Drop foreign key and index first
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            
            // Drop column
            $table->dropColumn('organization_id');
        });
    }
};
