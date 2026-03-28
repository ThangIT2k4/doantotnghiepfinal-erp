<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lease;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Observers\LeaseObserver;
use Illuminate\Support\Facades\Log;

/**
 * Command: CreateMissingInvoices
 * 
 * MỤC ĐÍCH:
 * Tạo hóa đơn bị thiếu cho các hợp đồng thuê (lease) chưa có hóa đơn nào.
 * Command này được dùng để đảm bảo tất cả hợp đồng thuê đều có hóa đơn tương ứng.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận dữ liệu từ: Model Lease (bảng leases)
 * 2. Tìm các hợp đồng thuê chưa có hóa đơn:
 *    - Load relationship leases->invoices
 *    - Lọc các lease có invoices->count() == 0
 * 3. Xử lý:
 *    - Hiển thị danh sách lease cần tạo invoice
 *    - Xác nhận từ người dùng (nếu không có --dry-run)
 *    - Với mỗi lease: Gọi LeaseObserver::createInvoiceForExistingLease()
 * 4. Ghi log: Lưu thông tin vào Log để theo dõi
 * 
 * CÁCH CHẠY:
 * php artisan invoices:create-missing [--dry-run]
 * 
 * Options:
 * --dry-run: Chỉ hiển thị những gì sẽ được tạo, không thực sự tạo invoice
 */
