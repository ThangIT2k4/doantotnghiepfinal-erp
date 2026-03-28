<?php

namespace App\Console\Commands;

use App\Models\BookingDeposit;
use App\Models\Unit;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command: AutoExpireHoldUntilDeposits
 * 
 * MỤC ĐÍCH:
 * Tự động cập nhật trạng thái hết hạn cho các đặt cọc quá thời gian giữ chỗ (hold_until).
 * Command này được chạy định kỳ để kiểm tra và cập nhật trạng thái đặt cọc đã quá thời gian giữ chỗ.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận dữ liệu từ: Model BookingDeposit (bảng booking_deposits)
 * 2. Tìm các đặt cọc quá thời gian giữ chỗ:
 *    - payment_status = 'pending' hoặc 'paid'
 *    - hold_until <= thời gian hiện tại (đã quá thời gian giữ chỗ)
 *    - payment_status != 'expired' và != 'cancelled'
 * 3. Xử lý cho mỗi đặt cọc:
 *    - Cập nhật payment_status = 'expired' và expired_at = now
 *    - Hủy các invoice liên quan (nếu chưa thanh toán)
 *    - Cập nhật trạng thái unit về 'available' (nếu không còn đặt cọc/lease khác)
 * 4. Ghi log: Lưu thông tin vào Log để theo dõi
 * 
 * CÁCH CHẠY:
 * php artisan deposits:auto-expire-hold-until
 */
