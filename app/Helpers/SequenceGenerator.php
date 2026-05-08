<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class: SequenceGenerator
 * 
 * MỤC ĐÍCH:
 * Helper class để tạo số sequence (số thứ tự) duy nhất cho các bảng khác nhau sử dụng bảng sequences tập trung.
 * Class này đảm bảo tính duy nhất và thread-safe khi tạo số sequence trong môi trường multi-thread/multi-process.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Tạo sequence key theo format: {type}_sequence_{organization_id}_{year}_{month}
 * 2. Sử dụng database transaction và row locking để đảm bảo thread-safe
 * 3. Tự động khởi tạo sequence nếu chưa tồn tại
 * 4. Tăng giá trị sequence và trả về số tiếp theo
 * 
 * FORMAT SEQUENCE KEY:
 * - Cơ bản: {type}_sequence_{organization_id}_{year}_{month}
 *   Ví dụ: invoice_sequence_1_2025_11
 * 
 * - Với payment method: {type}_sequence_{payment_method_key_code}_{organization_id}_{year}_{month}
 *   Ví dụ: payment_sequence_sepay_1_2025_11
 * 
 * CÁCH SỬ DỤNG:
 * // Tạo sequence key
 * $key = SequenceGenerator::buildKey('invoice', $organizationId, 2025, 11);
 * 
 * // Lấy số sequence tiếp theo
 * $nextNumber = SequenceGenerator::getNext($key);
 * 
 * // Hoặc với callback để tìm max từ records hiện có (lần đầu tiên)
 * $nextNumber = SequenceGenerator::getNext($key, function() {
 *     return Invoice::where('organization_id', $orgId)->max('sequence_number') ?? 0;
 * });
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng sequences: Lấy và cập nhật current_value
 * 
 * DỮ LIỆU GHI VÀO:
 * - Tạo mới hoặc cập nhật bản ghi trong bảng sequences
 * 
 * LƯU Ý:
 * - Sử dụng database transaction và lockForUpdate() để đảm bảo thread-safe
 * - Sequence được reset theo tháng (mỗi tháng có sequence riêng)
 * - Có thể khởi tạo sequence từ max value của records hiện có (qua callback)
 */
