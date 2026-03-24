<?php

namespace App\Services;

use App\Models\ChatAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * Service: FileUploadService
 * 
 * MỤC ĐÍCH:
 * Service quản lý upload và xử lý file attachments cho chat - validate file, upload file, tạo thumbnail cho ảnh,
 * và quản lý lifecycle của attachments (upload, delete, cleanup)
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. uploadChatAttachment(): Upload file attachment cho chat → Validate, upload, tạo thumbnail (nếu là ảnh)
 * 2. createThumbnail(): Tạo thumbnail cho ảnh → Dùng để hiển thị preview nhanh
 * 3. validateFile(): Validate file upload → Kiểm tra size, extension, MIME type, security
 * 4. performSecurityChecks(): Kiểm tra bảo mật file → Chặn file nguy hiểm và nội dung đáng ngờ
 * 5. deleteAttachment(): Xóa attachment và files → Xóa file, thumbnail, và database record
 * 6. cleanupOldAttachments(): Dọn dẹp attachments cũ → Dùng cho maintenance
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: ChatAttachment (bảng chat_attachments) - Lấy thông tin attachments
 * - Storage: public disk - Đọc file đã upload
 * 
 * DỮ LIỆU GHI VÀO:
 * - Storage: public/chat-attachments/ - Lưu file và thumbnail
 * - Model: ChatAttachment - Lưu thông tin attachment (nếu cần)
 * 
 * LƯU Ý:
 * - Hỗ trợ upload: ảnh (jpg, png, gif, webp), documents (pdf, doc, xls, ...), video (mp4, avi, mov, wmv)
 * - File size tối đa: 20MB
 * - Tự động tạo thumbnail cho ảnh (300x300, giữ aspect ratio)
 * - Có kiểm tra bảo mật: chặn file nguy hiểm (exe, bat, js, ...) và nội dung đáng ngờ
 * - Files được lưu theo cấu trúc: chat-attachments/YYYY/MM/DD/filename
 */
class FileUploadService
{
    const MAX_FILE_SIZE = 20 * 1024 * 1024; // 20MB - Kích thước file tối đa cho phép
    const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp']; // Các loại ảnh được phép upload
    const ALLOWED_DOCUMENT_TYPES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar']; // Các loại document được phép
    const ALLOWED_VIDEO_TYPES = ['mp4', 'avi', 'mov', 'wmv']; // Các loại video được phép

    /**
     * Upload file attachment cho chat
     * 
     * MỤC ĐÍCH:
     * Upload file attachment cho chat conversation - validate file, tạo filename unique, upload vào storage,
     * và tạo thumbnail nếu là ảnh
     * 
     * INPUT:
     * - file: UploadedFile cần upload
     * - userId: ID của user upload file
     * 
     * OUTPUT:
     * - array: Thông tin file đã upload (file_name, file_path, file_size, file_type, file_extension, thumbnail_path, uploaded_by)
     * - Storage: File được lưu vào public/chat-attachments/YYYY/MM/DD/
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate file (size, extension, MIME type, security)
     * 2. Tạo filename unique (UUID + extension)
     * 3. Xác định storage path (chat-attachments/YYYY/MM/DD/)
     * 4. Upload file vào storage
     * 5. Nếu là ảnh: tạo thumbnail
     * 6. Trả về thông tin file
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có (chỉ xử lý file upload)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Storage public/chat-attachments/: Lưu file và thumbnail
     * 
     * LƯU Ý:
     * - File được lưu theo cấu trúc: chat-attachments/YYYY/MM/DD/filename
     * - Thumbnail chỉ được tạo cho ảnh (300x300, giữ aspect ratio)
     * - Filename sử dụng UUID để tránh trùng lặp
     */
    public function uploadChatAttachment(UploadedFile $file, int $userId)
    {
        $this->validateFile($file); // Validate file → Kiểm tra size, extension, MIME type, security

        $extension = $file->getClientOriginalExtension(); // Lấy extension của file → Dùng để tạo filename
        $filename = Str::uuid() . '.' . $extension; // Tạo filename unique (UUID + extension) → Tránh trùng lặp
        
        $path = 'chat-attachments/' . date('Y/m/d') . '/' . $filename; // Xác định storage path → Tổ chức file theo ngày

        $storedPath = $file->storeAs('chat-attachments/' . date('Y/m/d'), $filename, 'public'); // Upload file vào storage → Lưu file với tên unique

        $thumbnailPath = null; // Khởi tạo thumbnail path → Lưu path thumbnail nếu có
        if ($this->isImage($file)) { // Nếu file là ảnh
            $thumbnailPath = $this->createThumbnail($storedPath, $filename); // Tạo thumbnail → Dùng để hiển thị preview nhanh
        }

        return [
            'file_name' => $file->getClientOriginalName(), // Tên file gốc → Hiển thị cho user
            'file_path' => $storedPath, // Đường dẫn file trong storage → Dùng để truy cập file
            'file_size' => $file->getSize(), // Kích thước file → Hiển thị thông tin
            'file_type' => $file->getMimeType(), // MIME type → Xác định loại file
            'file_extension' => $extension, // Extension → Xác định loại file
            'thumbnail_path' => $thumbnailPath, // Đường dẫn thumbnail → Dùng để hiển thị preview
            'uploaded_by' => $userId, // ID user upload → Lưu lịch sử
        ];
    }

