<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Command: InitDatabase
 * 
 * MỤC ĐÍCH:
 * Khởi tạo database bằng cách thực thi file SQL thay vì chạy migrations.
 * Command này được dùng để khởi tạo database từ file SQL có sẵn.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận tham số từ command line:
 *    - --sql-file: Đường dẫn đến file SQL (mặc định: database/sql/init_database.sql)
 *    - --force: Thực thi không cần xác nhận
 * 2. Kiểm tra file SQL có tồn tại không
 * 3. Xác nhận từ người dùng (nếu không có --force)
 * 4. Xử lý:
 *    - Đọc nội dung file SQL
 *    - Thực thi SQL statements (tùy loại database: SQLite hoặc MySQL/PostgreSQL)
 *    - Tạo bảng migrations và đánh dấu đã khởi tạo
 * 5. Hiển thị kết quả
 * 
 * CÁCH CHẠY:
 * php artisan db:init [--sql-file=path/to/file.sql] [--force]
 * 
 * Ví dụ:
 * php artisan db:init
 * php artisan db:init --sql-file=database/sql/custom.sql
 */
class InitDatabase extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --sql-file: Path to SQL file to execute (mặc định: database/sql/init_database.sql)
     * - --force: Force execution without confirmation
     * 
     * @var string
     */
    protected $signature = 'db:init 
                            {--sql-file= : Path to SQL file to execute}
                            {--force : Force execution without confirmation}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Initialize database with SQL file instead of migrations';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy đường dẫn file SQL từ option hoặc dùng mặc định
     * 2. Kiểm tra file SQL có tồn tại không
     * 3. Xác nhận từ người dùng (nếu không có --force)
     * 4. Đọc nội dung file SQL
     * 5. Thực thi SQL statements:
     *    - Nếu là SQLite: Tách statements và thực thi từng cái
     *    - Nếu là MySQL/PostgreSQL: Thực thi toàn bộ SQL
     * 6. Tạo bảng migrations và đánh dấu đã khởi tạo (để tránh chạy migrations sau này)
     * 7. Hiển thị kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - File SQL: database/sql/init_database.sql (mặc định) hoặc file được chỉ định
     * 
     * DỮ LIỆU GHI VÀO:
     * - Thực thi SQL statements trong file (tạo bảng, insert dữ liệu, etc.)
     * - Tạo bảng migrations và insert record đánh dấu đã khởi tạo
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        $sqlFile = $this->option('sql-file') ?: database_path('sql/init_database.sql');
        $force = $this->option('force');

        if (!File::exists($sqlFile)) {
            $this->error("SQL file not found: {$sqlFile}");
            return 1;
        }

        if (!$force && !$this->confirm('This will initialize the database with SQL. Continue?')) {
            $this->info('Database initialization cancelled.');
            return 0;
        }

        $this->info('Initializing database with SQL file...');
        $this->info("File: {$sqlFile}");

        try {
            $sql = File::get($sqlFile);
            
            // For SQLite, we need to handle statements differently
            if (config('database.default') === 'sqlite') {
                $this->executeSqliteStatements($sql);
            } else {
                $this->executeMysqlStatements($sql);
            }

            $this->info('✅ Database initialized successfully!');
            
            // Create migrations table to prevent future migration runs
            $this->createMigrationsTable();
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Database initialization failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Execute SQL statements for SQLite
     */
    private function executeSqliteStatements(string $sql): void
    {
        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($statement) {
                return !empty($statement) && !preg_match('/^--/', $statement);
            }
        );

        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                DB::unprepared($statement);
            }
        }
    }

    /**
     * Execute SQL statements for MySQL/PostgreSQL
     */
    private function executeMysqlStatements(string $sql): void
    {
        DB::unprepared($sql);
    }

    /**
     * Create migrations table to prevent future migration runs
     */
    private function createMigrationsTable(): void
    {
        try {
            // Check if migrations table exists
            if (!DB::getSchemaBuilder()->hasTable('migrations')) {
                DB::statement("
                    CREATE TABLE migrations (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        migration VARCHAR(255) NOT NULL,
                        batch INTEGER NOT NULL
                    )
                ");
                
                // Insert a dummy migration to mark as initialized
                DB::table('migrations')->insert([
                    'migration' => '2024_01_01_000000_database_initialized_with_sql',
                    'batch' => 1
                ]);
                
                $this->info('✅ Migrations table created and marked as initialized.');
            }
        } catch (\Exception $e) {
            $this->warn("Could not create migrations table: " . $e->getMessage());
        }
    }
}
