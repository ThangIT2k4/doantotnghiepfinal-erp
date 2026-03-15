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
     * Đổi các foreign key constraints từ CASCADE sang RESTRICT để:
     * 1. Chặn xóa property khi còn units
     * 2. Chặn xóa unit khi còn booking_deposits
     * 
     * Kết hợp với Business Rules Validators để chặn soft delete ở application level.
     */
    public function up(): void
    {
        // 1. Sửa units.property_id → properties.id (CASCADE → RESTRICT)
        // Tên constraint thực tế: fk_units_property
        DB::statement('ALTER TABLE `units` DROP FOREIGN KEY `fk_units_property`');
        
        Schema::table('units', function (Blueprint $table) {
            // Tạo lại với RESTRICT
            $table->foreign('property_id', 'fk_units_property')
                ->references('id')
                ->on('properties')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });

        // 2. Sửa booking_deposits.unit_id → units.id (CASCADE → RESTRICT)
        // Tên constraint thực tế: booking_deposits_unit_id_foreign
        DB::statement('ALTER TABLE `booking_deposits` DROP FOREIGN KEY `booking_deposits_unit_id_foreign`');
        
        Schema::table('booking_deposits', function (Blueprint $table) {
            // Tạo lại với RESTRICT
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Khôi phục lại CASCADE (theo policy ban đầu)
     */
    public function down(): void
    {
        // 1. Khôi phục units.property_id → properties.id (RESTRICT → CASCADE)
        DB::statement('ALTER TABLE `units` DROP FOREIGN KEY `fk_units_property`');
        
        Schema::table('units', function (Blueprint $table) {
            $table->foreign('property_id', 'fk_units_property')
                ->references('id')
                ->on('properties')
                ->onDelete('cascade')
                ->onUpdate('restrict');
        });

        // 2. Khôi phục booking_deposits.unit_id → units.id (RESTRICT → CASCADE)
        DB::statement('ALTER TABLE `booking_deposits` DROP FOREIGN KEY `booking_deposits_unit_id_foreign`');
        
        Schema::table('booking_deposits', function (Blueprint $table) {
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('cascade')
                ->onUpdate('restrict');
        });
    }
};
