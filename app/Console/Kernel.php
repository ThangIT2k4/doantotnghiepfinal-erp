<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class: Kernel
 * 
 * MỤC ĐÍCH:
 * Quản lý scheduling (lịch chạy tự động) và đăng ký các console commands trong ứng dụng Laravel.
 * Class này kế thừa từ Illuminate\Foundation\Console\Kernel và định nghĩa các commands sẽ được chạy tự động theo lịch.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Method schedule(): Định nghĩa lịch chạy tự động cho các commands
 *    - Các commands được schedule sẽ chạy tự động theo thời gian đã định
 *    - Sử dụng Laravel Task Scheduler (cần cấu hình cron job)
 * 2. Method commands(): Đăng ký các commands để có thể gọi từ terminal
 *    - Load tất cả commands từ thư mục app/Console/Commands
 *    - Load commands từ routes/console.php (nếu có)
 * 
 * CÁCH SỬ DỤNG:
 * - Các commands được schedule sẽ chạy tự động khi Laravel Task Scheduler chạy
 * - Cần cấu hình cron job: * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 * - Các commands có thể được gọi thủ công: php artisan {command-name}
 * 
 * LƯU Ý:
 * - withoutOverlapping(): Đảm bảo command không chạy đồng thời nhiều lần
 * - runInBackground(): Chạy command ở background để không block các commands khác
 * - Thứ tự schedule quan trọng: auto-renew (10:00) chạy trước auto-cancel-expired (11:00)
 */
