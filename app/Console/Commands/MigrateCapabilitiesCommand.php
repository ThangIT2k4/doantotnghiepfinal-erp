<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Capability;
use App\Models\OrganizationUser;

/**
 * Command: MigrateCapabilitiesCommand
 * 
 * MỤC ĐÍCH:
 * Migrate dữ liệu capabilities từ dạng JSON (capabilities_json) sang bảng quan hệ (organization_user_capabilities).
 * Command này được dùng để chuyển đổi từ lưu trữ JSON sang relational database.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận options: --dry-run (chỉ hiển thị), --force (ghi đè dữ liệu đã tồn tại)
 * 2. Tìm tất cả organization_users có capabilities_json không rỗng
 * 3. Với mỗi organization_user:
 *    - Parse JSON capabilities_json
 *    - Với mỗi capability trong JSON:
 *      + Tìm capability trong bảng capabilities theo key_code
 *      + Kiểm tra xem đã tồn tại trong organization_user_capabilities chưa
 *      + Nếu chưa tồn tại hoặc có --force: Tạo mới hoặc cập nhật
 * 4. Hiển thị kết quả: Số lượng đã migrate, skipped, errors
 * 
 * CÁCH CHẠY:
 * php artisan migrate:capabilities [--dry-run] [--force]
 * 
 * Options:
 * --dry-run: Chỉ hiển thị những gì sẽ được migrate, không thực sự thay đổi database
 * --force: Ghi đè dữ liệu đã tồn tại trong organization_user_capabilities
 */
class MigrateCapabilitiesCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --dry-run: Show what would be migrated without making changes
     * - --force: Overwrite existing records
     * 
     * @var string
     */
    protected $signature = 'migrate:capabilities {--dry-run} {--force}';
    
    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Migrate capabilities_json to organization_user_capabilities relational table';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy options: dry-run và force
     * 2. Query từ bảng organization_users:
     *    - Tìm các record có capabilities_json không null và không rỗng
     * 3. Với mỗi organization_user:
     *    - Parse JSON capabilities_json
     *    - Với mỗi capability trong JSON:
     *      + Tìm capability trong bảng capabilities theo key_code
     *      + Kiểm tra xem đã tồn tại trong organization_user_capabilities chưa
     *      + Nếu chưa tồn tại hoặc có --force: Tạo mới hoặc cập nhật
     * 4. Hiển thị kết quả: Số lượng đã migrate, skipped, errors
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_users: Lấy capabilities_json
     * - Bảng capabilities: Tìm capability theo key_code
     * - Bảng organization_user_capabilities: Kiểm tra tồn tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo mới hoặc cập nhật bản ghi trong bảng organization_user_capabilities
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle(): int
    {
        /**
         * Lấy options từ command line
         * 
         * - dry-run: Chỉ hiển thị những gì sẽ được migrate, không thực sự thay đổi database
         * - force: Ghi đè dữ liệu đã tồn tại trong organization_user_capabilities
         */
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info('Migrating capabilities from JSON to relational tables...');

        /**
         * Tìm tất cả organization_users có capabilities_json không rỗng
         * 
         * Điều kiện:
         * - capabilities_json không null
         * - capabilities_json != '{}' (không phải object rỗng)
         * - capabilities_json != 'null' (không phải string "null")
         */
        $orgUsers = DB::table('organization_users')
            ->whereNotNull('capabilities_json')
            ->where('capabilities_json', '!=', '{}')
            ->where('capabilities_json', '!=', 'null')
            ->get();

        $total = $orgUsers->count();
        $migrated = 0;
        $errors = 0;
        $skipped = 0;

        if ($total === 0) {
            $this->info('No organization_users with capabilities_json found.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} organization_users with capabilities_json to migrate.");

        /**
         * Tạo progress bar để hiển thị tiến trình
         */
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        /**
         * Xử lý từng organization_user
         * 
         * Với mỗi organization_user:
         * 1. Parse JSON capabilities_json
         * 2. Với mỗi capability trong JSON:
         *    - Tìm capability trong bảng capabilities
         *    - Kiểm tra xem đã tồn tại trong organization_user_capabilities chưa
         *    - Tạo mới hoặc cập nhật (tùy vào options)
         */
        foreach ($orgUsers as $ou) {
            try {
                /**
                 * Parse JSON capabilities_json
                 * 
                 * Decode JSON string thành array
                 * Nếu decode thất bại hoặc rỗng: Bỏ qua organization_user này
                 */
                $decoded = json_decode($ou->capabilities_json ?? '{}', true) ?: [];
                
                if (empty($decoded)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                /**
                 * Xử lý từng capability trong JSON
                 * 
                 * Format: { "capability_key_code": true/false }
                 */
                foreach ($decoded as $capKey => $granted) {
                    /**
                     * Tìm capability trong bảng capabilities theo key_code
                     * 
                     * Nếu không tìm thấy: Bỏ qua capability này
                     */
                    $capability = Capability::where('key_code', $capKey)->first();
                    
                    if (!$capability) {
                        $this->warn("\nCapability '{$capKey}' not found in capabilities table. Skipping.");
                        continue;
                    }

                    /**
                     * Kiểm tra xem đã tồn tại trong organization_user_capabilities chưa
                     * 
                     * Query từ bảng organization_user_capabilities với:
                     * - organization_user_id = $ou->id
                     * - capability_id = $capability->id
                     */
                    $exists = DB::table('organization_user_capabilities')
                        ->where('organization_user_id', $ou->id)
                        ->where('capability_id', $capability->id)
                        ->exists();

                    /**
                     * Nếu đã tồn tại và không có flag --force: Bỏ qua
                     */
                    if ($exists && !$force) {
                        $skipped++;
                        continue;
                    }

                    /**
                     * Nếu không phải dry-run: Tạo mới hoặc cập nhật
                     */
                    if (!$dry) {
                        if ($exists && $force) {
                            /**
                             * Cập nhật bản ghi đã tồn tại
                             * 
                             * Cập nhật:
                             * - granted: true/false từ JSON
                             * - granted_at: now() nếu granted = true
                             * - revoked_at: now() nếu granted = false, null nếu granted = true
                             * - updated_at: now()
                             */
                            DB::table('organization_user_capabilities')
                                ->where('organization_user_id', $ou->id)
                                ->where('capability_id', $capability->id)
                                ->update([
                                    'granted' => (bool) $granted,
                                    'granted_at' => now(),
                                    'revoked_at' => (bool) $granted ? null : now(),
                                    'updated_at' => now(),
                                ]);
                        } else {
                            /**
                             * Tạo bản ghi mới
                             * 
                             * Insert vào bảng organization_user_capabilities:
                             * - organization_user_id: ID của organization_user
                             * - capability_id: ID của capability
                             * - granted: true/false từ JSON
                             * - granted_at: now() nếu granted = true
                             * - revoked_at: null nếu granted = true, now() nếu granted = false
                             * - created_at, updated_at: now()
                             */
                            DB::table('organization_user_capabilities')->insert([
                                'organization_user_id' => $ou->id,
                                'capability_id' => $capability->id,
                                'granted' => (bool) $granted,
                                'granted_at' => now(),
                                'revoked_at' => (bool) $granted ? null : now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    $migrated++;
                }
            } catch (\Exception $e) {
                /**
                 * Xử lý lỗi khi migrate một organization_user
                 * 
                 * Nếu có lỗi: Ghi log và tiếp tục với organization_user tiếp theo
                 */
                $errors++;
                $this->error("\nError migrating organization_user_id {$ou->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dry) {
            $this->info("Dry-run complete. Would migrate {$migrated} capability overrides.");
        } else {
            $this->info("Migration complete!");
            $this->info("- Migrated: {$migrated} capability overrides");
            $this->info("- Skipped: {$skipped}");
            if ($errors > 0) {
                $this->error("- Errors: {$errors}");
            }
        }

        return self::SUCCESS;
    }
}

