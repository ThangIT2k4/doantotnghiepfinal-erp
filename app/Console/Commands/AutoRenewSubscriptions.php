<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrganizationSubscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Command: AutoRenewSubscriptions
 * 
 * MỤC ĐÍCH:
 * Tự động gia hạn subscription bằng cách tạo subscription mới với trial và invoice
 * khi subscription cũ sắp hết hạn (7 ngày trước khi hết hạn).
 * Command này được chạy định kỳ để đảm bảo subscription được gia hạn tự động.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận dữ liệu từ: Model OrganizationSubscription (bảng organization_subscriptions)
 * 2. Tìm các subscription cần gia hạn:
 *    - Status = 'active' (chỉ active subscriptions mới auto_renew)
 *    - auto_renew = true (đã bật tính năng tự động gia hạn)
 *    - current_period_end <= (now + 7 ngày) (sắp hết hạn trong 7 ngày)
 *    - current_period_end > now (chưa hết hạn)
 *    - Chưa có subscription trial mới cho organization này
 * 3. Xử lý: Gọi SubscriptionService->createRenewalSubscription() để tạo subscription mới
 * 4. Ghi log: Lưu thông tin vào Log để theo dõi
 * 
 * CÁCH CHẠY:
 * php artisan subscriptions:auto-renew
 */