    /**
     * Tạo thumbnail cho ảnh
     * 
     * MỤC ĐÍCH:
     * Tạo thumbnail (ảnh nhỏ) cho ảnh đã upload để hiển thị preview nhanh trong chat
     * 
     * INPUT:
     * - imagePath: Đường dẫn ảnh gốc trong storage
     * - filename: Tên file ảnh
     * 
     * OUTPUT:
     * - string|null: Đường dẫn thumbnail hoặc null nếu tạo thất bại
     * - Storage: Thumbnail được lưu vào cùng thư mục với ảnh gốc
     * 
     * LUỒNG XỬ LÝ:
     * 1. Xác định đường dẫn đầy đủ của ảnh gốc
     * 2. Tạo tên thumbnail (thumb_filename)
     * 3. Xác định đường dẫn lưu thumbnail
     * 4. Load ảnh bằng Intervention Image
     * 5. Resize ảnh về 300x300 (giữ aspect ratio, không upsize)
     * 6. Lưu thumbnail
     * 7. Trả về đường dẫn thumbnail
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Storage: Ảnh gốc từ public disk
     * 
     * DỮ LIỆU GHI VÀO:
     * - Storage: Thumbnail vào cùng thư mục với ảnh gốc
     * 
     * LƯU Ý:
     * - Thumbnail size: 300x300 pixels
     * - Giữ aspect ratio (tỷ lệ khung hình)
     * - Không upsize (không phóng to ảnh nhỏ hơn 300x300)
     * - Nếu tạo thất bại, trả về null (không throw exception)
     */
    private function createThumbnail(string $imagePath, string $filename)
    {
        try {
            $fullPath = storage_path('app/public/' . $imagePath); // Đường dẫn đầy đủ của ảnh gốc → Load ảnh
            $thumbnailFilename = 'thumb_' . $filename; // Tên thumbnail → Lưu với prefix "thumb_"
            $thumbnailPath = dirname($imagePath) . '/' . $thumbnailFilename; // Đường dẫn thumbnail → Lưu cùng thư mục với ảnh gốc

            $img = Image::make($fullPath); // Load ảnh bằng Intervention Image → Xử lý ảnh
            $img->resize(300, 300, function ($constraint) {
                $constraint->aspectRatio(); // Giữ tỷ lệ khung hình → Không bị méo
                $constraint->upsize(); // Không phóng to ảnh nhỏ hơn → Giữ nguyên ảnh nhỏ
            }); // Resize ảnh về 300x300 → Tạo thumbnail nhỏ
            $img->save(storage_path('app/public/' . $thumbnailPath)); // Lưu thumbnail → Dùng để hiển thị preview

            return $thumbnailPath; // Trả về đường dẫn thumbnail → Dùng để lưu vào database
        } catch (\Exception $e) {
            // Nếu tạo thumbnail thất bại, tiếp tục không có thumbnail
            return null; // Trả về null → Không throw exception để không làm gián đoạn upload
        }
    }

