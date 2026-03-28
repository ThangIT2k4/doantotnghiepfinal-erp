<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationEmailService;
use App\Support\MailHelper;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Command: CheckInvoiceDueDate
 * 
 * MỤC ĐÍCH:
 * Kiểm tra các hóa đơn sắp đến hạn và quá hạn, sau đó gửi thông báo cho tenant, agent và manager.
 * Command này được chạy định kỳ (thường qua cron job) để nhắc nhở thanh toán hóa đơn.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận dữ liệu từ: Model Invoice (bảng invoices)
 * 2. Kiểm tra các hóa đơn theo các mốc thời gian:
 *    - Sắp đến hạn: 3 ngày, 2 ngày, 1 ngày trước ngày đến hạn
 *    - Quá hạn: 1 ngày, 2 ngày, 3 ngày sau ngày đến hạn
 * 3. Xử lý: Gửi thông báo (email + in-app) cho:
 *    - Tenant (khách thuê): Email + in-app notification
 *    - Agent (đại lý): In-app notification
 *    - Manager (quản lý): In-app notification
 * 4. Ghi log: Lưu thông tin vào Log để theo dõi
 * 
 * CÁCH CHẠY:
 * php artisan invoices:check-due-date
 */
class CheckInvoiceDueDate extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * @var string
     */
    protected $signature = 'invoices:check-due-date';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Check for invoices nearing due date and send notifications';

    /**
     * Service xử lý email notification (được inject qua constructor)
     * 
     * @var NotificationEmailService
     */
    protected $notificationEmailService;

    /**
     * Khởi tạo command
     * 
     * Nhận NotificationEmailService từ Laravel container (dependency injection)
     * Service này nằm tại: app/Services/NotificationEmailService.php
     * 
     * @param NotificationEmailService $notificationEmailService Service xử lý email notification
     */
    public function __construct(NotificationEmailService $notificationEmailService)
    {
        parent::__construct();
        $this->notificationEmailService = $notificationEmailService;
    }

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy thời gian hiện tại
     * 2. Kiểm tra các hóa đơn sắp đến hạn:
     *    - 3 ngày trước ngày đến hạn
     *    - 2 ngày trước ngày đến hạn
     *    - 1 ngày trước ngày đến hạn
     * 3. Kiểm tra các hóa đơn quá hạn:
     *    - 1 ngày sau ngày đến hạn
     *    - 2 ngày sau ngày đến hạn
     *    - 3 ngày sau ngày đến hạn
     * 4. Với mỗi hóa đơn tìm được: Gửi thông báo cho tenant, agent, manager
     * 5. Ghi log và hiển thị kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\Invoice (bảng invoices)
     * - Relationship: lease.tenant, lease.agent, organization (qua with())
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app notifications)
     * - Gửi email qua Mail facade (không lưu vào database)
     * - Ghi log vào storage/logs/laravel.log
     * 
     * SERVICE ĐƯỢC GỌI:
     * - App\Services\CapabilityService::getUsersWithModuleAccess() - lấy danh sách manager
     * - Illuminate\Support\Facades\Mail - gửi email
     * 
     * @return int Command::SUCCESS (0) hoặc Command::FAILURE (1)
     */
    public function handle()
    {
        /**
         * Hiển thị thông báo bắt đầu kiểm tra invoices
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - 'Checking for invoices nearing due date...'
         *   - Thông báo cho user biết command đang bắt đầu chạy
         */
        $this->info('Checking for invoices nearing due date...');
        
        /**
         * Lấy thời gian hiện tại
         * 
         * now() - Lấy Carbon instance của thời gian hiện tại
         *   - now() là helper function của Laravel, tương đương với Carbon::now()
         *   - Trả về Carbon instance với timezone được cấu hình trong config/app.php
         *   - Carbon instance cung cấp nhiều methods để thao tác với date/time
         * 
         * $today - Biến lưu Carbon instance của thời gian hiện tại
         *   - Sẽ được sử dụng để tính toán các mốc thời gian (3 ngày trước, 2 ngày trước, etc.)
         */
        $today = now();
        
        /**
         * Khởi tạo biến đếm số lượng thông báo đã gửi
         * 
         * $notificationsSent - Biến đếm tổng số thông báo đã gửi (in-app + email)
         *   - Khởi tạo = 0
         *   - Sẽ được tăng lên mỗi khi gửi thông báo thành công
         *   - Được truyền bằng tham chiếu (&) vào các methods để có thể cập nhật giá trị
         *   - Sẽ được hiển thị ở cuối command để báo cáo kết quả
         */
        $notificationsSent = 0;
        
        /**
         * Kiểm tra các hóa đơn sắp đến hạn trong 3 ngày
         * 
         * $this->checkInvoicesDueIn($today, 3, '3 days', $notificationsSent) - Gọi method để kiểm tra
         *   - $today - Thời gian hiện tại (Carbon instance)
         *   - 3 - Số ngày trước ngày đến hạn (3 ngày trước)
         *   - '3 days' - Mô tả khoảng thời gian (dùng để hiển thị và tạo nội dung thông báo)
         *   - $notificationsSent - Tham chiếu đến biến đếm (sẽ được cập nhật trong method)
         *   - Method này sẽ tìm các invoice có due_date = today + 3 days và gửi thông báo
         */
        $this->checkInvoicesDueIn($today, 3, '3 days', $notificationsSent);
        
        /**
         * Kiểm tra các hóa đơn sắp đến hạn trong 2 ngày
         * 
         * $this->checkInvoicesDueIn($today, 2, '2 days', $notificationsSent) - Gọi method để kiểm tra
         *   - Tương tự như trên, nhưng với 2 ngày trước ngày đến hạn
         */
        $this->checkInvoicesDueIn($today, 2, '2 days', $notificationsSent);
        
        /**
         * Kiểm tra các hóa đơn sắp đến hạn trong 1 ngày
         * 
         * $this->checkInvoicesDueIn($today, 1, '1 day', $notificationsSent) - Gọi method để kiểm tra
         *   - Tương tự như trên, nhưng với 1 ngày trước ngày đến hạn
         */
        $this->checkInvoicesDueIn($today, 1, '1 day', $notificationsSent);
        
        /**
         * Kiểm tra các hóa đơn quá hạn 1 ngày
         * 
         * $this->checkOverdueInvoices($today, 1, '1 day overdue', $notificationsSent) - Gọi method để kiểm tra
         *   - $today - Thời gian hiện tại (Carbon instance)
         *   - 1 - Số ngày quá hạn (1 ngày sau ngày đến hạn)
         *   - '1 day overdue' - Mô tả khoảng thời gian (dùng để hiển thị và tạo nội dung thông báo)
         *   - $notificationsSent - Tham chiếu đến biến đếm (sẽ được cập nhật trong method)
         *   - Method này sẽ tìm các invoice có due_date = today - 1 day và status = 'overdue', sau đó gửi thông báo
         */
        $this->checkOverdueInvoices($today, 1, '1 day overdue', $notificationsSent);
        
        /**
         * Kiểm tra các hóa đơn quá hạn 2 ngày
         * 
         * $this->checkOverdueInvoices($today, 2, '2 days overdue', $notificationsSent) - Gọi method để kiểm tra
         *   - Tương tự như trên, nhưng với 2 ngày quá hạn
         */
        $this->checkOverdueInvoices($today, 2, '2 days overdue', $notificationsSent);
        
        /**
         * Kiểm tra các hóa đơn quá hạn 3 ngày
         * 
         * $this->checkOverdueInvoices($today, 3, '3 days overdue', $notificationsSent) - Gọi method để kiểm tra
         *   - Tương tự như trên, nhưng với 3 ngày quá hạn
         */
        $this->checkOverdueInvoices($today, 3, '3 days overdue', $notificationsSent);
        
        /**
         * Hiển thị tổng số thông báo đã gửi
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Total notifications sent: {$notificationsSent}"
         *   - {$notificationsSent} là string interpolation, sẽ thay thế bằng giá trị $notificationsSent
         *   - Ví dụ: "Total notifications sent: 15"
         *   - Thông báo cho user biết tổng số thông báo đã được gửi trong lần chạy command này
         */
        $this->info("Total notifications sent: {$notificationsSent}");
        
        /**
         * Ghi log kết quả vào file log
         * 
         * Log::info() - Ghi log với level INFO (thông tin bình thường)
         *   - Log được ghi vào: storage/logs/laravel.log
         *   - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
         * 
         * Tham số 1: 'Invoice due date check completed' - Message mô tả sự kiện
         * 
         * Tham số 2: Array chứa context data
         * - 'notifications_sent' => $notificationsSent - Số lượng thông báo đã gửi (để theo dõi)
         * - 'date' => $today->toDateString() - Ngày chạy command (format YYYY-MM-DD)
         *   - toDateString() là method của Carbon để chuyển date thành string format YYYY-MM-DD
         *   - Ví dụ: "2024-01-15"
         * 
         * Log này giúp theo dõi lịch sử chạy command và số lượng thông báo đã gửi
         */
        Log::info('Invoice due date check completed', [
            'notifications_sent' => $notificationsSent,
            'date' => $today->toDateString()
        ]);
    }
    
    /**
     * Kiểm tra các hóa đơn sắp đến hạn trong X ngày
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tính ngày đến hạn: today + $days
     * 2. Query từ bảng invoices:
     *    - Status = 'issued' hoặc 'overdue'
     *    - due_date = ngày đến hạn đã tính
     *    - Load relationship: lease.tenant, lease.agent, organization
     * 3. Với mỗi hóa đơn: Gọi sendDueDateNotifications() để gửi thông báo
     * 
     * @param Carbon $today Thời gian hiện tại
     * @param int $days Số ngày trước ngày đến hạn
     * @param string $period Mô tả khoảng thời gian (ví dụ: '3 days')
     * @param int &$notificationsSent Tham chiếu đến biến đếm số thông báo đã gửi
     */
    private function checkInvoicesDueIn($today, $days, $period, &$notificationsSent)
    {
        /**
         * Tính ngày đến hạn dựa trên số ngày trước ngày đến hạn
         * 
         * $today->copy() - Tạo bản sao của Carbon instance $today
         *   - copy() là method của Carbon để tạo bản sao, không thay đổi instance gốc
         *   - Cần copy() vì addDays() sẽ thay đổi instance, nếu không copy sẽ ảnh hưởng đến $today
         *   - Ví dụ: Nếu $today = 2024-01-15 và $days = 3, sau copy() và addDays(3) => 2024-01-18
         * 
         * ->addDays($days) - Thêm số ngày vào date
         *   - addDays() là method của Carbon để thêm số ngày vào date
         *   - $days là số ngày trước ngày đến hạn (ví dụ: 3, 2, 1)
         *   - Ví dụ: Nếu $today = 2024-01-15 và $days = 3, addDays(3) => 2024-01-18
         *   - Kết quả: Ngày đến hạn = hôm nay + $days ngày
         * 
         * $dueDate - Biến lưu Carbon instance của ngày đến hạn đã tính
         *   - Sẽ được sử dụng để query invoices có due_date = $dueDate
         */
        $dueDate = $today->copy()->addDays($days);
        
        /**
         * Query các invoices sắp đến hạn từ database
         * 
         * Invoice::whereIn('status', ['issued', 'overdue']) - Lọc theo status
         *   - whereIn() là method của Eloquent query builder để lọc với điều kiện IN
         *   - 'status' là tên column trong bảng invoices
         *   - ['issued', 'overdue'] là array chứa các giá trị status hợp lệ
         *   - Chỉ lấy invoices có status = 'issued' (đã phát hành) hoặc 'overdue' (quá hạn)
         *   - Không lấy invoices có status = 'paid' (đã thanh toán), 'cancelled' (đã hủy), 'draft' (nháp)
         * 
         * ->whereDate('due_date', $dueDate->toDateString()) - Lọc theo ngày đến hạn
         *   - whereDate() là method của Eloquent để so sánh date (bỏ qua phần time)
         *   - 'due_date' là tên column trong bảng invoices
         *   - $dueDate->toDateString() chuyển Carbon instance thành string format YYYY-MM-DD
         *   - Ví dụ: toDateString() = "2024-01-18"
         *   - Chỉ lấy invoices có due_date = $dueDate (chính xác ngày, không quan tâm giờ)
         * 
         * ->with(['lease.tenant', 'lease.agent', 'organization']) - Eager load relationships
         *   - with() là method của Eloquent để eager load relationships (tránh N+1 queries)
         *   - 'lease.tenant' - Load relationship lease và tenant (nested relationship)
         *     - lease là relationship từ Invoice model (BelongsTo)
         *     - tenant là relationship từ Lease model (BelongsTo)
         *   - 'lease.agent' - Load relationship lease và agent (nested relationship)
         *     - agent là relationship từ Lease model (BelongsTo)
         *   - 'organization' - Load relationship organization từ Invoice model (BelongsTo)
         *   - Eager loading sẽ execute các queries: SELECT * FROM leases, SELECT * FROM users (tenant, agent), SELECT * FROM organizations
         *   - Tránh N+1 queries: thay vì query tenant/agent cho từng invoice riêng lẻ, tất cả được load trong 1 query
         * 
         * ->get() - Thực thi query và trả về Collection chứa các Invoice models
         *   - get() sẽ execute SQL query: SELECT * FROM invoices WHERE status IN ('issued', 'overdue') AND DATE(due_date) = '2024-01-18'
         *   - Trả về Collection chứa các Invoice models với relationships đã được load sẵn
         * 
         * $dueInvoices - Biến lưu Collection chứa các Invoice models sắp đến hạn
         */
        $dueInvoices = Invoice::whereIn('status', ['issued', 'overdue'])
            ->whereDate('due_date', $dueDate->toDateString())
            ->with(['lease.tenant', 'lease.agent', 'organization'])
            ->get();
            
        /**
         * Hiển thị số lượng invoices tìm được
         * 
         * $dueInvoices->count() - Đếm số phần tử trong Collection
         *   - count() là method của Laravel Collection để đếm số phần tử
         *   - Trả về integer (số lượng invoices)
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Found {$dueInvoices->count()} invoices due in {$period}"
         *   - {$dueInvoices->count()} và {$period} là string interpolation
         *   - Ví dụ: "Found 5 invoices due in 3 days"
         *   - Thông báo cho user biết số lượng invoices tìm được
         */
        $this->info("Found {$dueInvoices->count()} invoices due in {$period}");
        
        /**
         * Xử lý từng invoice sắp đến hạn bằng vòng lặp foreach
         * 
         * foreach ($dueInvoices as $invoice) - Lặp qua từng phần tử trong Collection
         * - $dueInvoices là Collection chứa các Invoice models đã query được
         * - $invoice là từng Invoice model trong Collection
         * - Mỗi lần lặp, $invoice sẽ là một instance của App\Models\Invoice
         * 
         * $this->sendDueDateNotifications($invoice, $period, $notificationsSent) - Gọi method để gửi thông báo
         *   - sendDueDateNotifications() là private method để gửi thông báo sắp đến hạn
         *   - $invoice - Invoice model cần gửi thông báo
         *   - $period - Mô tả khoảng thời gian (ví dụ: '3 days')
         *   - $notificationsSent - Tham chiếu đến biến đếm (sẽ được cập nhật trong method)
         *   - Method này sẽ gửi thông báo cho tenant, agent, và manager
         */
        foreach ($dueInvoices as $invoice) {
            $this->sendDueDateNotifications($invoice, $period, $notificationsSent);
        }
    }
    
    /**
     * Kiểm tra các hóa đơn quá hạn X ngày
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tính ngày quá hạn: today - $daysOverdue
     * 2. Query từ bảng invoices:
     *    - Status = 'overdue' (chỉ hóa đơn quá hạn)
     *    - due_date = ngày quá hạn đã tính
     *    - Load relationship: lease.tenant, lease.agent, organization
     * 3. Với mỗi hóa đơn: Gọi sendOverdueNotifications() để gửi thông báo
     * 
     * @param Carbon $today Thời gian hiện tại
     * @param int $daysOverdue Số ngày quá hạn
     * @param string $period Mô tả khoảng thời gian (ví dụ: '1 day overdue')
     * @param int &$notificationsSent Tham chiếu đến biến đếm số thông báo đã gửi
     */
    private function checkOverdueInvoices($today, $daysOverdue, $period, &$notificationsSent)
    {
        /**
         * Tính ngày quá hạn dựa trên số ngày quá hạn
         * 
         * $today->copy() - Tạo bản sao của Carbon instance $today
         *   - copy() là method của Carbon để tạo bản sao, không thay đổi instance gốc
         *   - Cần copy() vì subDays() sẽ thay đổi instance, nếu không copy sẽ ảnh hưởng đến $today
         *   - Ví dụ: Nếu $today = 2024-01-15 và $daysOverdue = 1, sau copy() và subDays(1) => 2024-01-14
         * 
         * ->subDays($daysOverdue) - Trừ số ngày từ date
         *   - subDays() là method của Carbon để trừ số ngày từ date
         *   - $daysOverdue là số ngày quá hạn (ví dụ: 1, 2, 3)
         *   - Ví dụ: Nếu $today = 2024-01-15 và $daysOverdue = 1, subDays(1) => 2024-01-14
         *   - Kết quả: Ngày quá hạn = hôm nay - $daysOverdue ngày
         * 
         * $overdueDate - Biến lưu Carbon instance của ngày quá hạn đã tính
         *   - Sẽ được sử dụng để query invoices có due_date = $overdueDate
         */
        $overdueDate = $today->copy()->subDays($daysOverdue);
        
        /**
         * Query các invoices quá hạn từ database
         * 
         * Invoice::where('status', 'overdue') - Lọc theo status = 'overdue'
         *   - where() là method của Eloquent query builder để lọc với điều kiện
         *   - 'status' là tên column trong bảng invoices
         *   - 'overdue' là giá trị status (quá hạn)
         *   - Chỉ lấy invoices có status = 'overdue' (quá hạn)
         *   - Không lấy invoices có status = 'issued' (đã phát hành), 'paid' (đã thanh toán), etc.
         * 
         * ->whereDate('due_date', $overdueDate->toDateString()) - Lọc theo ngày đến hạn
         *   - whereDate() là method của Eloquent để so sánh date (bỏ qua phần time)
         *   - 'due_date' là tên column trong bảng invoices
         *   - $overdueDate->toDateString() chuyển Carbon instance thành string format YYYY-MM-DD
         *   - Ví dụ: toDateString() = "2024-01-14"
         *   - Chỉ lấy invoices có due_date = $overdueDate (chính xác ngày, không quan tâm giờ)
         * 
         * ->with(['lease.tenant', 'lease.agent', 'organization']) - Eager load relationships
         *   - with() là method của Eloquent để eager load relationships (tránh N+1 queries)
         *   - 'lease.tenant' - Load relationship lease và tenant (nested relationship)
         *   - 'lease.agent' - Load relationship lease và agent (nested relationship)
         *   - 'organization' - Load relationship organization từ Invoice model
         *   - Eager loading sẽ execute các queries: SELECT * FROM leases, SELECT * FROM users, SELECT * FROM organizations
         *   - Tránh N+1 queries: thay vì query tenant/agent cho từng invoice riêng lẻ, tất cả được load trong 1 query
         * 
         * ->get() - Thực thi query và trả về Collection chứa các Invoice models
         *   - get() sẽ execute SQL query: SELECT * FROM invoices WHERE status = 'overdue' AND DATE(due_date) = '2024-01-14'
         *   - Trả về Collection chứa các Invoice models với relationships đã được load sẵn
         * 
         * $overdueInvoices - Biến lưu Collection chứa các Invoice models quá hạn
         */
        $overdueInvoices = Invoice::where('status', 'overdue')
            ->whereDate('due_date', $overdueDate->toDateString())
            ->with(['lease.tenant', 'lease.agent', 'organization'])
            ->get();
            
        /**
         * Hiển thị số lượng invoices quá hạn tìm được
         * 
         * $overdueInvoices->count() - Đếm số phần tử trong Collection
         *   - count() là method của Laravel Collection để đếm số phần tử
         *   - Trả về integer (số lượng invoices)
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Found {$overdueInvoices->count()} invoices {$period}"
         *   - {$overdueInvoices->count()} và {$period} là string interpolation
         *   - Ví dụ: "Found 3 invoices 1 day overdue"
         *   - Thông báo cho user biết số lượng invoices quá hạn tìm được
         */
        $this->info("Found {$overdueInvoices->count()} invoices {$period}");
        
        /**
         * Xử lý từng invoice quá hạn bằng vòng lặp foreach
         * 
         * foreach ($overdueInvoices as $invoice) - Lặp qua từng phần tử trong Collection
         * - $overdueInvoices là Collection chứa các Invoice models đã query được
         * - $invoice là từng Invoice model trong Collection
         * - Mỗi lần lặp, $invoice sẽ là một instance của App\Models\Invoice
         * 
         * $this->sendOverdueNotifications($invoice, $period, $notificationsSent) - Gọi method để gửi thông báo
         *   - sendOverdueNotifications() là private method để gửi thông báo quá hạn
         *   - $invoice - Invoice model cần gửi thông báo
         *   - $period - Mô tả khoảng thời gian (ví dụ: '1 day overdue')
         *   - $notificationsSent - Tham chiếu đến biến đếm (sẽ được cập nhật trong method)
         *   - Method này sẽ gửi thông báo cho tenant, agent, và manager
         */
        foreach ($overdueInvoices as $invoice) {
            $this->sendOverdueNotifications($invoice, $period, $notificationsSent);
        }
    }
    
    /**
     * Gửi thông báo sắp đến hạn cho tenant, agent và manager
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra xem đã gửi thông báo hôm nay chưa (tránh duplicate)
     * 2. Gửi thông báo cho tenant: Email + in-app notification
     * 3. Gửi thông báo cho agent: In-app notification
     * 4. Gửi thông báo cho manager: In-app notification
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\Notification (bảng notifications) - kiểm tra duplicate
     * - Model: App\Models\Invoice (đã được truyền vào)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app notifications)
     * - Gửi email qua Mail facade
     * 
     * @param Invoice $invoice Hóa đơn cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '3 days')
     * @param int &$notificationsSent Tham chiếu đến biến đếm số thông báo đã gửi
     */
    private function sendDueDateNotifications($invoice, $period, &$notificationsSent)
    {
        try {
            $lease = $invoice->lease;
            if (!$lease) {
                return;
            }
            
            // Get all recipients (tenant + residents)
            $recipients = $this->getLeaseRecipients($lease);
            $recipientIds = array_map(fn($r) => $r->id, $recipients);
            
            // Check if notifications already sent today for this invoice to any recipient
            $today = now()->toDateString();
            $existingNotifications = Notification::whereIn('to_user_id', $recipientIds)
                ->where('subject', 'like', '%' . $invoice->invoice_no . '%')
                ->where('subject', 'like', '%sắp đến hạn%')
                ->whereDate('created_at', $today)
                ->count();
                
            if ($existingNotifications > 0) {
                $this->line("Notifications already sent today for invoice: {$invoice->invoice_no}");
                return;
            }
            
            $tenantName = $lease->tenant->full_name ?? $lease->tenant->name ?? 'N/A';
            $agentName = $lease->agent->full_name ?? $lease->agent->name ?? 'N/A';
            
            // Tenant notifications (includes all recipients: tenant + residents)
            $this->sendTenantDueDateNotification($invoice, $period, $tenantName);
            
            // Agent notifications
            $this->sendAgentDueDateNotification($invoice, $period, $tenantName, $agentName);
            
            // Manager notifications
            $this->sendManagerDueDateNotification($invoice, $period, $tenantName);
            
            // Count notifications: recipients (in-app + emails) + agent (in-app) + manager (in-app)
            $recipientCount = count($recipients);
            $emailCount = count(array_filter($recipients, fn($r) => !empty($r->email)));
            $notificationsSent += $recipientCount + $emailCount + 1 + 1; // recipients (in-app) + emails + agent + manager
            
            $this->line("Sent due date notifications for invoice: {$invoice->invoice_no} (to " . count($recipients) . " recipients)");
            
        } catch (\Exception $e) {
            Log::error('Failed to send due date notifications', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Gửi thông báo quá hạn cho tenant, agent và manager
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra xem đã gửi thông báo hôm nay chưa (tránh duplicate)
     * 2. Gửi thông báo cho tenant: Email + in-app notification
     * 3. Gửi thông báo cho agent: In-app notification
     * 4. Gửi thông báo cho manager: In-app notification
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\Notification (bảng notifications) - kiểm tra duplicate
     * - Model: App\Models\Invoice (đã được truyền vào)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app notifications)
     * - Gửi email qua Mail facade
     * 
     * @param Invoice $invoice Hóa đơn cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '1 day overdue')
     * @param int &$notificationsSent Tham chiếu đến biến đếm số thông báo đã gửi
     */
    private function sendOverdueNotifications($invoice, $period, &$notificationsSent)
    {
        try {
            $lease = $invoice->lease;
            if (!$lease) {
                return;
            }
            
            // Get all recipients (tenant + residents)
            $recipients = $this->getLeaseRecipients($lease);
            $recipientIds = array_map(fn($r) => $r->id, $recipients);
            
            // Check if notifications already sent today for this invoice to any recipient
            $today = now()->toDateString();
            $existingNotifications = Notification::whereIn('to_user_id', $recipientIds)
                ->where('subject', 'like', '%' . $invoice->invoice_no . '%')
                ->where('subject', 'like', '%quá hạn%')
                ->whereDate('created_at', $today)
                ->count();
                
            if ($existingNotifications > 0) {
                $this->line("Notifications already sent today for overdue invoice: {$invoice->invoice_no}");
                return;
            }
            
            $tenantName = $lease->tenant->full_name ?? $lease->tenant->name ?? 'N/A';
            $agentName = $lease->agent->full_name ?? $lease->agent->name ?? 'N/A';
            
            // Tenant notifications (includes all recipients: tenant + residents)
            $this->sendTenantOverdueNotification($invoice, $period, $tenantName);
            
            // Agent notifications
            $this->sendAgentOverdueNotification($invoice, $period, $tenantName, $agentName);
            
            // Manager notifications
            $this->sendManagerOverdueNotification($invoice, $period, $tenantName);
            
            // Count notifications: recipients (in-app + emails) + agent (in-app) + manager (in-app)
            $recipientCount = count($recipients);
            $emailCount = count(array_filter($recipients, fn($r) => !empty($r->email)));
            $notificationsSent += $recipientCount + $emailCount + 1 + 1; // recipients (in-app) + emails + agent + manager
            
            $this->line("Sent overdue notifications for invoice: {$invoice->invoice_no} (to " . count($recipients) . " recipients)");
            
        } catch (\Exception $e) {
            Log::error('Failed to send overdue notifications', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy tất cả recipients từ lease (tenant + lease_residents)
     */
    private function getLeaseRecipients($lease): array
    {
        $recipients = [];
        
        // Add main tenant
        if ($lease->tenant_id) {
            $tenant = \App\Models\User::find($lease->tenant_id);
            if ($tenant) {
                $recipients[] = $tenant;
            }
        }
        
        // Add lease residents
        $residents = \App\Models\LeaseResident::where('lease_id', $lease->id)
            ->whereNotNull('user_id')
            ->with('user')
            ->get();
        
        foreach ($residents as $resident) {
            if ($resident->user && !in_array($resident->user, $recipients, true)) {
                $recipients[] = $resident->user;
            }
        }
        
        return $recipients;
    }

    /**
     * Gửi thông báo sắp đến hạn cho tenant và lease_residents (email + in-app)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tất cả recipients (tenant + lease_residents)
     * 2. Tạo nội dung thông báo (subject và content)
     * 3. Tạo in-app notification cho từng recipient
     * 4. Gửi email cho từng recipient (chỉ nếu có email)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app) cho từng recipient
     * - Gửi email qua Mail facade (không lưu vào database)
     * - Ghi log vào storage/logs/laravel.log
     * 
     * @param Invoice $invoice Hóa đơn cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '3 days')
     * @param string $tenantName Tên tenant
     */
    private function sendTenantDueDateNotification($invoice, $period, $tenantName)
    {
        $lease = $invoice->lease;
        if (!$lease) {
            return;
        }
        
        $recipients = $this->getLeaseRecipients($lease);
        
        $subject = "Thông báo hóa đơn sắp đến hạn - {$invoice->invoice_no}";
        $baseContent = "Chúng tôi xin thông báo rằng hóa đơn sắp đến hạn thanh toán trong {$period}.\n\n";
        $baseContent .= "Thông tin hóa đơn:\n";
        $baseContent .= "- Số hóa đơn: {$invoice->invoice_no}\n";
        $baseContent .= "- Tổng tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " {$invoice->currency}\n";
        $baseContent .= "- Ngày đến hạn: " . Carbon::parse($invoice->due_date)->format('d/m/Y') . "\n";
        $baseContent .= "- Trạng thái: " . ucfirst($invoice->status) . "\n\n";
        $baseContent .= "Vui lòng thanh toán hóa đơn trước ngày đến hạn để tránh phí trễ hạn.\n\n";
        $baseContent .= "Trân trọng,\n";
        $baseContent .= "Hệ thống quản lý thuê";
        
        foreach ($recipients as $recipient) {
            $recipientName = $recipient->full_name ?? $recipient->email ?? 'N/A';
            $content = "Chào {$recipientName},\n\n" . $baseContent;
            
            // Create in-app notification
            Notification::create([
                'channel_id' => 1, // in_app
                'to_user_id' => $recipient->id,
                'subject' => $subject,
                'content' => "Hóa đơn {$invoice->invoice_no} sắp đến hạn thanh toán trong {$period}. Số tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " {$invoice->currency}",
                'status' => 'queued',
                'created_at' => now(),
            ]);
            
            // Send email notification if recipient has email
            if (!empty($recipient->email)) {
                try {
                    $mailable = new \App\Mail\NotificationMail(
                        $subject,
                        $content,
                        $recipientName,
                        'warning',
                        null,
                        null
                    );

                    MailHelper::sendWithOptionalOrgMail($mailable, $recipient->email, $invoice->organization_id);
                    
                    Log::info('Due date email sent to recipient', [
                        'invoice_id' => $invoice->id,
                        'recipient_id' => $recipient->id,
                        'recipient_email' => $recipient->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send due date email to recipient', [
                        'invoice_id' => $invoice->id,
                        'recipient_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    /**
     * Gửi thông báo sắp đến hạn cho agent (chỉ in-app)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo nội dung thông báo (subject và content)
     * 2. Tạo in-app notification trong bảng notifications cho agent
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app)
     * 
     * @param Invoice $invoice Hóa đơn cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '3 days')
     * @param string $tenantName Tên tenant
     * @param string $agentName Tên agent
     */
    private function sendAgentDueDateNotification($invoice, $period, $tenantName, $agentName)
    {
        $subject = "Hóa đơn sắp đến hạn - {$invoice->invoice_no}";
        $content = "Hóa đơn {$invoice->invoice_no} của khách thuê {$tenantName} sắp đến hạn thanh toán trong {$period}. ";
        $content .= "Số tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " {$invoice->currency}. ";
        $content .= "Ngày đến hạn: " . Carbon::parse($invoice->due_date)->format('d/m/Y') . ". ";
        $content .= "Vui lòng nhắc nhở khách thuê thanh toán.";
        
        // Create in-app notification for agent
        Notification::create([
            'channel_id' => 1, // in_app
            'to_user_id' => $invoice->lease->agent_id,
            'subject' => $subject,
            'content' => $content,
            'status' => 'queued',
            'created_at' => now(),
        ]);
    }
    
    /**
     * Gửi thông báo sắp đến hạn cho managers (chỉ in-app)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy danh sách manager có quyền truy cập module 'billing' qua CapabilityService
     * 2. Tạo nội dung thông báo (subject và content)
     * 3. Tạo in-app notification cho từng manager
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - App\Services\CapabilityService::getUsersWithModuleAccess('billing', $organization_id)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app) cho từng manager
     * 
     * @param Invoice $invoice Hóa đơn cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '3 days')
     * @param string $tenantName Tên tenant
     */
    private function sendManagerDueDateNotification($invoice, $period, $tenantName)
    {
        // Get users with billing module access (replaces manager role check)
        $managers = \App\Services\CapabilityService::getUsersWithModuleAccess('billing', $invoice->organization_id);
        
        $subject = "Hóa đơn sắp đến hạn - {$invoice->invoice_no}";
        $content = "Hóa đơn {$invoice->invoice_no} của khách thuê {$tenantName} sắp đến hạn thanh toán trong {$period}. ";
        $content .= "Số tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " {$invoice->currency}. ";
        $content .= "Ngày đến hạn: " . Carbon::parse($invoice->due_date)->format('d/m/Y') . ".";
        
        foreach ($managers as $manager) {
            Notification::create([
                'channel_id' => 1, // in_app
                'to_user_id' => $manager->id,
                'subject' => $subject,
                'content' => $content,
                'status' => 'queued',
                'created_at' => now(),
            ]);
        }
    }
    
    /**
     * Gửi thông báo quá hạn cho tenant và lease_residents (email + in-app)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tất cả recipients (tenant + lease_residents)
     * 2. Tạo nội dung thông báo (subject và content)
     * 3. Tạo in-app notification cho từng recipient
     * 4. Gửi email cho từng recipient (chỉ nếu có email)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app) cho từng recipient
     * - Gửi email qua Mail facade (không lưu vào database)
     * - Ghi log vào storage/logs/laravel.log
     * 
     * @param Invoice $invoice Hóa đơn cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '1 day overdue')
     * @param string $tenantName Tên tenant
     */
    private function sendTenantOverdueNotification($invoice, $period, $tenantName)
    {
        $lease = $invoice->lease;
        if (!$lease) {
            return;
        }
        
        $recipients = $this->getLeaseRecipients($lease);
        
        $subject = "Thông báo hóa đơn quá hạn - {$invoice->invoice_no}";
        $baseContent = "Chúng tôi xin thông báo rằng hóa đơn đã quá hạn thanh toán {$period}.\n\n";
        $baseContent .= "Thông tin hóa đơn:\n";
        $baseContent .= "- Số hóa đơn: {$invoice->invoice_no}\n";
        $baseContent .= "- Tổng tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " {$invoice->currency}\n";
        $baseContent .= "- Ngày đến hạn: " . Carbon::parse($invoice->due_date)->format('d/m/Y') . "\n";
        $baseContent .= "- Trạng thái: Quá hạn\n\n";
        $baseContent .= "Vui lòng thanh toán hóa đơn ngay lập tức để tránh các khoản phí bổ sung.\n\n";
        $baseContent .= "Trân trọng,\n";
        $baseContent .= "Hệ thống quản lý thuê";
        
        foreach ($recipients as $recipient) {
            $recipientName = $recipient->full_name ?? $recipient->email ?? 'N/A';
            $content = "Chào {$recipientName},\n\n" . $baseContent;
            
            // Create in-app notification
            Notification::create([
                'channel_id' => 1, // in_app
                'to_user_id' => $recipient->id,
                'subject' => $subject,
                'content' => "Hóa đơn {$invoice->invoice_no} đã quá hạn thanh toán {$period}. Số tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " {$invoice->currency}",
                'status' => 'queued',
                'created_at' => now(),
            ]);
            
            // Send email notification if recipient has email
            if (!empty($recipient->email)) {
                try {
                    $mailable = new \App\Mail\NotificationMail(
                        $subject,
                        $content,
                        $recipientName,
                        'error',
                        null,
                        null
                    );

                    MailHelper::sendWithOptionalOrgMail($mailable, $recipient->email, $invoice->organization_id);
                    
                    Log::info('Overdue email sent to recipient', [
                        'invoice_id' => $invoice->id,
                        'recipient_id' => $recipient->id,
                        'recipient_email' => $recipient->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send overdue email to recipient', [
                        'invoice_id' => $invoice->id,
                        'recipient_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    /**
     * Gửi thông báo quá hạn cho agent (chỉ in-app)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo nội dung thông báo (subject và content)
     * 2. Tạo in-app notification trong bảng notifications cho agent
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app)
     * 
     * @param Invoice $invoice Hóa đơn cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '1 day overdue')
     * @param string $tenantName Tên tenant
     * @param string $agentName Tên agent
     */
    private function sendAgentOverdueNotification($invoice, $period, $tenantName, $agentName)
    {
        $subject = "Hóa đơn quá hạn - {$invoice->invoice_no}";
        $content = "Hóa đơn {$invoice->invoice_no} của khách thuê {$tenantName} đã quá hạn thanh toán {$period}. ";
        $content .= "Số tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " {$invoice->currency}. ";
        $content .= "Ngày đến hạn: " . Carbon::parse($invoice->due_date)->format('d/m/Y') . ". ";
        $content .= "Vui lòng liên hệ với khách thuê để thu tiền ngay lập tức.";
        
        // Create in-app notification for agent
        Notification::create([
            'channel_id' => 1, // in_app
            'to_user_id' => $invoice->lease->agent_id,
            'subject' => $subject,
            'content' => $content,
            'status' => 'queued',
            'created_at' => now(),
        ]);
    }
    
    /**
     * Gửi thông báo quá hạn cho managers (chỉ in-app)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy danh sách manager có quyền truy cập module 'billing' qua CapabilityService
     * 2. Tạo nội dung thông báo (subject và content)
     * 3. Tạo in-app notification cho từng manager
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - App\Services\CapabilityService::getUsersWithModuleAccess('billing', $organization_id)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (in-app) cho từng manager
     * 
     * @param Invoice $invoice Hóa đơn cần gửi thông báo
     * @param string $period Khoảng thời gian (ví dụ: '1 day overdue')
     * @param string $tenantName Tên tenant
     */
    private function sendManagerOverdueNotification($invoice, $period, $tenantName)
    {
        // Get users with billing module access (replaces manager role check)
        $managers = \App\Services\CapabilityService::getUsersWithModuleAccess('billing', $invoice->organization_id);
        
        $subject = "Hóa đơn quá hạn - {$invoice->invoice_no}";
        $content = "Hóa đơn {$invoice->invoice_no} của khách thuê {$tenantName} đã quá hạn thanh toán {$period}. ";
        $content .= "Số tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " {$invoice->currency}. ";
        $content .= "Ngày đến hạn: " . Carbon::parse($invoice->due_date)->format('d/m/Y') . ".";
        
        foreach ($managers as $manager) {
            Notification::create([
                'channel_id' => 1, // in_app
                'to_user_id' => $manager->id,
                'subject' => $subject,
                'content' => $content,
                'status' => 'queued',
                'created_at' => now(),
            ]);
        }
    }
}
