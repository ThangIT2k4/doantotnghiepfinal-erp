<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lease;
use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationEmailService;
use App\Support\MailHelper;
use Illuminate\Support\Facades\Log;

/**
 * Command: CheckExpiringLeases
 * 
 * MỤC ĐÍCH:
 * Kiểm tra các hợp đồng thuê sắp hết hạn và gửi thông báo cho tenant và agent.
 * Command này được chạy định kỳ (thường qua cron job) để nhắc nhở về hợp đồng sắp hết hạn.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận dữ liệu từ: Model Lease (bảng leases)
 * 2. Kiểm tra các hợp đồng sắp hết hạn theo các mốc thời gian:
 *    - 2 tháng trước ngày hết hạn
 *    - 1 tháng trước ngày hết hạn
 *    - 15 ngày trước ngày hết hạn
 * 3. Xử lý: Gửi thông báo (email + in-app) cho:
 *    - Tenant (khách thuê): Email + in-app notification
 *    - Agent (đại lý): In-app notification
 * 4. Ghi log: Lưu thông tin vào Log để theo dõi
 * 
 * CÁCH CHẠY:
 * php artisan leases:check-expiring
 */
class CheckExpiringLeases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leases:check-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring leases and send notifications';

    protected $notificationEmailService;

    public function __construct(NotificationEmailService $notificationEmailService)
    {
        parent::__construct();
        $this->notificationEmailService = $notificationEmailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Hiển thị thông báo bắt đầu cho người dùng khi chạy command
        // $this->info() là method của Laravel Command để hiển thị message màu xanh trong console
        $this->info('Checking for expiring leases...');
        
        // Lấy thời gian hiện tại và lưu vào biến $today
        // now() trả về Carbon instance với thời gian hiện tại (giờ, phút, giây, ngày, tháng, năm)
        // Carbon là thư viện xử lý date/time trong Laravel, cung cấp nhiều method tiện ích
        // $today sẽ được sử dụng để tính toán các mốc thời gian (2 tháng, 1 tháng, 15 ngày)
        $today = now();
        
        // Khởi tạo biến đếm số lượng thông báo đã gửi
        // Biến này sẽ được tăng lên mỗi khi gửi một thông báo (email hoặc in-app)
        // Sử dụng tham chiếu (&) khi truyền vào method để có thể cập nhật giá trị từ trong method
        $notificationsSent = 0;
        
        // Kiểm tra các hợp đồng sắp hết hạn trong 2 tháng (60 ngày)
        // $this->checkLeasesExpiringIn() - Gọi method private để kiểm tra leases
        // Tham số 1: $today - Thời gian hiện tại (Carbon instance)
        // Tham số 2: 60 - Số ngày trước ngày hết hạn (60 ngày = 2 tháng)
        // Tham số 3: '2 months' - Mô tả khoảng thời gian (dùng để hiển thị trong thông báo)
        // Tham số 4: &$notificationsSent - Tham chiếu đến biến đếm (cho phép method cập nhật giá trị)
        $this->checkLeasesExpiringIn($today, 60, '2 months', $notificationsSent);
        
        // Kiểm tra các hợp đồng sắp hết hạn trong 1 tháng (30 ngày)
        // Tương tự như trên, nhưng với 30 ngày thay vì 60 ngày
        $this->checkLeasesExpiringIn($today, 30, '1 month', $notificationsSent);
        
        // Kiểm tra các hợp đồng sắp hết hạn trong 15 ngày
        // Đây là mốc thời gian gần nhất, cần nhắc nhở khẩn cấp hơn
        $this->checkLeasesExpiringIn($today, 15, '15 days', $notificationsSent);
        
        // Hiển thị tổng số thông báo đã gửi cho người dùng
        // $this->info() - Hiển thị message màu xanh trong console
        // "Total notifications sent: {$notificationsSent}" - String interpolation, thay {$notificationsSent} bằng giá trị
        // Ví dụ: Nếu $notificationsSent = 9, message sẽ là "Total notifications sent: 9"
        $this->info("Total notifications sent: {$notificationsSent}");
        
        // Ghi log thông tin hoàn thành việc kiểm tra vào file log
        // Log::info() - Ghi log với level INFO (thông tin bình thường)
        // Log được ghi vào: storage/logs/laravel.log
        // 
        // Tham số 1: 'Expiring leases check completed' - Message mô tả hành động
        // 
        // Tham số 2: Array chứa context data
        // - 'notifications_sent' => $notificationsSent - Tổng số thông báo đã gửi
        // - 'date' => $today->toDateString() - Ngày kiểm tra (format: YYYY-MM-DD)
        //   - toDateString() là method của Carbon, trả về string ngày (không có giờ)
        //   - Ví dụ: "2024-01-15"
        // 
        // Mục đích log:
        // - Theo dõi số lượng thông báo đã gửi mỗi ngày
        // - Debug khi có vấn đề
        // - Phân tích số liệu sau này
        Log::info('Expiring leases check completed', [
            'notifications_sent' => $notificationsSent,
            'date' => $today->toDateString()
        ]);
    }
    
    /**
     * Kiểm tra các hợp đồng thuê sắp hết hạn trong X ngày
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tính ngày hết hạn: today + $days
     * 2. Query từ bảng leases:
     *    - Status = 'active' (chỉ kiểm tra hợp đồng đang hoạt động)
     *    - end_date = ngày hết hạn đã tính
     *    - created_at < now - 30 giây (tránh kiểm tra hợp đồng vừa tạo)
     *    - Load relationship: tenant, agent, unit.property
     * 3. Với mỗi hợp đồng: Gọi sendExpiryNotifications() để gửi thông báo
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\Lease (bảng leases)
     * - Relationship: tenant, agent, unit.property (qua with())
     * 
     * @param Carbon $today Thời gian hiện tại
     * @param int $days Số ngày trước ngày hết hạn
     * @param string $period Mô tả khoảng thời gian (ví dụ: '2 months')
     * @param int &$notificationsSent Tham chiếu đến biến đếm số thông báo đã gửi
     */
    private function checkLeasesExpiringIn($today, $days, $period, &$notificationsSent)
    {
        /**
         * Tính ngày hết hạn dựa trên thời gian hiện tại và số ngày
         * 
         * $today->copy() - Tạo một bản copy của Carbon instance $today
         *   - copy() cần thiết vì addDays() sẽ modify Carbon instance gốc
         *   - Nếu không copy, $today sẽ bị thay đổi và ảnh hưởng đến các lần gọi sau
         * 
         * ->addDays($days) - Thêm $days ngày vào ngày hiện tại
         *   - $days là số ngày trước ngày hết hạn (ví dụ: 60, 30, 15)
         *   - addDays() trả về Carbon instance mới với ngày đã được cộng thêm
         *   - Ví dụ: Nếu $today = 2024-01-15 và $days = 60, thì $expiryDate = 2024-03-15
         * 
         * $expiryDate sẽ là ngày mà các lease có end_date = ngày này sẽ được kiểm tra
         * Ví dụ: Nếu kiểm tra leases hết hạn trong 60 ngày, $expiryDate = today + 60 days
         */
        $expiryDate = $today->copy()->addDays($days);
        
        /**
         * Query các hợp đồng thuê sắp hết hạn từ bảng leases sử dụng Eloquent ORM
         * 
         * Lease::where('status', 'active') - Lọc các lease có status = 'active' (đang hoạt động)
         *   - Chỉ kiểm tra các hợp đồng đang active, không kiểm tra draft, expired, terminated
         * 
         * ->whereDate('end_date', $expiryDate->toDateString()) - Lọc các lease có end_date = $expiryDate
         *   - whereDate() so sánh chỉ phần ngày (bỏ qua giờ, phút, giây)
         *   - toDateString() chuyển Carbon instance thành string format YYYY-MM-DD
         *   - Ví dụ: whereDate('end_date', '2024-03-15') sẽ match tất cả lease có end_date = 2024-03-15 (bất kể giờ nào)
         * 
         * ->where('created_at', '<', $today->subSeconds(30)) - Chỉ kiểm tra leases được tạo hơn 30 giây trước
         *   - $today->subSeconds(30) trừ 30 giây từ thời gian hiện tại
         *   - created_at < (now - 30 seconds) nghĩa là lease phải được tạo ít nhất 30 giây trước
         *   - Mục đích: Tránh gửi thông báo cho leases vừa mới tạo (có thể là test hoặc nhầm lẫn)
         *   - Lưu ý: subSeconds() sẽ modify $today, nhưng vì đã copy ở trên nên không ảnh hưởng
         * 
         * ->with(['tenant', 'agent', 'unit.property']) - Eager load relationships để tránh N+1 queries
         *   - with() sẽ load các relationship trước khi sử dụng
         *   - 'tenant' - Load User model liên quan (khách thuê)
         *   - 'agent' - Load User model liên quan (đại lý)
         *   - 'unit.property' - Load Unit model và Property model liên quan (nested relationship)
         *   - Nếu không dùng with(), mỗi lần truy cập $lease->tenant sẽ tạo một query mới (N+1 problem)
         *   - Với with(), tất cả data được load trong 1 query duy nhất (sử dụng JOIN hoặc separate queries)
         * 
         * ->get() - Thực thi query và trả về Collection chứa các Lease models
         *   - get() sẽ execute SQL query và trả về kết quả
         *   - SQL tương đương: SELECT * FROM leases WHERE status='active' AND DATE(end_date)='2024-03-15' AND created_at < '2024-01-15 10:00:00'
         */
        $expiringLeases = Lease::where('status', 'active')
            ->whereDate('end_date', $expiryDate->toDateString())
            ->where('created_at', '<', $today->subSeconds(30)) // Only check leases created more than 30 seconds ago
            ->with(['tenant', 'agent', 'unit.property'])
            ->get();
            
        // Hiển thị số lượng leases tìm được cho người dùng
        // $expiringLeases->count() - Đếm số phần tử trong Collection
        // "Found {$expiringLeases->count()} leases expiring in {$period}" - String interpolation
        // Ví dụ: "Found 5 leases expiring in 2 months"
        $this->info("Found {$expiringLeases->count()} leases expiring in {$period}");
        
        /**
         * Xử lý từng hợp đồng thuê sắp hết hạn bằng vòng lặp foreach
         * 
         * foreach ($expiringLeases as $lease) - Lặp qua từng phần tử trong Collection
         * - $expiringLeases là Collection chứa các Lease models đã query được
         * - $lease là từng Lease model trong Collection
         * - Mỗi lần lặp, $lease sẽ là một instance của App\Models\Lease
         * 
         * $this->sendExpiryNotifications() - Gọi method private để gửi thông báo cho lease này
         * - Tham số 1: $lease - Lease model cần gửi thông báo
         * - Tham số 2: $period - Khoảng thời gian (ví dụ: '2 months', '1 month', '15 days')
         * - Tham số 3: &$notificationsSent - Tham chiếu đến biến đếm (cho phép method cập nhật giá trị)
         * - Method này sẽ gửi email và in-app notification cho tenant và agent
         */
        foreach ($expiringLeases as $lease) {
            $this->sendExpiryNotifications($lease, $period, $notificationsSent);
        }
    }
    
    /**
     * Gửi thông báo sắp hết hạn cho tenant và agent
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra xem đã gửi thông báo hôm nay chưa (tránh duplicate)
     * 2. Lấy thông tin: property name, unit name, tenant name, agent name
     * 3. Gửi thông báo cho tenant: Email + in-app notification
     * 4. Gửi thông báo cho agent: In-app notification
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\Notification (bảng notifications) - kiểm tra duplicate
     * - Model: App\Models\Lease (đã được truyền vào)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app notifications)
     * - Gửi email qua Mail facade (không lưu vào database)
     * 
     * @param Lease $lease Hợp đồng thuê cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '2 months')
     * @param int &$notificationsSent Tham chiếu đến biến đếm số thông báo đã gửi
     */
    private function sendExpiryNotifications($lease, $period, &$notificationsSent)
    {
        try {
            /**
             * Kiểm tra xem đã gửi thông báo hôm nay cho lease này chưa (tránh duplicate)
             * 
             * now()->toDateString() - Lấy ngày hiện tại dạng string (YYYY-MM-DD)
             *   - now() trả về Carbon instance với thời gian hiện tại
             *   - toDateString() chuyển thành string format YYYY-MM-DD (ví dụ: "2024-01-15")
             *   - Lưu vào biến $today để sử dụng trong query
             */
            $today = now()->toDateString();
            
            /**
             * Query kiểm tra xem đã có notification nào được gửi hôm nay cho lease này chưa
             * 
             * Notification::where('to_user_id', $lease->tenant_id) - Lọc notifications gửi cho tenant của lease này
             *   - $lease->tenant_id là ID của tenant (foreign key trong bảng leases)
             *   - to_user_id là ID của user nhận notification (foreign key trong bảng notifications)
             * 
             * ->where('subject', 'like', '%' . $lease->contract_no . '%') - Tìm notifications có subject chứa contract_no
             *   - 'like' là SQL LIKE operator (pattern matching)
             *   - '%' . $lease->contract_no . '%' là pattern: bất kỳ text nào + contract_no + bất kỳ text nào
             *   - Ví dụ: Nếu contract_no = "L-2024-001", pattern sẽ là "%L-2024-001%"
             *   - Sẽ match: "Thông báo hợp đồng sắp hết hạn - L-2024-001"
             * 
             * ->where('subject', 'like', '%sắp hết hạn%') - Tìm notifications có subject chứa "sắp hết hạn"
             *   - Pattern "%sắp hết hạn%" sẽ match bất kỳ text nào chứa "sắp hết hạn"
             *   - Đảm bảo chỉ kiểm tra notifications về hết hạn, không phải loại khác
             * 
             * ->whereDate('created_at', $today) - Chỉ kiểm tra notifications được tạo hôm nay
             *   - whereDate() so sánh chỉ phần ngày (bỏ qua giờ, phút, giây)
             *   - $today là string format YYYY-MM-DD
             *   - Đảm bảo chỉ kiểm tra notifications hôm nay, không phải ngày khác
             * 
             * ->count() - Đếm số lượng notifications match các điều kiện trên
             *   - count() trả về integer (số lượng)
             *   - Nếu > 0 nghĩa là đã có notification được gửi hôm nay
             *   - Nếu = 0 nghĩa là chưa có notification nào được gửi hôm nay
             */
            $existingNotifications = Notification::where('to_user_id', $lease->tenant_id)
                ->where('subject', 'like', '%' . $lease->contract_no . '%')
                ->where('subject', 'like', '%sắp hết hạn%')
                ->whereDate('created_at', $today)
                ->count();
                
            /**
             * Nếu đã có notification được gửi hôm nay, bỏ qua (không gửi lại)
             * 
             * if ($existingNotifications > 0) - Kiểm tra xem có notification nào không
             * - $existingNotifications > 0 nghĩa là đã có ít nhất 1 notification được gửi hôm nay
             * 
             * $this->line() - Hiển thị message màu trắng trong console (thông tin bình thường)
             * - "Notifications already sent today for lease: {$lease->contract_no}"
             * - String interpolation, thay {$lease->contract_no} bằng giá trị contract_no
             * 
             * return - Thoát khỏi method ngay lập tức, không thực thi code phía dưới
             * - Tránh gửi duplicate notifications
             * - Tiết kiệm tài nguyên (không gửi email, không tạo notification records)
             */
            if ($existingNotifications > 0) {
                $this->line("Notifications already sent today for lease: {$lease->contract_no}");
                return;
            }
            
            /**
             * Lấy thông tin cần thiết từ relationships đã được eager load
             * 
             * $lease->unit->property->name - Lấy tên property qua nested relationship
             *   - $lease->unit - Truy cập Unit model (đã được eager load với with(['unit.property']))
             *   - ->property - Truy cập Property model từ Unit (relationship trong Unit model)
             *   - ->name - Lấy field name từ Property model
             *   - ?? 'N/A' - Null coalescing operator: Nếu giá trị null, dùng 'N/A' thay thế
             *   - Tránh lỗi khi property không tồn tại hoặc name = null
             * 
             * $lease->unit->name - Lấy tên unit (có thể là code hoặc name field)
             *   - ?? 'N/A' - Nếu null, dùng 'N/A'
             * 
             * $lease->tenant->full_name - Lấy full_name từ User model (tenant)
             *   - full_name thường lấy từ user_profiles table (qua relationship)
             *   - ?? $lease->tenant->name - Nếu full_name null, dùng name field
             *   - ?? 'N/A' - Nếu cả hai đều null, dùng 'N/A'
             *   - Null coalescing operator có thể chain: $a ?? $b ?? $c
             * 
             * $lease->agent->full_name - Tương tự như tenant, lấy full_name của agent
             *   - Agent cũng là User model, nhưng với role 'agent'
             */
            $propertyName = $lease->unit->property->name ?? 'N/A';
            $unitName = $lease->unit->name ?? 'N/A';
            $tenantName = $lease->tenant->full_name ?? $lease->tenant->name ?? 'N/A';
            $agentName = $lease->agent->full_name ?? $lease->agent->name ?? 'N/A';
            
            // Gửi thông báo cho tenant (email + in-app notification)
            // $this->sendTenantExpiryNotification() - Gọi method private để gửi thông báo cho tenant
            // Method này sẽ:
            // - Tạo in-app notification trong bảng notifications
            // - Gửi email cho tenant qua Mail facade
            $this->sendTenantExpiryNotification($lease, $period, $propertyName, $unitName, $tenantName);
            
            // Gửi thông báo cho agent (chỉ in-app notification, không gửi email)
            // $this->sendAgentExpiryNotification() - Gọi method private để gửi thông báo cho agent
            // Method này sẽ:
            // - Tạo in-app notification trong bảng notifications cho agent
            $this->sendAgentExpiryNotification($lease, $period, $propertyName, $unitName, $tenantName, $agentName);
            
            // Tăng biến đếm lên 3 sau khi gửi thông báo
            // $notificationsSent += 3 tương đương với $notificationsSent = $notificationsSent + 3
            // 3 notifications bao gồm:
            // - 1 email cho tenant (không lưu vào database, chỉ gửi email)
            // - 1 in-app notification cho tenant (lưu vào bảng notifications)
            // - 1 in-app notification cho agent (lưu vào bảng notifications)
            $notificationsSent += 3; // 1 email + 1 in-app for tenant, 1 in-app for agent
            
            // Hiển thị thông báo đã gửi thành công cho người dùng
            // $this->line() - Hiển thị message màu trắng trong console
            // "Sent expiry notifications for lease: {$lease->contract_no}" - String interpolation
            $this->line("Sent expiry notifications for lease: {$lease->contract_no}");
            
        } catch (\Exception $e) {
            /**
             * Xử lý lỗi: Ghi log lỗi nếu có exception xảy ra
             * 
             * catch (\Exception $e) - Bắt bất kỳ exception nào xảy ra trong block try
             * - \Exception là base class của tất cả exceptions trong PHP
             * - $e là exception object chứa thông tin về lỗi
             * 
             * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
             * - Log được ghi vào: storage/logs/laravel.log
             * - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message {context}
             * 
             * Tham số 1: 'Failed to send expiry notifications' - Message mô tả lỗi
             * 
             * Tham số 2: Array chứa context data
             * - 'lease_id' => $lease->id - ID của lease gặp lỗi (để debug)
             * - 'error' => $e->getMessage() - Error message của exception
             *   - getMessage() trả về error message (ví dụ: "Call to a member function on null")
             * 
             * Lưu ý: Không throw exception lại, chỉ ghi log
             * - Đảm bảo command không bị dừng vì lỗi của một lease
             * - Các lease khác vẫn có thể được xử lý tiếp
             */
            Log::error('Failed to send expiry notifications', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Gửi thông báo sắp hết hạn cho tenant (email + in-app)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo nội dung thông báo (subject và content) với thông tin hợp đồng
     * 2. Tạo in-app notification trong bảng notifications cho tenant
     * 3. Gửi email cho tenant qua Mail facade
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app)
     * - Gửi email qua Mail facade (không lưu vào database)
     * - Ghi log vào storage/logs/laravel.log
     * 
     * @param Lease $lease Hợp đồng thuê cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '2 months')
     * @param string $propertyName Tên tài sản
     * @param string $unitName Tên phòng
     * @param string $tenantName Tên tenant
     */
    private function sendTenantExpiryNotification($lease, $period, $propertyName, $unitName, $tenantName)
    {
        /**
         * Tạo subject (tiêu đề) cho thông báo
         * 
         * "Thông báo hợp đồng sắp hết hạn - {$lease->contract_no}" - String interpolation
         * - {$lease->contract_no} sẽ được thay thế bằng giá trị contract_no của lease
         * - Ví dụ: "Thông báo hợp đồng sắp hết hạn - L-2024-001"
         * - Subject này sẽ được dùng cho cả email và in-app notification
         */
        $subject = "Thông báo hợp đồng sắp hết hạn - {$lease->contract_no}";
        
        /**
         * Tạo nội dung (content) cho email thông báo
         * 
         * $content = "Chào {$tenantName},\n\n" - Bắt đầu với lời chào
         *   - {$tenantName} là tên tenant (đã được truyền vào method)
         *   - \n\n là 2 ký tự xuống dòng (newline) để tạo khoảng trống
         * 
         * $content .= "Chúng tôi xin thông báo..." - Nối thêm text vào biến $content
         *   - .= là string concatenation operator (nối chuỗi)
         *   - Tương đương với: $content = $content . "Chúng tôi xin thông báo..."
         *   - {$period} sẽ được thay thế bằng khoảng thời gian (ví dụ: "2 months", "1 month", "15 days")
         * 
         * $content .= "Thông tin hợp đồng:\n" - Tiêu đề phần thông tin
         *   - \n là ký tự xuống dòng
         * 
         * $content .= "- Số hợp đồng: {$lease->contract_no}\n" - Hiển thị số hợp đồng
         *   - $lease->contract_no truy cập field contract_no từ Lease model
         * 
         * $content .= "- Tài sản: {$propertyName}\n" - Hiển thị tên tài sản
         *   - $propertyName đã được truyền vào method (lấy từ $lease->unit->property->name)
         * 
         * $content .= "- Phòng: {$unitName}\n" - Hiển thị tên phòng
         *   - $unitName đã được truyền vào method (lấy từ $lease->unit->name)
         * 
         * $content .= "- Ngày hết hạn: " . $lease->end_date->format('d/m/Y') . "\n" - Hiển thị ngày hết hạn
         *   - $lease->end_date là Carbon date instance (đã được cast trong model)
         *   - ->format('d/m/Y') chuyển date thành string format dd/mm/yyyy (ví dụ: "15/03/2024")
         *   - Dấu . là string concatenation operator
         * 
         * $content .= "- Tiền thuê: " . number_format($lease->rent_amount, 0, ',', '.') . " VNĐ/tháng\n\n"
         *   - $lease->rent_amount là số tiền thuê (decimal, ví dụ: 5000000.00)
         *   - number_format($value, $decimals, $decimal_separator, $thousands_separator)
         *   - number_format($lease->rent_amount, 0, ',', '.') format số với:
         *     - 0 decimal places (không có số thập phân)
         *     - ',' là decimal separator (không dùng vì 0 decimals)
         *     - '.' là thousands separator (dấu chấm phân cách hàng nghìn)
         *   - Ví dụ: 5000000.00 -> "5.000.000"
         *   - Kết quả: "5.000.000 VNĐ/tháng"
         * 
         * $content .= "Vui lòng liên hệ..." - Hướng dẫn tenant
         * 
         * $content .= "Trân trọng,\n" - Lời kết
         * 
         * $content .= "Hệ thống quản lý thuê" - Chữ ký
         */
        $content = "Chào {$tenantName},\n\n";
        $content .= "Chúng tôi xin thông báo rằng hợp đồng thuê của bạn sắp hết hạn trong {$period}.\n\n";
        $content .= "Thông tin hợp đồng:\n";
        $content .= "- Số hợp đồng: {$lease->contract_no}\n";
        $content .= "- Tài sản: {$propertyName}\n";
        $content .= "- Phòng: {$unitName}\n";
        $content .= "- Ngày hết hạn: " . $lease->end_date->format('d/m/Y') . "\n";
        $content .= "- Tiền thuê: " . number_format($lease->rent_amount, 0, ',', '.') . " VNĐ/tháng\n\n";
        $content .= "Vui lòng liên hệ với đại lý của bạn để gia hạn hợp đồng.\n\n";
        $content .= "Trân trọng,\n";
        $content .= "Hệ thống quản lý thuê";
        
        /**
         * Tạo in-app notification cho tenant trong bảng notifications
         * 
         * Notification::create([...]) - Tạo bản ghi mới trong bảng notifications sử dụng Eloquent
         * - create() nhận một array các field cần insert
         * - Method này sẽ:
         *   1. Tạo SQL INSERT query: INSERT INTO notifications (channel_id, to_user_id, subject, content, status, created_at) VALUES (...)
         *   2. Thực thi query và insert vào database
         *   3. Trả về Notification model instance đã được tạo
         * 
         * 'channel_id' => 1 - ID của channel notification
         *   - 1 = in_app (thông báo trong ứng dụng)
         *   - Các channel khác có thể là: 2 = email, 3 = SMS, etc.
         * 
         * 'to_user_id' => $lease->tenant_id - ID của user nhận notification
         *   - $lease->tenant_id là foreign key trong bảng leases, trỏ đến bảng users
         *   - Notification sẽ được hiển thị cho tenant này trong ứng dụng
         * 
         * 'subject' => $subject - Tiêu đề notification (đã tạo ở trên)
         *   - Subject sẽ được hiển thị ở đầu notification
         * 
         * 'content' => "Hợp đồng {$lease->contract_no}..." - Nội dung ngắn gọn cho in-app notification
         *   - Nội dung này ngắn hơn email content (vì in-app notification thường hiển thị ngắn)
         *   - String interpolation, thay {$lease->contract_no}, {$propertyName}, {$unitName}, {$period} bằng giá trị
         * 
         * 'status' => 'queued' - Trạng thái notification
         *   - 'queued' nghĩa là notification đã được tạo và đang chờ xử lý
         *   - Có thể có các status khác: 'sent', 'read', 'failed', etc.
         * 
         * 'created_at' => now() - Thời gian tạo notification
         *   - now() trả về Carbon instance với thời gian hiện tại
         *   - Eloquent sẽ tự động set created_at, nhưng set thủ công để đảm bảo chính xác
         */
        Notification::create([
            'channel_id' => 1, // in_app
            'to_user_id' => $lease->tenant_id,
            'subject' => $subject,
            'content' => "Hợp đồng {$lease->contract_no} tại {$propertyName} - {$unitName} sắp hết hạn trong {$period}.",
            'status' => 'queued',
            'created_at' => now(),
        ]);
        
        /**
         * Gửi email notification cho tenant (không lưu vào database, chỉ gửi email)
         * 
         * try { ... } catch { ... } - Xử lý lỗi khi gửi email
         * - Nếu gửi email thành công: tiếp tục
         * - Nếu gửi email thất bại: catch exception và ghi log, nhưng không throw lại
         * - Đảm bảo command không bị dừng vì lỗi gửi email
         */
        try {
            /**
             * Tạo instance của NotificationMail mailable class
             * 
             * new \App\Mail\NotificationMail(...) - Tạo instance của NotificationMail class
             * - \App\Mail\NotificationMail là fully qualified class name (không cần use statement)
             * - NotificationMail là Laravel Mailable class, dùng để gửi email
             * 
             * Tham số constructor:
             * - $subject - Tiêu đề email (đã tạo ở trên)
             * - $content - Nội dung email (đã tạo ở trên)
             * - $tenantName - Tên tenant (để hiển thị trong email)
             * - 'warning' - Loại notification (có thể ảnh hưởng đến màu sắc, icon trong email template)
             * - null - Tham số thứ 5 (có thể là action URL hoặc button text)
             * - null - Tham số thứ 6 (có thể là additional data)
             */
            $mailable = new \App\Mail\NotificationMail(
                $subject,
                $content,
                $tenantName,
                'warning',
                null,
                null
            );
            
            /**
             * Gửi email cho tenant sử dụng Laravel Mail facade
             * 
             * \Illuminate\Support\Facades\Mail::to($lease->tenant->email) - Chỉ định người nhận email
             *   - Mail::to() nhận email address hoặc User model
             *   - $lease->tenant->email truy cập email của tenant (đã được eager load)
             *   - Ví dụ: "tenant@example.com"
             * 
             * ->send($mailable) - Gửi email với mailable instance đã tạo
             *   - send() sẽ:
             *     1. Render email template với data từ mailable
             *     2. Kết nối đến mail server (SMTP, Mailgun, etc.) theo config
             *     3. Gửi email thực tế
             *     4. Trả về void (không trả về gì)
             *   - Nếu có lỗi (SMTP connection failed, invalid email, etc.), sẽ throw exception
             *   - Exception sẽ được catch ở block catch bên dưới
             */
            MailHelper::sendWithOptionalOrgMail($mailable, $lease->tenant->email, $lease->organization_id);
            
            /**
             * Ghi log thành công khi email đã được gửi
             * 
             * Log::info() - Ghi log với level INFO (thông tin bình thường)
             * - Log được ghi vào: storage/logs/laravel.log
             * 
             * Tham số 1: 'Expiry email sent to tenant' - Message mô tả hành động
             * 
             * Tham số 2: Array chứa context data
             * - 'lease_id' => $lease->id - ID của lease (để debug)
             * - 'tenant_email' => $lease->tenant->email - Email của tenant (để verify)
             */
            Log::info('Expiry email sent to tenant', [
                'lease_id' => $lease->id,
                'tenant_email' => $lease->tenant->email
            ]);
        } catch (\Exception $e) {
            /**
             * Xử lý lỗi khi gửi email: Ghi log lỗi nhưng không throw lại
             * 
             * catch (\Exception $e) - Bắt exception khi gửi email thất bại
             * - Có thể là: SMTP connection failed, invalid email address, mail server error, etc.
             * 
             * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
             * - Log được ghi vào: storage/logs/laravel.log
             * 
             * Tham số 1: 'Failed to send expiry email to tenant' - Message mô tả lỗi
             * 
             * Tham số 2: Array chứa context data
             * - 'lease_id' => $lease->id - ID của lease gặp lỗi
             * - 'tenant_email' => $lease->tenant->email - Email của tenant (để kiểm tra)
             * - 'error' => $e->getMessage() - Error message của exception
             * 
             * Lưu ý: Không throw exception lại
             * - Đảm bảo command không bị dừng vì lỗi gửi email của một lease
             * - In-app notification đã được tạo thành công, chỉ email bị lỗi
             * - Các lease khác vẫn có thể được xử lý tiếp
             */
            Log::error('Failed to send expiry email to tenant', [
                'lease_id' => $lease->id,
                'tenant_email' => $lease->tenant->email,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Gửi thông báo sắp hết hạn cho agent (chỉ in-app)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo nội dung thông báo (subject và content) với thông tin hợp đồng
     * 2. Tạo in-app notification trong bảng notifications cho agent
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app)
     * 
     * @param Lease $lease Hợp đồng thuê cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '2 months')
     * @param string $propertyName Tên tài sản
     * @param string $unitName Tên phòng
     * @param string $tenantName Tên tenant
     * @param string $agentName Tên agent
     */
    private function sendAgentExpiryNotification($lease, $period, $propertyName, $unitName, $tenantName, $agentName)
    {
        /**
         * Tạo subject (tiêu đề) cho thông báo cho agent
         * 
         * "Hợp đồng sắp hết hạn - {$lease->contract_no}" - String interpolation
         * - {$lease->contract_no} sẽ được thay thế bằng giá trị contract_no của lease
         * - Ví dụ: "Hợp đồng sắp hết hạn - L-2024-001"
         * - Subject này ngắn gọn hơn so với tenant notification
         */
        $subject = "Hợp đồng sắp hết hạn - {$lease->contract_no}";
        
        /**
         * Tạo nội dung (content) cho in-app notification cho agent
         * 
         * $content = "Hợp đồng {$lease->contract_no}..." - Bắt đầu với thông tin hợp đồng
         *   - {$lease->contract_no} - Số hợp đồng
         *   - {$tenantName} - Tên khách thuê (đã được truyền vào method)
         *   - {$propertyName} - Tên tài sản
         *   - {$unitName} - Tên phòng
         *   - {$period} - Khoảng thời gian (ví dụ: "2 months")
         * 
         * $content .= "Ngày hết hạn: " . $lease->end_date->format('d/m/Y') . ". "
         *   - Nối thêm thông tin ngày hết hạn
         *   - $lease->end_date->format('d/m/Y') chuyển date thành string format dd/mm/yyyy
         *   - Ví dụ: "15/03/2024"
         * 
         * $content .= "Vui lòng liên hệ..." - Hướng dẫn agent
         *   - Nối thêm hướng dẫn cho agent
         * 
         * Lưu ý: Content này ngắn gọn hơn email content vì in-app notification thường hiển thị ngắn
         */
        $content = "Hợp đồng {$lease->contract_no} của khách thuê {$tenantName} tại {$propertyName} - {$unitName} sắp hết hạn trong {$period}. ";
        $content .= "Ngày hết hạn: " . $lease->end_date->format('d/m/Y') . ". ";
        $content .= "Vui lòng liên hệ với khách thuê để gia hạn hợp đồng.";
        
        /**
         * Tạo in-app notification cho agent trong bảng notifications
         * 
         * Notification::create([...]) - Tạo bản ghi mới trong bảng notifications sử dụng Eloquent
         * - create() nhận một array các field cần insert
         * - Method này sẽ:
         *   1. Tạo SQL INSERT query: INSERT INTO notifications (channel_id, to_user_id, subject, content, status, created_at) VALUES (...)
         *   2. Thực thi query và insert vào database
         *   3. Trả về Notification model instance đã được tạo
         * 
         * 'channel_id' => 1 - ID của channel notification
         *   - 1 = in_app (thông báo trong ứng dụng)
         *   - Agent chỉ nhận in-app notification, không nhận email (khác với tenant)
         * 
         * 'to_user_id' => $lease->agent_id - ID của user nhận notification (agent)
         *   - $lease->agent_id là foreign key trong bảng leases, trỏ đến bảng users
         *   - Notification sẽ được hiển thị cho agent này trong ứng dụng
         *   - Agent có thể là null, nhưng nếu null thì sẽ không tạo notification (cần check trước)
         * 
         * 'subject' => $subject - Tiêu đề notification (đã tạo ở trên)
         *   - Subject sẽ được hiển thị ở đầu notification
         * 
         * 'content' => $content - Nội dung notification (đã tạo ở trên)
         *   - Nội dung ngắn gọn, chứa thông tin cần thiết cho agent
         * 
         * 'status' => 'queued' - Trạng thái notification
         *   - 'queued' nghĩa là notification đã được tạo và đang chờ xử lý
         *   - Notification sẽ được hiển thị cho agent trong ứng dụng
         * 
         * 'created_at' => now() - Thời gian tạo notification
         *   - now() trả về Carbon instance với thời gian hiện tại
         *   - Eloquent sẽ tự động set created_at, nhưng set thủ công để đảm bảo chính xác
         * 
         * Lưu ý: Agent chỉ nhận in-app notification, không nhận email
         * - Khác với tenant (nhận cả email và in-app)
         * - Có thể do business logic: Agent thường check ứng dụng thường xuyên hơn
         */
        Notification::create([
            'channel_id' => 1, // in_app
            'to_user_id' => $lease->agent_id,
            'subject' => $subject,
            'content' => $content,
            'status' => 'queued',
            'created_at' => now(),
        ]);
    }
}