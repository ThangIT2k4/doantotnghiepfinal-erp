<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrganizationSubscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Command: AutoCancelExpiredSubscriptions
 * 
 * MỤC ĐÍCH:
 * Tự động hủy các subscription đã hết hạn (trial hoặc active) trong hệ thống.
 * Command này được chạy định kỳ (thường qua cron job) để kiểm tra và cập nhật trạng thái subscription.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận dữ liệu từ: Model OrganizationSubscription (bảng organization_subscriptions)
 * 2. Tìm các subscription đã hết hạn dựa trên điều kiện:
 *    - Status = 'trial' hoặc 'active'
 *    - current_period_end < thời gian hiện tại
 *    - Không có auto_renew hoặc đã bị cancelled_at
 * 3. Xử lý: Cập nhật trạng thái subscription thành 'cancelled' hoặc 'expired'
 * 4. Ghi log: Lưu thông tin vào Log để theo dõi
 * 
 * CÁCH CHẠY:
 * php artisan subscriptions:auto-cancel-expired
 */
class AutoCancelExpiredSubscriptions extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * @var string
     */
    protected $signature = 'subscriptions:auto-cancel-expired';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Tự động hủy các subscription đã hết hạn (trial hoặc active)';

    /**
     * Service xử lý subscription (được inject qua constructor)
     * 
     * @var SubscriptionService
     */
    protected $subscriptionService;

    /**
     * Khởi tạo command
     * 
     * Nhận SubscriptionService từ Laravel container (dependency injection)
     * Service này nằm tại: app/Services/Subscription/SubscriptionService.php
     * 
     * @param SubscriptionService $subscriptionService Service xử lý subscription
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy thời gian hiện tại (Carbon::now())
     * 2. Query từ bảng organization_subscriptions:
     *    - Tìm các subscription có status = 'trial' hoặc 'active'
     *    - Có current_period_end không null
     *    - current_period_end < thời gian hiện tại (đã hết hạn)
     *    - Không có auto_renew = true HOẶC đã có cancelled_at
     * 3. Với mỗi subscription tìm được:
     *    - Nếu đã có cancelled_at: Cập nhật status = 'expired'
     *    - Nếu chưa có cancelled_at: Cập nhật status = 'cancelled' và set cancelled_at = now
     * 4. Ghi log thông tin subscription đã hủy
     * 5. Trả về kết quả thành công hoặc thất bại
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\OrganizationSubscription
     * - Bảng database: organization_subscriptions
     * 
     * DỮ LIỆU GHI VÀO:
     * - Cập nhật bảng organization_subscriptions (status, cancelled_at)
     * - Ghi log vào storage/logs/laravel.log
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle()
    {
        // Hiển thị thông báo bắt đầu cho người dùng khi chạy command
        // $this->info() là method của Laravel Command để hiển thị message màu xanh trong console
        $this->info('Đang kiểm tra và hủy các subscription đã hết hạn...');

        try {
            /**
             * Lấy thời gian hiện tại và lưu vào biến $now
             * 
             * Carbon::now() - Tạo Carbon instance với thời gian hiện tại (giờ, phút, giây, ngày, tháng, năm)
             * - Carbon là thư viện xử lý date/time trong Laravel, cung cấp nhiều method tiện ích
             * - $now sẽ được sử dụng để so sánh với current_period_end của subscriptions
             * - Ví dụ: 2024-01-15 10:30:00
             */
            $now = Carbon::now();
            
            // Khởi tạo biến đếm số lượng subscription đã được hủy
            // Biến này sẽ được tăng lên mỗi khi hủy một subscription thành công
            $cancelledCount = 0;

            /**
             * Tìm các subscription đã hết hạn từ bảng organization_subscriptions sử dụng Eloquent ORM
             * 
             * OrganizationSubscription::whereIn('status', ['trial', 'active') - Bắt đầu query builder từ model OrganizationSubscription
             *   - whereIn('status', ['trial', 'active']) lọc các subscription có status trong array ['trial', 'active']
             *   - whereIn() kiểm tra status có trong array không (SQL IN operator)
             *   - 'trial' nghĩa là subscription đang trong thời gian trial (chưa thanh toán)
             *   - 'active' nghĩa là subscription đang hoạt động (đã thanh toán)
             *   - Chỉ hủy các subscription đang hoạt động, không hủy 'expired', 'cancelled' (đã hủy rồi)
             * 
             * ->whereNotNull('current_period_end') - Chỉ lấy các subscription có current_period_end không null
             *   - whereNotNull() kiểm tra column không phải NULL trong database
             *   - current_period_end là ngày kết thúc chu kỳ hiện tại (phải có để biết khi nào hết hạn)
             *   - Nếu current_period_end = NULL, không thể xác định subscription đã hết hạn hay chưa
             * 
             * ->where('current_period_end', '<', $now) - Lọc các subscription có current_period_end nhỏ hơn thời gian hiện tại (đã hết hạn)
             *   - '<' là SQL LESS THAN operator
             *   - current_period_end < now nghĩa là subscription đã hết hạn (current_period_end đã qua)
             *   - Ví dụ: Nếu current_period_end = 2024-01-10 và now() = 2024-01-15, thì subscription đã hết hạn 5 ngày
             * 
             * ->where(function($query) { ... }) - Nhóm các điều kiện với OR logic
             *   - where(function($query) { ... }) tạo một subquery với điều kiện OR
             *   - $query->where('auto_renew', false) - Subscription không có auto_renew (auto_renew = false)
             *     - Nếu auto_renew = false, subscription sẽ không được gia hạn tự động, có thể hủy
             *   - ->orWhereNotNull('cancelled_at') - HOẶC subscription đã bị cancelled (cancelled_at != NULL)
             *     - orWhereNotNull() kiểm tra column != NULL trong database
             *     - cancelled_at != NULL nghĩa là subscription đã được đánh dấu hủy trước đó
             *   - Kết quả: subscription có auto_renew = false HOẶC cancelled_at != NULL (có thể hủy)
             *   - Logic này tránh hủy các subscription đang auto_renew (auto_renew = true và cancelled_at = NULL)
             *     - Subscription đang auto_renew sẽ được gia hạn tự động bởi AutoRenewSubscriptions command
             *     - Không nên hủy subscription đang auto_renew vì nó sẽ được gia hạn
             * 
             * ->get() - Thực thi query và trả về Collection chứa các OrganizationSubscription models
             *   - get() sẽ execute SQL query và trả về kết quả
             *   - SQL tương đương: SELECT * FROM organization_subscriptions WHERE status IN ('trial', 'active') AND current_period_end IS NOT NULL AND current_period_end < '2024-01-15 10:30:00' AND (auto_renew = 0 OR cancelled_at IS NOT NULL)
             */
            $expiredSubscriptions = OrganizationSubscription::whereIn('status', ['trial', 'active'])
                ->whereNotNull('current_period_end')
                ->where('current_period_end', '<', $now)
                ->where(function($query) {
                    // Chỉ hủy nếu không có auto_renew hoặc đã bị cancelled
                    $query->where('auto_renew', false)
                          ->orWhereNotNull('cancelled_at');
                })
                ->get();

            /**
             * Xử lý từng subscription đã hết hạn bằng vòng lặp foreach
             * 
             * foreach ($expiredSubscriptions as $subscription) - Lặp qua từng phần tử trong Collection
             * - $expiredSubscriptions là Collection chứa các OrganizationSubscription models đã query được
             * - $subscription là từng OrganizationSubscription model trong Collection
             * - Mỗi lần lặp, $subscription sẽ là một instance của App\Models\OrganizationSubscription
             */
            foreach ($expiredSubscriptions as $subscription) {
                /**
                 * Xử lý lỗi riêng cho từng subscription (không dừng toàn bộ process)
                 * 
                 * try { ... } catch { ... } - Xử lý lỗi khi hủy một subscription cụ thể
                 * - Nếu hủy subscription thành công: tiếp tục
                 * - Nếu hủy subscription thất bại: catch exception, ghi log, nhưng tiếp tục xử lý subscription tiếp theo
                 * - Đảm bảo command không bị dừng vì lỗi của một subscription
                 * - Các subscription khác vẫn có thể được xử lý tiếp
                 */
                try {
                    /**
                     * Cập nhật trạng thái subscription dựa trên trạng thái hiện tại
                     * 
                     * if ($subscription->cancelled_at) - Kiểm tra xem subscription đã có cancelled_at chưa
                     *   - $subscription->cancelled_at truy cập field cancelled_at từ OrganizationSubscription model
                     *   - cancelled_at là Carbon date instance hoặc NULL
                     *   - Nếu cancelled_at != NULL, subscription đã được đánh dấu hủy trước đó (có thể là manual cancel)
                     * 
                     * Nếu đã có cancelled_at (đã được hủy trước đó):
                     *   - $subscription->update(['status' => 'expired']) - Cập nhật status thành 'expired'
                     *     - update() nhận một array các field cần cập nhật
                     *     - 'status' => 'expired' - Cập nhật field status trong database thành giá trị 'expired'
                     *     - 'expired' nghĩa là subscription đã hết hạn hoàn toàn (đã được hủy trước đó, giờ hết hạn)
                     *     - Không cập nhật cancelled_at vì đã có sẵn (giữ nguyên giá trị cũ)
                     *     - SQL tương đương: UPDATE organization_subscriptions SET status='expired' WHERE id=$subscription->id
                     * 
                     * else (chưa có cancelled_at, vừa mới hết hạn):
                     *   - $subscription->update([...]) - Cập nhật status và cancelled_at
                     *     - 'status' => 'cancelled' - Cập nhật field status thành 'cancelled'
                     *       - 'cancelled' nghĩa là subscription vừa mới bị hủy (do hết hạn)
                     *     - 'cancelled_at' => $now - Cập nhật field cancelled_at với thời gian hiện tại
                     *       - $now là Carbon instance với thời gian hiện tại
                     *       - cancelled_at lưu thời gian chính xác khi subscription bị hủy
                     *       - Có thể dùng để phân tích, báo cáo sau này
                     *     - SQL tương đương: UPDATE organization_subscriptions SET status='cancelled', cancelled_at='2024-01-15 10:30:00' WHERE id=$subscription->id
                     */
                    if ($subscription->cancelled_at) {
                        // Đã được hủy trước đó, giờ chuyển sang expired
                        $subscription->update(['status' => 'expired']);
                    } else {
                        // Vừa mới hủy, set cancelled_at
                        $subscription->update([
                            'status' => 'cancelled',
                            'cancelled_at' => $now,
                        ]);
                    }

                    // Tăng biến đếm lên 1 sau mỗi lần hủy subscription thành công
                    // $cancelledCount++ tương đương với $cancelledCount = $cancelledCount + 1
                    $cancelledCount++;

                    /**
                     * Ghi log thông tin subscription đã hủy
                     * 
                     * Log::info() - Ghi log với level INFO (thông tin bình thường)
                     * - Log được ghi vào: storage/logs/laravel.log
                     * - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
                     * 
                     * Tham số 1: 'Subscription tự động hủy do hết hạn' - Message mô tả hành động
                     * 
                     * Tham số 2: Array chứa context data
                     * - 'subscription_id' => $subscription->id - ID của subscription trong database (primary key)
                     * - 'organization_id' => $subscription->organization_id - ID của organization liên quan (foreign key)
                     * - 'plan_id' => $subscription->plan_id - ID của subscription plan liên quan (foreign key)
                     * - 'status' => $subscription->status - Trạng thái mới của subscription (sau khi update)
                     *   - Có thể là 'cancelled' hoặc 'expired' tùy vào điều kiện ở trên
                     * - 'current_period_end' => $subscription->current_period_end?->toDateTimeString() - Ngày hết hạn
                     *   - $subscription->current_period_end là Carbon date instance (hoặc null)
                     *   - ?-> là null-safe operator (chỉ gọi toDateTimeString() nếu current_period_end != null)
                     *   - toDateTimeString() chuyển Carbon instance thành string format YYYY-MM-DD HH:MM:SS
                     *   - Ví dụ: "2024-01-10 00:00:00"
                     * - 'auto_renew' => $subscription->auto_renew - Giá trị auto_renew (true/false)
                     * 
                     * Mục đích log:
                     * - Theo dõi lịch sử thay đổi trạng thái subscription
                     * - Debug khi có vấn đề
                     * - Audit trail (dấu vết kiểm toán)
                     * - Phân tích số liệu sau này
                     */
                    Log::info('Subscription tự động hủy do hết hạn', [
                        'subscription_id' => $subscription->id,
                        'organization_id' => $subscription->organization_id,
                        'plan_id' => $subscription->plan_id,
                        'status' => $subscription->status,
                        'current_period_end' => $subscription->current_period_end?->toDateTimeString(),
                        'auto_renew' => $subscription->auto_renew,
                    ]);

                } catch (\Exception $e) {
                    /**
                     * Xử lý lỗi khi hủy từng subscription
                     * 
                     * catch (\Exception $e) - Bắt exception khi hủy subscription thất bại
                     * - Có thể là: database error, validation error, etc.
                     * - $e là exception object chứa thông tin về lỗi
                     * 
                     * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
                     * - Log được ghi vào: storage/logs/laravel.log
                     * - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message {context}
                     * 
                     * Tham số 1: 'Lỗi khi hủy subscription: ' . $e->getMessage()
                     *   - $e->getMessage() trả về error message của exception
                     *   - Dấu . là string concatenation operator trong PHP
                     * 
                     * Tham số 2: Array chứa context data
                     * - 'subscription_id' => $subscription->id - ID của subscription gặp lỗi (để debug)
                     * - 'error' => $e->getTraceAsString() - Stack trace của exception (chi tiết lỗi)
                     *   - getTraceAsString() trả về string chứa stack trace (file, line, function calls)
                     *   - Giúp debug khi cần biết lỗi xảy ra ở đâu trong code
                     * 
                     * Lưu ý: Không throw exception lại, chỉ ghi log
                     * - Đảm bảo command không bị dừng vì lỗi của một subscription
                     * - Các subscription khác vẫn có thể được xử lý tiếp
                     * - Process sẽ tiếp tục với subscription tiếp theo trong vòng lặp
                     */
                    Log::error('Lỗi khi hủy subscription: ' . $e->getMessage(), [
                        'subscription_id' => $subscription->id,
                        'error' => $e->getTraceAsString()
                    ]);
                }
            }

            /**
             * Hiển thị kết quả cho người dùng dựa trên số lượng subscription đã xử lý
             * 
             * if ($cancelledCount > 0) - Kiểm tra xem có subscription nào được hủy không
             * - $cancelledCount > 0 nghĩa là có ít nhất 1 subscription đã được hủy
             * 
             * $this->info() - Hiển thị message màu xanh trong console
             * - "Đã tự động hủy {$cancelledCount} subscription hết hạn."
             * - {$cancelledCount} là string interpolation, sẽ thay thế bằng giá trị của biến $cancelledCount
             * - Ví dụ: Nếu $cancelledCount = 5, message sẽ là "Đã tự động hủy 5 subscription hết hạn."
             * 
             * else - Nếu không có subscription nào được hủy ($cancelledCount = 0)
             * - Hiển thị message thông báo không có subscription nào hết hạn
             */
            if ($cancelledCount > 0) {
                $this->info("Đã tự động hủy {$cancelledCount} subscription hết hạn.");
            } else {
                $this->info('Không có subscription nào hết hạn.');
            }

            // Trả về Command::SUCCESS (giá trị = 0) để báo cho Laravel biết command đã chạy thành công
            // Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thành công không
            return Command::SUCCESS;

        } catch (\Exception $e) {
            /**
             * Xử lý lỗi tổng thể của command (lỗi nghiêm trọng)
             * 
             * catch (\Exception $e) - Bắt bất kỳ exception nào xảy ra trong block try
             * - \Exception là base class của tất cả exceptions trong PHP
             * - $e là exception object chứa thông tin về lỗi
             * - Lỗi này có thể là: database connection error, query error, etc.
             * - Khác với lỗi trong foreach (chỉ ảnh hưởng một subscription), lỗi này ảnh hưởng toàn bộ command
             * 
             * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
             * - 'Lỗi khi chạy command: ' . $e->getMessage()
             * - $e->getMessage() trả về error message của exception
             * - Dấu . là string concatenation operator trong PHP
             * - Hiển thị lỗi cho người dùng để họ biết command đã thất bại
             * 
             * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
             * - Log được ghi vào: storage/logs/laravel.log
             * - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message
             * - 'Lỗi trong AutoCancelExpiredSubscriptions command: ' . $e->getMessage()
             *   - Ghi log để theo dõi và debug sau này
             * 
             * return Command::FAILURE - Trả về Command::FAILURE (giá trị = 1) để báo command thất bại
             * - Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thất bại không
             * - Có thể trigger alert hoặc retry logic
             */
            $this->error('Lỗi khi chạy command: ' . $e->getMessage());
            Log::error('Lỗi trong AutoCancelExpiredSubscriptions command: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
