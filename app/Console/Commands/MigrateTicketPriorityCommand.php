<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command: MigrateTicketPriorityCommand
 * 
 * MỤC ĐÍCH:
 * Migrate dữ liệu priority từ enum (tickets.priority) sang foreign key (tickets.priority_id) tham chiếu đến bảng ticket_priorities.
 * Command này được dùng để chuyển đổi từ enum sang relational database.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận option: --dry-run (chỉ hiển thị, không cập nhật)
 * 2. Tạo map từ ticket_priorities: key_code => id
 * 3. Lấy tất cả tickets chưa có priority_id
 * 4. Với mỗi ticket:
 *    - Tìm priority_id từ map theo priority (enum value)
 *    - Nếu tìm thấy: Cập nhật tickets.priority_id
 *    - Nếu không tìm thấy: Ghi cảnh báo và bỏ qua
 * 5. Hiển thị kết quả: Số lượng đã cập nhật, số lượng thiếu map
 * 
 * CÁCH CHẠY:
 * php artisan migrate:tickets-priority [--dry-run]
 * 
 * Options:
 * --dry-run: Chỉ hiển thị những gì sẽ được migrate, không thực sự cập nhật database
 * 
 * LƯU Ý:
 * - Phải có dữ liệu trong bảng ticket_priorities trước
 * - key_code trong ticket_priorities phải khớp với giá trị enum trong tickets.priority
 */
class MigrateTicketPriorityCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --dry-run: Show what would be migrated without making changes
     * 
     * @var string
     */
    protected $signature = 'migrate:tickets-priority {--dry-run}';
    
    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Map tickets.priority enum to ticket_priorities and fill tickets.priority_id';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy option dry-run
     * 2. Tạo map từ bảng ticket_priorities: key_code => id
     * 3. Lấy tất cả tickets từ database (id, priority, priority_id)
     * 4. Với mỗi ticket:
     *    - Nếu đã có priority_id: Bỏ qua
     *    - Tìm priority_id từ map theo priority (enum value)
     *    - Nếu tìm thấy: Cập nhật tickets.priority_id (trừ khi dry-run)
     *    - Nếu không tìm thấy: Ghi cảnh báo và đếm missing
     * 5. Hiển thị kết quả: Số lượng đã cập nhật, số lượng thiếu map
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng ticket_priorities: Lấy map key_code => id
     * - Bảng tickets: Lấy id, priority, priority_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Cập nhật tickets.priority_id (trừ khi dry-run)
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $map = DB::table('ticket_priorities')->pluck('id','key_code'); // key_code => id
        $updated = 0; $missing = 0;

        $tickets = DB::table('tickets')->select(['id','priority','priority_id'])->get();
        foreach ($tickets as $t) {
            if ($t->priority_id) { continue; }
            $priorityId = $map[$t->priority] ?? null;
            if (!$priorityId) { $missing++; $this->warn("Missing map for priority {$t->priority}"); continue; }
            $this->line("Ticket #{$t->id} -> priority_id={$priorityId}");
            if (!$dry) {
                DB::table('tickets')->where('id', $t->id)->update(['priority_id' => $priorityId]);
                $updated++;
            }
        }

        $this->info($dry ? 'Dry-run complete' : ("Updated $updated tickets; missing map: $missing"));
        return self::SUCCESS;
    }
}


