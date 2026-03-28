<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command: VerifyBackfillCommand
 * 
 * MỤC ĐÍCH:
 * Verify tính toàn vẹn của dữ liệu backfill cho ticket priorities.
 * Command này được dùng để kiểm tra xem tất cả tickets đã có priority_id chưa sau khi backfill.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Đếm tổng số tickets trong database
 * 2. Đếm số tickets đã có priority_id (không null)
 * 3. Hiển thị tỷ lệ: tickets_with_priority_id / total_tickets
 * 
 * CÁCH CHẠY:
 * php artisan verify:backfill
 * 
 * LƯU Ý:
 * - Command này chỉ KIỂM TRA, không thay đổi dữ liệu
 * - Dùng để verify sau khi chạy migrate:tickets-priority
 */
class VerifyBackfillCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * @var string
     */
    protected $signature = 'verify:backfill';
    
    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Verify data backfill integrity for ticket priorities';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Đếm tổng số tickets trong database
     * 2. Đếm số tickets đã có priority_id (không null)
     * 3. Hiển thị kết quả: tickets_with_priority_id / total_tickets
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng tickets: Đếm tổng số và số có priority_id
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle(): int
    {
        // Ticket priority fill rate
        $ticketsTotal = DB::table('tickets')->count();
        $ticketsWithFk = DB::table('tickets')->whereNotNull('priority_id')->count();
        $this->info("Tickets with priority_id: $ticketsWithFk / $ticketsTotal");

        return self::SUCCESS;
    }
}


