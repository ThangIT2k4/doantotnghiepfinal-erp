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
        // Drop bảng document_attachments nếu tồn tại
        if (Schema::hasTable('document_attachments')) {
            Schema::dropIfExists('document_attachments');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần recreate bảng vì đây là bảng không được sử dụng
        // Nếu cần rollback, có thể tạo lại bảng từ documentation
    }
};