class CreateMissingInvoices extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --dry-run: Show what would be done without actually creating invoices
     * 
     * @var string
     */
    protected $signature = 'invoices:create-missing {--dry-run : Show what would be done without actually creating invoices}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Create missing invoices for leases that don\'t have any invoices';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Kiểm tra dry-run mode (nếu có flag --dry-run)
     * 2. Query từ bảng leases:
     *    - Load relationship: invoices
     *    - Lấy tất cả leases
     * 3. Lọc các lease chưa có invoice:
     *    - Kiểm tra invoices->count() == 0
     * 4. Hiển thị danh sách lease cần tạo invoice (dạng table)
     * 5. Nếu dry-run: Hiển thị thông báo và dừng
     * 6. Nếu không dry-run:
     *    - Xác nhận từ người dùng
     *    - Với mỗi lease: Gọi createInvoiceForLease()
     *    - Hiển thị progress bar
     * 7. Hiển thị kết quả: Số lượng invoice đã tạo
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\Lease (bảng leases)
     * - Relationship: leases->invoices (bảng invoices)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng invoices (qua LeaseObserver)
     * - Tạo bản ghi mới trong bảng invoice_items (qua LeaseObserver)
     * - Ghi log vào storage/logs/laravel.log
     * 
     * OBSERVER ĐƯỢC GỌI:
     * - App\Observers\LeaseObserver::createInvoiceForExistingLease($lease)
     *   File: app/Observers/LeaseObserver.php
     *   Method này sẽ:
     *   + Tạo invoice cho lease
     *   + Tạo invoice items (rent, deposit, etc.)
     *   + Tính tổng tiền
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        /**
         * Kiểm tra xem command có chạy ở dry-run mode không
         * 
         * $this->option('dry-run') - Lấy giá trị của option 'dry-run' từ command line
         *   - option() là method của Laravel Command để lấy giá trị của command option
         *   - 'dry-run' là tên option được định nghĩa trong $signature
         *   - Nếu user chạy: php artisan invoices:create-missing --dry-run => $isDryRun = true
         *   - Nếu user chạy: php artisan invoices:create-missing => $isDryRun = false
         *   - Trả về boolean (true/false)
         * 
         * $isDryRun - Biến lưu trạng thái dry-run mode
         *   - true = dry-run mode (chỉ hiển thị, không thực sự tạo invoice)
         *   - false = normal mode (sẽ tạo invoice thực sự)
         */
        $isDryRun = $this->option('dry-run');
        
        /**
         * Hiển thị thông báo nếu đang ở dry-run mode
         * 
         * if ($isDryRun) - Kiểm tra xem có đang ở dry-run mode không
         *   - Nếu true, hiển thị thông báo để user biết không có invoice nào được tạo
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - 'Running in DRY RUN mode - no invoices will be created'
         *   - Thông báo cho user biết command chỉ hiển thị, không thực sự tạo invoice
         */
        if ($isDryRun) {
            $this->info('Running in DRY RUN mode - no invoices will be created');
        }
        
        // Hiển thị thông báo bắt đầu kiểm tra
        // $this->info() là method của Laravel Command để hiển thị message màu xanh trong console
        $this->info('Checking for leases without invoices...');
        
        /**
         * Query tất cả leases từ database và eager load relationship invoices
         * 
         * Lease::with('invoices') - Bắt đầu query builder từ model Lease và eager load relationship invoices
         *   - with('invoices') sẽ load các Invoice models liên quan trước khi sử dụng
         *   - 'invoices' là tên relationship trong Lease model (HasMany relationship)
         *   - Relationship này query từ bảng invoices với điều kiện lease_id = leases.id
         *   - Eager loading tránh N+1 queries: thay vì query invoices cho từng lease riêng lẻ, tất cả được load trong 1 query
         * 
         * ->get() - Thực thi query và trả về Collection chứa các Lease models
         *   - get() sẽ execute SQL query: SELECT * FROM leases
         *   - Sau đó load invoices: SELECT * FROM invoices WHERE lease_id IN (1, 2, 3, ...)
         *   - Trả về Collection chứa các Lease models với invoices đã được load sẵn
         * 
         * Lưu ý: Query này có thể load rất nhiều data nếu có nhiều leases
         *   - Có thể cần optimize bằng cách thêm where conditions hoặc chunk() nếu có quá nhiều leases
         */
        $leases = Lease::with('invoices')->get();
        
        // Khởi tạo mảng rỗng để lưu các leases chưa có invoice
        // Mảng này sẽ được populate trong vòng lặp foreach bên dưới
        $missingInvoices = [];
        
        /**
         * Lọc các leases chưa có invoice bằng vòng lặp foreach
         * 
         * foreach ($leases as $lease) - Lặp qua từng phần tử trong Collection
         * - $leases là Collection chứa các Lease models đã query được
         * - $lease là từng Lease model trong Collection
         * - Mỗi lần lặp, $lease sẽ là một instance của App\Models\Lease
         * 
         * if ($lease->invoices->count() == 0) - Kiểm tra xem lease có invoice nào không
         *   - $lease->invoices truy cập relationship invoices (đã được eager load với with('invoices'))
         *   - invoices là Collection chứa các Invoice models liên quan
         *   - count() đếm số phần tử trong Collection
         *   - count() == 0 nghĩa là lease chưa có invoice nào
         *   - Nếu count() > 0, lease đã có invoice, không cần thêm vào $missingInvoices
         * 
         * $missingInvoices[] = $lease - Thêm lease vào mảng $missingInvoices
         *   - [] là array append operator trong PHP
         *   - Tương đương với: array_push($missingInvoices, $lease)
         *   - Lease này sẽ được xử lý để tạo invoice sau
         */
        foreach ($leases as $lease) {
            if ($lease->invoices->count() == 0) {
                $missingInvoices[] = $lease;
            }
        }
        
        /**
         * Hiển thị số lượng leases chưa có invoice
         * 
         * count($missingInvoices) - Đếm số phần tử trong mảng $missingInvoices
         *   - count() là PHP built-in function để đếm số phần tử trong array
         *   - Trả về integer (số lượng leases chưa có invoice)
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Found " . count($missingInvoices) . " leases without invoices."
         *   - Dấu . là string concatenation operator trong PHP
         *   - Ví dụ: Nếu count($missingInvoices) = 5, message sẽ là "Found 5 leases without invoices."
         */
        $this->info("Found " . count($missingInvoices) . " leases without invoices.");
        
        /**
         * Kiểm tra xem có lease nào cần tạo invoice không
         * 
         * if (count($missingInvoices) == 0) - Kiểm tra xem mảng $missingInvoices có rỗng không
         *   - count($missingInvoices) == 0 nghĩa là tất cả leases đã có invoice
         *   - Không cần làm gì thêm, dừng command
         * 
         * $this->info() - Hiển thị message thông báo
         *   - 'All leases have invoices. Nothing to do.'
         * 
         * return 0 - Trả về 0 (Command::SUCCESS) để báo command đã hoàn thành thành công
         *   - 0 nghĩa là command thành công, không có lỗi
         *   - Command sẽ dừng ở đây, không thực thi code phía dưới
         */
        if (count($missingInvoices) == 0) {
            $this->info('All leases have invoices. Nothing to do.');
            return 0;
        }
        
        /**
         * Hiển thị bảng danh sách leases chưa có invoice
         * 
         * $this->table() - Hiển thị dữ liệu dạng bảng trong console
         *   - table() là method của Laravel Command để hiển thị data dạng table
         *   - Table sẽ có headers và rows, format đẹp trong console
         * 
         * Tham số 1: ['Lease ID', 'Rent Amount', 'Deposit Amount', 'Status', 'Created At'] - Headers của bảng
         *   - Array chứa các tên cột sẽ được hiển thị
         *   - Headers: Lease ID, Rent Amount, Deposit Amount, Status, Created At
         * 
         * Tham số 2: collect($missingInvoices)->map(function($lease) { ... }) - Rows của bảng
         *   - collect($missingInvoices) - Chuyển array $missingInvoices thành Laravel Collection
         *     - Collection cung cấp nhiều method tiện ích như map(), filter(), etc.
         *   - ->map(function($lease) { ... }) - Transform từng lease thành array data cho table row
         *     - map() là method của Collection, nhận một closure function
         *     - Closure function nhận $lease (từng Lease model) và trả về array
         *     - map() sẽ tạo một Collection mới chứa các array đã được transform
         *   - return [...] - Trả về array chứa data cho một row trong table
         *     - $lease->id - ID của lease (integer)
         *     - number_format($lease->rent_amount, 0, ',', '.') . ' VNĐ' - Format số tiền thuê
         *       - number_format($value, $decimals, $decimal_separator, $thousands_separator)
         *       - number_format($lease->rent_amount, 0, ',', '.') format số với:
         *         - 0 decimal places (không có số thập phân)
         *         - ',' là decimal separator (không dùng vì 0 decimals)
         *         - '.' là thousands separator (dấu chấm phân cách hàng nghìn)
         *       - Ví dụ: 5000000.00 -> "5.000.000"
         *       - Kết quả: "5.000.000 VNĐ"
         *     - number_format($lease->deposit_amount, 0, ',', '.') . ' VNĐ' - Format số tiền cọc (tương tự)
         *     - $lease->status - Trạng thái của lease (string: 'draft', 'active', 'expired', 'terminated')
         *     - $lease->created_at->format('Y-m-d H:i:s') - Format ngày tạo
         *       - $lease->created_at là Carbon date instance (đã được cast trong model)
         *       - ->format('Y-m-d H:i:s') chuyển date thành string format YYYY-MM-DD HH:MM:SS
         *       - Ví dụ: "2024-01-15 10:30:00"
         */
        $this->table(
            ['Lease ID', 'Rent Amount', 'Deposit Amount', 'Status', 'Created At'],
            collect($missingInvoices)->map(function($lease) {
                return [
                    $lease->id,
                    number_format($lease->rent_amount, 0, ',', '.') . ' VNĐ',
                    number_format($lease->deposit_amount, 0, ',', '.') . ' VNĐ',
                    $lease->status,
                    $lease->created_at->format('Y-m-d H:i:s')
                ];
            })
        );
        
        /**
         * Nếu đang ở dry-run mode, dừng command và không tạo invoice
         * 
         * if ($isDryRun) - Kiểm tra xem có đang ở dry-run mode không
         *   - Nếu true, chỉ hiển thị thông tin, không thực sự tạo invoice
         * 
         * $this->info() - Hiển thị message thông báo
         *   - 'DRY RUN completed. Use without --dry-run to actually create invoices.'
         *   - Hướng dẫn user cách chạy command để thực sự tạo invoice
         * 
         * return 0 - Trả về 0 (Command::SUCCESS) để báo command đã hoàn thành
         *   - Command sẽ dừng ở đây, không thực thi code phía dưới
         *   - Không có invoice nào được tạo (dry-run mode)
         */
        if ($isDryRun) {
            $this->info('DRY RUN completed. Use without --dry-run to actually create invoices.');
            return 0;
        }
        
        /**
         * Xác nhận từ người dùng trước khi tạo invoice
         * 
         * $this->confirm() - Hiển thị câu hỏi yes/no và chờ user trả lời
         *   - confirm() là method của Laravel Command để xác nhận từ user
         *   - Hiển thị: "Do you want to create invoices for these leases? (yes/no) [no]:"
         *   - User có thể nhập: yes, y, no, n (case insensitive)
         *   - Trả về boolean: true nếu user chọn yes, false nếu user chọn no
         * 
         * if (!$this->confirm(...)) - Kiểm tra xem user có chọn no không
         *   - ! là NOT operator, đảo ngược giá trị boolean
         *   - Nếu user chọn no (false), !false = true, vào block if
         * 
         * $this->info('Operation cancelled.') - Hiển thị message thông báo đã hủy
         * 
         * return 0 - Trả về 0 (Command::SUCCESS) để báo command đã hoàn thành
         *   - Command sẽ dừng ở đây, không tạo invoice nào
         *   - User đã chọn không tạo invoice
         */
        if (!$this->confirm('Do you want to create invoices for these leases?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        // Hiển thị thông báo bắt đầu tạo invoice
        // $this->info() - Hiển thị message màu xanh trong console
        $this->info('Creating invoices...');
        
        /**
         * Tạo progress bar để hiển thị tiến độ tạo invoice
         * 
         * $this->output->createProgressBar(count($missingInvoices)) - Tạo progress bar
         *   - $this->output là OutputInterface instance (Laravel Command output)
         *   - createProgressBar($max) tạo progress bar với số lượng tối đa là $max
         *   - count($missingInvoices) là số lượng leases cần tạo invoice (số lượng tối đa)
         *   - Progress bar sẽ hiển thị: [====>----------] 40% (ví dụ)
         * 
         * $progressBar->start() - Bắt đầu progress bar
         *   - start() khởi tạo progress bar và hiển thị trong console
         *   - Progress bar sẽ được update sau mỗi lần tạo invoice (qua advance())
         */
        $progressBar = $this->output->createProgressBar(count($missingInvoices));
        $progressBar->start();
        
        // Khởi tạo biến đếm số lượng invoice đã tạo thành công
        // Biến này sẽ được tăng lên mỗi khi tạo invoice thành công
        $created = 0;
        
        // Khởi tạo biến đếm số lượng lỗi khi tạo invoice
        // Biến này sẽ được tăng lên mỗi khi có lỗi xảy ra
        $errors = 0;
        
        /**
         * Xử lý từng lease chưa có invoice bằng vòng lặp foreach
         * 
         * foreach ($missingInvoices as $lease) - Lặp qua từng phần tử trong mảng
         * - $missingInvoices là array chứa các Lease models chưa có invoice
         * - $lease là từng Lease model trong mảng
         * - Mỗi lần lặp, $lease sẽ là một instance của App\Models\Lease
         */
        foreach ($missingInvoices as $lease) {
            /**
             * Xử lý lỗi riêng cho từng lease (không dừng toàn bộ process)
             * 
             * try { ... } catch { ... } - Xử lý lỗi khi tạo invoice cho một lease cụ thể
             * - Nếu tạo invoice thành công: tiếp tục
             * - Nếu tạo invoice thất bại: catch exception, ghi log, hiển thị lỗi, nhưng tiếp tục xử lý lease tiếp theo
             * - Đảm bảo command không bị dừng vì lỗi của một lease
             * - Các lease khác vẫn có thể được xử lý tiếp
             */
            try {
                /**
                 * Kiểm tra xem lease có rent_amount hợp lệ không
                 * 
                 * if ($lease->rent_amount && $lease->rent_amount > 0) - Kiểm tra điều kiện
                 *   - $lease->rent_amount - Truy cập field rent_amount từ Lease model
                 *   - && - Logical AND operator (cả hai điều kiện phải true)
                 *   - $lease->rent_amount - Kiểm tra rent_amount có tồn tại không (không null, không false, không 0)
                 *   - $lease->rent_amount > 0 - Kiểm tra rent_amount có lớn hơn 0 không
                 *   - Chỉ tạo invoice nếu lease có rent_amount > 0 (có tiền thuê)
                 *   - Nếu rent_amount = 0 hoặc null, không cần tạo invoice (lease miễn phí hoặc chưa có giá)
                 */
                if ($lease->rent_amount && $lease->rent_amount > 0) {
                    /**
                     * Gọi method private để tạo invoice cho lease này
                     * 
                     * $this->createInvoiceForLease($lease) - Gọi method private createInvoiceForLease()
                     *   - Method này sẽ gọi LeaseObserver::createInvoiceForExistingLease($lease)
                     *   - LeaseObserver sẽ tạo invoice với logic nhất quán với khi tạo lease mới
                     *   - Invoice sẽ được tạo với các items: rent, deposit, services (nếu có)
                     *   - Method này có thể throw exception nếu có lỗi (database error, validation error, etc.)
                     *   - Exception sẽ được catch ở block catch bên dưới
                     */
                    $this->createInvoiceForLease($lease);
                    
                    // Tăng biến đếm lên 1 sau mỗi lần tạo invoice thành công
                    // $created++ tương đương với $created = $created + 1
                    $created++;
                } else {
                    /**
                     * Bỏ qua lease không có rent_amount hoặc rent_amount = 0
                     * 
                     * $this->warn() - Hiển thị message màu vàng trong console (cảnh báo)
                     *   - "Skipping lease {$lease->id} - no rent amount"
                     *   - {$lease->id} là string interpolation, sẽ thay thế bằng giá trị lease->id
                     *   - Ví dụ: "Skipping lease 5 - no rent amount"
                     *   - Thông báo cho user biết lease này bị bỏ qua (không tạo invoice)
                     */
                    $this->warn("Skipping lease {$lease->id} - no rent amount");
                }
            } catch (\Exception $e) {
                /**
                 * Xử lý lỗi khi tạo invoice cho một lease cụ thể
                 * 
                 * catch (\Exception $e) - Bắt exception khi tạo invoice thất bại
                 * - Có thể là: database error, validation error, observer error, etc.
                 * - $e là exception object chứa thông tin về lỗi
                 * 
                 * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
                 *   - "Error creating invoice for lease {$lease->id}: " . $e->getMessage()
                 *   - {$lease->id} là string interpolation
                 *   - $e->getMessage() trả về error message của exception
                 *   - Dấu . là string concatenation operator trong PHP
                 *   - Hiển thị lỗi cho user để họ biết lease nào gặp lỗi
                 * 
                 * $errors++ - Tăng biến đếm lỗi lên 1
                 *   - $errors++ tương đương với $errors = $errors + 1
                 * 
                 * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
                 *   - Log được ghi vào: storage/logs/laravel.log
                 *   - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message {context}
                 * 
                 * Tham số 1: 'Error creating invoice via command' - Message mô tả lỗi
                 * 
                 * Tham số 2: Array chứa context data
                 * - 'lease_id' => $lease->id - ID của lease gặp lỗi (để debug)
                 * - 'error' => $e->getMessage() - Error message của exception
                 * 
                 * Lưu ý: Không throw exception lại, chỉ ghi log và hiển thị lỗi
                 * - Đảm bảo command không bị dừng vì lỗi của một lease
                 * - Các lease khác vẫn có thể được xử lý tiếp
                 * - Process sẽ tiếp tục với lease tiếp theo trong vòng lặp
                 */
                $this->error("Error creating invoice for lease {$lease->id}: " . $e->getMessage());
                $errors++;
                Log::error('Error creating invoice via command', [
                    'lease_id' => $lease->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            /**
             * Cập nhật progress bar sau mỗi lần xử lý một lease
             * 
             * $progressBar->advance() - Tăng progress bar lên 1 đơn vị
             *   - advance() cập nhật progress bar và hiển thị lại trong console
             *   - Progress bar sẽ hiển thị: [====>----------] 40% -> [=====>---------] 50% (ví dụ)
             *   - Gọi sau mỗi lần xử lý một lease (thành công hoặc thất bại)
             *   - Giúp user biết tiến độ của command
             */
            $progressBar->advance();
        }
        
        /**
         * Kết thúc progress bar
         * 
         * $progressBar->finish() - Hoàn thành progress bar
         *   - finish() đặt progress bar về 100% và hiển thị lại
         *   - Progress bar sẽ hiển thị: [====================] 100%
         */
        $progressBar->finish();
        
        /**
         * Xuống dòng 2 lần để tạo khoảng trống trước khi hiển thị kết quả
         * 
         * $this->newLine(2) - Xuống dòng 2 lần
         *   - newLine($count) là method của Laravel Command để xuống dòng
         *   - 2 là số lần xuống dòng
         *   - Tạo khoảng trống giữa progress bar và kết quả cuối cùng
         */
        $this->newLine(2);
        
        /**
         * Hiển thị kết quả cuối cùng cho người dùng
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Completed! Created {$created} invoices, {$errors} errors."
         *   - {$created} và {$errors} là string interpolation, sẽ thay thế bằng giá trị
         *   - Ví dụ: Nếu $created = 5 và $errors = 1, message sẽ là "Completed! Created 5 invoices, 1 errors."
         *   - Thông báo cho user biết số lượng invoice đã tạo và số lỗi (nếu có)
         */
        $this->info("Completed! Created {$created} invoices, {$errors} errors.");
        
        // Trả về 0 (Command::SUCCESS) để báo cho Laravel biết command đã chạy thành công
        // Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thành công không
        return 0;
    }
    
    /**
     * Tạo invoice cho một lease sử dụng logic giống LeaseObserver
     * 
     * LUỒNG XỬ LÝ:
     * 1. Gọi LeaseObserver::createInvoiceForExistingLease($lease)
     * 2. Observer sẽ:
     *    - Tạo invoice mới với thông tin từ lease
     *    - Tạo invoice items (rent, deposit, etc.)
     *    - Tính tổng tiền
     *    - Lưu vào database
     * 
     * OBSERVER ĐƯỢC GỌI:
     * - App\Observers\LeaseObserver::createInvoiceForExistingLease($lease)
     *   Method này đảm bảo logic tạo invoice nhất quán với khi tạo lease mới
     * 
     * @param Lease $lease Lease cần tạo invoice
     */
    private function createInvoiceForLease(Lease $lease)
    {
        /**
         * Gọi static method của LeaseObserver để tạo invoice cho lease
         * 
         * LeaseObserver::createInvoiceForExistingLease($lease) - Gọi static method
         *   - LeaseObserver là class nằm tại: app/Observers/LeaseObserver.php
         *   - createInvoiceForExistingLease() là static method (có thể gọi trực tiếp từ class, không cần instance)
         *   - Method này nhận Lease model instance làm tham số
         * 
         * Method này sẽ thực hiện:
         * 1. Tạo Invoice mới với thông tin từ lease:
         *    - lease_id = $lease->id
         *    - organization_id = $lease->organization_id
         *    - tenant_id = $lease->tenant_id
         *    - invoice_no = generated (tự động tạo số hóa đơn)
         *    - issue_date = now() (ngày phát hành = hôm nay)
         *    - due_date = calculated (ngày đến hạn, tính dựa trên payment cycle)
         *    - status = 'draft' hoặc 'issued' (tùy vào logic)
         * 
         * 2. Tạo InvoiceItem cho rent (tiền thuê):
         *    - item_type = 'rent'
         *    - description = "Tiền thuê tháng X"
         *    - quantity = 1
         *    - unit_price = $lease->rent_amount
         *    - total = $lease->rent_amount
         * 
         * 3. Tạo InvoiceItem cho deposit (tiền cọc) nếu có:
         *    - item_type = 'deposit'
         *    - description = "Tiền cọc"
         *    - quantity = 1
         *    - unit_price = $lease->deposit_amount
         *    - total = $lease->deposit_amount
         * 
         * 4. Tạo InvoiceItem cho services (dịch vụ) nếu có:
         *    - Lấy từ lease->leaseServiceSet->items
         *    - Tạo item cho mỗi service trong set
         * 
         * 5. Tính tổng tiền invoice:
         *    - total_amount = sum của tất cả items
         *    - Cập nhật invoice->total_amount
         * 
         * 6. Lưu tất cả vào database (invoice và invoice_items)
         * 
         * Lưu ý: Sử dụng LeaseObserver đảm bảo logic nhất quán
         * - Logic tạo invoice giống hệt khi tạo lease mới (qua LeaseObserver::created())
         * - Tránh duplicate code và đảm bảo consistency
         * - Nếu logic thay đổi trong LeaseObserver, command này cũng tự động sử dụng logic mới
         */
        LeaseObserver::createInvoiceForExistingLease($lease);
    }
}