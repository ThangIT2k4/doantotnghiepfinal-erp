<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\OrganizationUserCapability;
use App\Models\OrganizationUser;
use App\Models\User;

/**
 * Command: CleanOrganizationUserCapabilities
 * 
 * MỤC ĐÍCH:
 * Dọn dẹp bảng organization_user_capabilities, xóa các bản ghi orphaned (không còn liên kết với organization_user, user, hoặc organization hợp lệ).
 * Command này được dùng để làm sạch dữ liệu sau khi xóa user hoặc organization.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận options: --dry-run (chỉ hiển thị), --force (không hỏi xác nhận)
 * 2. Tìm các capability records orphaned:
 *    - Có organization_user_id không tồn tại trong organization_users
 *    - Có organization_user_id trỏ đến user đã bị soft delete
 *    - Có organization_user_id trỏ đến organization đã bị soft delete
 * 3. Hiển thị danh sách các bản ghi sẽ bị xóa
 * 4. Nếu không có --dry-run: Xác nhận và xóa các bản ghi orphaned
 * 5. Hiển thị kết quả
 * 
 * CÁCH CHẠY:
 * php artisan capabilities:clean [--dry-run] [--force]
 * 
 * Options:
 * --dry-run: Chỉ hiển thị thông tin, không xóa dữ liệu
 * --force: Xóa dữ liệu mà không hỏi xác nhận
 */
