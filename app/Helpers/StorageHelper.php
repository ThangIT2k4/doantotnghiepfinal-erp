<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/**
 * Class: StorageHelper
 * 
 * MỤC ĐÍCH:
 * Helper class để xử lý upload và lấy URL của files trong public storage.
 * Class này cung cấp các phương thức tiện ích để làm việc với file storage trong Laravel.
 * 
 * LUỒNG XỬ LÝ:
 * 1. uploadToPublicStorage(): Upload file lên public storage và trả về path và URL
 * 2. getFileUrl(): Chuyển đổi storage path thành full URL để truy cập từ browser
 * 
 * CÁCH SỬ DỤNG:
 * // Upload file
 * $result = StorageHelper::uploadToPublicStorage($file, 'documents');
 * // $result = ['path' => 'documents/1234567890_file.pdf', 'url' => 'http://domain.com/storage/documents/1234567890_file.pdf']
 * 
 * // Lấy URL từ path
 * $url = StorageHelper::getFileUrl('documents/file.pdf');
 * // $url = 'http://domain.com/storage/documents/file.pdf'
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - UploadedFile: File được upload từ request
 * - Storage path: Đường dẫn file đã lưu trong database
 * 
 * DỮ LIỆU GHI VÀO:
 * - File system: Lưu file vào public/storage/{directory}/
 * 
 * LƯU Ý:
 * - Files được lưu trong public/storage/ để có thể truy cập trực tiếp từ browser
 * - Tên file được thêm timestamp để tránh trùng lặp
 * - Path lưu trong database không có prefix 'storage/'
 * - URL được tạo bằng asset() helper của Laravel
 */
