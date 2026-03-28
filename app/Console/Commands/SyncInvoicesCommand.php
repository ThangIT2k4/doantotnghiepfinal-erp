<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceSyncService;
use App\Models\Invoice;

/**
 * Command: SyncInvoicesCommand
 * 
 * MỤC ĐÍCH:
 * Đồng bộ hóa đơn với các thay đổi dữ liệu liên quan (tính lại tổng tiền, cập nhật trạng thái).
 * Command này được dùng để đảm bảo dữ liệu hóa đơn luôn đồng bộ với dữ liệu liên quan.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận tham số từ command line:
 *    - --all: Sync tất cả hóa đơn pending
 *    - --invoice-id: Sync một hóa đơn cụ thể
 *    - --force: Force sync ngay cả khi hóa đơn đã paid
 * 2. Xử lý:
 *    - Nếu có --invoice-id: Gọi syncSpecificInvoice()
 *    - Nếu có --all: Gọi syncAllInvoices()
 *    - Nếu không có: Hiển thị hướng dẫn
 * 3. Gọi InvoiceSyncService để tính lại và cập nhật hóa đơn
 * 
 * CÁCH CHẠY:
 * php artisan invoices:sync [--all] [--invoice-id=ID] [--force]
 * 
 * Ví dụ:
 * php artisan invoices:sync --all
 * php artisan invoices:sync --invoice-id=123
 * php artisan invoices:sync --invoice-id=123 --force
 */
