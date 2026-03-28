<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command: AutoMarkOverdueInvoices
 * 
 * MỤC ĐÍCH:
 * Tự động đánh dấu các hóa đơn quá hạn thanh toán (chuyển status từ 'issued' sang 'overdue').
 * Command này được chạy định kỳ (thường qua cron job) để cập nhật trạng thái hóa đơn quá hạn.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận dữ liệu từ: Model Invoice (bảng invoices)
 * 2. Tìm các hóa đơn quá hạn:
 *    - status = 'issued' (đã phát hành)
 *    - due_date < thời gian hiện tại (đã quá hạn)
 *    - Chưa bị xóa (deleted_at = null)
 * 3. Xử lý: Cập nhật status = 'overdue' cho từng hóa đơn
 * 4. Ghi log: Lưu thông tin vào Log để theo dõi
 * 
 * CÁCH CHẠY:
 * php artisan invoices:auto-mark-overdue
 */
class AutoMarkOverdueInvoices extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * @var string
     */
    protected $signature = 'invoices:auto-mark-overdue';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Tự động đánh dấu các hóa đơn quá hạn thanh toán';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Bắt đầu transaction để đảm bảo tính nhất quán dữ liệu
     * 2. Query từ bảng invoices:
     *    - Tìm các invoice có status = 'issued' (đã phát hành)
     *    - Có due_date không null (phải có ngày đến hạn)
     *    - due_date < now() (đã quá hạn)
     *    - deleted_at = null (chưa bị xóa)
     * 3. Với mỗi invoice quá hạn:
     *    - Cập nhật status = 'overdue' (quá hạn)
     *    - Ghi log thông tin invoice đã được đánh dấu
     * 4. Commit transaction
     * 5. Hiển thị kết quả: Số lượng invoice đã được đánh dấu
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\Invoice (bảng invoices)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Cập nhật bảng invoices (status = 'overdue')
     * - Ghi log vào storage/logs/laravel.log
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle()
    {
        // Hiển thị thông báo bắt đầu cho người dùng khi chạy command
        // $this->info() là method của Laravel Command để hiển thị message màu xanh trong console
        $this->info('Đang kiểm tra các hóa đơn quá hạn thanh toán...');

        try {
            /**
             * Bắt đầu database transaction để đảm bảo tính nhất quán dữ liệu
             * 
             * DB::beginTransaction() tạo một transaction mới trong database
             * - Tất cả các thay đổi sau đó sẽ được nhóm lại thành một đơn vị
             * - Nếu có lỗi xảy ra ở bất kỳ bước nào, tất cả thay đổi sẽ được rollback (không lưu)
             * - Chỉ khi gọi DB::commit() thì tất cả thay đổi mới được lưu vào database
             * - Đảm bảo tính ACID (Atomicity, Consistency, Isolation, Durability)
             */
            DB::beginTransaction();

            /**
             * Tìm các hóa đơn quá hạn từ bảng invoices sử dụng Eloquent ORM
             * 
             * Invoice::where() - Bắt đầu query builder từ model Invoice
             * ->where('status', 'issued') - Lọc các invoice có status = 'issued' (đã phát hành, chưa thanh toán)
             *   - 'issued' nghĩa là invoice đã được tạo và gửi cho khách hàng nhưng chưa được thanh toán
             * ->whereNotNull('due_date') - Chỉ lấy các invoice có due_date không null (phải có ngày đến hạn)
             *   - whereNotNull() kiểm tra column không phải NULL trong database
             * ->where('due_date', '<', now()) - Lọc các invoice có due_date nhỏ hơn thời gian hiện tại (đã quá hạn)
             *   - now() trả về Carbon instance với thời gian hiện tại (giờ, phút, giây)
             *   - So sánh '<' nghĩa là due_date đã qua (quá hạn)
             * ->whereNull('deleted_at') - Chỉ lấy các invoice chưa bị soft delete (deleted_at = NULL)
             *   - whereNull() kiểm tra column = NULL trong database
             *   - Soft delete: không xóa thật trong database, chỉ đánh dấu deleted_at
             * ->get() - Thực thi query và trả về Collection chứa các Invoice models
             *   - get() sẽ execute SQL query: SELECT * FROM invoices WHERE status='issued' AND due_date IS NOT NULL AND due_date < NOW() AND deleted_at IS NULL
             */
            $overdueInvoices = Invoice::where('status', 'issued')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->whereNull('deleted_at')
                ->get();

            // Khởi tạo biến đếm số lượng invoice đã được đánh dấu quá hạn
            // Biến này sẽ được tăng lên mỗi khi cập nhật một invoice
            $markedCount = 0;

            /**
             * Xử lý từng hóa đơn quá hạn bằng vòng lặp foreach
             * 
             * foreach ($overdueInvoices as $invoice) - Lặp qua từng phần tử trong Collection
             * - $overdueInvoices là Collection chứa các Invoice models đã query được
             * - $invoice là từng Invoice model trong Collection
             * - Mỗi lần lặp, $invoice sẽ là một instance của App\Models\Invoice
             */
            foreach ($overdueInvoices as $invoice) {
                /**
                 * Cập nhật trạng thái invoice thành 'overdue' sử dụng Eloquent update()
                 * 
                 * $invoice->update([...]) - Gọi method update() trên Invoice model instance
                 * - update() nhận một array các field cần cập nhật
                 * - Method này sẽ:
                 *   1. Tạo SQL UPDATE query: UPDATE invoices SET status='overdue' WHERE id=$invoice->id
                 *   2. Thực thi query trong transaction hiện tại
                 *   3. Cập nhật các attribute trong model instance
                 *   4. Trigger các Eloquent events (updating, updated)
                 * - 'status' => 'overdue' - Cập nhật field status trong database thành giá trị 'overdue'
                 *   - 'overdue' nghĩa là hóa đơn đã quá hạn thanh toán
                 *   - Status này sẽ được sử dụng để filter, hiển thị, và xử lý các invoice quá hạn
                 */
                $invoice->update([
                    'status' => 'overdue',
                ]);

                // Tăng biến đếm lên 1 sau mỗi lần cập nhật invoice thành công
                // $markedCount++ tương đương với $markedCount = $markedCount + 1
                $markedCount++;

                /**
                 * Ghi log thông tin invoice đã được đánh dấu quá hạn vào file log
                 * 
                 * Log::info() - Ghi log với level INFO (thông tin bình thường, không phải lỗi)
                 * - Log được ghi vào: storage/logs/laravel.log (hoặc file log được config)
                 * - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
                 * 
                 * Tham số 1: 'Invoice automatically marked as overdue' - Message mô tả hành động
                 * 
                 * Tham số 2: Array chứa context data (dữ liệu liên quan)
                 * - 'invoice_id' => $invoice->id - ID của invoice trong database (primary key)
                 * - 'invoice_no' => $invoice->invoice_no - Số hóa đơn (mã định danh do người dùng thấy)
                 * - 'due_date' => $invoice->due_date - Ngày đến hạn thanh toán (Carbon date instance)
                 * - 'lease_id' => $invoice->lease_id - ID của lease liên quan (foreign key, có thể null)
                 * - 'booking_deposit_id' => $invoice->booking_deposit_id - ID của booking deposit liên quan (foreign key, có thể null)
                 * 
                 * Mục đích log:
                 * - Theo dõi lịch sử thay đổi trạng thái invoice
                 * - Debug khi có vấn đề
                 * - Audit trail (dấu vết kiểm toán)
                 * - Phân tích số liệu sau này
                 */
                Log::info('Invoice automatically marked as overdue', [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'due_date' => $invoice->due_date,
                    'lease_id' => $invoice->lease_id,
                    'booking_deposit_id' => $invoice->booking_deposit_id,
                ]);
            }

            /**
             * Commit transaction - Xác nhận và lưu tất cả thay đổi vào database
             * 
             * DB::commit() - Commit transaction hiện tại
             * - Tất cả các thay đổi đã thực hiện trong transaction (các update() ở trên) sẽ được lưu vào database
             * - Sau khi commit, không thể rollback được nữa
             * - Nếu không có lỗi, code sẽ tiếp tục thực thi các dòng sau
             * - Nếu có lỗi trước khi commit, transaction sẽ tự động rollback khi catch exception
             */
            DB::commit();

            /**
             * Hiển thị kết quả cho người dùng dựa trên số lượng invoice đã xử lý
             * 
             * if ($markedCount > 0) - Kiểm tra xem có invoice nào được đánh dấu không
             * - $markedCount > 0 nghĩa là có ít nhất 1 invoice đã được cập nhật
             * 
             * $this->info() - Hiển thị message màu xanh trong console
             * - "Đã tự động đánh dấu {$markedCount} hóa đơn quá hạn thanh toán."
             * - {$markedCount} là string interpolation, sẽ thay thế bằng giá trị của biến $markedCount
             * - Ví dụ: Nếu $markedCount = 5, message sẽ là "Đã tự động đánh dấu 5 hóa đơn quá hạn thanh toán."
             * 
             * else - Nếu không có invoice nào được đánh dấu ($markedCount = 0)
             * - Hiển thị message thông báo không có invoice nào quá hạn
             */
            if ($markedCount > 0) {
                $this->info("Đã tự động đánh dấu {$markedCount} hóa đơn quá hạn thanh toán.");
            } else {
                $this->info('Không có hóa đơn nào quá hạn thanh toán.');
            }

            // Trả về Command::SUCCESS (giá trị = 0) để báo cho Laravel biết command đã chạy thành công
            // Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thành công không
            return Command::SUCCESS;
        } catch (\Exception $e) {
            /**
             * Xử lý lỗi: Rollback transaction và ghi log lỗi
             * 
             * catch (\Exception $e) - Bắt bất kỳ exception nào xảy ra trong block try
             * - \Exception là base class của tất cả exceptions trong PHP
             * - $e là exception object chứa thông tin về lỗi
             * 
             * DB::rollBack() - Hủy bỏ tất cả thay đổi trong transaction hiện tại
             * - Tất cả các update() đã thực hiện sẽ bị hủy (không lưu vào database)
             * - Database sẽ quay về trạng thái trước khi beginTransaction()
             * - Đảm bảo tính nhất quán dữ liệu: hoặc tất cả thành công, hoặc tất cả thất bại
             * 
             * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
             * - Log được ghi vào: storage/logs/laravel.log
             * - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message
             * - 'Error auto-marking overdue invoices: ' . $e->getMessage()
             *   - $e->getMessage() trả về error message của exception (ví dụ: "SQLSTATE[42S22]: Column not found")
             *   - Dấu . là string concatenation operator trong PHP
             * 
             * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
             * - 'Có lỗi xảy ra khi tự động đánh dấu hóa đơn quá hạn: ' . $e->getMessage()
             * - Hiển thị lỗi cho người dùng để họ biết command đã thất bại
             * 
             * return Command::FAILURE - Trả về Command::FAILURE (giá trị = 1) để báo command thất bại
             * - Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thất bại không
             * - Có thể trigger alert hoặc retry logic
             */
            DB::rollBack();
            Log::error('Error auto-marking overdue invoices: ' . $e->getMessage());
            $this->error('Có lỗi xảy ra khi tự động đánh dấu hóa đơn quá hạn: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

