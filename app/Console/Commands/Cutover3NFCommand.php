<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Command: Cutover3NFCommand
 * 
 * MỤC ĐÍCH:
 * Chạy chuỗi cutover 3NF (Third Normal Form) gồm: migrate, backfill, verify.
 * Command này được dùng để thực hiện migration và backfill dữ liệu khi chuyển sang cấu trúc 3NF.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận option: --dry-run (chỉ hiển thị các bước, không thực thi)
 * 2. Bước 1: Chạy migrations (php artisan migrate --force)
 * 3. Bước 2: Backfill billing policies (php artisan backfill:billing-policies)
 * 4. Bước 2 (tiếp): Migrate ticket priorities (php artisan migrate:tickets-priority)
 * 5. Bước 3: Verify backfill integrity (php artisan verify:backfill)
 * 6. Hiển thị kết quả
 * 
 * CÁCH CHẠY:
 * php artisan cutover:3nf [--dry-run]
 * 
 * Options:
 * --dry-run: Chỉ hiển thị các bước sẽ được thực thi, không thực sự chạy
 * 
 * LƯU Ý:
 * - Command này chạy nhiều commands khác theo thứ tự
 * - Phải đảm bảo các commands được gọi đã tồn tại
 */
class Cutover3NFCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --dry-run: Show steps only without executing
     * 
     * @var string
     */
    protected $signature = 'cutover:3nf {--dry-run}';
    
    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Run 3NF cutover sequence: migrate, backfill, verify';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy option dry-run
     * 2. Bước 1: Chạy migrations (Artisan::call('migrate', ['--force' => true]))
     * 3. Bước 2: Backfill billing policies (Artisan::call('backfill:billing-policies'))
     * 4. Bước 2 (tiếp): Migrate ticket priorities (Artisan::call('migrate:tickets-priority'))
     * 5. Bước 3: Verify backfill (Artisan::call('verify:backfill'))
     * 6. Hiển thị kết quả
     * 
     * COMMANDS ĐƯỢC GỌI:
     * - php artisan migrate --force
     * - php artisan backfill:billing-policies
     * - php artisan migrate:tickets-priority
     * - php artisan verify:backfill
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('DRY-RUN: showing steps only');
        }

        // 1) Migrate
        $this->info('Step 1/3: Running migrations...');
        if (!$dry) {
            Artisan::call('migrate', ['--force' => true]);
            $this->line(Artisan::output());
        }

        // 2) Backfill
        $this->info('Step 2/3: Backfilling billing policies...');
        if (!$dry) {
            Artisan::call('backfill:billing-policies');
            $this->line(Artisan::output());
        }

        $this->info('Step 2/3: Migrating ticket priorities to FK...');
        if (!$dry) {
            Artisan::call('migrate:tickets-priority');
            $this->line(Artisan::output());
        }

        // 3) Verify
        $this->info('Step 3/3: Verifying backfill integrity...');
        if (!$dry) {
            Artisan::call('verify:backfill');
            $this->line(Artisan::output());
        }

        $this->info('Cutover sequence completed ' . ($dry ? '(dry-run)' : ''));
        return self::SUCCESS;
    }
}


