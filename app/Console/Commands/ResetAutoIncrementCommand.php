<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Command: ResetAutoIncrementCommand
 * 
 * MỤC ĐÍCH:
 * Reset AUTO_INCREMENT của tất cả các bảng về giá trị bằng ID nhỏ nhất hiện tại trong bảng.
 * Command này được dùng để tối ưu AUTO_INCREMENT sau khi xóa dữ liệu, tránh khoảng trống lớn.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Kiểm tra xác nhận từ người dùng (trừ khi có flag --force)
 * 2. Kiểm tra loại database (SQLite không hỗ trợ AUTO_INCREMENT)
 * 3. Lấy danh sách tất cả các bảng
 * 4. Với mỗi bảng:
 *    - Kiểm tra xem có cột id với AUTO_INCREMENT không
 *    - Lấy ID nhỏ nhất trong bảng
 *    - Reset AUTO_INCREMENT = min_id (hoặc 1 nếu bảng rỗng)
 * 5. Hiển thị kết quả
 * 
 * CÁCH CHẠY:
 * php artisan db:reset-auto-increment [--force]
 * 
 * Options:
 * --force: Thực thi không cần xác nhận
 * 
 * LƯU Ý:
 * - Chỉ hoạt động với MySQL/MariaDB (SQLite không hỗ trợ)
 * - Reset AUTO_INCREMENT về min_id giúp tối ưu không gian
 */
class ResetAutoIncrementCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --force: Force execution without confirmation
     * 
     * @var string
     */
    protected $signature = 'db:reset-auto-increment 
                            {--force : Force execution without confirmation}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Reset AUTO_INCREMENT của tất cả các bảng theo ID nhỏ nhất hiện tại';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Kiểm tra xác nhận từ người dùng (trừ khi có flag --force)
     * 2. Kiểm tra loại database:
     *    - Nếu là SQLite: Báo không hỗ trợ và dừng
     *    - Nếu là MySQL/MariaDB: Tiếp tục
     * 3. Lấy danh sách tất cả các bảng trong database
     * 4. Với mỗi bảng:
     *    - Bỏ qua system tables
     *    - Kiểm tra xem có cột id với AUTO_INCREMENT không
     *    - Lấy ID nhỏ nhất trong bảng
     *    - Reset AUTO_INCREMENT = min_id (hoặc 1 nếu bảng rỗng)
     * 5. Hiển thị kết quả: Số lượng bảng đã reset, số bảng đã bỏ qua
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Lấy danh sách bảng qua SHOW TABLES
     * - Database: Kiểm tra cột id qua SHOW COLUMNS
     * - Database: Lấy min(id) từ mỗi bảng
     * 
     * DỮ LIỆU GHI VÀO:
     * - Cập nhật AUTO_INCREMENT của các bảng (ALTER TABLE ... AUTO_INCREMENT = value)
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        $force = $this->option('force');

        if (!$force && !$this->confirm('⚠️  Bạn có chắc muốn reset AUTO_INCREMENT của tất cả các bảng?')) {
            $this->info('Đã hủy.');
            return 0;
        }

        $this->info('🔄 Bắt đầu reset AUTO_INCREMENT...');
        $this->newLine();

        try {
            /**
             * Kiểm tra loại database
             * 
             * SQLite không hỗ trợ AUTO_INCREMENT (sử dụng INTEGER PRIMARY KEY tự động)
             * Chỉ MySQL/MariaDB mới hỗ trợ AUTO_INCREMENT
             */
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'sqlite') {
                $this->info('ℹ️  SQLite không hỗ trợ AUTO_INCREMENT. Bỏ qua.');
                return 0;
            }

            /**
             * Lấy danh sách tất cả các bảng trong database
             * 
             * Gọi method getAllTables() để lấy danh sách bảng
             */
            $allTables = $this->getAllTables();
            $this->info('📋 Tìm thấy ' . count($allTables) . ' bảng trong database');
            $this->newLine();

            $resetCount = 0;
            $skippedCount = 0;
            $errors = [];

            /**
             * Xử lý từng bảng
             * 
             * Với mỗi bảng:
             * - Bỏ qua system tables
             * - Kiểm tra xem có cột id với AUTO_INCREMENT không
             * - Lấy ID nhỏ nhất trong bảng
             * - Reset AUTO_INCREMENT = min_id (hoặc 1 nếu bảng rỗng)
             */
            foreach ($allTables as $table) {
                // Bỏ qua system tables (không reset AUTO_INCREMENT của các bảng hệ thống)
                if (in_array($table, ['migrations', 'failed_jobs', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) {
                    continue;
                }

                // Kiểm tra bảng có tồn tại không
                if (!Schema::hasTable($table)) {
                    continue;
                }

                /**
                 * Kiểm tra xem bảng có cột id với AUTO_INCREMENT không
                 * 
                 * Nếu không có: Bỏ qua bảng này
                 */
                if (!$this->hasAutoIncrementId($table)) {
                    $skippedCount++;
                    continue;
                }

                /**
                 * Lấy ID nhỏ nhất trong bảng
                 * 
                 * Gọi method getMinId() để lấy min(id)
                 */
                $minId = $this->getMinId($table);
                
                /**
                 * Tính giá trị AUTO_INCREMENT mới
                 * 
                 * Logic:
                 * - Nếu bảng có dữ liệu (minId > 0): Set AUTO_INCREMENT = minId
                 * - Nếu bảng rỗng (minId = 0): Set AUTO_INCREMENT = 1
                 */
                $newAutoIncrement = $minId > 0 ? $minId : 1;

                /**
                 * Reset AUTO_INCREMENT của bảng
                 * 
                 * Gọi method resetAutoIncrement() để thực thi ALTER TABLE
                 */
                try {
                    $this->resetAutoIncrement($table, $newAutoIncrement);
                    $this->line("✓ <fg=green>{$table}</>: Reset AUTO_INCREMENT = {$newAutoIncrement} (Min ID: {$minId})");
                    $resetCount++;
                } catch (\Exception $e) {
                    // Ghi lại lỗi nếu có
                    $errors[] = "{$table}: " . $e->getMessage();
                    $this->line("✗ <fg=red>{$table}</>: Lỗi - " . $e->getMessage());
                }
            }

            $this->newLine();
            $this->info("✅ Hoàn thành!");
            $this->info("   - Đã reset: {$resetCount} bảng");
            $this->info("   - Đã bỏ qua: {$skippedCount} bảng (không có AUTO_INCREMENT)");

            if (!empty($errors)) {
                $this->newLine();
                $this->warn("⚠️  Có " . count($errors) . " lỗi:");
                foreach ($errors as $error) {
                    $this->line("   - {$error}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Lỗi: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Lấy danh sách tất cả các bảng trong database
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra loại database (SQLite hoặc MySQL/MariaDB)
     * 2. Với SQLite: Query từ sqlite_master
     * 3. Với MySQL/MariaDB: Query SHOW TABLES
     * 4. Trích xuất tên bảng từ kết quả
     * 5. Trả về mảng tên bảng
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Query SHOW TABLES (MySQL) hoặc sqlite_master (SQLite)
     * 
     * @return array Mảng chứa tên các bảng
     */
    protected function getAllTables(): array
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: Query từ sqlite_master
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return array_map(function($table) {
                return $table->name;
            }, $tables);
        }

        // MySQL/MariaDB: Query SHOW TABLES
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
     * Kiểm tra xem bảng có cột id với AUTO_INCREMENT không
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra loại database (SQLite hoặc MySQL/MariaDB)
     * 2. Với SQLite: Kiểm tra qua PRAGMA table_info (INTEGER PRIMARY KEY)
     * 3. Với MySQL/MariaDB: Kiểm tra qua SHOW COLUMNS (Extra có 'auto_increment')
     * 4. Trả về true nếu có, false nếu không
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Query SHOW COLUMNS (MySQL) hoặc PRAGMA table_info (SQLite)
     * 
     * @param string $table Tên bảng cần kiểm tra
     * @return bool true nếu có cột id với AUTO_INCREMENT, false nếu không
     */
    protected function hasAutoIncrementId(string $table): bool
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            /**
             * SQLite không có AUTO_INCREMENT, sử dụng INTEGER PRIMARY KEY
             * 
             * Kiểm tra qua PRAGMA table_info để tìm cột id có pk = 1
             */
            try {
                $columns = DB::select("PRAGMA table_info({$table})");
                foreach ($columns as $column) {
                    if ($column->name === 'id' && $column->pk == 1) {
                        return true;
                    }
                }
                return false;
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * MySQL/MariaDB: Kiểm tra qua SHOW COLUMNS
         * 
         * Tìm cột id và kiểm tra Extra có chứa 'auto_increment' không
         */
        try {
            $columns = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = 'id'");
            if (empty($columns)) {
                return false;
            }
            
            $column = $columns[0];
            return stripos($column->Extra, 'auto_increment') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Lấy ID nhỏ nhất trong bảng
     * 
     * LUỒNG XỬ LÝ:
     * 1. Query min('id') từ bảng
     * 2. Nếu có kết quả: Trả về (int)result
     * 3. Nếu không có kết quả (bảng rỗng): Trả về 0
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Query min('id') từ bảng
     * 
     * @param string $table Tên bảng cần lấy min ID
     * @return int ID nhỏ nhất trong bảng, hoặc 0 nếu bảng rỗng
     */
    protected function getMinId(string $table): int
    {
        try {
            $result = DB::table($table)->min('id');
            return $result ? (int)$result : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Reset AUTO_INCREMENT của bảng về giá trị cụ thể
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra loại database (SQLite không hỗ trợ)
     * 2. Với MySQL/MariaDB: Thực thi ALTER TABLE ... AUTO_INCREMENT = value
     * 
     * DỮ LIỆU GHI VÀO:
     * - Database: ALTER TABLE ... AUTO_INCREMENT = value
     * 
     * @param string $table Tên bảng cần reset AUTO_INCREMENT
     * @param int $value Giá trị AUTO_INCREMENT mới
     */
    protected function resetAutoIncrement(string $table, int $value): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            /**
             * SQLite không hỗ trợ ALTER TABLE AUTO_INCREMENT
             * Sequence được quản lý tự động
             */
            return;
        }

        /**
         * MySQL/MariaDB: Thực thi ALTER TABLE để reset AUTO_INCREMENT
         */
        DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$value}");
    }
}

