<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Capability;
use Illuminate\Support\Facades\DB;

/**
 * Command: SyncCapabilitiesCommand
 * 
 * MỤC ĐÍCH:
 * Đồng bộ capabilities từ file config/erp_modules.php vào database.
 * Command này được dùng để cập nhật danh sách capabilities khi có thay đổi trong config.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận dữ liệu từ: File config/erp_modules.php
 * 2. Đọc tất cả modules và capabilities từ config
 * 3. Xử lý:
 *    - Với mỗi capability trong config:
 *      + Nếu đã tồn tại trong database: Cập nhật (nếu có thay đổi)
 *      + Nếu chưa tồn tại: Tạo mới
 *    - Trích xuất category từ key_code (ví dụ: 'party.user.view' -> 'party')
 * 4. Ghi log: Hiển thị số lượng created/updated/skipped
 * 
 * CÁCH CHẠY:
 * php artisan capabilities:sync [--dry-run]
 * 
 * Options:
 * --dry-run: Chỉ hiển thị những gì sẽ được sync, không thực sự thay đổi database
 */
class SyncCapabilitiesCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --dry-run: Show what would be synced without making changes
     * 
     * @var string
     */
    protected $signature = 'capabilities:sync 
                            {--dry-run : Show what would be synced without making changes}
                            {--detailed : Show detailed information about each capability}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Sync capabilities from config/erp_modules.php to database';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Kiểm tra dry-run mode (nếu có flag --dry-run)
     * 2. Đọc config từ config('erp_modules'):
     *    - File: config/erp_modules.php
     *    - Chứa danh sách modules và capabilities
     * 3. Thu thập tất cả capabilities từ tất cả modules:
     *    - Với mỗi module: Lấy danh sách capabilities
     *    - Trích xuất category từ key_code (phần đầu trước dấu chấm)
     *    - Tạo mảng capabilities với: key_code, name, description, category, display_order
     * 4. Bắt đầu transaction
     * 5. Với mỗi capability:
     *    - Tìm capability trong database theo key_code
     *    - Nếu đã tồn tại:
     *      + So sánh các field: name, description, category, display_order
     *      + Nếu có thay đổi: Cập nhật (trừ khi dry-run)
     *      + Nếu không có thay đổi: Bỏ qua
     *    - Nếu chưa tồn tại: Tạo mới (trừ khi dry-run)
     * 6. Commit hoặc rollback (nếu dry-run)
     * 7. Hiển thị kết quả: Số lượng created/updated/skipped
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - File config: config/erp_modules.php
     * - Model: App\Models\Capability (bảng capabilities) - để kiểm tra tồn tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo mới hoặc cập nhật bản ghi trong bảng capabilities
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🔄 Syncing capabilities from config/erp_modules.php to database...');
        $this->newLine();

        $modules = config('erp_modules', []);
        
        if (empty($modules)) {
            $this->error('❌ No modules found in config/erp_modules.php');
            return self::FAILURE;
        }

        $allCapabilities = [];
        $displayOrder = 0;

        // Collect all capabilities from all modules
        foreach ($modules as $moduleKey => $module) {
            if (!isset($module['capabilities']) || !is_array($module['capabilities'])) {
                if ($this->option('detailed')) {
                    $this->warn("  ⚠️  Module '{$moduleKey}' has no capabilities array");
                }
                continue;
            }

            $capCount = count($module['capabilities']);
            $this->info("📦 Processing module: {$module['name']} ({$moduleKey}) - {$capCount} capabilities");
            
            foreach ($module['capabilities'] as $keyCode => $name) {
                // Extract category from key_code (e.g., 'party.user.view' -> 'party')
                $category = explode('.', $keyCode)[0];
                
                $allCapabilities[] = [
                    'key_code' => $keyCode,
                    'name' => $name,
                    'description' => $module['description'] ?? null,
                    'category' => $category,
                    'display_order' => ++$displayOrder,
                ];
            }
        }

        $this->info("📊 Found " . count($allCapabilities) . " capabilities to sync");
        $this->newLine();

        if ($this->option('detailed') || $dryRun) {
            $this->info("📋 Capabilities list:");
            foreach ($allCapabilities as $cap) {
                $this->line("   - {$cap['key_code']} ({$cap['category']})");
            }
            $this->newLine();
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();
        
        try {
            foreach ($allCapabilities as $capabilityData) {
                $existing = Capability::where('key_code', $capabilityData['key_code'])->first();
                
                if ($existing) {
                    // Update existing capability
                    $hasChanges = false;
                    $changes = [];
                    
                    foreach (['name', 'description', 'category', 'display_order'] as $field) {
                        if ($existing->$field != $capabilityData[$field]) {
                            $hasChanges = true;
                            $changes[$field] = [
                                'old' => $existing->$field,
                                'new' => $capabilityData[$field]
                            ];
                        }
                    }
                    
                    if ($hasChanges) {
                        if (!$dryRun) {
                            $existing->update($capabilityData);
                        }
                        $updated++;
                        $this->line("  ✏️  Updated: {$capabilityData['key_code']}");
                        if ($this->option('detailed') || $dryRun) {
                            foreach ($changes as $field => $change) {
                                $this->line("     {$field}: '{$change['old']}' → '{$change['new']}'");
                            }
                        }
                    } else {
                        $skipped++;
                        if ($this->option('detailed') || $dryRun) {
                            $this->line("  ✓   Skipped (no changes): {$capabilityData['key_code']}");
                        }
                    }
                } else {
                    // Create new capability
                    if (!$dryRun) {
                        Capability::create($capabilityData);
                    }
                    $created++;
                    $this->line("  ➕ Created: {$capabilityData['key_code']} - {$capabilityData['name']}");
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->newLine();
                $this->info("📋 Summary (DRY RUN):");
                $this->table(
                    ['Action', 'Count'],
                    [
                        ['Created', $created],
                        ['Updated', $updated],
                        ['Skipped', $skipped],
                        ['Total', count($allCapabilities)],
                    ]
                );
                $this->newLine();
                $this->warn("⚠️  Run without --dry-run to apply changes");
            } else {
                DB::commit();
                $this->newLine();
                $this->info("✅ Successfully synced capabilities!");
                $this->table(
                    ['Action', 'Count'],
                    [
                        ['Created', $created],
                        ['Updated', $updated],
                        ['Skipped', $skipped],
                        ['Total', count($allCapabilities)],
                    ]
                );
                
                if ($created === 0 && $updated === 0) {
                    $this->newLine();
                    $this->comment("ℹ️  All capabilities are already in sync. No changes needed.");
                }
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error syncing capabilities: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}