class StorageHelper
{
    /**
     * Upload file lên public storage
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo tên file mới: timestamp + tên file gốc (để tránh trùng lặp)
     * 2. Tạo đường dẫn thư mục: public/storage/{directory}
     * 3. Tạo thư mục nếu chưa tồn tại (với quyền 0775)
     * 4. Di chuyển file vào thư mục đích
     * 5. Tạo path để lưu vào database (không có prefix 'storage/')
     * 6. Tạo full URL để truy cập từ browser
     * 7. Trả về array chứa path và url
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - UploadedFile $file: File được upload từ request
     * 
     * DỮ LIỆU GHI VÀO:
     * - File system: Lưu file vào public/storage/{directory}/{filename}
     * 
     * @param UploadedFile $file File cần upload
     * @param string $directory Thư mục đích (ví dụ: 'documents', 'images', 'invoices')
     * @return array Mảng chứa:
     *               - 'path': Đường dẫn để lưu vào database (ví dụ: 'documents/1234567890_file.pdf')
     *               - 'url': Full URL để truy cập từ browser (ví dụ: 'http://domain.com/storage/documents/1234567890_file.pdf')
     */
    public static function uploadToPublicStorage(UploadedFile $file, string $directory): array
    {
        /**
         * Tạo tên file mới: timestamp + tên file gốc
         * 
         * time() - Lấy Unix timestamp hiện tại (số giây từ 1/1/1970)
         *   - time() là PHP built-in function trả về integer
         *   - Ví dụ: 1704067200 (timestamp của một thời điểm cụ thể)
         * 
         * '_' - Dấu gạch dưới để phân cách timestamp và tên file gốc
         * 
         * $file->getClientOriginalName() - Lấy tên file gốc từ client
         *   - getClientOriginalName() là method của UploadedFile class
         *   - Trả về tên file gốc mà user upload (ví dụ: "invoice.pdf")
         *   - Không bao gồm đường dẫn, chỉ có tên file
         * 
         * Dấu . là string concatenation operator trong PHP
         * 
         * $filename - Biến lưu tên file mới
         *   - Format: {timestamp}_{original_filename}
         *   - Ví dụ: "1704067200_invoice.pdf"
         *   - Mục đích: Tránh trùng lặp tên file, dễ dàng sắp xếp theo thời gian
         */
        $filename = time() . '_' . $file->getClientOriginalName();
        
        /**
         * Tạo đường dẫn thư mục đích: public/storage/{directory}
         * 
         * public_path() - Helper function của Laravel trả về đường dẫn tuyệt đối đến thư mục public
         *   - public_path() sẽ trả về: /var/www/html/public (ví dụ)
         *   - public_path('storage/' . $directory) sẽ trả về: /var/www/html/public/storage/{directory}
         *   - Ví dụ: Nếu $directory = 'documents', kết quả: /var/www/html/public/storage/documents
         * 
         * 'storage/' . $directory - Nối chuỗi để tạo đường dẫn con
         *   - Dấu . là string concatenation operator
         *   - Ví dụ: 'storage/' . 'documents' = 'storage/documents'
         * 
         * $publicStoragePath - Biến lưu đường dẫn tuyệt đối đến thư mục đích
         *   - Sẽ được sử dụng để tạo thư mục và lưu file
         */
        $publicStoragePath = public_path('storage/' . $directory);
        
        /**
         * Tạo thư mục nếu chưa tồn tại
         * 
         * is_dir($publicStoragePath) - Kiểm tra xem đường dẫn có phải là thư mục và đã tồn tại không
         *   - is_dir() là PHP built-in function
         *   - Trả về true nếu $publicStoragePath là thư mục và đã tồn tại
         *   - Trả về false nếu không tồn tại hoặc không phải thư mục
         * 
         * if (!is_dir($publicStoragePath)) - Kiểm tra xem thư mục chưa tồn tại
         *   - ! là NOT operator, đảo ngược giá trị boolean
         *   - Nếu thư mục chưa tồn tại (!is_dir() = true), vào block if để tạo thư mục
         * 
         * mkdir($publicStoragePath, 0775, true) - Tạo thư mục
         *   - mkdir() là PHP built-in function để tạo thư mục
         *   - Tham số 1: $publicStoragePath - Đường dẫn thư mục cần tạo
         *   - Tham số 2: 0775 - Quyền truy cập (permissions)
         *     - 0 = prefix cho octal number
         *     - 7 (owner) = read(4) + write(2) + execute(1) = rwx
         *     - 7 (group) = read(4) + write(2) + execute(1) = rwx
         *     - 5 (others) = read(4) + execute(1) = r-x (không có write)
         *   - Tham số 3: true - Recursive mode
         *     - true nghĩa là tạo tất cả thư mục cha nếu chưa tồn tại
         *     - Ví dụ: Nếu cần tạo /public/storage/documents, nhưng /public/storage chưa có, sẽ tạo cả 2
         *   - Trả về true nếu thành công, false nếu thất bại
         */
        if (!is_dir($publicStoragePath)) {
            mkdir($publicStoragePath, 0775, true);
        }
        
        /**
         * Di chuyển file vào thư mục đích
         * 
         * $file->move($publicStoragePath, $filename) - Di chuyển file từ temporary location đến thư mục đích
         *   - move() là method của UploadedFile class
         *   - Tham số 1: $publicStoragePath - Đường dẫn thư mục đích (ví dụ: /var/www/html/public/storage/documents)
         *   - Tham số 2: $filename - Tên file mới (ví dụ: "1704067200_invoice.pdf")
         *   - Method này sẽ:
         *     1. Di chuyển file từ temporary location (thường là /tmp) đến $publicStoragePath
         *     2. Đổi tên file thành $filename
         *     3. Trả về true nếu thành công, false nếu thất bại
         *   - Sau khi move(), file sẽ nằm tại: $publicStoragePath/$filename
         *   - Ví dụ: /var/www/html/public/storage/documents/1704067200_invoice.pdf
         */
        $file->move($publicStoragePath, $filename);
        
        /**
         * Tạo đường dẫn để lưu vào database (không có prefix 'storage/')
         * 
         * $directory - Tên thư mục (ví dụ: 'documents')
         * 
         * '/' - Dấu gạch chéo để phân cách thư mục và tên file
         * 
         * $filename - Tên file đã được tạo (ví dụ: "1704067200_invoice.pdf")
         * 
         * Dấu . là string concatenation operator trong PHP
         * 
         * $path - Biến lưu đường dẫn tương đối để lưu vào database
         *   - Format: {directory}/{filename}
         *   - Ví dụ: "documents/1704067200_invoice.pdf"
         *   - Lưu ý: Không có prefix 'storage/' vì sẽ được thêm khi tạo URL qua getFileUrl() hoặc asset()
         *   - Path này sẽ được lưu vào database (ví dụ: trong cột document_path, image_path, etc.)
         */
        $path = $directory . '/' . $filename;
        
        /**
         * Tạo full URL để truy cập từ browser
         * 
         * asset() - Helper function của Laravel tạo URL đến file trong public directory
         *   - asset() sẽ tạo URL dựa trên APP_URL trong file .env
         *   - Format: {APP_URL}/storage/{path}
         *   - Ví dụ: Nếu APP_URL = 'http://domain.com', asset('storage/documents/file.pdf') = 'http://domain.com/storage/documents/file.pdf'
         * 
         * 'storage/' . $path - Nối chuỗi để tạo đường dẫn đầy đủ
         *   - 'storage/' là prefix cố định (tương ứng với thư mục public/storage)
         *   - $path là đường dẫn tương đối (ví dụ: "documents/1704067200_invoice.pdf")
         *   - Kết quả: 'storage/documents/1704067200_invoice.pdf'
         * 
         * $url - Biến lưu full URL để truy cập file từ browser
         *   - Format: http://domain.com/storage/{path}
         *   - Ví dụ: "http://domain.com/storage/documents/1704067200_invoice.pdf"
         *   - URL này có thể được sử dụng trong HTML (ví dụ: <img src="{$url}">, <a href="{$url}">)
         */
        $url = asset('storage/' . $path);
        
        /**
         * Trả về array chứa path và url
         * 
         * return [...] - Trả về associative array
         *   - 'path' => $path - Đường dẫn tương đối để lưu vào database
         *     - Ví dụ: "documents/1704067200_invoice.pdf"
         *     - Dùng để lưu vào database, sau đó có thể dùng getFileUrl() để lấy URL
         *   - 'url' => $url - Full URL để truy cập file từ browser
         *     - Ví dụ: "http://domain.com/storage/documents/1704067200_invoice.pdf"
         *     - Dùng để hiển thị trong frontend (HTML, API response, etc.)
         * 
         * Array này sẽ được sử dụng bởi controller để:
         * - Lưu path vào database
         * - Trả về url cho frontend
         */
        return [
            'path' => $path,
            'url' => $url,
        ];
    }
    
