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
        // Kiểm tra xem bảng đã tồn tại chưa
        if (Schema::hasTable('documents')) {
            // Bảng đã tồn tại, bỏ qua migration này
            return;
        }
        
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship columns
            $table->string('owner_type', 50)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            
            // File information
            $table->string('file_url', 500);
            $table->string('file_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable()->comment('Kích thước file (bytes)');
            
            // Document type and metadata
            $table->enum('document_type', ['image', 'document', 'avatar', 'photo', 'attachment'])
                  ->default('document')
                  ->comment('Loại tài liệu');
            $table->boolean('is_primary')->default(false)
                  ->comment('Có phải file chính không (cho avatar, primary image)');
            $table->integer('sort_order')->default(0)
                  ->comment('Thứ tự sắp xếp');
            $table->text('description')->nullable();
            
            // User tracking
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
            
            // Indexes
            $table->index(['owner_type', 'owner_id'], 'idx_documents_owner');
            $table->index('document_type', 'idx_documents_type');
            $table->index('is_primary', 'idx_documents_primary');
            
            // Foreign keys
            $table->foreign('uploaded_by', 'fk_docs_uploader')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
            
            $table->foreign('deleted_by', 'documents_deleted_by_foreign')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
        
        // Lưu ý: CHECK constraint với REGEXP không được hỗ trợ tốt trong MySQL
        // Validation mime_type sẽ được xử lý ở application level (Model/Request validation)
        // Nếu cần, có thể tạo trigger để validate, nhưng khuyến nghị validate ở app level
        
        // Add table comment
        DB::statement("ALTER TABLE `documents` COMMENT = 'Tài liệu/ảnh'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