class AutoExpireHoldUntilDeposits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deposits:auto-expire-hold-until';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động cập nhật trạng thái hết hạn cho các đặt cọc quá thời gian giữ chỗ (hold_until)';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Bắt đầu transaction để đảm bảo tính nhất quán dữ liệu
     * 2. Query từ bảng booking_deposits:
     *    - Tìm các deposit có payment_status = 'pending' hoặc 'paid'
     *    - Có hold_until không null
     *    - hold_until <= now() (đã quá thời gian giữ chỗ)
     *    - payment_status != 'expired' và != 'cancelled' (chưa bị expired/cancelled)
     *    - deleted_at = null (chưa bị xóa)
     * 3. Với mỗi deposit quá thời gian giữ chỗ:
     *    a. Cập nhật payment_status = 'expired' và expired_at = now
     *    b. Tìm các invoice liên quan (qua relationship deposit->invoices())
     *    c. Đánh dấu các invoice chưa thanh toán thành 'expired'
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
     * - Cập nhật bảng invoices (status = 'expired')
     * - Cập nhật bảng units (status = 'available')
     * - Ghi log vào storage/logs/laravel.log
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle()
    {
        $this->info('Đang kiểm tra các đặt cọc quá thời gian giữ chỗ...');

        try {
            /**
             * Bắt đầu transaction để đảm bảo tính nhất quán dữ liệu
             * 
             * Nếu có lỗi xảy ra ở bất kỳ bước nào, tất cả thay đổi sẽ được rollback
             */
            DB::beginTransaction();

            /**
             * Tìm các đặt cọc quá thời gian giữ chỗ từ bảng booking_deposits
             * 
             * Điều kiện:
             * - payment_status = 'pending' hoặc 'paid' (chưa expired/cancelled)
             * - hold_until không null (phải có thời gian giữ chỗ)
             * - hold_until <= now() (đã quá thời gian giữ chỗ)
             * - payment_status != 'expired' và != 'cancelled' (tránh xử lý lại)
             * - deleted_at = null (chưa bị xóa)
             */
            $expiredDeposits = BookingDeposit::whereIn('payment_status', ['pending', 'paid'])
                ->whereNotNull('hold_until')
                ->where('hold_until', '<=', now())
                ->where('payment_status', '!=', 'expired')
                ->where('payment_status', '!=', 'cancelled')
                ->whereNull('deleted_at')
                ->get();

            $expiredCount = 0;

            /**
             * Xử lý từng đặt cọc quá thời gian giữ chỗ
             * 
             * Với mỗi deposit:
             * 1. Cập nhật trạng thái deposit
             * 2. Đánh dấu các invoice liên quan
             * 3. Cập nhật trạng thái unit (nếu cần)
             */
            /**
             * Xử lý từng đặt cọc quá thời gian giữ chỗ bằng vòng lặp foreach
             * 
             * foreach ($expiredDeposits as $deposit) - Lặp qua từng phần tử trong Collection
             * - $expiredDeposits là Collection chứa các BookingDeposit models đã query được
             * - $deposit là từng BookingDeposit model trong Collection
             * - Mỗi lần lặp, $deposit sẽ là một instance của App\Models\BookingDeposit
             */
            foreach ($expiredDeposits as $deposit) {
                /**
                 * Lưu trạng thái ban đầu của deposit để ghi log sau này
                 * 
                 * $deposit->payment_status - Truy cập field payment_status từ BookingDeposit model
                 *   - payment_status có thể là: 'pending', 'paid', 'expired', 'cancelled', etc.
                 *   - Lưu vào biến $originalStatus để so sánh sau khi update
                 *   - Sử dụng trong log để biết trạng thái trước khi thay đổi
                 */
                $originalStatus = $deposit->payment_status;
                
                /**
                 * Bước 1: Cập nhật trạng thái deposit thành 'expired' sử dụng Eloquent update()
                 * 
                 * $deposit->update([...]) - Gọi method update() trên BookingDeposit model instance
                 * - update() nhận một array các field cần cập nhật
                 * - Method này sẽ:
                 *   1. Tạo SQL UPDATE query: UPDATE booking_deposits SET payment_status='expired', expired_at='2024-01-15 10:00:00' WHERE id=$deposit->id
                 *   2. Thực thi query trong transaction hiện tại
                 *   3. Cập nhật các attribute trong model instance
                 *   4. Trigger các Eloquent events (updating, updated)
                 * 
                 * 'payment_status' => 'expired' - Cập nhật field payment_status trong database thành giá trị 'expired'
                 *   - 'expired' nghĩa là đặt cọc đã hết hạn (quá thời gian giữ chỗ)
                 *   - Status này sẽ được sử dụng để filter, hiển thị, và xử lý các deposit hết hạn
                 * 
                 * 'expired_at' => now() - Cập nhật field expired_at với thời gian hiện tại
                 *   - now() trả về Carbon instance với thời gian hiện tại (giờ, phút, giây)
                 *   - expired_at lưu thời gian chính xác khi deposit bị hết hạn
                 *   - Có thể dùng để phân tích, báo cáo sau này
                 */
                $deposit->update([
                    'payment_status' => 'expired',
                    'expired_at' => now(),
                ]);

                /**
                 * Bước 2: Đánh dấu các invoice liên quan thành 'expired'
                 * 
                 * $deposit->invoices() - Truy cập relationship invoices() từ BookingDeposit model
                 *   - invoices() là method trong BookingDeposit model, trả về HasMany relationship
                 *   - Relationship này query từ bảng invoices với điều kiện booking_deposit_id = $deposit->id
                 *   - Trả về query builder, chưa thực thi query
                 * 
                 * ->where('status', '!=', 'paid') - Lọc các invoice chưa thanh toán
                 *   - '!=' là SQL NOT EQUAL operator
                 *   - Chỉ lấy các invoice có status khác 'paid' (chưa thanh toán)
                 *   - Không cần đánh dấu expired cho invoice đã thanh toán
                 * 
                 * ->where('status', '!=', 'cancelled') - Lọc các invoice chưa bị hủy
                 *   - Chỉ lấy các invoice có status khác 'cancelled' (chưa bị hủy)
                 *   - Không cần đánh dấu expired cho invoice đã bị hủy
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
                     * Cập nhật trạng thái invoice thành 'expired' sử dụng Eloquent update()
                     * 
                     * $invoice->update([...]) - Gọi method update() trên Invoice model instance
                     * - update() nhận một array các field cần cập nhật
                     * - Method này sẽ:
                     *   1. Tạo SQL UPDATE query: UPDATE invoices SET status='expired' WHERE id=$invoice->id
                     *   2. Thực thi query trong transaction hiện tại
                     *   3. Cập nhật các attribute trong model instance
                     *   4. Trigger các Eloquent events (updating, updated)
                     * 
                     * 'status' => 'expired' - Cập nhật field status trong database thành giá trị 'expired'
                     *   - 'expired' nghĩa là invoice đã hết hạn (do deposit hết hạn)
                     *   - Invoice hết hạn sẽ không còn hiệu lực, không cần thanh toán nữa
                     */
                    $invoice->update([
                        'status' => 'expired',
                    ]);
                    
                    /**
                     * Ghi log thông tin invoice đã được đánh dấu expired
                     * 
                     * Log::info() - Ghi log với level INFO (thông tin bình thường)
                     * - Log được ghi vào: storage/logs/laravel.log
                     * - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
                     * 
                     * Tham số 1: 'Invoice automatically expired due to booking deposit expired' - Message mô tả hành động
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
                    Log::info('Invoice automatically expired due to booking deposit expired', [
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
                     *   - 'pending' - Đang chờ thanh toán
                     *   - 'paid' - Đã thanh toán (nhưng chưa hết hạn)
                     *   - Các status này được coi là "active" (còn hiệu lực)
                     * 
                     * ->whereNull('deleted_at') - Chỉ lấy các deposit chưa bị soft delete
                     *   - whereNull() kiểm tra column = NULL trong database
                     *   - deleted_at = NULL nghĩa là chưa bị xóa
                     * 
                     * ->where(function($q) { ... }) - Nhóm các điều kiện với OR logic
                     *   - where(function($q) { ... }) tạo một subquery với điều kiện OR
                     *   - $q->whereNull('hold_until') - Deposit không có thời gian giữ chỗ (hold_until = NULL)
                     *     - Nếu hold_until = NULL, deposit không bao giờ hết hạn
                     *   - ->orWhere('hold_until', '>', now()) - HOẶC deposit có hold_until > thời gian hiện tại (chưa hết hạn)
                     *     - '>' là SQL GREATER THAN operator
                     *     - now() trả về Carbon instance với thời gian hiện tại
                     *   - Kết quả: deposit có hold_until = NULL HOẶC hold_until > now() (chưa hết hạn)
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
                        ->where(function($q) {
                            $q->whereNull('hold_until')
                              ->orWhere('hold_until', '>', now());
                        })
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
                        
                        /**
                         * Ghi log thông tin unit đã được cập nhật status
                         * 
                         * Log::info() - Ghi log với level INFO (thông tin bình thường)
                         * - Log được ghi vào: storage/logs/laravel.log
                         * 
                         * Tham số 1: 'Unit status updated to available after deposit expired' - Message mô tả hành động
                         * 
                         * Tham số 2: Array chứa context data
                         * - 'unit_id' => $unit->id - ID của unit đã được cập nhật
                         * - 'booking_deposit_id' => $deposit->id - ID của deposit gây ra thay đổi
                         * 
                         * Mục đích log:
                         * - Theo dõi lịch sử thay đổi trạng thái unit
                         * - Debug khi có vấn đề
                         * - Audit trail (dấu vết kiểm toán)
                         */
                        Log::info('Unit status updated to available after deposit expired', [
                            'unit_id' => $unit->id,
                            'booking_deposit_id' => $deposit->id,
                        ]);
                    }
                }

                // Tăng biến đếm lên 1 sau mỗi lần xử lý deposit thành công
                // $expiredCount++ tương đương với $expiredCount = $expiredCount + 1
                $expiredCount++;

                /**
                 * Ghi log thông tin deposit đã hết hạn
                 * 
                 * Log::info() - Ghi log với level INFO (thông tin bình thường)
                 * - Log được ghi vào: storage/logs/laravel.log
                 * - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
                 * 
                 * Tham số 1: 'Booking deposit automatically expired due to hold_until date passed' - Message mô tả hành động
                 * 
                 * Tham số 2: Array chứa context data
                 * - 'booking_deposit_id' => $deposit->id - ID của deposit trong database (primary key)
                 * - 'reference_number' => $deposit->reference_number - Số tham chiếu deposit (mã định danh do người dùng thấy)
                 * - 'hold_until' => $deposit->hold_until - Ngày hết hạn giữ chỗ (Carbon date instance)
                 * - 'unit_id' => $deposit->unit_id - ID của unit liên quan (foreign key)
                 * - 'previous_payment_status' => $originalStatus - Trạng thái trước khi thay đổi (đã lưu ở đầu method)
                 * 
                 * Mục đích log:
                 * - Theo dõi lịch sử thay đổi trạng thái deposit
                 * - Debug khi có vấn đề
                 * - Audit trail (dấu vết kiểm toán)
                 * - Phân tích số liệu sau này
                 */
                Log::info('Booking deposit automatically expired due to hold_until date passed', [
                    'booking_deposit_id' => $deposit->id,
                    'reference_number' => $deposit->reference_number,
                    'hold_until' => $deposit->hold_until,
                    'unit_id' => $deposit->unit_id,
                    'previous_payment_status' => $originalStatus,
                ]);
            }

            /**
             * Commit transaction - xác nhận tất cả thay đổi
             * 
             * Nếu đến bước này mà không có lỗi, tất cả thay đổi sẽ được lưu vào database
             */
            DB::commit();

            if ($expiredCount > 0) {
                $this->info("Đã tự động cập nhật {$expiredCount} đặt cọc quá thời gian giữ chỗ thành hết hạn.");
            } else {
                $this->info('Không có đặt cọc nào quá thời gian giữ chỗ.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            /**
             * Xử lý lỗi: Rollback transaction và ghi log
             * 
             * Nếu có lỗi xảy ra:
             * - Rollback tất cả thay đổi (không lưu gì vào database)
             * - Ghi log lỗi
             * - Hiển thị lỗi cho người dùng
             * - Trả về Command::FAILURE
             */
            DB::rollBack();
            Log::error('Error auto-expiring hold_until deposits: ' . $e->getMessage(), [
                'error' => $e->getTraceAsString()
            ]);
            $this->error('Có lỗi xảy ra khi tự động cập nhật trạng thái hết hạn: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