class SyncInvoicesCommand extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --all: Sync all pending invoices
     * - --invoice-id: Sync specific invoice by ID
     * - --force: Force sync even if invoice is paid
     * 
     * @var string
     */
    protected $signature = 'invoices:sync 
                            {--all : Sync all pending invoices}
                            {--invoice-id= : Sync specific invoice by ID}
                            {--force : Force sync even if invoice is paid}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Sync invoices with related data changes';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Khởi tạo InvoiceSyncService
     * 2. Kiểm tra options:
     *    - Nếu có --invoice-id: Gọi syncSpecificInvoice()
     *    - Nếu có --all: Gọi syncAllInvoices()
     *    - Nếu không có: Hiển thị hướng dẫn sử dụng
     * 
     * SERVICE ĐƯỢC GỌI:
     * - App\Services\InvoiceSyncService
     *   Methods:
     *   + recalculateInvoiceTotals($invoice) - tính lại tổng tiền hóa đơn
     *   + syncAllPendingInvoices() - sync tất cả hóa đơn pending
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        /**
         * Khởi tạo InvoiceSyncService instance
         * 
         * new InvoiceSyncService() - Tạo instance mới của InvoiceSyncService class
         *   - InvoiceSyncService nằm tại: app/Services/InvoiceSyncService.php
         *   - Service này chứa business logic để sync invoices (tính lại tổng tiền, cập nhật trạng thái)
         *   - Service sẽ được sử dụng để gọi các methods: recalculateInvoiceTotals(), syncAllPendingInvoices()
         *   - Không sử dụng dependency injection vì service này không có dependencies phức tạp
         */
        $invoiceSyncService = new InvoiceSyncService();
        
        /**
         * Kiểm tra xem có option --invoice-id không
         * 
         * $this->option('invoice-id') - Lấy giá trị của option 'invoice-id' từ command line
         *   - option() là method của Laravel Command để lấy giá trị của command option
         *   - 'invoice-id' là tên option được định nghĩa trong $signature
         *   - Nếu user chạy: php artisan invoices:sync --invoice-id=123 => trả về "123"
         *   - Nếu user không cung cấp option này => trả về null
         *   - Trả về string (giá trị option) hoặc null
         * 
         * if ($this->option('invoice-id')) - Kiểm tra xem có giá trị không
         *   - Nếu có giá trị (không null, không empty), vào block if
         *   - Gọi syncSpecificInvoice() để sync một invoice cụ thể
         *   - return để dừng command, không thực thi code phía dưới
         */
        if ($this->option('invoice-id')) {
            return $this->syncSpecificInvoice($invoiceSyncService);
        }
        
        /**
         * Kiểm tra xem có option --all không
         * 
         * $this->option('all') - Lấy giá trị của option 'all' từ command line
         *   - 'all' là tên option được định nghĩa trong $signature
         *   - Nếu user chạy: php artisan invoices:sync --all => trả về true
         *   - Nếu user không cung cấp option này => trả về false
         *   - Trả về boolean (true/false)
         * 
         * if ($this->option('all')) - Kiểm tra xem có giá trị true không
         *   - Nếu true, vào block if
         *   - Gọi syncAllInvoices() để sync tất cả pending invoices
         *   - return để dừng command, không thực thi code phía dưới
         */
        if ($this->option('all')) {
            return $this->syncAllInvoices($invoiceSyncService);
        }
        
        /**
         * Nếu không có option nào được cung cấp, hiển thị hướng dẫn sử dụng
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - 'Invoice Sync Command' - Tiêu đề command
         * 
         * $this->line() - Hiển thị message màu trắng trong console (thông tin bình thường)
         *   - 'Available options:' - Tiêu đề phần options
         *   - '  --all              Sync all pending invoices' - Mô tả option --all
         *   - '  --invoice-id=ID    Sync specific invoice' - Mô tả option --invoice-id
         *   - '  --force            Force sync even if paid' - Mô tả option --force
         *   - Hiển thị hướng dẫn cho user biết cách sử dụng command
         */
        $this->info('Invoice Sync Command');
        $this->line('Available options:');
        $this->line('  --all              Sync all pending invoices');
        $this->line('  --invoice-id=ID    Sync specific invoice');
        $this->line('  --force            Force sync even if paid');
        
        // Trả về 0 (Command::SUCCESS) để báo command đã hoàn thành (hiển thị hướng dẫn)
        return 0;
    }

    /**
     * Sync một hóa đơn cụ thể
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy invoice_id từ option --invoice-id
     * 2. Tìm invoice trong database
     * 3. Kiểm tra trạng thái:
     *    - Nếu invoice đã paid hoặc cancelled và không có --force: Báo lỗi
     * 4. Gọi InvoiceSyncService->recalculateInvoiceTotals() để tính lại hóa đơn
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\Invoice (bảng invoices)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Cập nhật bảng invoices (qua InvoiceSyncService)
     * 
     * @param InvoiceSyncService $service Service xử lý sync invoice
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    private function syncSpecificInvoice(InvoiceSyncService $service)
    {
        /**
         * Lấy invoice_id từ option --invoice-id
         * 
         * $this->option('invoice-id') - Lấy giá trị của option 'invoice-id' từ command line
         *   - option() là method của Laravel Command để lấy giá trị của command option
         *   - 'invoice-id' là tên option được định nghĩa trong $signature
         *   - Nếu user chạy: php artisan invoices:sync --invoice-id=123 => trả về "123"
         *   - Trả về string (ID của invoice cần sync)
         * 
         * $invoiceId - Biến lưu ID của invoice cần sync
         *   - Sẽ được sử dụng để tìm invoice trong database
         */
        $invoiceId = $this->option('invoice-id');
        
        /**
         * Lấy giá trị của option --force
         * 
         * $this->option('force') - Lấy giá trị của option 'force' từ command line
         *   - 'force' là tên option được định nghĩa trong $signature
         *   - Nếu user chạy: php artisan invoices:sync --invoice-id=123 --force => trả về true
         *   - Nếu user không cung cấp option này => trả về false
         *   - Trả về boolean (true/false)
         * 
         * $force - Biến lưu trạng thái force mode
         *   - true = force sync (sync ngay cả khi invoice đã paid/cancelled)
         *   - false = normal mode (không sync invoice đã paid/cancelled)
         */
        $force = $this->option('force');
        
        /**
         * Tìm invoice trong database bằng ID
         * 
         * Invoice::find($invoiceId) - Tìm invoice trong database bằng primary key
         *   - find() là method của Eloquent Model để tìm record bằng ID
         *   - find() sẽ execute SQL query: SELECT * FROM invoices WHERE id = $invoiceId LIMIT 1
         *   - Trả về Invoice model instance nếu tìm thấy
         *   - Trả về null nếu không tìm thấy
         *   - find() chỉ tìm bằng primary key (id), không tìm bằng các field khác
         * 
         * $invoice - Biến lưu Invoice model instance (hoặc null)
         *   - Nếu tìm thấy: $invoice là instance của App\Models\Invoice
         *   - Nếu không tìm thấy: $invoice = null
         */
        $invoice = Invoice::find($invoiceId);
        
        /**
         * Kiểm tra xem invoice có tồn tại không
         * 
         * if (!$invoice) - Kiểm tra xem $invoice có null không
         *   - ! là NOT operator, đảo ngược giá trị boolean
         *   - Nếu $invoice = null, !null = true, vào block if
         *   - Nếu $invoice != null, !$invoice = false, không vào block if
         * 
         * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
         *   - "Invoice with ID {$invoiceId} not found."
         *   - {$invoiceId} là string interpolation, sẽ thay thế bằng giá trị $invoiceId
         *   - Ví dụ: "Invoice with ID 123 not found."
         *   - Hiển thị lỗi cho user để họ biết invoice không tồn tại
         * 
         * return 1 - Trả về 1 (Command::FAILURE) để báo command thất bại
         *   - 1 nghĩa là command thất bại, có lỗi
         *   - Command sẽ dừng ở đây, không thực thi code phía dưới
         */
        if (!$invoice) {
            $this->error("Invoice with ID {$invoiceId} not found.");
            return 1;
        }
        
        /**
         * Kiểm tra xem có cần force sync không
         * 
         * if (!$force && in_array($invoice->status, ['paid', 'cancelled'])) - Kiểm tra điều kiện
         *   - !$force - Không có force mode (NOT operator)
         *   - && - Logical AND operator (cả hai điều kiện phải true)
         *   - in_array($invoice->status, ['paid', 'cancelled']) - Kiểm tra status có trong array không
         *     - in_array() là PHP built-in function để kiểm tra giá trị có trong array không
         *     - $invoice->status truy cập field status từ Invoice model
         *     - ['paid', 'cancelled'] là array chứa các status không nên sync (trừ khi force)
         *     - 'paid' nghĩa là invoice đã thanh toán (không nên thay đổi)
         *     - 'cancelled' nghĩa là invoice đã bị hủy (không nên thay đổi)
         *   - Chỉ vào block if khi: không có force VÀ status là paid hoặc cancelled
         *   - Nếu có force, không vào block if (cho phép sync invoice đã paid/cancelled)
         * 
         * $this->warn() - Hiển thị message màu vàng trong console (cảnh báo)
         *   - "Invoice {$invoiceId} is {$invoice->status}. Use --force to sync anyway."
         *   - {$invoiceId} và {$invoice->status} là string interpolation
         *   - Ví dụ: "Invoice 123 is paid. Use --force to sync anyway."
         *   - Hướng dẫn user cách force sync nếu cần
         * 
         * return 1 - Trả về 1 (Command::FAILURE) để báo command thất bại
         *   - Command sẽ dừng ở đây, không sync invoice
         */
        if (!$force && in_array($invoice->status, ['paid', 'cancelled'])) {
            $this->warn("Invoice {$invoiceId} is {$invoice->status}. Use --force to sync anyway.");
            return 1;
        }
        
        /**
         * Hiển thị thông báo bắt đầu sync invoice
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Syncing invoice {$invoiceId}..."
         *   - {$invoiceId} là string interpolation
         *   - Ví dụ: "Syncing invoice 123..."
         *   - Thông báo cho user biết đang sync invoice nào
         */
        $this->info("Syncing invoice {$invoiceId}...");
        
        /**
         * Gọi service để tính lại và sync invoice
         * 
         * $service->recalculateInvoiceTotals($invoice) - Gọi method để tính lại tổng tiền invoice
         *   - recalculateInvoiceTotals() là method của InvoiceSyncService
         *   - Method này nhận Invoice model instance làm tham số
         *   - Method này sẽ:
         *     1. Tính lại tổng tiền từ các invoice items
         *     2. Cập nhật invoice->total_amount, subtotal, tax, discount (nếu có)
         *     3. Cập nhật invoice->status nếu cần (dựa trên payment status)
         *     4. Lưu vào database
         *   - Trả về boolean: true nếu thành công, false nếu thất bại
         * 
         * if ($service->recalculateInvoiceTotals($invoice)) - Kiểm tra kết quả
         *   - Nếu true (thành công), vào block if
         *   - Nếu false (thất bại), vào block else
         */
        if ($service->recalculateInvoiceTotals($invoice)) {
            /**
             * Hiển thị thông báo thành công
             * 
             * $this->info() - Hiển thị message màu xanh trong console
             *   - "✓ Invoice {$invoiceId} synced successfully."
             *   - ✓ là ký tự checkmark (Unicode)
             *   - {$invoiceId} là string interpolation
             *   - Ví dụ: "✓ Invoice 123 synced successfully."
             *   - Thông báo cho user biết sync thành công
             * 
             * return 0 - Trả về 0 (Command::SUCCESS) để báo command thành công
             */
            $this->info("✓ Invoice {$invoiceId} synced successfully.");
            return 0;
        } else {
            /**
             * Hiển thị thông báo thất bại
             * 
             * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
             *   - "✗ Failed to sync invoice {$invoiceId}."
             *   - ✗ là ký tự cross mark (Unicode)
             *   - {$invoiceId} là string interpolation
             *   - Ví dụ: "✗ Failed to sync invoice 123."
             *   - Thông báo cho user biết sync thất bại
             * 
             * return 1 - Trả về 1 (Command::FAILURE) để báo command thất bại
             */
            $this->error("✗ Failed to sync invoice {$invoiceId}.");
            return 1;
        }
    }

    /**
     * Sync tất cả hóa đơn pending
     * 
     * LUỒNG XỬ LÝ:
     * 1. Gọi InvoiceSyncService->syncAllPendingInvoices()
     * 2. Service sẽ:
     *    - Tìm tất cả hóa đơn có trạng thái pending
     *    - Tính lại tổng tiền cho từng hóa đơn
     *    - Cập nhật vào database
     * 3. Hiển thị số lượng hóa đơn đã sync
     * 
     * SERVICE ĐƯỢC GỌI:
     * - App\Services\InvoiceSyncService::syncAllPendingInvoices()
     *   Method này sẽ:
     *   + Tìm tất cả invoice có status = 'pending'
     *   + Gọi recalculateInvoiceTotals() cho từng invoice
     *   + Trả về số lượng invoice đã sync
     * 
     * @param InvoiceSyncService $service Service xử lý sync invoice
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    private function syncAllInvoices(InvoiceSyncService $service)
    {
        // Hiển thị thông báo bắt đầu sync tất cả pending invoices
        // $this->info() - Hiển thị message màu xanh trong console
        $this->info('Syncing all pending invoices...');
        
        /**
         * Gọi service để sync tất cả pending invoices
         * 
         * $service->syncAllPendingInvoices() - Gọi method để sync tất cả pending invoices
         *   - syncAllPendingInvoices() là method của InvoiceSyncService
         *   - Method này sẽ:
         *     1. Query tất cả invoices có status = 'pending' từ database
         *     2. Với mỗi invoice: Gọi recalculateInvoiceTotals() để tính lại tổng tiền
         *     3. Cập nhật invoice vào database
         *     4. Đếm số lượng invoice đã sync thành công
         *   - Trả về integer: số lượng invoice đã sync thành công
         *   - Nếu không có invoice nào pending, trả về 0
         * 
         * $syncedCount - Biến lưu số lượng invoice đã sync thành công
         *   - Sẽ được sử dụng để hiển thị kết quả cho user
         */
        $syncedCount = $service->syncAllPendingInvoices();
        
        /**
         * Hiển thị kết quả cho người dùng dựa trên số lượng invoice đã sync
         * 
         * if ($syncedCount > 0) - Kiểm tra xem có invoice nào được sync không
         *   - $syncedCount > 0 nghĩa là có ít nhất 1 invoice đã được sync thành công
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "✓ Successfully synced {$syncedCount} invoices."
         *   - ✓ là ký tự checkmark (Unicode)
         *   - {$syncedCount} là string interpolation, sẽ thay thế bằng giá trị $syncedCount
         *   - Ví dụ: Nếu $syncedCount = 10, message sẽ là "✓ Successfully synced 10 invoices."
         *   - Thông báo cho user biết số lượng invoice đã sync thành công
         * 
         * else - Nếu không có invoice nào được sync ($syncedCount = 0)
         *   - $this->warn() - Hiển thị message màu vàng trong console (cảnh báo)
         *   - 'No invoices were synced.' - Thông báo không có invoice nào được sync
         *   - Có thể do: không có invoice pending, hoặc tất cả đều đã được sync trước đó
         */
        if ($syncedCount > 0) {
            $this->info("✓ Successfully synced {$syncedCount} invoices.");
        } else {
            $this->warn('No invoices were synced.');
        }
        
        // Trả về 0 (Command::SUCCESS) để báo command đã hoàn thành thành công
        // Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thành công không
        return 0;
    }
}