class SequenceGenerator
{
    /**
     * Lấy số sequence tiếp theo cho một sequence key
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Bắt đầu database transaction để đảm bảo tính nhất quán
     * 2. Lock row trong bảng sequences để tránh race condition:
     *    - Sử dụng lockForUpdate() để lock row cho đến khi transaction commit
     *    - Đảm bảo chỉ một process có thể tăng sequence tại một thời điểm
     * 3. Kiểm tra sequence đã tồn tại chưa:
     *    - Nếu chưa tồn tại và có callback: Gọi callback để tìm max từ records hiện có
     *    - Nếu chưa tồn tại và không có callback: Khởi tạo với giá trị 0
     *    - Nếu đã tồn tại: Lấy current_value hiện tại
     * 4. Tăng sequence: newSequence = currentSequence + 1
     * 5. Cập nhật current_value trong bảng sequences
     * 6. Commit transaction và trả về số sequence mới
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng sequences: Lấy current_value với lockForUpdate()
     * - Callback (nếu có): Tìm max value từ records hiện có trong bảng tương ứng
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo mới hoặc cập nhật bản ghi trong bảng sequences (current_value, updated_at)
     * 
     * THREAD-SAFE:
     * - Sử dụng database transaction và lockForUpdate() để đảm bảo chỉ một process tăng sequence tại một thời điểm
     * - Tránh race condition khi nhiều request cùng tạo sequence cùng lúc
     * 
     * @param string $sequenceKey Format: "{type}_sequence_{org_id}_{year}_{month}" hoặc "{type}_sequence_{key_code}_{org_id}_{year}_{month}"
     * @param callable|null $findMaxCallback Callback để tìm max từ records hiện có (dùng cho lần đầu tiên khởi tạo)
     *                                      Ví dụ: function() { return Invoice::max('sequence_number') ?? 0; }
     * @return int Số sequence tiếp theo
     * @throws \Exception Nếu không thể tạo sequence (lỗi database, etc.)
     */
    public static function getNext(string $sequenceKey, callable $findMaxCallback = null): int
    {
        /**
         * Bắt đầu database transaction để đảm bảo tính nhất quán
         * 
         * DB::transaction(function () { ... }) - Bắt đầu database transaction
         *   - transaction() là method của Laravel DB facade
         *   - Tất cả các thao tác trong closure sẽ được thực thi trong một transaction
         *   - Nếu có lỗi (exception), transaction sẽ tự động rollback
         *   - Nếu thành công, transaction sẽ tự động commit khi closure return
         *   - Đảm bảo tính nhất quán dữ liệu (ACID properties)
         * 
         * use ($sequenceKey, $findMaxCallback) - Closure sử dụng biến từ scope bên ngoài
         *   - use() cho phép closure truy cập biến từ scope bên ngoài
         *   - $sequenceKey - Sequence key cần xử lý
         *   - $findMaxCallback - Callback function để tìm max value (nếu có)
         * 
         * return DB::transaction(...) - Trả về giá trị từ closure
         *   - Giá trị return từ closure sẽ là giá trị return của transaction()
         *   - Trong trường hợp này, sẽ trả về $newSequence (số sequence mới)
         */
        return DB::transaction(function () use ($sequenceKey, $findMaxCallback) {
            /**
             * Lock row trong bảng sequences để tránh race condition
             * 
             * DB::table('sequences') - Bắt đầu query builder từ bảng sequences
             *   - table() là method của Laravel DB facade để query từ một bảng cụ thể
             *   - 'sequences' là tên bảng trong database
             *   - Trả về query builder instance
             * 
             * ->where('sequence_key', $sequenceKey) - Lọc theo sequence_key
             *   - where() là method của query builder để thêm điều kiện WHERE
             *   - 'sequence_key' là tên column trong bảng sequences
             *   - $sequenceKey là giá trị cần tìm (ví dụ: "invoice_sequence_1_2025_11")
             *   - Query sẽ tìm row có sequence_key = $sequenceKey
             * 
             * ->lockForUpdate() - Lock row để tránh race condition
             *   - lockForUpdate() là method của query builder để thêm SELECT ... FOR UPDATE
             *   - SELECT ... FOR UPDATE sẽ lock row cho đến khi transaction commit hoặc rollback
             *   - Ngăn các transaction khác đọc/ghi vào row này (exclusive lock)
             *   - Đảm bảo chỉ một process có thể tăng sequence tại một thời điểm
             *   - Tránh race condition khi nhiều request cùng tạo sequence cùng lúc
             *   - Lưu ý: lockForUpdate() chỉ hoạt động trong transaction
             * 
             * ->value('current_value') - Lấy giá trị của column current_value
             *   - value() là method của query builder để lấy giá trị của một column
             *   - 'current_value' là tên column cần lấy
             *   - Trả về giá trị của current_value (integer) nếu tìm thấy row
             *   - Trả về null nếu không tìm thấy row
             *   - Chỉ lấy một giá trị, không lấy toàn bộ row (tối ưu performance)
             * 
             * $currentSequence - Biến lưu giá trị sequence hiện tại
             *   - Nếu tìm thấy: $currentSequence là integer (ví dụ: 5)
             *   - Nếu không tìm thấy: $currentSequence = null
             *   - Sẽ được sử dụng để tính sequence tiếp theo
             */
            $currentSequence = DB::table('sequences')
                ->where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->value('current_value');
            
            /**
             * Xử lý trường hợp sequence chưa tồn tại và có callback
             * 
             * if ($currentSequence === null && $findMaxCallback) - Kiểm tra điều kiện
             *   - $currentSequence === null - Kiểm tra xem sequence chưa tồn tại (strict comparison)
             *     - === là strict equal operator (so sánh cả giá trị và kiểu dữ liệu)
             *     - null nghĩa là không tìm thấy sequence key trong database
             *   - && - Logical AND operator (cả hai điều kiện phải true)
             *   - $findMaxCallback - Kiểm tra xem có callback được cung cấp không
             *     - Nếu có callback (không null), vào block if
             *   - Chỉ vào block if khi: sequence chưa tồn tại VÀ có callback
             * 
             * Lần đầu tiên sử dụng sequence key này:
             * - Gọi callback để tìm max value từ records hiện có
             * - Khởi tạo sequence với giá trị max đã tìm được
             * - Nếu callback lỗi: Khởi tạo với giá trị 0
             */
            if ($currentSequence === null && $findMaxCallback) {
                /**
                 * Xử lý lỗi khi gọi callback
                 * 
                 * try { ... } catch { ... } - Xử lý lỗi khi callback thất bại
                 * - Nếu callback thành công: tiếp tục
                 * - Nếu callback thất bại: catch exception, ghi log, và khởi tạo với giá trị 0
                 */
                try {
                    /**
                     * Gọi callback để tìm max value từ records hiện có
                     * 
                     * $findMaxCallback() - Gọi callback function
                     *   - $findMaxCallback là callable (closure hoặc function)
                     *   - Callback thường là closure trả về max(sequence_number) từ bảng tương ứng
                     *   - Ví dụ: function() { return Invoice::where('organization_id', $orgId)->max('sequence_number') ?? 0; }
                     *   - Callback sẽ query từ bảng tương ứng (ví dụ: invoices) để tìm max sequence_number
                     *   - Trả về integer (max value) hoặc null
                     *   - Nếu không có records, trả về null (sẽ được xử lý với ?? 0)
                     * 
                     * $currentSequence - Biến lưu giá trị max đã tìm được
                     *   - Nếu tìm thấy: $currentSequence là integer (ví dụ: 10)
                     *   - Nếu không tìm thấy: $currentSequence = null (sẽ được xử lý)
                     *   - Sẽ được sử dụng để khởi tạo sequence
                     */
                    $currentSequence = $findMaxCallback();
                    
                    /**
                     * Tạo bản ghi sequence mới với giá trị max đã tìm được
                     * 
                     * DB::table('sequences')->insert([...]) - Insert bản ghi mới vào bảng sequences
                     *   - insert() là method của query builder để insert một hoặc nhiều rows
                     *   - Tham số là associative array chứa data cần insert
                     * 
                     * Array chứa:
                     * - 'sequence_key' => $sequenceKey - Sequence key (ví dụ: "invoice_sequence_1_2025_11")
                     * - 'current_value' => $currentSequence - Giá trị sequence hiện tại (max đã tìm được)
                     *   - Ví dụ: Nếu max = 10, current_value = 10
                     *   - Sequence tiếp theo sẽ là 11 (10 + 1)
                     * - 'created_at' => now() - Thời gian tạo (Carbon instance)
                     *   - now() là helper function của Laravel, trả về Carbon instance của thời gian hiện tại
                     * - 'updated_at' => now() - Thời gian cập nhật (Carbon instance)
                     * 
                     * Đảm bảo sequence tiếp theo sẽ là max + 1, không bị trùng với records hiện có
                     */
                    DB::table('sequences')->insert([
                        'sequence_key' => $sequenceKey,
                        'current_value' => $currentSequence,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } catch (\Exception $e) {
                    /**
                     * Xử lý lỗi khi callback thất bại
                     * 
                     * catch (\Exception $e) - Bắt exception khi callback thất bại
                     * - Có thể là: database error, query error, etc.
                     * - $e là exception object chứa thông tin về lỗi
                     * 
                     * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
                     *   - Log được ghi vào: storage/logs/laravel.log
                     *   - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message
                     * 
                     * "Error finding max sequence for key {$sequenceKey}: " . $e->getMessage()
                     *   - {$sequenceKey} là string interpolation
                     *   - $e->getMessage() trả về error message của exception
                     *   - Dấu . là string concatenation operator
                     *   - Ví dụ: "Error finding max sequence for key invoice_sequence_1_2025_11: Database connection failed"
                     * 
                     * $currentSequence = 0 - Gán giá trị mặc định 0
                     *   - Nếu callback lỗi, khởi tạo sequence với giá trị 0
                     *   - Sequence tiếp theo sẽ là 1 (0 + 1)
                     */
                    Log::error("Error finding max sequence for key {$sequenceKey}: " . $e->getMessage());
                    $currentSequence = 0;
                    
                    /**
                     * Tạo bản ghi sequence mới với giá trị mặc định 0
                     * 
                     * DB::table('sequences')->insert([...]) - Insert bản ghi mới
                     *   - Tương tự như trên, nhưng với current_value = 0
                     *   - Sequence tiếp theo sẽ là 1 (0 + 1)
                     */
                    DB::table('sequences')->insert([
                        'sequence_key' => $sequenceKey,
                        'current_value' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            } elseif ($currentSequence === null) {
                /**
                 * Xử lý trường hợp sequence chưa tồn tại và không có callback
                 * 
                 * elseif ($currentSequence === null) - Kiểm tra xem sequence chưa tồn tại
                 *   - $currentSequence === null nghĩa là không tìm thấy sequence key trong database
                 *   - Và không có callback ($findMaxCallback = null)
                 *   - Vào block elseif để khởi tạo sequence với giá trị mặc định
                 * 
                 * $currentSequence = 0 - Gán giá trị mặc định 0
                 *   - Khởi tạo sequence với giá trị 0
                 *   - Sequence tiếp theo sẽ là 1 (0 + 1)
                 */
                $currentSequence = 0;
                
                /**
                 * Tạo bản ghi sequence mới với giá trị mặc định 0
                 * 
                 * DB::table('sequences')->insert([...]) - Insert bản ghi mới
                 *   - Tương tự như trên, với current_value = 0
                 *   - Sequence tiếp theo sẽ là 1 (0 + 1)
                 */
                DB::table('sequences')->insert([
                    'sequence_key' => $sequenceKey,
                    'current_value' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            /**
             * Tăng sequence: newSequence = currentSequence + 1
             * 
             * $currentSequence - Giá trị sequence hiện tại
             *   - Nếu đã tồn tại: giá trị từ database (ví dụ: 5)
             *   - Nếu mới khởi tạo: 0 (từ callback hoặc mặc định)
             * 
             * + 1 - Tăng sequence lên 1
             *   - Toán tử + là addition operator
             *   - Ví dụ: Nếu currentSequence = 5, thì 5 + 1 = 6
             * 
             * $newSequence - Biến lưu giá trị sequence mới
             *   - Ví dụ: Nếu currentSequence = 5, thì newSequence = 6
             *   - Sẽ được cập nhật vào database và trả về
             */
            $newSequence = $currentSequence + 1;
            
            /**
             * Cập nhật current_value trong bảng sequences
             * 
             * DB::table('sequences') - Bắt đầu query builder từ bảng sequences
             *   - table() là method của Laravel DB facade
             * 
             * ->where('sequence_key', $sequenceKey) - Lọc theo sequence_key
             *   - where() là method của query builder để thêm điều kiện WHERE
             *   - 'sequence_key' là tên column
             *   - $sequenceKey là giá trị cần tìm
             *   - Query sẽ tìm row có sequence_key = $sequenceKey
             * 
             * ->update([...]) - Cập nhật row đã tìm được
             *   - update() là method của query builder để cập nhật rows
             *   - Tham số là associative array chứa data cần cập nhật
             * 
             * Array chứa:
             * - 'current_value' => $newSequence - Cập nhật giá trị sequence mới
             *   - Ví dụ: Nếu newSequence = 6, current_value sẽ được cập nhật thành 6
             * - 'updated_at' => now() - Cập nhật thời gian cập nhật
             *   - now() là helper function của Laravel, trả về Carbon instance của thời gian hiện tại
             * 
             * update() sẽ execute SQL query: UPDATE sequences SET current_value = $newSequence, updated_at = now() WHERE sequence_key = $sequenceKey
             *   - Trả về số lượng rows đã được cập nhật (thường là 1)
             */
            DB::table('sequences')
                ->where('sequence_key', $sequenceKey)
                ->update([
                    'current_value' => $newSequence,
                    'updated_at' => now()
                ]);
            
            /**
             * Trả về số sequence mới
             * 
             * return $newSequence - Trả về giá trị sequence mới
             *   - $newSequence là integer (ví dụ: 6)
             *   - Giá trị này sẽ được sử dụng để tạo số sequence cho record mới
             *   - Ví dụ: Invoice number = "INV-2025-11-0006"
             * 
             * Transaction sẽ tự động commit khi return
             *   - Khi closure return, transaction sẽ tự động commit
             *   - Tất cả các thay đổi (insert/update) sẽ được lưu vào database
             *   - Lock sẽ được giải phóng sau khi commit
             */
            return $newSequence;
        });
    }
    
    /**
     * Tạo sequence key theo format chuẩn
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy year và month (mặc định: năm và tháng hiện tại)
     * 2. Đảm bảo month có 2 chữ số (01, 02, ..., 12)
     * 3. Nếu có paymentMethodKeyCode: Tạo key với format có payment method
     * 4. Nếu không có: Tạo key với format cơ bản
     * 
     * FORMAT:
     * - Cơ bản: {type}_sequence_{organization_id}_{year}_{month}
     *   Ví dụ: invoice_sequence_1_2025_11
     * 
     * - Với payment method: {type}_sequence_{payment_method_key_code}_{organization_id}_{year}_{month}
     *   Ví dụ: payment_sequence_sepay_1_2025_11
     * 
     * @param string $type Loại sequence (invoice, company_invoice, lease, master_lease, payment, etc.)
     * @param int $organizationId ID của organization
     * @param int|null $year Năm (mặc định: năm hiện tại)
     * @param int|null $month Tháng (mặc định: tháng hiện tại, 1-12)
     * @param string|null $paymentMethodKeyCode Key code của payment method (tùy chọn, dùng cho payment-related sequences)
     * @return string Sequence key theo format đã định nghĩa
     */
    public static function buildKey(string $type, int $organizationId, ?int $year = null, ?int $month = null, ?string $paymentMethodKeyCode = null): string
    {
        /**
         * Lấy year (mặc định: năm hiện tại)
         * 
         * $year ?? (int) date('Y') - Null coalescing operator
         *   - ?? là null coalescing operator trong PHP 7.0+
         *   - Nếu $year != null, sử dụng $year
         *   - Nếu $year = null, sử dụng (int) date('Y')
         * 
         * (int) date('Y') - Lấy năm hiện tại và chuyển thành integer
         *   - date('Y') là PHP built-in function trả về năm hiện tại (4 chữ số, ví dụ: "2025")
         *   - (int) là type casting, chuyển string thành integer
         *   - Ví dụ: date('Y') = "2025", (int) date('Y') = 2025
         * 
         * $year - Biến lưu năm (integer)
         *   - Nếu được cung cấp: sử dụng giá trị đó
         *   - Nếu không: sử dụng năm hiện tại
         */
        $year = $year ?? (int) date('Y');
        
        /**
         * Lấy month (mặc định: tháng hiện tại)
         * 
         * $month ?? (int) date('m') - Null coalescing operator
         *   - ?? là null coalescing operator
         *   - Nếu $month != null, sử dụng $month
         *   - Nếu $month = null, sử dụng (int) date('m')
         * 
         * (int) date('m') - Lấy tháng hiện tại và chuyển thành integer
         *   - date('m') là PHP built-in function trả về tháng hiện tại (2 chữ số, ví dụ: "11")
         *   - (int) là type casting, chuyển string thành integer
         *   - Ví dụ: date('m') = "11", (int) date('m') = 11
         *   - Lưu ý: date('m') luôn trả về 2 chữ số (01-12), nhưng (int) sẽ chuyển thành 1-12
         * 
         * $month - Biến lưu tháng (integer, 1-12)
         *   - Nếu được cung cấp: sử dụng giá trị đó
         *   - Nếu không: sử dụng tháng hiện tại
         */
        $month = $month ?? (int) date('m');
        
        /**
         * Đảm bảo month có 2 chữ số (01, 02, ..., 12)
         * 
         * str_pad($month, 2, '0', STR_PAD_LEFT) - Thêm số 0 ở đầu nếu month < 10
         *   - str_pad() là PHP built-in function để thêm ký tự vào string
         *   - Tham số 1: $month - String cần xử lý (sẽ được convert sang string tự động)
         *   - Tham số 2: 2 - Độ dài mong muốn (2 ký tự)
         *   - Tham số 3: '0' - Ký tự để thêm vào (số 0)
         *   - Tham số 4: STR_PAD_LEFT - Thêm ở bên trái (left padding)
         *   - Ví dụ: str_pad(1, 2, '0', STR_PAD_LEFT) = "01"
         *   - Ví dụ: str_pad(11, 2, '0', STR_PAD_LEFT) = "11" (không thêm vì đã đủ 2 ký tự)
         * 
         * $month - Biến lưu tháng đã được format (string, 2 chữ số)
         *   - Ví dụ: 1 -> "01", 11 -> "11"
         *   - Sẽ được sử dụng trong sequence key
         */
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        /**
         * Kiểm tra xem có paymentMethodKeyCode không
         * 
         * if ($paymentMethodKeyCode) - Kiểm tra xem có paymentMethodKeyCode không
         *   - Nếu $paymentMethodKeyCode != null và != empty, vào block if
         *   - Nếu $paymentMethodKeyCode = null hoặc empty, vào block else
         */
        if ($paymentMethodKeyCode) {
            /**
             * Tạo key với format có payment method
             * 
             * "{$type}_sequence_{$paymentMethodKeyCode}_{$organizationId}_{$year}_{$month}" - String interpolation
             *   - {$type} - Loại sequence (ví dụ: "payment")
             *   - "_sequence_" - Phần cố định
             *   - {$paymentMethodKeyCode} - Key code của payment method (ví dụ: "sepay")
             *   - "_" - Dấu gạch dưới phân cách
             *   - {$organizationId} - ID của organization (ví dụ: 1)
             *   - "_" - Dấu gạch dưới phân cách
             *   - {$year} - Năm (ví dụ: 2025)
             *   - "_" - Dấu gạch dưới phân cách
             *   - {$month} - Tháng đã format (ví dụ: "11")
             * 
             * Format: {type}_sequence_{payment_method_key_code}_{organization_id}_{year}_{month}
             * Ví dụ: "payment_sequence_sepay_1_2025_11"
             * 
             * return - Trả về sequence key với payment method
             *   - Key này sẽ được sử dụng cho payment-related sequences
             *   - Mỗi payment method có sequence riêng
             */
            return "{$type}_sequence_{$paymentMethodKeyCode}_{$organizationId}_{$year}_{$month}";
        }
        
        /**
         * Tạo key với format cơ bản (không có payment method)
         * 
         * "{$type}_sequence_{$organizationId}_{$year}_{$month}" - String interpolation
         *   - {$type} - Loại sequence (ví dụ: "invoice", "lease", "master_lease")
         *   - "_sequence_" - Phần cố định
         *   - {$organizationId} - ID của organization (ví dụ: 1)
         *   - "_" - Dấu gạch dưới phân cách
         *   - {$year} - Năm (ví dụ: 2025)
         *   - "_" - Dấu gạch dưới phân cách
         *   - {$month} - Tháng đã format (ví dụ: "11")
         * 
         * Format: {type}_sequence_{organization_id}_{year}_{month}
         * Ví dụ: "invoice_sequence_1_2025_11"
         * 
         * return - Trả về sequence key cơ bản
         *   - Key này sẽ được sử dụng cho các loại sequence thông thường
         *   - Mỗi organization, năm, tháng có sequence riêng
         */
        return "{$type}_sequence_{$organizationId}_{$year}_{$month}";
    }
    
    /**
     * Lấy giá trị sequence hiện tại mà không tăng
     * 
     * LUỒNG XỬ LÝ:
     * 1. Query từ bảng sequences với sequence_key
     * 2. Lấy current_value (không lock, không tăng)
     * 3. Trả về giá trị hoặc null nếu không tìm thấy
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng sequences: Lấy current_value
     * 
     * MỤC ĐÍCH:
     * - Kiểm tra giá trị sequence hiện tại
     * - Không làm thay đổi giá trị sequence
     * 
     * @param string $sequenceKey Sequence key cần lấy giá trị
     * @return int|null Giá trị sequence hiện tại hoặc null nếu không tìm thấy
     */
    public static function getCurrent(string $sequenceKey): ?int
    {
        /**
         * Query và lấy giá trị sequence hiện tại (không lock, không tăng)
         * 
         * DB::table('sequences') - Bắt đầu query builder từ bảng sequences
         *   - table() là method của Laravel DB facade để query từ một bảng cụ thể
         *   - 'sequences' là tên bảng trong database
         *   - Trả về query builder instance
         * 
         * ->where('sequence_key', $sequenceKey) - Lọc theo sequence_key
         *   - where() là method của query builder để thêm điều kiện WHERE
         *   - 'sequence_key' là tên column trong bảng sequences
         *   - $sequenceKey là giá trị cần tìm (ví dụ: "invoice_sequence_1_2025_11")
         *   - Query sẽ tìm row có sequence_key = $sequenceKey
         * 
         * ->value('current_value') - Lấy giá trị của column current_value
         *   - value() là method của query builder để lấy giá trị của một column
         *   - 'current_value' là tên column cần lấy
         *   - Trả về giá trị của current_value (integer) nếu tìm thấy row
         *   - Trả về null nếu không tìm thấy row
         *   - Chỉ lấy một giá trị, không lấy toàn bộ row (tối ưu performance)
         *   - Lưu ý: Không sử dụng lockForUpdate(), không tăng sequence
         * 
         * return - Trả về giá trị sequence hiện tại hoặc null
         *   - Nếu tìm thấy: trả về integer (ví dụ: 5)
         *   - Nếu không tìm thấy: trả về null
         *   - Method này chỉ đọc, không thay đổi giá trị sequence
         */
        return DB::table('sequences')
            ->where('sequence_key', $sequenceKey)
            ->value('current_value');
    }

    /**
     * Reset sequence về một giá trị cụ thể
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra sequence key đã tồn tại chưa
     * 2. Nếu tồn tại: Cập nhật current_value = $value
     * 3. Nếu chưa tồn tại: Tạo mới với current_value = $value
     * 4. Cập nhật updated_at = now()
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng sequences: Kiểm tra tồn tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo mới hoặc cập nhật bản ghi trong bảng sequences (current_value, updated_at)
     * 
     * MỤC ĐÍCH:
     * - Reset sequence về giá trị cụ thể (thường dùng khi cần sửa lỗi hoặc test)
     * - Tự động tạo sequence nếu chưa tồn tại
     * 
     * @param string $sequenceKey Sequence key cần reset
     * @param int $value Giá trị để reset về (mặc định: 0)
     * @return bool true nếu thành công, false nếu có lỗi
     */
    public static function reset(string $sequenceKey, int $value = 0): bool
    {
        /**
         * Cập nhật hoặc tạo mới sequence key
         * 
         * DB::table('sequences') - Bắt đầu query builder từ bảng sequences
         *   - table() là method của Laravel DB facade
         * 
         * ->updateOrInsert([...], [...]) - Cập nhật hoặc insert row
         *   - updateOrInsert() là method của query builder
         *   - Tham số 1: Array chứa điều kiện tìm kiếm (WHERE conditions)
         *   - Tham số 2: Array chứa data cần insert/update
         * 
         * Tham số 1: ['sequence_key' => $sequenceKey] - Điều kiện tìm kiếm
         *   - Tìm row có sequence_key = $sequenceKey
         *   - Nếu tìm thấy: Cập nhật row đó
         *   - Nếu không tìm thấy: Tạo mới row
         * 
         * Tham số 2: Array chứa data cần insert/update
         *   - 'current_value' => $value - Giá trị sequence để reset về
         *     - $value là giá trị được truyền vào (mặc định: 0)
         *     - Ví dụ: Nếu $value = 0, current_value = 0
         *     - Sequence tiếp theo sẽ là 1 (0 + 1)
         *   - 'updated_at' => now() - Cập nhật thời gian cập nhật
         *     - now() là helper function của Laravel, trả về Carbon instance của thời gian hiện tại
         *     - Nếu tạo mới, Laravel sẽ tự động thêm created_at
         * 
         * updateOrInsert() sẽ execute:
         * - Nếu tồn tại: UPDATE sequences SET current_value = $value, updated_at = now() WHERE sequence_key = $sequenceKey
         * - Nếu không tồn tại: INSERT INTO sequences (sequence_key, current_value, updated_at, created_at) VALUES (...)
         * 
         * return - Trả về boolean
         *   - true nếu thành công (đã cập nhật hoặc tạo mới)
         *   - false nếu có lỗi
         */
        return DB::table('sequences')
            ->updateOrInsert(
                ['sequence_key' => $sequenceKey],
                [
                    'current_value' => $value,
                    'updated_at' => now()
                ]
            );
    }

    /**
     * Xóa một sequence key
     * 
     * LUỒNG XỬ LÝ:
     * 1. Xóa bản ghi trong bảng sequences với sequence_key
     * 2. Trả về true nếu đã xóa được ít nhất 1 bản ghi, false nếu không
     * 
     * DỮ LIỆU GHI VÀO:
     * - Xóa bản ghi trong bảng sequences
     * 
     * MỤC ĐÍCH:
     * - Xóa sequence key không còn sử dụng
     * - Dọn dẹp dữ liệu
     * 
     * LƯU Ý:
     * - Xóa sequence key sẽ làm mất thông tin sequence
     * - Sequence tiếp theo sẽ bắt đầu lại từ đầu (nếu được tạo lại)
     * 
     * @param string $sequenceKey Sequence key cần xóa
     * @return bool true nếu đã xóa được ít nhất 1 bản ghi, false nếu không
     */
    public static function delete(string $sequenceKey): bool
    {
        /**
         * Xóa bản ghi sequence key từ database
         * 
         * DB::table('sequences') - Bắt đầu query builder từ bảng sequences
         *   - table() là method của Laravel DB facade
         * 
         * ->where('sequence_key', $sequenceKey) - Lọc theo sequence_key
         *   - where() là method của query builder để thêm điều kiện WHERE
         *   - 'sequence_key' là tên column
         *   - $sequenceKey là giá trị cần tìm
         *   - Query sẽ tìm row có sequence_key = $sequenceKey
         * 
         * ->delete() - Xóa rows đã tìm được
         *   - delete() là method của query builder để xóa rows
         *   - Sẽ execute SQL query: DELETE FROM sequences WHERE sequence_key = $sequenceKey
         *   - Trả về số lượng rows đã được xóa (integer)
         *   - Ví dụ: Nếu xóa được 1 row, trả về 1
         *   - Ví dụ: Nếu không tìm thấy row nào, trả về 0
         * 
         * > 0 - So sánh số lượng rows đã xóa với 0
         *   - > là greater than operator
         *   - Nếu delete() > 0: có ít nhất 1 row đã được xóa, trả về true
         *   - Nếu delete() = 0: không có row nào được xóa, trả về false
         * 
         * return - Trả về boolean
         *   - true nếu đã xóa được ít nhất 1 bản ghi
         *   - false nếu không xóa được bản ghi nào (không tìm thấy)
         */
        return DB::table('sequences')
            ->where('sequence_key', $sequenceKey)
            ->delete() > 0;
    }
}

