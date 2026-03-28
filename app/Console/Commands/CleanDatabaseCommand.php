<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Command: CleanDatabaseCommand
 * 
 * MỤC ĐÍCH:
 * Dọn dẹp database bằng cách xóa dữ liệu từ hầu hết các bảng, chỉ giữ lại một số bảng và dữ liệu quan trọng.
 * Command này được dùng trong môi trường development/test để reset database về trạng thái sạch.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Kiểm tra xác nhận từ người dùng (trừ khi có flag --force)
 * 2. Tắt foreign key checks tạm thời
 * 3. Xử lý các bảng đặc biệt:
 *    - organizations: Giữ lại chỉ id=3, xóa các organization khác
 *    - services: Giữ lại 4 bản ghi đầu tiên, xóa các bản ghi còn lại
 *    - users: Giữ lại 7 user đầu tiên, xóa các user khác
 *    - user_profiles: Giữ lại profiles của các user được giữ
 * 4. Xóa dữ liệu từ các bảng không trong danh sách keepTables
 * 5. Bật lại foreign key checks
 * 6. Hiển thị kết quả
 * 
 * CÁCH CHẠY:
 * php artisan db:clean [--force]
 * 
 * Options:
 * --force: Thực thi không cần xác nhận
 * 
 * LƯU Ý:
 * - Command này XÓA DỮ LIỆU từ hầu hết các bảng
 * - Không thể hoàn tác sau khi chạy
 * - Chỉ nên dùng trong môi trường development/test
 */
class CleanDatabaseCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --force: Force execution without confirmation
     * 
     * @var string
     */
    protected $signature = 'db:clean 
                            {--force : Force execution without confirmation}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Clean database except specific tables (amenities, capabilities, notification_channels, organization (keep id=3), payment_methods, plan_features, roles, sepay_banks, services (keep first 4), subscription_plans, ticket_priorities, users (keep first 7), user_profiles (keep by users))';

    /**
     * Danh sách các bảng cần giữ nguyên hoàn toàn (không xóa dữ liệu)
     * 
     * Các bảng này chứa dữ liệu cấu hình quan trọng:
     * - amenities: Tiện ích
     * - capabilities: Quyền truy cập
     * - notification_channels: Kênh thông báo
     * - payment_methods: Phương thức thanh toán
     * - plan_features: Tính năng gói đăng ký
     * - roles: Vai trò
     * - sepay_banks: Ngân hàng Sepay
     * - subscription_plans: Gói đăng ký
     * - ticket_priorities: Độ ưu tiên ticket
     * 
     * @var array
     */
    protected $keepTables = [
        'amenities',
        'capabilities',
        'notification_channels',
        'payment_methods',
        'plan_features',
        'roles',
        'sepay_banks',
        'subscription_plans',
        'ticket_priorities',
    ];

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Kiểm tra xác nhận từ người dùng (trừ khi có flag --force)
     * 2. Tắt foreign key checks tạm thời (SET FOREIGN_KEY_CHECKS=0)
     * 3. Bắt đầu transaction
     * 4. Lấy danh sách tất cả các bảng trong database
     * 5. Xử lý các bảng đặc biệt (organizations, services, users, user_profiles)
     * 6. Xóa dữ liệu từ các bảng không trong keepTables và không phải system tables
     * 7. Commit transaction
     * 8. Bật lại foreign key checks (SET FOREIGN_KEY_CHECKS=1)
     * 9. Hiển thị kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Lấy danh sách bảng qua SHOW TABLES
     * 
     * DỮ LIỆU GHI VÀO:
     * - Xóa dữ liệu từ các bảng (TRUNCATE)
     * - Xóa một phần dữ liệu từ các bảng đặc biệt (DELETE với điều kiện)
     * 
     * LƯU Ý:
     * - Command này XÓA DỮ LIỆU từ hầu hết các bảng
     * - Không thể hoàn tác sau khi chạy
     * - Chỉ nên dùng trong môi trường development/test
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        $force = $this->option('force');

        /**
         * Kiểm tra xác nhận từ người dùng
         * 
         * Nếu không có flag --force: Hỏi xác nhận trước khi xóa dữ liệu
         * Nếu người dùng không xác nhận: Dừng command
         */
        if (!$force && !$this->confirm('⚠️  WARNING: This will delete all data from most tables. Are you sure you want to continue?')) {
            $this->info('Database cleaning cancelled.');
            return 0;
        }

        $this->info('🧹 Starting database cleaning...');
        $this->newLine();

        try {
            /**
             * Tắt foreign key checks tạm thời
             * 
             * Mục đích: Cho phép xóa dữ liệu mà không bị ràng buộc bởi foreign key
             * Lưu ý: Phải bật lại sau khi hoàn thành
             */
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            /**
             * Bắt đầu transaction để đảm bảo tính nhất quán dữ liệu
             */
            DB::beginTransaction();

            /**
             * Bước 1: Lấy danh sách tất cả các bảng trong database
             * 
             * Gọi method getAllTables() để lấy danh sách bảng
             */
            $allTables = $this->getAllTables();
            $this->info('📋 Found ' . count($allTables) . ' tables in database');
            $this->newLine();

            /**
             * Bước 2: Xử lý các bảng đặc biệt trước
             * 
             * Các bảng đặc biệt cần xử lý riêng:
             * - organizations: Giữ lại chỉ id=3
             * - services: Giữ lại 4 bản ghi đầu tiên
             * - users: Giữ lại 7 user đầu tiên
             * - user_profiles: Giữ lại profiles của các user được giữ
             */
            $this->handleSpecialTables();

            /**
             * Bước 3: Xóa dữ liệu từ các bảng không trong keep list
             * 
             * Với mỗi bảng:
             * - Bỏ qua system tables (migrations, failed_jobs, etc.)
             * - Bỏ qua các bảng trong keepTables (giữ nguyên)
             * - Bỏ qua các bảng đặc biệt (đã xử lý ở bước 2)
             * - Xóa dữ liệu từ các bảng còn lại (TRUNCATE)
             */
            $cleanedCount = 0;
            foreach ($allTables as $table) {
                // Bỏ qua system tables (không xóa dữ liệu từ các bảng hệ thống)
                if (in_array($table, ['migrations', 'failed_jobs', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) {
                    continue;
                }

                // Bỏ qua các bảng trong keep list (giữ nguyên hoàn toàn)
                if (in_array($table, $this->keepTables)) {
                    $this->line("✓ Keeping table: <fg=green>{$table}</>");
                    continue;
                }

                // Bỏ qua các bảng đặc biệt (đã xử lý ở bước 2)
                if (in_array($table, ['organizations', 'services', 'users', 'user_profiles'])) {
                    continue;
                }

                // Xóa dữ liệu từ bảng (TRUNCATE)
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $this->line("🗑️  Cleaned table: <fg=yellow>{$table}</> (deleted {$count} records)");
                    $cleanedCount++;
                }
            }

            /**
             * Commit transaction - xác nhận tất cả thay đổi
             */
            DB::commit();
            
            /**
             * Bật lại foreign key checks
             * 
             * Đảm bảo ràng buộc foreign key được bật lại sau khi hoàn thành
             */
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->newLine();
            $this->info("✅ Database cleaning completed successfully!");
            $this->info("   - Cleaned {$cleanedCount} tables");
            $this->info("   - Kept " . count($this->keepTables) . " tables intact");
            $this->info("   - Handled 4 special tables (organizations, services, users, user_profiles)");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            // Re-enable foreign key checks in case of error
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error("❌ Database cleaning failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Lấy danh sách tất cả các bảng trong database
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tên database từ connection
     * 2. Thực thi query SHOW TABLES để lấy danh sách bảng
     * 3. Trích xuất tên bảng từ kết quả
     * 4. Trả về mảng tên bảng
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Query SHOW TABLES
     * 
     * @return array Mảng chứa tên các bảng
     */
    protected function getAllTables(): array
    {
        $databaseName = DB::connection()->getDatabaseName();
        $tables = DB::select("SHOW TABLES");
        
        $tableList = [];
        $key = "Tables_in_{$databaseName}";
        
        foreach ($tables as $table) {
            $tableList[] = $table->$key;
        }
        
        return $tableList;
    }

    /**
     * Xử lý các bảng đặc biệt với điều kiện cụ thể
     * 
     * LUỒNG XỬ LÝ:
     * 1. Xử lý bảng organizations:
     *    - Giữ lại chỉ organization có id=3
     *    - Xóa tất cả các organization khác
     * 2. Xử lý bảng services:
     *    - Giữ lại 4 bản ghi đầu tiên (theo id)
     *    - Xóa các bản ghi còn lại
     * 3. Xử lý bảng users:
     *    - Giữ lại 7 user đầu tiên (theo id)
     *    - Xóa các user khác
     * 4. Xử lý bảng user_profiles:
     *    - Giữ lại profiles của các user được giữ
     *    - Xóa profiles của các user đã bị xóa
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Đếm và xóa
     * - Bảng services: Lấy danh sách id cần giữ
     * - Bảng users: Lấy danh sách id cần giữ
     * - Bảng user_profiles: Kiểm tra user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Xóa dữ liệu từ bảng organizations (DELETE với điều kiện)
     * - Xóa dữ liệu từ bảng services (DELETE với điều kiện)
     * - Xóa dữ liệu từ bảng users (DELETE với điều kiện)
     * - Xóa dữ liệu từ bảng user_profiles (DELETE với điều kiện)
     */
    protected function handleSpecialTables(): void
    {
        /**
         * Xử lý bảng organizations - chỉ giữ lại id=3
         * 
         * Mục đích: Giữ lại organization chính, xóa các organization test/development khác
         */
        if (Schema::hasTable('organizations')) {
            $count = DB::table('organizations')->where('id', '!=', 3)->count();
            DB::table('organizations')->where('id', '!=', 3)->delete();
            $this->line("🏢 Organizations: <fg=cyan>Kept id=3</>, deleted {$count} records");
        }

        /**
         * Xử lý bảng services - giữ lại 4 bản ghi đầu tiên
         * 
         * Mục đích: Giữ lại các service cơ bản, xóa các service test/development khác
         */
        if (Schema::hasTable('services')) {
            $totalCount = DB::table('services')->count();
            if ($totalCount > 4) {
                // Lấy IDs của 4 bản ghi đầu tiên (theo id tăng dần)
                $keepIds = DB::table('services')
                    ->orderBy('id')
                    ->limit(4)
                    ->pluck('id')
                    ->toArray();
                
                // Xóa các bản ghi không trong danh sách giữ
                $deletedCount = DB::table('services')
                    ->whereNotIn('id', $keepIds)
                    ->delete();
                
                $this->line("🛠️  Services: <fg=cyan>Kept first 4 records</>, deleted {$deletedCount} records");
            } else {
                $this->line("🛠️  Services: <fg=green>Already has 4 or fewer records</>");
            }
        }

        /**
         * Xử lý bảng users - giữ lại 7 user đầu tiên
         * 
         * Mục đích: Giữ lại các user admin/system, xóa các user test/development khác
         */
        if (Schema::hasTable('users')) {
            $totalCount = DB::table('users')->count();
            if ($totalCount > 7) {
                // Lấy IDs của 7 user đầu tiên (theo id tăng dần)
                $keepUserIds = DB::table('users')
                    ->orderBy('id')
                    ->limit(7)
                    ->pluck('id')
                    ->toArray();
                
                // Xóa các user không trong danh sách giữ
                $deletedUserCount = DB::table('users')
                    ->whereNotIn('id', $keepUserIds)
                    ->delete();
                
                $this->line("👥 Users: <fg=cyan>Kept first 7 records</>, deleted {$deletedUserCount} records");
                
                /**
                 * Xử lý bảng user_profiles - chỉ giữ lại profiles của các user được giữ
                 * 
                 * Mục đích: Đảm bảo tính nhất quán dữ liệu, xóa profiles của các user đã bị xóa
                 */
                if (Schema::hasTable('user_profiles')) {
                    $deletedProfileCount = DB::table('user_profiles')
                        ->whereNotIn('user_id', $keepUserIds)
                        ->delete();
                    
                    $this->line("👤 User Profiles: <fg=cyan>Kept profiles for 7 users</>, deleted {$deletedProfileCount} records");
                }
            } else {
                // Nếu đã có 7 user hoặc ít hơn, vẫn cần kiểm tra user_profiles
                $keepUserIds = DB::table('users')->orderBy('id')->pluck('id')->toArray();
                
                /**
                 * Xử lý bảng user_profiles - chỉ giữ lại profiles của các user hiện có
                 */
                if (Schema::hasTable('user_profiles')) {
                    $deletedProfileCount = DB::table('user_profiles')
                        ->whereNotIn('user_id', $keepUserIds)
                        ->delete();
                    
                    if ($deletedProfileCount > 0) {
                        $this->line("👤 User Profiles: <fg=cyan>Kept profiles for existing users</>, deleted {$deletedProfileCount} records");
                    } else {
                        $this->line("👤 User Profiles: <fg=green>Already clean</>");
                    }
                }
                
                $this->line("👥 Users: <fg=green>Already has 7 or fewer records</>");
            }
        }

        $this->newLine();
    }
}
