<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Command: ResetDatabaseCommand
 * 
 * MỤC ĐÍCH:
 * Reset toàn bộ database: xóa tất cả dữ liệu và đặt auto increment = 1 cho tất cả các bảng.
 * Command này được dùng trong môi trường development/test để reset database về trạng thái ban đầu.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Kiểm tra môi trường: Không cho chạy trong production (trừ khi có flag --force)
 * 2. Xác nhận từ người dùng: Hỏi xác nhận trước khi xóa dữ liệu
 * 3. Xử lý:
 *    - Tắt foreign key checks tạm thời
 *    - Lấy danh sách tất cả các bảng trong database
 *    - Với mỗi bảng:
 *      + Xóa tất cả dữ liệu (TRUNCATE)
 *      + Reset AUTO_INCREMENT về 1
 *    - Bật lại foreign key checks
 * 4. Hiển thị kết quả: Số lượng bảng đã reset
 * 
 * CÁCH CHẠY:
 * php artisan db:reset [--force] [--keep-migrations]
 * 
 * Options:
 * --force: Cho phép chạy trong production (nguy hiểm!)
 * --keep-migrations: Giữ lại bảng migrations (không xóa)
 */
class ResetDatabaseCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --force: Force the operation to run when in production
     * - --keep-migrations: Keep migrations table
     * 
     * @var string
     */
    protected $signature = 'db:reset 
                            {--force : Force the operation to run when in production}
                            {--keep-migrations : Keep migrations table}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Reset toàn bộ dữ liệu và đặt auto increment = 1 cho tất cả các bảng';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Kiểm tra môi trường production:
     *    - Nếu là production và không có flag --force: Dừng và báo lỗi
     * 2. Hiển thị cảnh báo và xác nhận từ người dùng
     * 3. Bắt đầu transaction
     * 4. Tắt foreign key checks tạm thời (SET FOREIGN_KEY_CHECKS=0)
     * 5. Lấy danh sách tất cả các bảng:
     *    - Query: SHOW TABLES
     *    - Bỏ qua bảng migrations nếu có flag --keep-migrations
     * 6. Với mỗi bảng:
     *    - Đếm số records trước khi xóa
     *    - Xóa tất cả dữ liệu: TRUNCATE TABLE
     *    - Reset AUTO_INCREMENT: ALTER TABLE ... AUTO_INCREMENT = 1
     * 7. Bật lại foreign key checks (SET FOREIGN_KEY_CHECKS=1)
     * 8. Commit transaction
     * 9. Hiển thị kết quả và hướng dẫn bước tiếp theo
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Lấy danh sách bảng qua SHOW TABLES
     * 
     * DỮ LIỆU GHI VÀO:
     * - Xóa tất cả dữ liệu trong các bảng (TRUNCATE)
     * - Reset AUTO_INCREMENT về 1 cho tất cả các bảng
     * 
     * LƯU Ý:
     * - Command này XÓA TẤT CẢ DỮ LIỆU trong database
     * - Không thể hoàn tác sau khi chạy
     * - Chỉ nên dùng trong môi trường development/test
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        // Kiểm tra môi trường production
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('⚠️  Không thể chạy lệnh này trong môi trường production!');
            $this->info('Nếu chắc chắn, hãy dùng flag --force');
            return 1;
        }

        $this->warn('========================================');
        $this->warn('  RESET DATABASE - XÓA TẤT CẢ DỮ LIỆU');
        $this->warn('========================================');
        $this->newLine();

        $this->warn('⚠️  CẢNH BÁO: Lệnh này sẽ XÓA TẤT CẢ DỮ LIỆU trong database!');
        $this->info('Database: ' . config('database.connections.mysql.database'));
        $this->newLine();

        if (!$this->confirm('Bạn có chắc chắn muốn tiếp tục?', false)) {
            $this->info('Đã hủy.');
            return 0;
        }

        $this->newLine();
        $this->info('Bắt đầu reset database...');
        $this->newLine();

        try {
            // KHÔNG dùng transaction vì TRUNCATE tự động commit và không thể rollback
            // Tắt foreign key checks tạm thời
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Lấy danh sách tất cả các bảng
            $tables = DB::select('SHOW TABLES');
            $databaseName = config('database.connections.mysql.database');
            $tableKey = "Tables_in_{$databaseName}";

            $tableNames = [];
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                // Bỏ qua migrations nếu có flag
                if ($this->option('keep-migrations') && $tableName === 'migrations') {
                    continue;
                }
                $tableNames[] = $tableName;
            }

            $this->info("Tìm thấy " . count($tableNames) . " bảng");
            $this->newLine();

            // Tạo progress bar
            $bar = $this->output->createProgressBar(count($tableNames));
            $bar->start();

            // Xóa dữ liệu và reset auto increment
            $deletedCount = 0;
            $errors = [];

            foreach ($tableNames as $tableName) {
                try {
                    // Đếm số records trước khi xóa
                    $count = DB::table($tableName)->count();

                    // Xóa tất cả dữ liệu
                    DB::table($tableName)->truncate();

                    // Reset auto increment về 1
                    DB::statement("ALTER TABLE `{$tableName}` AUTO_INCREMENT = 1");

                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'table' => $tableName,
                        'error' => $e->getMessage()
                    ];
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Bật lại foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Hiển thị kết quả
            $this->newLine();
            $this->info('========================================');
            $this->info('  RESET HOÀN TẤT!');
            $this->info('========================================');
            $this->info("Đã xóa dữ liệu và reset AUTO_INCREMENT cho {$deletedCount} bảng.");

            if (!empty($errors)) {
                $this->newLine();
                $this->warn('Có ' . count($errors) . ' bảng gặp lỗi:');
                foreach ($errors as $error) {
                    $this->error("  - {$error['table']}: {$error['error']}");
                }
            }

            $this->newLine();
            $this->info('Bước tiếp theo:');
            $this->line('  1. Chạy migrations: php artisan migrate');
            $this->line('  2. Chạy seeders: php artisan db:seed');

            return 0;

        } catch (\Exception $e) {
            // Đảm bảo bật lại foreign key checks trong trường hợp lỗi
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->newLine();
            $this->error('========================================');
            $this->error('  LỖI!');
            $this->error('========================================');
            $this->error('Có lỗi xảy ra: ' . $e->getMessage());
            $this->newLine();

            return 1;
        }
    }
}

