<?php

namespace App\Console\Commands;

use App\Models\BookingDeposit;
use App\Models\Unit;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command: AutoCancelOverdueDeposits
 * 
 * MỤC ĐÍCH:
 * Tự động hủy các đặt cọc (booking deposit) quá hạn thanh toán trong hệ thống.
 * Command này được chạy định kỳ để kiểm tra và cập nhật trạng thái đặt cọc quá hạn.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận dữ liệu từ: Model BookingDeposit (bảng booking_deposits)
 * 2. Tìm các đặt cọc quá hạn:
 *    - payment_status = 'pending' (chưa thanh toán)
 *    - payment_due_date < thời gian hiện tại (quá hạn)
 *    - Chưa bị xóa (deleted_at = null)
 * 3. Xử lý cho mỗi đặt cọc quá hạn:
 *    - Cập nhật payment_status = 'cancelled' và expired_at = now
 *    - Hủy các invoice liên quan (nếu chưa thanh toán)
 *    - Cập nhật trạng thái unit về 'available' (nếu không còn đặt cọc/lease khác)
 * 4. Ghi log: Lưu thông tin vào Log để theo dõi
 * 
 * CÁCH CHẠY:
 * php artisan deposits:auto-cancel-overdue
 */
class AutoCancelOverdueDeposits extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * @var string
     */
    protected $signature = 'deposits:auto-cancel-overdue';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Tự động hủy các đặt cọc quá hạn thanh toán';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Bắt đầu transaction để đảm bảo tính nhất quán dữ liệu
     * 2. Query từ bảng booking_deposits:
     *    - Tìm các deposit có payment_status = 'pending'
     *    - Có payment_due_date không null
     *    - payment_due_date < thời gian hiện tại (quá hạn)
     *    - Chưa bị xóa (deleted_at = null)
     * 3. Với mỗi deposit quá hạn:
     *    a. Cập nhật payment_status = 'cancelled' và expired_at = now
     *    b. Tìm các invoice liên quan (qua relationship deposit->invoices())
     *    c. Hủy các invoice chưa thanh toán (status != 'paid' và != 'cancelled')
     *    d. Kiểm tra unit liên quan:
     *       - Nếu unit.status = 'reserved': Kiểm tra xem còn deposit/lease khác không
     *       - Nếu không còn: Cập nhật unit.status = 'available'
     * 4. Commit transaction
     * 5. Ghi log và hiển thị kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\BookingDeposit (bảng booking_deposits)
     * - Model: App\Models\Invoice (bảng invoices) - qua relationship
     * - Model: App\Models\Unit (bảng units) - qua relationship
     * 
     * DỮ LIỆU GHI VÀO:
     * - Cập nhật bảng booking_deposits (payment_status, expired_at)
     * - Cập nhật bảng invoices (status = 'cancelled')
     * - Cập nhật bảng units (status = 'available')
     * - Ghi log vào storage/logs/laravel.log
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle()
    {
        // Hiển thị thông báo bắt đầu cho người dùng khi chạy command
        // $this->info() là method của Laravel Command để hiển thị message màu xanh trong console
        $this->info('Đang kiểm tra các đặt cọc quá hạn thanh toán...');

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
             * Tìm các đặt cọc quá hạn từ bảng booking_deposits sử dụng Eloquent ORM
             * 
             * BookingDeposit::where('payment_status', 'pending') - Bắt đầu query builder từ model BookingDeposit
             *   - where('payment_status', 'pending') lọc các deposit có payment_status = 'pending' (chưa thanh toán)
             *   - 'pending' nghĩa là deposit đã được tạo nhưng chưa được thanh toán
             * 
             * ->whereNotNull('payment_due_date') - Chỉ lấy các deposit có payment_due_date không null
             *   - whereNotNull() kiểm tra column không phải NULL trong database
             *   - payment_due_date là ngày đến hạn thanh toán (phải có để biết khi nào quá hạn)
             * 
             * ->where('payment_due_date', '<', now()) - Lọc các deposit có payment_due_date nhỏ hơn thời gian hiện tại (đã quá hạn)
             *   - '<' là SQL LESS THAN operator
             *   - now() trả về Carbon instance với thời gian hiện tại (giờ, phút, giây)
             *   - So sánh '<' nghĩa là payment_due_date đã qua (quá hạn thanh toán)
             *   - Ví dụ: Nếu payment_due_date = 2024-01-10 và now() = 2024-01-15, thì deposit đã quá hạn 5 ngày
             * 
             * ->whereNull('deleted_at') - Chỉ lấy các deposit chưa bị soft delete
             *   - whereNull() kiểm tra column = NULL trong database
             *   - deleted_at = NULL nghĩa là chưa bị xóa
             *   - Soft delete: không xóa thật trong database, chỉ đánh dấu deleted_at
             * 
             * ->get() - Thực thi query và trả về Collection chứa các BookingDeposit models
             *   - get() sẽ execute SQL query: SELECT * FROM booking_deposits WHERE payment_status='pending' AND payment_due_date IS NOT NULL AND payment_due_date < NOW() AND deleted_at IS NULL
             */
            $overdueDeposits = BookingDeposit::where('payment_status', 'pending')
                ->whereNotNull('payment_due_date')
                ->where('payment_due_date', '<', now())
                ->whereNull('deleted_at')
                ->get();

            // Khởi tạo biến đếm số lượng deposit đã được hủy
            // Biến này sẽ được tăng lên mỗi khi hủy một deposit thành công
            $cancelledCount = 0;

            /**
             * Xử lý từng đặt cọc quá hạn bằng vòng lặp foreach
             * 
             * foreach ($overdueDeposits as $deposit) - Lặp qua từng phần tử trong Collection
             * - $overdueDeposits là Collection chứa các BookingDeposit models đã query được
             * - $deposit là từng BookingDeposit model trong Collection
             * - Mỗi lần lặp, $deposit sẽ là một instance của App\Models\BookingDeposit
             */
            foreach ($overdueDeposits as $deposit) {
                /**
                 * Bước 1: Cập nhật trạng thái deposit thành 'cancelled' sử dụng Eloquent update()
                 * 
                 * $deposit->update([...]) - Gọi method update() trên BookingDeposit model instance
                 * - update() nhận một array các field cần cập nhật
                 * - Method này sẽ:
                 *   1. Tạo SQL UPDATE query: UPDATE booking_deposits SET payment_status='cancelled', expired_at='2024-01-15 10:00:00' WHERE id=$deposit->id
                 *   2. Thực thi query trong transaction hiện tại
                 *   3. Cập nhật các attribute trong model instance
                 *   4. Trigger các Eloquent events (updating, updated)
                 * 
                 * 'payment_status' => 'cancelled' - Cập nhật field payment_status trong database thành giá trị 'cancelled'
                 *   - 'cancelled' nghĩa là đặt cọc đã bị hủy (do quá hạn thanh toán)
                 *   - Status này sẽ được sử dụng để filter, hiển thị, và xử lý các deposit đã hủy
                 *   - Khác với 'expired' (hết hạn giữ chỗ), 'cancelled' nghĩa là hủy do quá hạn thanh toán
                 * 
                 * 'expired_at' => now() - Cập nhật field expired_at với thời gian hiện tại
                 *   - now() trả về Carbon instance với thời gian hiện tại (giờ, phút, giây)
                 *   - expired_at lưu thời gian chính xác khi deposit bị hủy
                 *   - Có thể dùng để phân tích, báo cáo sau này
                 */
                $deposit->update([
                    'payment_status' => 'cancelled',
                    'expired_at' => now(),
                ]);

                /**
                 * Bước 2: Hủy các invoice liên quan đến deposit này
                 * 
                 * $deposit->invoices() - Truy cập relationship invoices() từ BookingDeposit model
                 *   - invoices() là method trong BookingDeposit model, trả về HasMany relationship
                 *   - Relationship này query từ bảng invoices với điều kiện booking_deposit_id = $deposit->id
                 *   - Trả về query builder, chưa thực thi query
                 * 
                 * ->where('status', '!=', 'paid') - Lọc các invoice chưa thanh toán
                 *   - '!=' là SQL NOT EQUAL operator
                 *   - Chỉ lấy các invoice có status khác 'paid' (chưa thanh toán)
                 *   - Không cần hủy invoice đã thanh toán (đã hoàn tất giao dịch)
                 * 
                 * ->where('status', '!=', 'cancelled') - Lọc các invoice chưa bị hủy
                 *   - Chỉ lấy các invoice có status khác 'cancelled' (chưa bị hủy)
                 *   - Tránh xử lý lại các invoice đã bị hủy trước đó
                 * 
                 * ->get() - Thực thi query và trả về Collection chứa các Invoice models
                 *   - get() sẽ execute SQL query và trả về kết quả
                 *   - SQL tương đương: SELECT * FROM invoices WHERE booking_deposit_id=$deposit->id AND status != 'paid' AND status != 'cancelled'
                 */
                $invoices = $deposit->invoices()
                    ->where('status', '!=', 'paid')
                    ->where('status', '!=', 'cancelled')
                    ->get();
                
                /**
                 * Xử lý từng invoice liên quan bằng vòng lặp foreach
                 * 
                 * foreach ($invoices as $invoice) - Lặp qua từng phần tử trong Collection
                 * - $invoices là Collection chứa các Invoice models đã query được
                 * - $invoice là từng Invoice model trong Collection
                 * - Mỗi lần lặp, $invoice sẽ là một instance của App\Models\Invoice
                 */
                foreach ($invoices as $invoice) {
                    /**
                     * Cập nhật trạng thái invoice thành 'cancelled' sử dụng Eloquent update()
                     * 
                     * $invoice->update([...]) - Gọi method update() trên Invoice model instance
                     * - update() nhận một array các field cần cập nhật
                     * - Method này sẽ:
                     *   1. Tạo SQL UPDATE query: UPDATE invoices SET status='cancelled' WHERE id=$invoice->id
                     *   2. Thực thi query trong transaction hiện tại
                     *   3. Cập nhật các attribute trong model instance
                     *   4. Trigger các Eloquent events (updating, updated)
                     * 
                     * 'status' => 'cancelled' - Cập nhật field status trong database thành giá trị 'cancelled'
                     *   - 'cancelled' nghĩa là invoice đã bị hủy (do deposit bị hủy)
                     *   - Invoice bị hủy sẽ không còn hiệu lực, không cần thanh toán nữa
                     *   - Khác với 'expired' (hết hạn), 'cancelled' nghĩa là hủy do deposit bị hủy
                     */
                    $invoice->update([
                        'status' => 'cancelled',
                    ]);
                    
                    /**
                     * Ghi log thông tin invoice đã được hủy
                     * 
                     * Log::info() - Ghi log với level INFO (thông tin bình thường)
                     * - Log được ghi vào: storage/logs/laravel.log
                     * - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
                     * 
                     * Tham số 1: 'Invoice automatically cancelled due to booking deposit cancelled' - Message mô tả hành động
                     * 
                     * Tham số 2: Array chứa context data
                     * - 'invoice_id' => $invoice->id - ID của invoice trong database (primary key)
                     * - 'invoice_no' => $invoice->invoice_no - Số hóa đơn (mã định danh do người dùng thấy)
                     * - 'booking_deposit_id' => $deposit->id - ID của booking deposit liên quan (foreign key)
                     * 
                     * Mục đích log:
                     * - Theo dõi lịch sử thay đổi trạng thái invoice
                     * - Debug khi có vấn đề
                     * - Audit trail (dấu vết kiểm toán)
                     * - Phân tích số liệu sau này
                     */
                    Log::info('Invoice automatically cancelled due to booking deposit cancelled', [
                        'invoice_id' => $invoice->id,
                        'invoice_no' => $invoice->invoice_no,
                        'booking_deposit_id' => $deposit->id,
                    ]);
                }

                /**
                 * Bước 3: Cập nhật trạng thái unit về 'available' (nếu cần)
                 * 
                 * $deposit->unit - Truy cập relationship unit() từ BookingDeposit model
                 *   - unit() là method trong BookingDeposit model, trả về BelongsTo relationship
                 *   - Relationship này query từ bảng units với điều kiện id = $deposit->unit_id
                 *   - Trả về Unit model instance (hoặc null nếu không tồn tại)
                 *   - Unit đã được eager load hoặc lazy load khi truy cập
                 * 
                 * if ($unit && $unit->status === 'reserved') - Kiểm tra điều kiện
                 *   - $unit - Kiểm tra unit có tồn tại không (không null)
                 *   - && - Logical AND operator (cả hai điều kiện phải true)
                 *   - $unit->status === 'reserved' - Kiểm tra status của unit có bằng 'reserved' không
                 *   - 'reserved' nghĩa là unit đang được giữ chỗ (có deposit active)
                 *   - Chỉ cập nhật status nếu unit đang ở trạng thái 'reserved'
                 *   - Nếu unit đã là 'available' hoặc 'occupied', không cần cập nhật
                 */
                $unit = $deposit->unit;
                if ($unit && $unit->status === 'reserved') {
                    /**
                     * Kiểm tra xem còn deposit khác đang active không
                     * 
                     * BookingDeposit::where('unit_id', $unit->id) - Bắt đầu query từ model BookingDeposit
                     *   - where('unit_id', $unit->id) lọc các deposit của unit này
                     *   - $unit->id là ID của unit (primary key)
                     * 
                     * ->where('id', '!=', $deposit->id) - Loại trừ deposit hiện tại
                     *   - '!=' là SQL NOT EQUAL operator
                     *   - Chỉ kiểm tra các deposit khác, không kiểm tra deposit đang xử lý
                     * 
                     * ->whereIn('payment_status', ['pending_approval', 'pending', 'paid']) - Lọc các deposit còn active
                     *   - whereIn() kiểm tra payment_status có trong array ['pending_approval', 'pending', 'paid'] không
                     *   - 'pending_approval' - Đang chờ duyệt
                     *   - 'pending' - Đang chờ thanh toán (nhưng chưa quá hạn)
                     *   - 'paid' - Đã thanh toán (nhưng chưa hết hạn)
                     *   - Các status này được coi là "active" (còn hiệu lực)
                     * 
                     * ->whereNull('deleted_at') - Chỉ lấy các deposit chưa bị soft delete
                     *   - whereNull() kiểm tra column = NULL trong database
                     *   - deleted_at = NULL nghĩa là chưa bị xóa
                     * 
                     * ->exists() - Kiểm tra xem có ít nhất 1 record match các điều kiện không
                     *   - exists() trả về boolean (true/false)
                     *   - true = có deposit khác đang active
                     *   - false = không còn deposit nào đang active
                     *   - exists() tối ưu hơn count() vì chỉ cần check có/không, không cần đếm số lượng
                     *   - SQL tương đương: SELECT 1 FROM booking_deposits WHERE ... LIMIT 1
                     */
                    $hasOtherActiveDeposits = BookingDeposit::where('unit_id', $unit->id)
                        ->where('id', '!=', $deposit->id)
                        ->whereIn('payment_status', ['pending_approval', 'pending', 'paid'])
                        ->whereNull('deleted_at')
                        ->exists();

                    /**
                     * Kiểm tra xem còn lease active không
                     * 
                     * $unit->leases() - Truy cập relationship leases() từ Unit model
                     *   - leases() là method trong Unit model, trả về HasMany relationship
                     *   - Relationship này query từ bảng leases với điều kiện unit_id = $unit->id
                     *   - Trả về query builder, chưa thực thi query
                     * 
                     * ->where('status', 'active') - Lọc các lease có status = 'active'
                     *   - 'active' nghĩa là hợp đồng thuê đang có hiệu lực
                     *   - Chỉ kiểm tra các lease active, không kiểm tra draft, expired, terminated
                     * 
                     * ->whereNull('deleted_at') - Chỉ lấy các lease chưa bị soft delete
                     *   - whereNull() kiểm tra column = NULL trong database
                     *   - deleted_at = NULL nghĩa là chưa bị xóa
                     * 
                     * ->exists() - Kiểm tra xem có ít nhất 1 lease active không
                     *   - exists() trả về boolean (true/false)
                     *   - true = có lease active
                     *   - false = không có lease active
                     *   - exists() tối ưu hơn count() vì chỉ cần check có/không
                     *   - SQL tương đương: SELECT 1 FROM leases WHERE unit_id=$unit->id AND status='active' AND deleted_at IS NULL LIMIT 1
                     */
                    $hasActiveLease = $unit->leases()
                        ->where('status', 'active')
                        ->whereNull('deleted_at')
                        ->exists();

                    /**
                     * Nếu không còn deposit active và không còn lease active => Unit có thể được đặt lại
                     * 
                     * if (!$hasOtherActiveDeposits && !$hasActiveLease) - Kiểm tra điều kiện
                     *   - !$hasOtherActiveDeposits - Không có deposit khác đang active (NOT operator)
                     *   - && - Logical AND operator (cả hai điều kiện phải true)
                     *   - !$hasActiveLease - Không có lease active (NOT operator)
                     *   - Chỉ cập nhật status khi CẢ HAI điều kiện đều true:
                     *     + Không còn deposit nào đang active
                     *     + Không còn lease nào đang active
                     * 
                     * $unit->update(['status' => 'available']) - Cập nhật status của unit thành 'available'
                     *   - update() nhận một array các field cần cập nhật
                     *   - 'status' => 'available' - Cập nhật field status trong database thành giá trị 'available'
                     *   - 'available' nghĩa là unit đang trống, có thể cho thuê/đặt cọc
                     *   - Unit có thể được đặt lại vì không còn deposit/lease nào đang active
                     *   - SQL tương đương: UPDATE units SET status='available' WHERE id=$unit->id
                     */
                    if (!$hasOtherActiveDeposits && !$hasActiveLease) {
                        $unit->update(['status' => 'available']);
                    }
                }

                // Tăng biến đếm lên 1 sau mỗi lần xử lý deposit thành công
                // $cancelledCount++ tương đương với $cancelledCount = $cancelledCount + 1
                $cancelledCount++;

                /**
                 * Ghi log thông tin deposit đã hủy
                 * 
                 * Log::info() - Ghi log với level INFO (thông tin bình thường)
                 * - Log được ghi vào: storage/logs/laravel.log
                 * - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
                 * 
                 * Tham số 1: 'Booking deposit automatically cancelled due to overdue payment' - Message mô tả hành động
                 * 
                 * Tham số 2: Array chứa context data
                 * - 'booking_deposit_id' => $deposit->id - ID của deposit trong database (primary key)
                 * - 'reference_number' => $deposit->reference_number - Số tham chiếu deposit (mã định danh do người dùng thấy)
                 * - 'payment_due_date' => $deposit->payment_due_date - Ngày đến hạn thanh toán (Carbon date instance)
                 * - 'unit_id' => $deposit->unit_id - ID của unit liên quan (foreign key)
                 * 
                 * Mục đích log:
                 * - Theo dõi lịch sử thay đổi trạng thái deposit
                 * - Debug khi có vấn đề
                 * - Audit trail (dấu vết kiểm toán)
                 * - Phân tích số liệu sau này
                 */
                Log::info('Booking deposit automatically cancelled due to overdue payment', [
                    'booking_deposit_id' => $deposit->id,
                    'reference_number' => $deposit->reference_number,
                    'payment_due_date' => $deposit->payment_due_date,
                    'unit_id' => $deposit->unit_id,
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
             * Hiển thị kết quả cho người dùng dựa trên số lượng deposit đã xử lý
             * 
             * if ($cancelledCount > 0) - Kiểm tra xem có deposit nào được hủy không
             * - $cancelledCount > 0 nghĩa là có ít nhất 1 deposit đã được hủy
             * 
             * $this->info() - Hiển thị message màu xanh trong console
             * - "Đã tự động hủy {$cancelledCount} đặt cọc quá hạn thanh toán."
             * - {$cancelledCount} là string interpolation, sẽ thay thế bằng giá trị của biến $cancelledCount
             * - Ví dụ: Nếu $cancelledCount = 3, message sẽ là "Đã tự động hủy 3 đặt cọc quá hạn thanh toán."
             * 
             * else - Nếu không có deposit nào được hủy ($cancelledCount = 0)
             * - Hiển thị message thông báo không có deposit nào quá hạn
             */
            if ($cancelledCount > 0) {
                $this->info("Đã tự động hủy {$cancelledCount} đặt cọc quá hạn thanh toán.");
            } else {
                $this->info('Không có đặt cọc nào quá hạn thanh toán.');
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
             * - 'Error auto-cancelling overdue deposits: ' . $e->getMessage()
             *   - $e->getMessage() trả về error message của exception (ví dụ: "SQLSTATE[42S22]: Column not found")
             *   - Dấu . là string concatenation operator trong PHP
             * 
             * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
             * - 'Có lỗi xảy ra khi tự động hủy đặt cọc quá hạn: ' . $e->getMessage()
             * - Hiển thị lỗi cho người dùng để họ biết command đã thất bại
             * 
             * return Command::FAILURE - Trả về Command::FAILURE (giá trị = 1) để báo command thất bại
             * - Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thất bại không
             * - Có thể trigger alert hoặc retry logic
             */
            DB::rollBack();
            Log::error('Error auto-cancelling overdue deposits: ' . $e->getMessage());
            $this->error('Có lỗi xảy ra khi tự động hủy đặt cọc quá hạn: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