class AutoRenewSubscriptions extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * @var string
     */
    protected $signature = 'subscriptions:auto-renew';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Tự động gia hạn subscription: tạo subscription mới với trial và invoice khi subscription cũ sắp hết hạn';

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
     * 1. Lấy thời gian hiện tại và tính ngày gia hạn (7 ngày trước khi hết hạn)
     * 2. Query từ bảng organization_subscriptions:
     *    - Tìm các subscription có status = 'active'
     *    - auto_renew = true
     *    - current_period_end <= (now + 7 ngày) và > now (sắp hết hạn trong 7 ngày)
     *    - Chưa bị cancelled (cancelled_at = null)
     *    - Load relationship: organization, plan
     * 3. Lọc các subscription chưa có subscription trial mới:
     *    - Kiểm tra xem đã có subscription trial nào được tạo sau subscription hiện tại chưa
     * 4. Với mỗi subscription cần gia hạn:
     *    - Gọi SubscriptionService->createRenewalSubscription() để tạo subscription mới
     *    - Service này sẽ:
     *      + Tạo subscription mới với status = 'trial' (10 ngày trial)
     *      + Tạo invoice để thanh toán
     *      + Ghi log
     * 5. Ghi log và hiển thị kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\OrganizationSubscription (bảng organization_subscriptions)
     * - Relationship: organization, plan (qua with())
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng organization_subscriptions (qua SubscriptionService)
     * - Tạo bản ghi mới trong bảng subscription_invoices (qua SubscriptionService)
     * - Ghi log vào storage/logs/laravel.log
     * 
     * SERVICE ĐƯỢC GỌI:
     * - App\Services\Subscription\SubscriptionService::createRenewalSubscription()
     *   File: app/Services/Subscription/SubscriptionService.php
     *   Method này sẽ:
     *   + Tạo subscription mới với status = 'trial', trial_days = 10
     *   + Tạo invoice để thanh toán
     *   + Ghi log
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle()
    {
        // Hiển thị thông báo bắt đầu cho người dùng khi chạy command
        // $this->info() là method của Laravel Command để hiển thị message màu xanh trong console
        $this->info('Đang kiểm tra và tạo subscription mới cho auto_renew...');

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
            
            /**
             * Tính ngày gia hạn: Tạo subscription mới 7 ngày trước khi hết hạn
             * 
             * $renewalDays = 7 - Số ngày trước khi hết hạn để tạo subscription mới
             *   - 7 ngày là khoảng thời gian đủ để organization thanh toán invoice
             *   - Có thể điều chỉnh giá trị này nếu cần
             * 
             * $renewalDate = $now->copy()->addDays($renewalDays) - Tính ngày gia hạn
             *   - $now->copy() - Tạo một bản copy của Carbon instance $now
             *     - copy() cần thiết vì addDays() sẽ modify Carbon instance gốc
             *     - Nếu không copy, $now sẽ bị thay đổi và ảnh hưởng đến các lần sử dụng sau
             *   - ->addDays($renewalDays) - Thêm 7 ngày vào ngày hiện tại
             *     - addDays() trả về Carbon instance mới với ngày đã được cộng thêm
             *     - Ví dụ: Nếu $now = 2024-01-15, thì $renewalDate = 2024-01-22
             * 
             * Logic: Nếu subscription có current_period_end <= $renewalDate (và > $now)
             *   => Subscription sắp hết hạn trong 7 ngày, cần tạo subscription mới
             * 
             * Ví dụ: 
             * - Nếu subscription hết hạn vào ngày 15/01 (current_period_end = 2024-01-15)
             * - Và hôm nay là 08/01 ($now = 2024-01-08)
             * - Thì $renewalDate = 2024-01-15 (08 + 7 = 15)
             * - current_period_end (15) <= renewalDate (15) => Cần tạo subscription mới
             */
            $renewalDays = 7;
            $renewalDate = $now->copy()->addDays($renewalDays);
            
            // Khởi tạo biến đếm số lượng subscription đã được gia hạn
            // Biến này sẽ được tăng lên mỗi khi tạo subscription mới thành công
            $renewedCount = 0;

            /**
             * Tìm các subscription cần auto_renew từ bảng organization_subscriptions sử dụng Eloquent ORM
             * 
             * OrganizationSubscription::where('status', 'active') - Bắt đầu query builder từ model OrganizationSubscription
             *   - where('status', 'active') lọc các subscription có status = 'active' (đang hoạt động)
             *   - Chỉ active subscriptions mới có thể auto_renew (trial, expired, cancelled không auto_renew)
             * 
             * ->where('auto_renew', true) - Lọc các subscription đã bật tính năng tự động gia hạn
             *   - auto_renew là boolean field trong database
             *   - true nghĩa là organization đã bật tính năng tự động gia hạn
             *   - Nếu false, subscription sẽ không được gia hạn tự động
             * 
             * ->whereNotNull('current_period_end') - Chỉ lấy các subscription có current_period_end không null
             *   - whereNotNull() kiểm tra column không phải NULL trong database
             *   - current_period_end là ngày kết thúc chu kỳ hiện tại (phải có để biết khi nào hết hạn)
             * 
             * ->where('current_period_end', '<=', $renewalDate) - Lọc các subscription sắp hết hạn trong 7 ngày
             *   - '<=' là SQL LESS THAN OR EQUAL operator
             *   - current_period_end <= renewalDate nghĩa là subscription sẽ hết hạn trong vòng 7 ngày tới
             *   - Ví dụ: Nếu renewalDate = 2024-01-22, thì subscription có current_period_end <= 2024-01-22 sẽ được chọn
             * 
             * ->where('current_period_end', '>', $now) - Lọc các subscription chưa hết hạn
             *   - '>' là SQL GREATER THAN operator
             *   - current_period_end > now nghĩa là subscription chưa hết hạn (vẫn còn hiệu lực)
             *   - Tránh xử lý các subscription đã hết hạn (current_period_end <= now)
             * 
             * ->whereNull('cancelled_at') - Chỉ lấy các subscription chưa bị hủy
             *   - whereNull() kiểm tra column = NULL trong database
             *   - cancelled_at = NULL nghĩa là subscription chưa bị hủy
             *   - Nếu cancelled_at != NULL, subscription đã bị hủy, không cần gia hạn
             * 
             * ->with(['organization', 'plan']) - Eager load relationships để tránh N+1 queries
             *   - with() sẽ load các relationship trước khi sử dụng
             *   - 'organization' - Load Organization model liên quan
             *   - 'plan' - Load SubscriptionPlan model liên quan
             *   - Nếu không dùng with(), mỗi lần truy cập $subscription->organization sẽ tạo một query mới (N+1 problem)
             *   - Với with(), tất cả data được load trong 1 query duy nhất (sử dụng JOIN hoặc separate queries)
             * 
             * ->get() - Thực thi query và trả về Collection chứa các OrganizationSubscription models
             *   - get() sẽ execute SQL query và trả về kết quả
             *   - SQL tương đương: SELECT * FROM organization_subscriptions WHERE status='active' AND auto_renew=1 AND current_period_end IS NOT NULL AND current_period_end <= '2024-01-22' AND current_period_end > '2024-01-15' AND cancelled_at IS NULL
             * 
             * ->filter(function($subscription) { ... }) - Lọc Collection để loại bỏ các subscription đã có trial mới
             *   - filter() là method của Laravel Collection, nhận một closure function
             *   - Closure function nhận $subscription (từng OrganizationSubscription model) và trả về boolean
             *   - Nếu closure trả về true, subscription được giữ lại trong Collection
             *   - Nếu closure trả về false, subscription bị loại bỏ khỏi Collection
             *   - filter() sẽ tạo một Collection mới chỉ chứa các subscription match điều kiện
             */
            $subscriptionsToRenew = OrganizationSubscription::where('status', 'active')
                ->where('auto_renew', true)
                ->whereNotNull('current_period_end')
                ->where('current_period_end', '<=', $renewalDate)
                ->where('current_period_end', '>', $now)
                ->whereNull('cancelled_at')
                ->with(['organization', 'plan'])
                ->get()
                ->filter(function($subscription) {
                    /**
                     * Kiểm tra xem đã có subscription trial mới chưa (tránh tạo duplicate)
                     * 
                     * OrganizationSubscription::where('organization_id', $subscription->organization_id) - Tìm subscriptions của cùng organization
                     *   - where('organization_id', $subscription->organization_id) lọc các subscription của cùng organization
                     *   - $subscription->organization_id là ID của organization (foreign key)
                     *   - Mỗi organization chỉ nên có một subscription trial mới tại một thời điểm
                     * 
                     * ->where('status', 'trial') - Lọc các subscription có status = 'trial'
                     *   - 'trial' nghĩa là subscription đang trong thời gian trial (10 ngày)
                     *   - Subscription trial mới được tạo khi auto_renew
                     * 
                     * ->where('created_at', '>', $subscription->created_at) - Lọc các subscription được tạo sau subscription hiện tại
                     *   - '>' là SQL GREATER THAN operator
                     *   - created_at > subscription.created_at nghĩa là subscription trial được tạo sau subscription active hiện tại
                     *   - Đảm bảo chỉ kiểm tra subscription trial mới, không phải subscription trial cũ
                     *   - Ví dụ: Nếu subscription active được tạo vào 2024-01-01, chỉ kiểm tra trial được tạo sau 2024-01-01
                     * 
                     * ->exists() - Kiểm tra xem có ít nhất 1 subscription trial mới không
                     *   - exists() trả về boolean (true/false)
                     *   - true = đã có subscription trial mới (không cần gia hạn nữa)
                     *   - false = chưa có subscription trial mới (cần gia hạn)
                     *   - exists() tối ưu hơn count() vì chỉ cần check có/không, không cần đếm số lượng
                     *   - SQL tương đương: SELECT 1 FROM organization_subscriptions WHERE organization_id=$subscription->organization_id AND status='trial' AND created_at > '2024-01-01' LIMIT 1
                     * 
                     * $hasNewTrialSubscription - Biến lưu kết quả kiểm tra
                     *   - true = đã có subscription trial mới
                     *   - false = chưa có subscription trial mới
                     * 
                     * return !$hasNewTrialSubscription - Trả về kết quả đảo ngược
                     *   - !$hasNewTrialSubscription nghĩa là "chưa có subscription trial mới"
                     *   - Nếu chưa có trial mới (true), subscription được giữ lại trong Collection (cần gia hạn)
                     *   - Nếu đã có trial mới (false), subscription bị loại bỏ khỏi Collection (không cần gia hạn nữa)
                     */
                    $hasNewTrialSubscription = OrganizationSubscription::where('organization_id', $subscription->organization_id)
                        ->where('status', 'trial')
                        ->where('created_at', '>', $subscription->created_at)
                        ->exists();
                    
                    return !$hasNewTrialSubscription;
                });

            /**
             * Xử lý từng subscription cần gia hạn bằng vòng lặp foreach
             * 
             * foreach ($subscriptionsToRenew as $oldSubscription) - Lặp qua từng phần tử trong Collection
             * - $subscriptionsToRenew là Collection chứa các OrganizationSubscription models đã được filter
             * - $oldSubscription là từng OrganizationSubscription model trong Collection
             * - Mỗi lần lặp, $oldSubscription sẽ là một instance của App\Models\OrganizationSubscription
             * - $oldSubscription là subscription cũ (active) cần được gia hạn
             */
            foreach ($subscriptionsToRenew as $oldSubscription) {
                /**
                 * Xử lý lỗi riêng cho từng subscription (không dừng toàn bộ process)
                 * 
                 * try { ... } catch { ... } - Xử lý lỗi khi tạo subscription mới cho một organization cụ thể
                 * - Nếu tạo subscription mới thành công: tiếp tục
                 * - Nếu tạo subscription mới thất bại: catch exception, ghi log, hiển thị lỗi, nhưng tiếp tục xử lý subscription tiếp theo
                 * - Đảm bảo command không bị dừng vì lỗi của một organization
                 * - Các organization khác vẫn có thể được xử lý tiếp
                 */
                try {
                    /**
                     * Gọi service để tạo subscription mới cho organization
                     * 
                     * $this->subscriptionService - SubscriptionService instance (đã được inject qua constructor)
                     *   - SubscriptionService nằm tại: app/Services/Subscription/SubscriptionService.php
                     *   - Service này chứa business logic để xử lý subscriptions
                     * 
                     * ->createRenewalSubscription($oldSubscription) - Gọi method để tạo subscription mới
                     *   - createRenewalSubscription() nhận OrganizationSubscription model (subscription cũ)
                     *   - Method này sẽ:
                     *     1. Bắt đầu database transaction để đảm bảo tính nhất quán dữ liệu
                     *     2. Tạo subscription mới với:
                     *        - status = 'trial' (subscription mới bắt đầu với trial)
                     *        - trial_days = 10 (cố định cho auto_renew, 10 ngày trial)
                     *        - current_period_start = now() (bắt đầu từ hôm nay)
                     *        - current_period_end = now() + 10 ngày (kết thúc sau 10 ngày)
                     *        - organization_id = $oldSubscription->organization_id (cùng organization)
                     *        - plan_id = $oldSubscription->plan_id (cùng plan)
                     *        - auto_renew = true (tiếp tục auto_renew)
                     *     3. Tạo invoice để thanh toán (qua SubscriptionService->createInvoice())
                     *        - Invoice sẽ có due_date = current_period_end (10 ngày sau)
                     *        - Organization cần thanh toán trong 10 ngày trial
                     *     4. Ghi log thông tin subscription mới đã được tạo
                     *     5. Commit transaction (lưu tất cả thay đổi vào database)
                     *   - Method này có thể throw exception nếu có lỗi (database error, validation error, etc.)
                     *   - Exception sẽ được catch ở block catch bên dưới
                     */
                    $this->subscriptionService->createRenewalSubscription($oldSubscription);
                    
                    // Tăng biến đếm lên 1 sau mỗi lần tạo subscription mới thành công
                    // $renewedCount++ tương đương với $renewedCount = $renewedCount + 1
                    $renewedCount++;

                    /**
                     * Hiển thị thông báo thành công cho người dùng
                     * 
                     * $this->info() - Hiển thị message màu xanh trong console
                     * - "Đã tạo subscription mới cho organization ID: {$oldSubscription->organization_id}"
                     * - {$oldSubscription->organization_id} là string interpolation, sẽ thay thế bằng giá trị organization_id
                     * - Ví dụ: "Đã tạo subscription mới cho organization ID: 5"
                     */
                    $this->info("Đã tạo subscription mới cho organization ID: {$oldSubscription->organization_id}");

                } catch (\Exception $e) {
                    /**
                     * Xử lý lỗi khi tạo subscription mới cho một organization cụ thể
                     * 
                     * catch (\Exception $e) - Bắt exception khi tạo subscription mới thất bại
                     * - Có thể là: database error, validation error, service error, etc.
                     * - $e là exception object chứa thông tin về lỗi
                     * 
                     * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
                     * - Log được ghi vào: storage/logs/laravel.log
                     * - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message {context}
                     * 
                     * Tham số 1: 'Lỗi khi tạo subscription mới cho auto_renew: ' . $e->getMessage()
                     *   - $e->getMessage() trả về error message của exception
                     *   - Dấu . là string concatenation operator trong PHP
                     * 
                     * Tham số 2: Array chứa context data
                     * - 'old_subscription_id' => $oldSubscription->id - ID của subscription cũ (để debug)
                     * - 'organization_id' => $oldSubscription->organization_id - ID của organization gặp lỗi
                     * - 'error' => $e->getTraceAsString() - Stack trace của exception (chi tiết lỗi)
                     *   - getTraceAsString() trả về string chứa stack trace (file, line, function calls)
                     *   - Giúp debug khi cần biết lỗi xảy ra ở đâu trong code
                     * 
                     * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
                     * - "Lỗi khi tạo subscription mới cho organization ID: {$oldSubscription->organization_id} - {$e->getMessage()}"
                     * - String interpolation, thay {$oldSubscription->organization_id} và {$e->getMessage()} bằng giá trị
                     * - Hiển thị lỗi cho người dùng để họ biết organization nào gặp lỗi
                     * 
                     * Lưu ý: Không throw exception lại, chỉ ghi log và hiển thị lỗi
                     * - Đảm bảo command không bị dừng vì lỗi của một organization
                     * - Các organization khác vẫn có thể được xử lý tiếp
                     * - Process sẽ tiếp tục với subscription tiếp theo trong vòng lặp
                     */
                    Log::error('Lỗi khi tạo subscription mới cho auto_renew: ' . $e->getMessage(), [
                        'old_subscription_id' => $oldSubscription->id,
                        'organization_id' => $oldSubscription->organization_id,
                        'error' => $e->getTraceAsString()
                    ]);
                    $this->error("Lỗi khi tạo subscription mới cho organization ID: {$oldSubscription->organization_id} - {$e->getMessage()}");
                }
            }

            /**
             * Hiển thị kết quả tổng thể cho người dùng dựa trên số lượng subscription đã xử lý
             * 
             * if ($renewedCount > 0) - Kiểm tra xem có subscription nào được gia hạn không
             * - $renewedCount > 0 nghĩa là có ít nhất 1 subscription đã được tạo mới
             * 
             * $this->info() - Hiển thị message màu xanh trong console
             * - "Đã tạo {$renewedCount} subscription mới cho auto_renew."
             * - {$renewedCount} là string interpolation, sẽ thay thế bằng giá trị của biến $renewedCount
             * - Ví dụ: Nếu $renewedCount = 3, message sẽ là "Đã tạo 3 subscription mới cho auto_renew."
             * 
             * else - Nếu không có subscription nào được gia hạn ($renewedCount = 0)
             * - Hiển thị message thông báo không có subscription nào cần auto_renew
             */
            if ($renewedCount > 0) {
                $this->info("Đã tạo {$renewedCount} subscription mới cho auto_renew.");
            } else {
                $this->info('Không có subscription nào cần auto_renew.');
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
             * - Lỗi này có thể là: database connection error, service không tồn tại, etc.
             * - Khác với lỗi trong foreach (chỉ ảnh hưởng một organization), lỗi này ảnh hưởng toàn bộ command
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
             * - 'Lỗi trong AutoRenewSubscriptions command: ' . $e->getMessage()
             *   - Ghi log để theo dõi và debug sau này
             * 
             * return Command::FAILURE - Trả về Command::FAILURE (giá trị = 1) để báo command thất bại
             * - Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thất bại không
             * - Có thể trigger alert hoặc retry logic
             */
            $this->error('Lỗi khi chạy command: ' . $e->getMessage());
            Log::error('Lỗi trong AutoRenewSubscriptions command: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
