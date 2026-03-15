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
        Schema::table('documents', function (Blueprint $table) {
            // Cho phép uploaded_by nullable để hỗ trợ guest payment
            $table->unsignedBigInteger('uploaded_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Revert lại: uploaded_by không được null
            $table->unsignedBigInteger('uploaded_by')->nullable(false)->change();
        });
    }
};