    /**
     * Lấy file URL từ storage path
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra value có rỗng không (null hoặc empty string)
     * 2. Nếu đã là full URL (bắt đầu với http:// hoặc https://): Trả về luôn
     * 3. Loại bỏ dấu '/' ở đầu path (nếu có)
     * 4. Kiểm tra path đã có prefix 'storage/' chưa:
     *    - Nếu có: Tạo URL trực tiếp với asset($path)
     *    - Nếu chưa: Thêm prefix 'storage/' và tạo URL
     * 5. Trả về full URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Storage path: Đường dẫn file đã lưu trong database
     * 
     * MỤC ĐÍCH:
     * - Chuyển đổi storage path thành full URL để hiển thị trong frontend
     * - Xử lý các trường hợp: path có/không có prefix 'storage/', đã là full URL, null/empty
     * 
     * @param string|null $value Storage path hoặc URL (có thể null)
     * @return string|null Full URL để truy cập file hoặc null nếu value rỗng
     */
    public static function getFileUrl(?string $value): ?string
    {
        /**
         * Kiểm tra value có rỗng không
         * 
         * empty($value) - Kiểm tra xem value có rỗng không
         *   - empty() là PHP built-in function
         *   - Trả về true nếu value là: null, '', 0, '0', false, [], etc.
         *   - Trả về false nếu value có giá trị (không rỗng)
         * 
         * if (empty($value)) - Kiểm tra xem value có rỗng không
         *   - Nếu rỗng (null hoặc empty string), vào block if
         * 
         * return null - Trả về null nếu value rỗng
         *   - null nghĩa là không có file hoặc không có URL
         *   - Frontend có thể kiểm tra null để hiển thị placeholder hoặc ẩn image
         */
        if (empty($value)) {
            return null;
        }
        
        /**
         * Kiểm tra đã là full URL chưa
         * 
         * str_starts_with($value, 'http://') - Kiểm tra xem value có bắt đầu với 'http://' không
         *   - str_starts_with() là PHP 8.0+ built-in function
         *   - Trả về true nếu value bắt đầu với 'http://'
         *   - Trả về false nếu không
         * 
         * str_starts_with($value, 'https://') - Kiểm tra xem value có bắt đầu với 'https://' không
         *   - Tương tự như trên, nhưng kiểm tra 'https://'
         * 
         * || - Logical OR operator (hoặc)
         *   - Trả về true nếu một trong hai điều kiện là true
         * 
         * if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) - Kiểm tra xem value đã là full URL chưa
         *   - Nếu value đã bắt đầu với 'http://' hoặc 'https://', nghĩa là đã là full URL
         *   - Vào block if để trả về luôn, không cần xử lý thêm
         * 
         * return $value - Trả về value nguyên bản (đã là full URL)
         *   - Trường hợp này xảy ra khi value đã là URL đầy đủ từ trước (ví dụ: từ external source)
         *   - Ví dụ: "https://example.com/image.jpg" -> trả về "https://example.com/image.jpg"
         */
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        
        /**
         * Loại bỏ dấu '/' ở đầu path (nếu có)
         * 
         * ltrim($value, '/') - Loại bỏ tất cả dấu '/' ở đầu string
         *   - ltrim() là PHP built-in function để loại bỏ ký tự ở đầu string (left trim)
         *   - Tham số 1: $value - String cần xử lý
         *   - Tham số 2: '/' - Ký tự cần loại bỏ (có thể là nhiều ký tự)
         *   - Ví dụ: ltrim('/documents/file.pdf', '/') = 'documents/file.pdf'
         *   - Ví dụ: ltrim('///documents/file.pdf', '/') = 'documents/file.pdf' (loại bỏ tất cả '/' ở đầu)
         * 
         * $path - Biến lưu path đã được chuẩn hóa (không có '/' ở đầu)
         *   - Sẽ được sử dụng để tạo URL
         */
        $path = ltrim($value, '/');
        
        /**
         * Kiểm tra path đã có prefix 'storage/' chưa
         * 
         * str_starts_with($path, 'storage/') - Kiểm tra xem path có bắt đầu với 'storage/' không
         *   - str_starts_with() là PHP 8.0+ built-in function
         *   - Trả về true nếu path bắt đầu với 'storage/'
         *   - Trả về false nếu không
         * 
         * if (str_starts_with($path, 'storage/')) - Kiểm tra xem path đã có prefix 'storage/' chưa
         *   - Nếu đã có prefix 'storage/', vào block if
         *   - Nếu chưa có prefix 'storage/', vào block else
         */
        if (str_starts_with($path, 'storage/')) {
            /**
             * Path đã có prefix 'storage/'
             * 
             * asset($path) - Tạo URL từ path đã có prefix 'storage/'
             *   - asset() là helper function của Laravel
             *   - asset() sẽ tạo URL dựa trên APP_URL trong file .env
             *   - Format: {APP_URL}/{path}
             *   - Ví dụ: Nếu $path = 'storage/documents/file.pdf' và APP_URL = 'http://domain.com'
             *     Kết quả: 'http://domain.com/storage/documents/file.pdf'
             * 
             * return asset($path) - Trả về full URL
             *   - Path đã có prefix 'storage/' nên không cần thêm nữa
             *   - Ví dụ: "http://domain.com/storage/documents/file.pdf"
             */
            return asset($path);
        }
        
        /**
         * Path chưa có prefix 'storage/'
         * 
         * 'storage/' . $path - Thêm prefix 'storage/' vào path
         *   - 'storage/' là prefix cố định (tương ứng với thư mục public/storage)
         *   - Dấu . là string concatenation operator trong PHP
         *   - $path là path đã được chuẩn hóa (ví dụ: "documents/file.pdf")
         *   - Kết quả: 'storage/documents/file.pdf'
         * 
         * asset('storage/' . $path) - Tạo URL từ path đã thêm prefix
         *   - asset() là helper function của Laravel
         *   - asset() sẽ tạo URL dựa trên APP_URL trong file .env
         *   - Format: {APP_URL}/storage/{path}
         *   - Ví dụ: Nếu $path = 'documents/file.pdf' và APP_URL = 'http://domain.com'
         *     Kết quả: 'http://domain.com/storage/documents/file.pdf'
         * 
         * return asset('storage/' . $path) - Trả về full URL
         *   - Path chưa có prefix 'storage/' nên cần thêm vào
         *   - Ví dụ: "http://domain.com/storage/documents/file.pdf"
         */
        return asset('storage/' . $path);
    }
}