class CleanOrganizationUserCapabilities extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --dry-run: Chỉ hiển thị thông tin, không xóa dữ liệu
     * - --force: Xóa dữ liệu mà không hỏi xác nhận
     * 
     * @var string
     */
    protected $signature = 'capabilities:clean 
                            {--dry-run : Chỉ hiển thị thông tin, không xóa dữ liệu}
                            {--force : Xóa dữ liệu mà không hỏi xác nhận}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Dọn dẹp bảng organization_user_capabilities, chỉ giữ lại các user hiện tại';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy options: dry-run và force
     * 2. Đếm tổng số records hiện tại
     * 3. Tìm các records orphaned theo 3 loại:
     *    - organization_user_id không tồn tại
     *    - user đã bị soft delete
     *    - organization đã bị soft delete
     * 4. Tổng hợp danh sách IDs cần xóa
     * 5. Nếu dry-run: Hiển thị và dừng
     * 6. Nếu không dry-run: Xác nhận và xóa
     * 7. Hiển thị kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_user_capabilities: Đếm và tìm orphaned records
     * - Bảng organization_users: Kiểm tra tồn tại
     * - Bảng users: Kiểm tra soft delete
     * - Bảng organizations: Kiểm tra soft delete
     * 
     * DỮ LIỆU GHI VÀO:
     * - Xóa các bản ghi orphaned từ bảng organization_user_capabilities
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Đang kiểm tra dữ liệu trong bảng organization_user_capabilities...');
        $this->newLine();

        // Đếm tổng số records hiện tại
        $totalRecords = DB::table('organization_user_capabilities')->count();
        $this->info("📊 Tổng số records hiện tại: {$totalRecords}");
        $this->newLine();

        // 1. Tìm các capability records có organization_user_id không tồn tại trong organization_users
        $orphanedByOrgUser = DB::table('organization_user_capabilities')
            ->leftJoin('organization_users', 'organization_user_capabilities.organization_user_id', '=', 'organization_users.id')
            ->whereNull('organization_users.id')
            ->select('organization_user_capabilities.id', 'organization_user_capabilities.organization_user_id')
            ->get();

        $this->info("❌ Tìm thấy {$orphanedByOrgUser->count()} records có organization_user_id không tồn tại:");
        if ($orphanedByOrgUser->count() > 0) {
            $this->table(
                ['ID', 'Organization User ID'],
                $orphanedByOrgUser->map(fn($item) => [$item->id, $item->organization_user_id])
            );
        }
        $this->newLine();

        // 2. Tìm các capability records có organization_user_id trỏ đến user đã bị soft delete
        $orphanedByDeletedUser = DB::table('organization_user_capabilities')
            ->join('organization_users', 'organization_user_capabilities.organization_user_id', '=', 'organization_users.id')
            ->join('users', 'organization_users.user_id', '=', 'users.id')
            ->whereNotNull('users.deleted_at')
            ->select('organization_user_capabilities.id', 'organization_user_capabilities.organization_user_id', 'users.id as user_id', 'users.email')
            ->get();

        $this->info("🗑️  Tìm thấy {$orphanedByDeletedUser->count()} records có user đã bị soft delete:");
        if ($orphanedByDeletedUser->count() > 0) {
            $this->table(
                ['Capability ID', 'Organization User ID', 'User ID', 'Email'],
                $orphanedByDeletedUser->map(fn($item) => [
                    $item->id,
                    $item->organization_user_id,
                    $item->user_id,
                    $item->email
                ])
            );
        }
        $this->newLine();

        // 3. Tìm các capability records có organization_user_id trỏ đến organization đã bị soft delete
        $orphanedByDeletedOrg = DB::table('organization_user_capabilities')
            ->join('organization_users', 'organization_user_capabilities.organization_user_id', '=', 'organization_users.id')
            ->join('organizations', 'organization_users.organization_id', '=', 'organizations.id')
            ->whereNotNull('organizations.deleted_at')
            ->select('organization_user_capabilities.id', 'organization_user_capabilities.organization_user_id', 'organizations.id as org_id', 'organizations.name')
            ->get();

        $this->info("🏢 Tìm thấy {$orphanedByDeletedOrg->count()} records có organization đã bị soft delete:");
        if ($orphanedByDeletedOrg->count() > 0) {
            $this->table(
                ['Capability ID', 'Organization User ID', 'Organization ID', 'Organization Name'],
                $orphanedByDeletedOrg->map(fn($item) => [
                    $item->id,
                    $item->organization_user_id,
                    $item->org_id,
                    $item->name
                ])
            );
        }
        $this->newLine();

        // Tổng hợp
        $totalToDelete = $orphanedByOrgUser->count() + $orphanedByDeletedUser->count() + $orphanedByDeletedOrg->count();
        $idsToDelete = $orphanedByOrgUser->pluck('id')
            ->merge($orphanedByDeletedUser->pluck('id'))
            ->merge($orphanedByDeletedOrg->pluck('id'))
            ->unique()
            ->toArray();

        $this->info("📋 Tổng cộng: {$totalToDelete} records sẽ bị xóa (unique: " . count($idsToDelete) . " records)");
        $this->newLine();

        if ($totalToDelete === 0) {
            $this->info('✅ Không có dữ liệu cần dọn dẹp. Bảng organization_user_capabilities đã sạch!');
            return Command::SUCCESS;
        }

        // Dry run mode
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE: Không có dữ liệu nào bị xóa.');
            $this->info('   Để thực thi xóa, chạy lệnh không có --dry-run flag.');
            return Command::SUCCESS;
        }

        // Confirm deletion
        if (!$force) {
            if (!$this->confirm("⚠️  Bạn có chắc chắn muốn xóa {$totalToDelete} records?", false)) {
                $this->info('❌ Đã hủy thao tác.');
                return Command::FAILURE;
            }
        }

        // Perform deletion
        $this->info('🗑️  Đang xóa dữ liệu...');
        
        $deleted = DB::table('organization_user_capabilities')
            ->whereIn('id', $idsToDelete)
            ->delete();

        $this->newLine();
        $this->info("✅ Đã xóa thành công {$deleted} records!");
        
        // Show remaining count
        $remaining = DB::table('organization_user_capabilities')->count();
        $this->info("📊 Số records còn lại: {$remaining}");

        return Command::SUCCESS;
    }
}