    /**
     * Validate file upload
     * 
     * MỤC ĐÍCH:
     * Kiểm tra file upload có hợp lệ không - kiểm tra size, extension, MIME type, và security
     * 
     * INPUT:
     * - file: UploadedFile cần validate
     * 
     * OUTPUT:
     * - void: Throw exception nếu file không hợp lệ
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra file size (tối đa 20MB)
     * 2. Kiểm tra file extension (chỉ cho phép image, document, video)
     * 3. Kiểm tra MIME type (phải khớp với extension)
     * 4. Kiểm tra bảo mật (chặn file nguy hiểm và nội dung đáng ngờ)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có (chỉ kiểm tra file upload)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ validate)
     * 
     * LƯU Ý:
     * - Throw exception nếu file không hợp lệ
     * - Kiểm tra cả extension và MIME type để tránh bypass
     * - Có kiểm tra bảo mật để chặn file nguy hiểm
     */
    private function validateFile(UploadedFile $file)
    {
        // Kiểm tra kích thước file
        if ($file->getSize() > self::MAX_FILE_SIZE) { // Nếu file lớn hơn 20MB
            throw new \Exception('File size exceeds maximum allowed size of 20MB'); // Throw exception → Không cho upload
        }

        // Kiểm tra extension
        $extension = strtolower($file->getClientOriginalExtension()); // Lấy extension (lowercase) → Kiểm tra loại file
        $allowedExtensions = array_merge(
            self::ALLOWED_IMAGE_TYPES, // Các loại ảnh
            self::ALLOWED_DOCUMENT_TYPES, // Các loại document
            self::ALLOWED_VIDEO_TYPES // Các loại video
        ); // Gộp tất cả extensions được phép → Kiểm tra extension

        if (!in_array($extension, $allowedExtensions)) { // Nếu extension không được phép
            throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions)); // Throw exception → Không cho upload
        }

        // Kiểm tra MIME type
        $mimeType = $file->getMimeType(); // Lấy MIME type → Kiểm tra loại file thực tế
        $allowedMimeTypes = [
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip',
            'application/x-rar-compressed',
            // Videos
            'video/mp4',
            'video/avi',
            'video/quicktime',
            'video/x-ms-wmv',
        ]; // Danh sách MIME types được phép → Kiểm tra MIME type

        if (!in_array($mimeType, $allowedMimeTypes)) { // Nếu MIME type không được phép
            throw new \Exception('File MIME type not allowed'); // Throw exception → Không cho upload
        }

        $this->performSecurityChecks($file); // Kiểm tra bảo mật → Chặn file nguy hiểm và nội dung đáng ngờ
    }

    /**
     * Kiểm tra bảo mật file
     * 
     * MỤC ĐÍCH:
     * Kiểm tra file có chứa extension nguy hiểm hoặc nội dung đáng ngờ không để chặn các file có thể gây hại
     * 
     * INPUT:
     * - file: UploadedFile cần kiểm tra
     * 
     * OUTPUT:
     * - void: Throw exception nếu file có vấn đề bảo mật
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra filename có chứa extension nguy hiểm không (exe, bat, js, ...)
     * 2. Đọc nội dung file
     * 3. Kiểm tra nội dung có chứa pattern đáng ngờ không (PHP code, script tags, ...)
     * 4. Throw exception nếu phát hiện vấn đề
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - File upload: Đọc nội dung file để kiểm tra
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ kiểm tra)
     * 
     * LƯU Ý:
     * - Chặn các extension nguy hiểm: exe, bat, cmd, com, pif, scr, vbs, js, jar
     * - Chặn nội dung đáng ngờ: PHP code, script tags, javascript:, vbscript:, data:text/html
     * - Đây là kiểm tra cơ bản, không thay thế các biện pháp bảo mật khác
     */
    private function performSecurityChecks(UploadedFile $file)
    {
        // Kiểm tra extension nguy hiểm trong filename
        $filename = $file->getClientOriginalName(); // Lấy tên file gốc → Kiểm tra extension
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar']; // Danh sách extensions nguy hiểm → Chặn file thực thi
        
        foreach ($dangerousExtensions as $ext) { // Duyệt qua từng extension nguy hiểm
            if (str_contains(strtolower($filename), '.' . $ext)) { // Nếu filename chứa extension nguy hiểm
                throw new \Exception('File type not allowed for security reasons'); // Throw exception → Chặn upload
            }
        }

        // Kiểm tra nội dung file có pattern đáng ngờ không
        $content = file_get_contents($file->getPathname()); // Đọc nội dung file → Kiểm tra nội dung
        $suspiciousPatterns = [
            '<?php', // PHP code
            '<script', // Script tags
            'javascript:', // JavaScript protocol
            'vbscript:', // VBScript protocol
            'data:text/html', // Data URI với HTML
        ]; // Danh sách pattern đáng ngờ → Chặn file có code độc hại

        foreach ($suspiciousPatterns as $pattern) { // Duyệt qua từng pattern
            if (stripos($content, $pattern) !== false) { // Nếu nội dung chứa pattern đáng ngờ
                throw new \Exception('File contains suspicious content'); // Throw exception → Chặn upload
            }
        }
    }

    /**
     * Kiểm tra file có phải là ảnh không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra file upload có phải là ảnh không dựa trên extension để quyết định có tạo thumbnail không
     * 
     * INPUT:
     * - file: UploadedFile cần kiểm tra
     * 
     * OUTPUT:
     * - bool: true nếu là ảnh, false nếu không
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy extension của file
     * 2. Kiểm tra extension có trong danh sách ALLOWED_IMAGE_TYPES không
     * 3. Trả về kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có (chỉ kiểm tra extension)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ kiểm tra)
     */
    private function isImage(UploadedFile $file)
    {
        $extension = strtolower($file->getClientOriginalExtension()); // Lấy extension (lowercase) → Kiểm tra loại file
        return in_array($extension, self::ALLOWED_IMAGE_TYPES); // Kiểm tra extension có trong danh sách ảnh không → Trả về true/false
    }

    /**
     * Xóa attachment và các files liên quan
     * 
     * MỤC ĐÍCH:
     * Xóa attachment bao gồm file chính, thumbnail (nếu có), và database record
     * 
     * INPUT:
     * - attachment: ChatAttachment model cần xóa
     * 
     * OUTPUT:
     * - bool: true nếu xóa thành công
     * - Storage: File và thumbnail được xóa khỏi storage
     * - Database: Record được xóa khỏi database
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra file chính có tồn tại không, nếu có thì xóa
     * 2. Xác định đường dẫn thumbnail
     * 3. Kiểm tra thumbnail có tồn tại không, nếu có thì xóa
     * 4. Xóa database record
     * 5. Trả về true
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: ChatAttachment - Lấy thông tin file_path
     * - Storage: Kiểm tra file có tồn tại không
     * 
     * DỮ LIỆU GHI VÀO:
     * - Storage: Xóa file và thumbnail
     * - Database: Xóa record trong chat_attachments
     * 
     * LƯU Ý:
     * - Xóa cả file chính và thumbnail (nếu có)
     * - Xóa database record sau khi xóa files
     * - Throw exception nếu có lỗi
     */
    public function deleteAttachment(ChatAttachment $attachment)
    {
        try {
            // Xóa file chính
            if (Storage::disk('public')->exists($attachment->file_path)) { // Kiểm tra file có tồn tại không
                Storage::disk('public')->delete($attachment->file_path); // Xóa file → Giải phóng dung lượng
            }

            // Xóa thumbnail nếu có
            $thumbnailPath = dirname($attachment->file_path) . '/thumb_' . basename($attachment->file_path); // Xác định đường dẫn thumbnail → Xóa thumbnail
            if (Storage::disk('public')->exists($thumbnailPath)) { // Kiểm tra thumbnail có tồn tại không
                Storage::disk('public')->delete($thumbnailPath); // Xóa thumbnail → Giải phóng dung lượng
            }

            // Xóa database record
            $attachment->delete(); // Xóa record trong database → Dọn dẹp dữ liệu

            return true; // Trả về true → Xóa thành công
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete attachment: ' . $e->getMessage()); // Throw exception → Báo lỗi nếu xóa thất bại
        }
    }

    /**
     * Lấy URL của file
     * 
     * MỤC ĐÍCH:
     * Lấy URL công khai của file để hiển thị hoặc download
     * 
     * INPUT:
     * - filePath: Đường dẫn file trong storage
     * 
     * OUTPUT:
     * - string: URL công khai của file
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Storage: Lấy URL từ public disk
     */
    public function getFileUrl(string $filePath)
    {
        return Storage::disk('public')->url($filePath); // Lấy URL công khai của file → Dùng để hiển thị hoặc download
    }

    /**
     * Lấy URL của thumbnail
     * 
     * MỤC ĐÍCH:
     * Lấy URL công khai của thumbnail để hiển thị preview
     * 
     * INPUT:
     * - filePath: Đường dẫn file gốc trong storage
     * 
     * OUTPUT:
     * - string|null: URL công khai của thumbnail hoặc null nếu không có thumbnail
     * 
     * LUỒNG XỬ LÝ:
     * 1. Xác định đường dẫn thumbnail từ file path
     * 2. Kiểm tra thumbnail có tồn tại không
     * 3. Trả về URL nếu có, null nếu không
     */
    public function getThumbnailUrl(string $filePath)
    {
        $thumbnailPath = dirname($filePath) . '/thumb_' . basename($filePath); // Xác định đường dẫn thumbnail → Kiểm tra có tồn tại không
        
        if (Storage::disk('public')->exists($thumbnailPath)) { // Nếu thumbnail tồn tại
            return Storage::disk('public')->url($thumbnailPath); // Trả về URL thumbnail → Dùng để hiển thị preview
        }

        return null; // Trả về null → Không có thumbnail
    }

    /**
     * Lấy thông tin file
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin chi tiết của file (size, MIME type, last modified) để hiển thị
     * 
     * INPUT:
     * - filePath: Đường dẫn file trong storage
     * 
     * OUTPUT:
     * - array|null: Thông tin file (size, size_human, mime_type, last_modified) hoặc null nếu file không tồn tại
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Storage: Đọc thông tin file từ filesystem
     */
    public function getFileInfo(string $filePath)
    {
        if (!Storage::disk('public')->exists($filePath)) { // Kiểm tra file có tồn tại không
            return null; // Trả về null → File không tồn tại
        }

        $fullPath = Storage::disk('public')->path($filePath); // Lấy đường dẫn đầy đủ của file → Đọc thông tin
        
        return [
            'size' => filesize($fullPath), // Kích thước file (bytes) → Hiển thị thông tin
            'size_human' => $this->formatBytes(filesize($fullPath)), // Kích thước file (human readable) → Hiển thị cho user
            'mime_type' => mime_content_type($fullPath), // MIME type → Xác định loại file
            'last_modified' => filemtime($fullPath), // Thời gian sửa đổi cuối → Hiển thị thông tin
        ];
    }

    /**
     * Format bytes sang định dạng dễ đọc
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi bytes sang định dạng dễ đọc (KB, MB, GB, TB) để hiển thị cho user
     * 
     * INPUT:
     * - bytes: Số bytes cần format
     * - precision: Số chữ số thập phân (mặc định 2)
     * 
     * OUTPUT:
     * - string: Chuỗi định dạng dễ đọc (ví dụ: "1.5 MB")
     */
    private function formatBytes(int $bytes, int $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB']; // Danh sách đơn vị → Chuyển đổi bytes
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) { // Chia bytes cho 1024 cho đến khi < 1024
            $bytes /= 1024; // Chia cho 1024 → Chuyển sang đơn vị lớn hơn
        }
        
        return round($bytes, $precision) . ' ' . $units[$i]; // Làm tròn và thêm đơn vị → Trả về định dạng dễ đọc
    }

    /**
     * Dọn dẹp attachments cũ
     * 
     * MỤC ĐÍCH:
     * Xóa các attachments cũ hơn số ngày chỉ định để giải phóng dung lượng (dùng cho maintenance)
     * 
     * INPUT:
     * - daysOld: Số ngày cũ (mặc định 90 ngày)
     * - Database: chat_attachments
     * 
     * OUTPUT:
     * - int: Số lượng attachments đã xóa
     * - Storage: Files và thumbnails được xóa
     * - Database: Records được xóa
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tính ngày cutoff (hiện tại - daysOld)
     * 2. Tìm các attachments cũ hơn cutoff date
     * 3. Duyệt qua từng attachment và xóa
     * 4. Trả về số lượng đã xóa
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng chat_attachments: Tìm attachments cũ
     * 
     * DỮ LIỆU GHI VÀO:
     * - Storage: Xóa files và thumbnails
     * - Database: Xóa records
     */
    public function cleanupOldAttachments(int $daysOld = 90)
    {
        $cutoffDate = now()->subDays($daysOld); // Tính ngày cutoff → Tìm attachments cũ hơn ngày này
        
        $oldAttachments = ChatAttachment::where('created_at', '<', $cutoffDate)->get(); // Tìm attachments cũ → Xóa để giải phóng dung lượng
        
        foreach ($oldAttachments as $attachment) { // Duyệt qua từng attachment
            $this->deleteAttachment($attachment); // Xóa attachment → Xóa file, thumbnail, và database record
        }
        
        return $oldAttachments->count(); // Trả về số lượng đã xóa → Báo cáo kết quả
    }
}
