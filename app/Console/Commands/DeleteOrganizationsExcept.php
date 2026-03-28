<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Command: DeleteOrganizationsExcept
 * 
 * MỤC ĐÍCH:
 * Xóa tất cả organizations trừ organization có ID được chỉ định. Command này được dùng để dọn dẹp database trong môi trường development/test.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận tham số: id (organization ID cần giữ lại)
 * 2. Nhận options: --force (hard delete), --no-confirm (không hỏi xác nhận)
 * 3. Kiểm tra organization cần giữ có tồn tại không
 * 4. Tìm tất cả organizations khác
 * 5. Hiển thị thông tin organizations sẽ bị xóa (kèm số lượng users và properties)
 * 6. Xác nhận từ người dùng (trừ khi có --no-confirm)
 * 7. Xóa organizations (soft delete hoặc hard delete tùy --force)
 * 8. Hiển thị kết quả
 * 
 * CÁCH CHẠY:
 * php artisan organizations:delete-except {id} [--force] [--no-confirm]
 * 
 * Ví dụ:
 * php artisan organizations:delete-except 3
 * php artisan organizations:delete-except 3 --force
 * 
 * Options:
 * --force: Force delete (hard delete) thay vì soft delete
 * --no-confirm: Xóa mà không hỏi xác nhận
 * 
 * LƯU Ý:
 * - Command này XÓA ORGANIZATIONS (có thể hard delete)
 * - Không thể hoàn tác sau khi chạy
 * - Chỉ nên dùng trong môi trường development/test
 */
class DeleteOrganizationsExcept extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Tham số:
     * - {id}: Organization ID cần giữ lại (bắt buộc)
     * 
     * Options:
     * - --force: Force delete (hard delete)
     * - --no-confirm: Skip confirmation
     * 
     * @var string
     */
    protected $signature = 'organizations:delete-except {id : The organization ID to keep} {--force : Force delete (hard delete)} {--no-confirm : Skip confirmation}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Delete all organizations except the specified organization ID';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy organization ID cần giữ từ tham số
     * 2. Lấy options: force và no-confirm
     * 3. Kiểm tra organization cần giữ có tồn tại không
     * 4. Tìm tất cả organizations khác
     * 5. Hiển thị thông tin organizations sẽ bị xóa (kèm số lượng users và properties)
     * 6. Xác nhận từ người dùng (trừ khi có --no-confirm)
     * 7. Bắt đầu transaction
     * 8. Xóa từng organization (soft delete hoặc hard delete)
     * 9. Commit transaction
     * 10. Hiển thị kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\Organization (bảng organizations)
     * - Relationship: organization->users(), organization->properties()
     * 
     * DỮ LIỆU GHI VÀO:
     * - Xóa organizations (soft delete hoặc hard delete tùy --force)
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        $keepId = (int) $this->argument('id');
        $force = $this->option('force');
        $noConfirm = $this->option('no-confirm');

        // Check if the organization to keep exists
        $keepOrganization = Organization::withTrashed()->find($keepId);
        if (!$keepOrganization) {
            $this->error("Organization with ID {$keepId} does not exist!");
            return 1;
        }

        // Get all organizations except the one to keep
        $organizationsToDelete = Organization::where('id', '!=', $keepId)->get();
        
        if ($organizationsToDelete->isEmpty()) {
            $this->info('No organizations to delete.');
            return 0;
        }

        // Display information
        $this->info("Organization to keep: ID {$keepId} - {$keepOrganization->name}");
        $this->info("Organizations to delete: {$organizationsToDelete->count()}");
        
        // Show organizations with users or properties
        $organizationsWithData = [];
        foreach ($organizationsToDelete as $org) {
            $userCount = $org->users()->count();
            $propertyCount = $org->properties()->count();
            if ($userCount > 0 || $propertyCount > 0) {
                $organizationsWithData[] = [
                    'id' => $org->id,
                    'name' => $org->name,
                    'users' => $userCount,
                    'properties' => $propertyCount,
                ];
            }
        }

        if (!empty($organizationsWithData)) {
            $this->warn("\nOrganizations with users or properties:");
            $this->table(
                ['ID', 'Name', 'Users', 'Properties'],
                $organizationsWithData
            );
        }

        // Confirmation
        if (!$noConfirm) {
            if (!$this->confirm("Are you sure you want to " . ($force ? 'force ' : '') . "delete {$organizationsToDelete->count()} organization(s)?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Delete organizations
        $this->info("\nStarting deletion...");
        $deletedCount = 0;
        $errorCount = 0;

        DB::beginTransaction();
        try {
            foreach ($organizationsToDelete as $org) {
                try {
                    if ($force) {
                        // Force delete (hard delete)
                        $org->forceDelete();
                        $this->line("Force deleted: ID {$org->id} - {$org->name}");
                    } else {
                        // Soft delete
                        $org->delete();
                        $this->line("Soft deleted: ID {$org->id} - {$org->name}");
                    }
                    $deletedCount++;
                } catch (\Exception $e) {
                    $this->error("Error deleting organization ID {$org->id}: {$e->getMessage()}");
                    $errorCount++;
                }
            }

            DB::commit();
            
            $this->info("\nDeletion completed!");
            $this->info("Successfully deleted: {$deletedCount}");
            if ($errorCount > 0) {
                $this->warn("Errors: {$errorCount}");
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Transaction failed: {$e->getMessage()}");
            return 1;
        }
    }
}