class Kernel extends ConsoleKernel
{
    /**
     * Định nghĩa lịch chạy tự động cho các console commands
     * 
     * LUỒNG XỬ LÝ:
     * 1. Schedule command kiểm tra hợp đồng sắp hết hạn (9:00 AM hàng ngày)
     * 2. Schedule command kiểm tra hóa đơn sắp đến hạn (10:00 AM hàng ngày)
     * 3. Schedule command tự động hủy đặt cọc quá hạn (mỗi giờ)
     * 4. Schedule command tự động hết hạn đặt cọc quá hold_until (mỗi giờ)
     * 5. Schedule command tự động đánh dấu hóa đơn quá hạn (mỗi giờ)
     * 6. Schedule command tự động hủy subscription hết hạn (11:00 AM hàng ngày)
     * 7. Schedule command tự động gia hạn subscription (10:00 AM hàng ngày)
     * 
     * CÁC COMMANDS ĐƯỢC SCHEDULE:
     * - leases:check-expiring: Kiểm tra hợp đồng sắp hết hạn
     *   File: app/Console/Commands/CheckExpiringLeases.php
     *   Thời gian: 9:00 AM hàng ngày
     *   Mục đích: Gửi thông báo cho tenant và agent về hợp đồng sắp hết hạn
     * 
     * - invoices:check-due-date: Kiểm tra hóa đơn sắp đến hạn
     *   File: app/Console/Commands/CheckInvoiceDueDate.php
     *   Thời gian: 10:00 AM hàng ngày
     *   Mục đích: Gửi thông báo cho tenant, agent và manager về hóa đơn sắp đến hạn/quá hạn
     * 
     * - deposits:auto-cancel-overdue: Tự động hủy đặt cọc quá hạn
     *   File: app/Console/Commands/AutoCancelOverdueDeposits.php
     *   Thời gian: Mỗi giờ
     *   Mục đích: Hủy các đặt cọc quá hạn thanh toán và cập nhật trạng thái unit
     * 
     * - deposits:auto-expire-hold-until: Tự động hết hạn đặt cọc quá hold_until
     *   File: app/Console/Commands/AutoExpireHoldUntilDeposits.php
     *   Thời gian: Mỗi giờ
     *   Mục đích: Cập nhật trạng thái hết hạn cho các đặt cọc quá thời gian giữ chỗ
     * 
     * - invoices:auto-mark-overdue: Tự động đánh dấu hóa đơn quá hạn
     *   File: app/Console/Commands/AutoMarkOverdueInvoices.php
     *   Thời gian: Mỗi giờ
     *   Mục đích: Cập nhật status từ 'issued' sang 'overdue' cho các hóa đơn quá hạn
     * 
     * - subscriptions:auto-cancel-expired: Tự động hủy subscription hết hạn
     *   File: app/Console/Commands/AutoCancelExpiredSubscriptions.php
     *   Thời gian: 11:00 AM hàng ngày
     *   Mục đích: Cập nhật trạng thái subscription từ 'trial'/'active' sang 'cancelled'/'expired'
     * 
     * - subscriptions:auto-renew: Tự động gia hạn subscription
     *   File: app/Console/Commands/AutoRenewSubscriptions.php
     *   Thời gian: 10:00 AM hàng ngày (chạy trước auto-cancel-expired)
     *   Mục đích: Tạo subscription mới với trial và invoice khi subscription cũ sắp hết hạn
     * 
     * LƯU Ý:
     * - withoutOverlapping(): Ngăn command chạy đồng thời nhiều lần (nếu command trước chưa xong, bỏ qua lần chạy mới)
     * - runInBackground(): Chạy command ở background để không block các commands khác
     * - Thứ tự schedule: auto-renew (10:00) chạy trước auto-cancel-expired (11:00) để đảm bảo logic đúng
     * 
     * @param Schedule $schedule Đối tượng Schedule để định nghĩa lịch chạy commands
     */
    protected function schedule(Schedule $schedule): void
    {
        /**
         * Schedule 1: Kiểm tra hợp đồng sắp hết hạn
         * 
         * Command: leases:check-expiring
         * File: app/Console/Commands/CheckExpiringLeases.php
         * 
         * Thời gian: 9:00 AM hàng ngày
         * Mục đích: Gửi thông báo cho tenant và agent về hợp đồng sắp hết hạn (2 tháng, 1 tháng, 15 ngày)
         * 
         * withoutOverlapping(): Đảm bảo không chạy đồng thời nhiều lần
         * runInBackground(): Chạy ở background để không block các commands khác
         */
        $schedule->command('leases:check-expiring')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->runInBackground();
            
        /**
         * Schedule 2: Kiểm tra hóa đơn sắp đến hạn và quá hạn
         * 
         * Command: invoices:check-due-date
         * File: app/Console/Commands/CheckInvoiceDueDate.php
         * 
         * Thời gian: 10:00 AM hàng ngày
         * Mục đích: Gửi thông báo cho tenant, agent và manager về hóa đơn sắp đến hạn (3, 2, 1 ngày) và quá hạn (1, 2, 3 ngày)
         * 
         * withoutOverlapping(): Đảm bảo không chạy đồng thời nhiều lần
         * runInBackground(): Chạy ở background để không block các commands khác
         */
        $schedule->command('invoices:check-due-date')
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->runInBackground();
            
        /**
         * Schedule 3: Tự động hủy đặt cọc quá hạn thanh toán
         * 
         * Command: deposits:auto-cancel-overdue
         * File: app/Console/Commands/AutoCancelOverdueDeposits.php
         * 
         * Thời gian: Mỗi giờ
         * Mục đích: 
         * - Hủy các đặt cọc có payment_status = 'pending' và payment_due_date < now
         * - Hủy các invoice liên quan
         * - Cập nhật trạng thái unit về 'available' (nếu không còn deposit/lease khác)
         * 
         * Chạy mỗi giờ để đảm bảo đặt cọc quá hạn được xử lý kịp thời
         * withoutOverlapping(): Đảm bảo không chạy đồng thời nhiều lần
         * runInBackground(): Chạy ở background để không block các commands khác
         */
        $schedule->command('deposits:auto-cancel-overdue')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
            
        /**
         * Schedule 4: Tự động hết hạn đặt cọc quá thời gian giữ chỗ
         * 
         * Command: deposits:auto-expire-hold-until
         * File: app/Console/Commands/AutoExpireHoldUntilDeposits.php
         * 
         * Thời gian: Mỗi giờ
         * Mục đích:
         * - Cập nhật trạng thái hết hạn cho các đặt cọc có hold_until <= now
         * - Đánh dấu các invoice liên quan thành 'expired'
         * - Cập nhật trạng thái unit về 'available' (nếu không còn deposit/lease khác)
         * 
         * Chạy mỗi giờ để đảm bảo đặt cọc quá thời gian giữ chỗ được xử lý kịp thời
         * withoutOverlapping(): Đảm bảo không chạy đồng thời nhiều lần
         * runInBackground(): Chạy ở background để không block các commands khác
         */
        $schedule->command('deposits:auto-expire-hold-until')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
            
        /**
         * Schedule 5: Tự động đánh dấu hóa đơn quá hạn
         * 
         * Command: invoices:auto-mark-overdue
         * File: app/Console/Commands/AutoMarkOverdueInvoices.php
         * 
         * Thời gian: Mỗi giờ
         * Mục đích:
         * - Tìm các hóa đơn có status = 'issued' và due_date < now
         * - Cập nhật status = 'overdue' để đánh dấu hóa đơn đã quá hạn
         * 
         * Chạy mỗi giờ để đảm bảo hóa đơn quá hạn được đánh dấu kịp thời
         * withoutOverlapping(): Đảm bảo không chạy đồng thời nhiều lần
         * runInBackground(): Chạy ở background để không block các commands khác
         */
        $schedule->command('invoices:auto-mark-overdue')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
            
        /**
         * Schedule 6: Tự động hủy subscription hết hạn
         * 
         * Command: subscriptions:auto-cancel-expired
         * File: app/Console/Commands/AutoCancelExpiredSubscriptions.php
         * 
         * Thời gian: 11:00 AM hàng ngày (chạy sau auto-renew)
         * Mục đích:
         * - Tìm các subscription có status = 'trial' hoặc 'active' và current_period_end < now
         * - Cập nhật status = 'cancelled' hoặc 'expired' tùy trường hợp
         * 
         * Chạy sau auto-renew (11:00) để đảm bảo subscription mới được tạo trước khi hủy subscription cũ
         * withoutOverlapping(): Đảm bảo không chạy đồng thời nhiều lần
         * runInBackground(): Chạy ở background để không block các commands khác
         */
        $schedule->command('subscriptions:auto-cancel-expired')
            ->dailyAt('11:00')
            ->withoutOverlapping()
            ->runInBackground();
            
        /**
         * Schedule 7: Tự động gia hạn subscription
         * 
         * Command: subscriptions:auto-renew
         * File: app/Console/Commands/AutoRenewSubscriptions.php
         * 
         * Thời gian: 10:00 AM hàng ngày (chạy trước auto-cancel-expired)
         * Mục đích:
         * - Tìm các subscription có auto_renew = true và sắp hết hạn (trong 7 ngày)
         * - Tạo subscription mới với status = 'trial' (10 ngày trial)
         * - Tạo invoice để thanh toán
         * 
         * Chạy trước auto-cancel-expired (10:00) để đảm bảo subscription mới được tạo trước khi hủy subscription cũ
         * withoutOverlapping(): Đảm bảo không chạy đồng thời nhiều lần
         * runInBackground(): Chạy ở background để không block các commands khác
         */
        $schedule->command('subscriptions:auto-renew')
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->runInBackground();

        /**
         * Schedule 7b: Áp dụng hạ gói đã đặt lịch (sau renew, trước auto-cancel)
         *
         * Command: subscriptions:apply-pending-downgrades
         * File: app/Console/Commands/ApplyPendingSubscriptionDowngrades.php
         */
        $schedule->command('subscriptions:apply-pending-downgrades')
            ->dailyAt('10:30')
            ->withoutOverlapping()
            ->runInBackground();
            
        /**
         * Schedule 8: Tự động xóa vĩnh viễn dữ liệu đã xóa mềm sau 30 ngày
         * 
         * Command: trash:force-delete-old
         * File: app/Console/Commands/ForceDeleteOldTrash.php
         * 
         * Thời gian: 2:00 AM hàng ngày
         * Mục đích:
         * - Tìm các bản ghi đã xóa mềm (soft delete) trước 30 ngày
         * - Xóa vĩnh viễn (force delete) các bản ghi này để giải phóng không gian database
         * - Áp dụng cho tất cả các bảng có soft delete trong hệ thống
         * 
         * Chạy vào 2:00 AM để tránh ảnh hưởng đến hiệu suất hệ thống trong giờ cao điểm
         * withoutOverlapping(): Đảm bảo không chạy đồng thời nhiều lần
         * runInBackground(): Chạy ở background để không block các commands khác
         */
        $schedule->command('trash:force-delete-old')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Đăng ký các console commands cho ứng dụng
     * 
     * LUỒNG XỬ LÝ:
     * 1. Load tất cả commands từ thư mục app/Console/Commands
     *    - Laravel sẽ tự động scan và đăng ký tất cả classes extends Command trong thư mục này
     *    - Mỗi command sẽ được đăng ký với signature được định nghĩa trong class
     * 2. Load commands từ routes/console.php (nếu có)
     *    - File này có thể chứa các commands được định nghĩa bằng closure hoặc route-based commands
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Thư mục: app/Console/Commands/*.php
     *   Tất cả các file Command trong thư mục này sẽ được tự động load
     *   Ví dụ: AutoRenewSubscriptions.php, CheckInvoiceDueDate.php, etc.
     * 
     * - File: routes/console.php (nếu có)
     *   File này có thể chứa các commands được định nghĩa bằng Artisan::command()
     * 
     * LƯU Ý:
     * - Laravel tự động scan và đăng ký tất cả commands trong thư mục Commands
     * - Commands được đăng ký sẽ có thể gọi từ terminal: php artisan {command-signature}
     * - Không cần đăng ký thủ công từng command, Laravel tự động làm điều này
     */
    protected function commands(): void
    {
        /**
         * Load tất cả commands từ thư mục app/Console/Commands
         * 
         * Laravel sẽ tự động:
         * - Scan tất cả files trong thư mục Commands
         * - Tìm các classes extends Illuminate\Console\Command
         * - Đăng ký các commands với signature được định nghĩa trong class
         * 
         * Ví dụ:
         * - AutoRenewSubscriptions.php với signature 'subscriptions:auto-renew'
         * - CheckInvoiceDueDate.php với signature 'invoices:check-due-date'
         * - etc.
         */
        $this->load(__DIR__.'/Commands');

        /**
         * Load commands từ routes/console.php (nếu có)
         * 
         * File routes/console.php có thể chứa:
         * - Commands được định nghĩa bằng Artisan::command()
         * - Route-based commands
         * 
         * Nếu file không tồn tại, require sẽ không gây lỗi (Laravel xử lý gracefully)
         */
        require base_path('routes/console.php');
    }
}
