<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Command: MigrateOrganizationEmailSettings
 * 
 * MỤC ĐÍCH:
 * Migrate email settings từ bảng organizations sang bảng organization_email_settings và xóa các cột email khỏi bảng organizations.
 * Command này được dùng để tách email settings ra bảng riêng, chuẩn hóa cấu trúc database.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Kiểm tra xác nhận từ người dùng (trừ khi có flag --force)
 * 2. Bước 1: Migrate dữ liệu email settings:
 *    - Đọc email settings từ bảng organizations
 *    - Tạo bản ghi mới trong bảng organization_email_settings
 * 3. Bước 2: Xóa các cột email khỏi bảng organizations:
 *    - Xóa các cột: mail_username, mail_password, mail_from_address, mail_host, mail_port, mail_encryption
 * 4. Hiển thị kết quả
 * 
 * CÁCH CHẠY:
 * php artisan migrate:organization-email-settings [--force]
 * 
 * Options:
 * --force: Thực thi không cần xác nhận
 * 
 * LƯU Ý:
 * - Command này THAY ĐỔI CẤU TRÚC DATABASE (xóa cột)
 * - Phải chạy migration tạo bảng organization_email_settings trước
 */
class MigrateOrganizationEmailSettings extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --force: Force migration without confirmation
     * 
     * @var string
     */
    protected $signature = 'migrate:organization-email-settings 
                            {--force : Force migration without confirmation}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Migrate email settings from organizations table to organization_email_settings table and remove email columns from organizations';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Kiểm tra xác nhận từ người dùng (trừ khi có flag --force)
     * 2. Bước 1: Gọi migrateEmailSettingsData() để migrate dữ liệu
     * 3. Bước 2: Gọi removeEmailColumns() để xóa các cột email
     * 4. Hiển thị kết quả thành công
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Lấy email settings (mail_username, mail_password, etc.)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng organization_email_settings
     * - Xóa các cột email khỏi bảng organizations (ALTER TABLE DROP COLUMN)
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle(): int
    {
        $force = $this->option('force');

        if (!$force && !$this->confirm('This will migrate email settings and remove email columns from organizations table. Continue?')) {
            $this->info('Migration cancelled.');
            return self::FAILURE;
        }

        $this->info('Starting organization email settings migration...');
        $this->newLine();

        // Step 1: Migrate data from organizations to organization_email_settings
        $this->info('Step 1/2: Migrating email settings data...');
        try {
            $this->migrateEmailSettingsData();
            $this->info('✅ Email settings data migrated successfully.');
        } catch (\Exception $e) {
            $this->error('❌ Failed to migrate email settings data: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();

        // Step 2: Remove email columns from organizations table
        $this->info('Step 2/2: Removing email columns from organizations table...');
        try {
            $this->removeEmailColumns();
            $this->info('✅ Email columns removed successfully.');
        } catch (\Exception $e) {
            $this->error('❌ Failed to remove email columns: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Organization email settings migration completed successfully!');
        
        return self::SUCCESS;
    }

    /**
     * Migrate dữ liệu email settings từ bảng organizations sang bảng organization_email_settings
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra bảng organization_email_settings có tồn tại không
     * 2. Kiểm tra bảng organizations có tồn tại không
     * 3. Kiểm tra các cột email có tồn tại trong bảng organizations không
     * 4. Lấy tất cả organizations từ database
     * 5. Với mỗi organization:
     *    - Kiểm tra xem có email settings không
     *    - Kiểm tra xem đã có bản ghi trong organization_email_settings chưa
     *    - Nếu chưa có: Tạo bản ghi mới trong organization_email_settings
     * 6. Hiển thị số lượng đã migrate và skipped
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Lấy email settings (mail_username, mail_password, mail_from_address, mail_host, mail_port, mail_encryption)
     * - Bảng organization_email_settings: Kiểm tra tồn tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng organization_email_settings
     */
    private function migrateEmailSettingsData(): void
    {
        // Check if organization_email_settings table exists
        if (!Schema::hasTable('organization_email_settings')) {
            throw new \Exception('organization_email_settings table does not exist. Please run the migration to create it first.');
        }

        // Check if organizations table exists
        if (!Schema::hasTable('organizations')) {
            throw new \Exception('organizations table does not exist.');
        }

        // Check if email columns exist in organizations table
        $hasEmailColumns = Schema::hasColumn('organizations', 'mail_username')
            || Schema::hasColumn('organizations', 'mail_password')
            || Schema::hasColumn('organizations', 'mail_from_address')
            || Schema::hasColumn('organizations', 'mail_host')
            || Schema::hasColumn('organizations', 'mail_port')
            || Schema::hasColumn('organizations', 'mail_encryption');

        if (!$hasEmailColumns) {
            $this->warn('⚠️  Email columns do not exist in organizations table. Skipping data migration.');
            return;
        }

        $organizations = DB::table('organizations')->get();
        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($organizations as $organization) {
            // Check if any mail settings exist for this organization
            $hasMailSettings = !empty($organization->mail_username)
                || !empty($organization->mail_password)
                || !empty($organization->mail_from_address)
                || !empty($organization->mail_host)
                || !empty($organization->mail_port)
                || !empty($organization->mail_encryption);

            if ($hasMailSettings) {
                // Check if email settings already exist for this organization
                $existingSettings = DB::table('organization_email_settings')
                    ->where('organization_id', $organization->id)
                    ->first();

                if (!$existingSettings) {
                    // Create email settings record
                    DB::table('organization_email_settings')->insert([
                        'organization_id' => $organization->id,
                        'mail_username' => $organization->mail_username,
                        'mail_password' => $organization->mail_password,
                        'mail_from_address' => $organization->mail_from_address,
                        'mail_host' => $organization->mail_host,
                        'mail_port' => $organization->mail_port,
                        'mail_encryption' => $organization->mail_encryption,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $migratedCount++;
                } else {
                    $skippedCount++;
                }
            }
        }

        $this->line("   Migrated: {$migratedCount} organizations");
        if ($skippedCount > 0) {
            $this->line("   Skipped: {$skippedCount} organizations (already have email settings)");
        }
    }

    /**
     * Xóa các cột email khỏi bảng organizations
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra bảng organizations có tồn tại không
     * 2. Định nghĩa danh sách các cột cần xóa:
     *    - mail_username, mail_password, mail_from_address, mail_host, mail_port, mail_encryption
     * 3. Với mỗi cột:
     *    - Kiểm tra xem cột có tồn tại không
     *    - Nếu có: Xóa cột bằng ALTER TABLE DROP COLUMN
     * 4. Hiển thị số lượng cột đã xóa
     * 
     * DỮ LIỆU GHI VÀO:
     * - Xóa các cột khỏi bảng organizations (ALTER TABLE DROP COLUMN)
     */
    private function removeEmailColumns(): void
    {
        if (!Schema::hasTable('organizations')) {
            throw new \Exception('organizations table does not exist.');
        }

        $columnsToDrop = [
            'mail_username',
            'mail_password',
            'mail_from_address',
            'mail_host',
            'mail_port',
            'mail_encryption',
        ];

        $droppedCount = 0;
        foreach ($columnsToDrop as $column) {
            if (Schema::hasColumn('organizations', $column)) {
                Schema::table('organizations', function ($table) use ($column) {
                    $table->dropColumn($column);
                });
                $droppedCount++;
            }
        }

        if ($droppedCount > 0) {
            $this->line("   Dropped {$droppedCount} column(s) from organizations table");
        } else {
            $this->warn('⚠️  No email columns found in organizations table to drop.');
        }
    }
}
