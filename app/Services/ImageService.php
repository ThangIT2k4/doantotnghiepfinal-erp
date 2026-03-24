<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
// use Intervention\Image\Facades\Image; // Temporarily disabled

/**
 * Service: ImageService
 * 
 * MỤC ĐÍCH:
 * Service quản lý upload và xử lý images/files - upload file (ảnh hoặc document), tạo thumbnails,
 * xóa file, validate file, và quản lý storage statistics
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. uploadFile(): Upload file (ảnh hoặc document) → Validate, tạo filename, lưu vào storage
 * 2. uploadImage(): Upload ảnh (backward compatibility) → Wrapper cho uploadFile với basePath='images'
 * 3. uploadMultipleFiles(): Upload nhiều files → Duyệt qua từng file và upload
 * 4. deleteImage(): Xóa ảnh và thumbnails → Xóa file gốc và các thumbnails
 * 5. deleteMultipleImages(): Xóa nhiều ảnh → Duyệt qua và xóa từng ảnh
 * 6. getImageUrl(): Lấy URL của ảnh → Trả về URL ảnh gốc hoặc thumbnail
 * 7. validateImage(): Validate ảnh → Kiểm tra size, MIME type, dimensions
 * 8. getStorageStats(): Lấy thống kê storage → Tính tổng size và số lượng files
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Config: filesystems.default - Lấy disk mặc định
 * - Storage: public/storage/ - Đọc files đã upload
 * 
 * DỮ LIỆU GHI VÀO:
 * - Storage: public/storage/images/ hoặc public/storage/{folder}/ - Lưu files và thumbnails
 * - Logs: Ghi log quá trình upload và xóa
 * 
 * LƯU Ý:
 * - Files được lưu trực tiếp vào public/storage/ (không dùng symbolic link)
 * - Cấu trúc thư mục: {basePath}/YYYY/MM/filename
 * - Thumbnail creation tạm thời bị disable (cần intervention/image package)
 * - Hỗ trợ cả ảnh và documents
 * - File size tối đa cho ảnh: 5MB
 */
class ImageService
{
    private $disk; // Disk storage mặc định → Dùng để lưu file
    private $basePath; // Base path cho images → Mặc định 'images'

    public function __construct()
    {
        $this->disk = config('filesystems.default'); // Lấy disk mặc định từ config → Dùng để lưu file
        $this->basePath = 'images'; // Base path mặc định → Tổ chức thư mục
    }

    /**
     * Lấy base path cho images
     * 
     * MỤC ĐÍCH:
     * Lấy base path mặc định cho images để sử dụng trong các method khác
     * 
     * OUTPUT:
     * - string: Base path (mặc định 'images')
     */
    public function getBasePath(): string
    {
        return $this->basePath; // Trả về base path → Dùng để tổ chức thư mục
    }

    /**
     * Upload file đơn (ảnh hoặc document)
     * 
     * MỤC ĐÍCH:
     * Upload một file (ảnh hoặc document) vào storage - validate, tạo filename unique, lưu vào storage,
     * và tạo thumbnails (nếu là ảnh, hiện tại disabled)
     * 
     * INPUT:
     * - file: UploadedFile cần upload
     * - folder: Tên thư mục (ví dụ: 'properties', 'tickets', 'payments')
     * - basePath: Base path tùy chỉnh (mặc định: 'images' cho ảnh, dùng folder name cho documents)
     * 
     * OUTPUT:
     * - array: Thông tin file đã upload {original, thumbnails, url, filename, original_name, size, mime_type}
     * - Storage: File được lưu vào public/storage/{basePath}/YYYY/MM/
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lưu thông tin file trước khi move (size, mimeType, originalName)
     * 2. Tạo filename unique (time + random + originalName, sanitize)
     * 3. Xác định basePath (ảnh: 'images', document: folder name)
     * 4. Tạo thư mục nếu chưa tồn tại
     * 5. Di chuyển file vào public/storage
     * 6. Verify file đã được lưu
     * 7. Tạo thumbnails (nếu là ảnh, hiện tại disabled)
     * 8. Trả về thông tin file
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có (chỉ xử lý file upload)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Storage public/storage/{basePath}/YYYY/MM/: Lưu file
     * - Logs: Ghi log quá trình upload
     * 
     * LƯU Ý:
     * - Files được lưu trực tiếp vào public/storage/ (không dùng symbolic link)
     * - Cấu trúc: {basePath}/YYYY/MM/filename
     * - Thumbnail creation tạm thời disabled
     */
    public function uploadFile(UploadedFile $file, string $folder = 'general', ?string $basePath = null): array
    {
        $fileSize = $file->getSize(); // Lưu kích thước file → Dùng để hiển thị và validate
        $mimeType = $file->getMimeType(); // Lưu MIME type → Xác định loại file
        $originalName = $file->getClientOriginalName(); // Lưu tên file gốc → Hiển thị cho user (lưu trước khi move vì sau khi move file không còn ở /tmp)
        
        $filename = time() . '_' . Str::random(8) . '_' . $originalName; // Tạo filename với timestamp và random → Tránh trùng lặp
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename); // Sanitize filename → Loại bỏ ký tự đặc biệt
        
        // Xác định basePath: dùng basePath được cung cấp, hoặc 'images' cho ảnh, hoặc folder name cho documents
        if ($basePath === null) {
            $isImage = str_starts_with($mimeType, 'image/'); // Kiểm tra có phải ảnh không → Xác định basePath
            $basePath = $isImage ? $this->basePath : $folder; // Ảnh: 'images', document: folder name → Tổ chức thư mục
        }
        
        $directory = $basePath . '/' . date('Y/m'); // Tạo đường dẫn thư mục theo năm/tháng → Tổ chức files
        
        $publicStoragePath = public_path('storage/' . $directory); // Đường dẫn đầy đủ trong public/storage → Lưu file trực tiếp (không dùng symbolic link)
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($publicStoragePath)) { // Kiểm tra thư mục có tồn tại không
            if (!mkdir($publicStoragePath, 0775, true)) { // Tạo thư mục (recursive) → Đảm bảo thư mục tồn tại
                Log::error('Failed to create directory', [
                    'path' => $publicStoragePath
                ]); // Ghi log lỗi → Dùng để debug
                throw new \Exception('Không thể tạo thư mục lưu file. Vui lòng kiểm tra quyền ghi file.'); // Throw exception → Báo lỗi
            }
        }
        
        $fullPath = $publicStoragePath . '/' . $filename; // Đường dẫn đầy đủ của file → Lưu file
        if (!$file->move($publicStoragePath, $filename)) { // Di chuyển file vào storage → Lưu file
            Log::error('Failed to move uploaded file', [
                'source' => $file->getPathname(),
                'destination' => $fullPath
            ]); // Ghi log lỗi → Dùng để debug
            throw new \Exception('Không thể lưu file vào storage. Vui lòng kiểm tra quyền ghi file.'); // Throw exception → Báo lỗi
        }
        
        // Verify file đã được lưu thực sự
        if (!file_exists($fullPath)) { // Kiểm tra file có tồn tại không
            Log::error('File upload failed - file does not exist after upload', [
                'path' => $fullPath,
                'directory' => $directory,
                'filename' => $filename
            ]); // Ghi log lỗi → Dùng để debug
            throw new \Exception('File không được lưu vào storage. Đường dẫn: ' . $fullPath); // Throw exception → Báo lỗi
        }
        
        $originalPath = $directory . '/' . $filename; // Đường dẫn để lưu vào database (không có storage/ prefix) → Lưu vào database
        
        Log::info('File uploaded successfully', [
            'path' => $originalPath,
            'full_path' => $fullPath,
            'size' => $fileSize,
            'mime_type' => $mimeType,
            'file_exists' => file_exists($fullPath),
            'file_size_actual' => file_exists($fullPath) ? filesize($fullPath) : 0
        ]); // Ghi log thành công → Dùng để theo dõi
        
        $thumbnails = []; // Khởi tạo thumbnails rỗng → Thumbnail creation tạm thời disabled (cần intervention/image package)
        
        return [
            'original' => $originalPath, // Đường dẫn file gốc → Lưu vào database
            'thumbnails' => $thumbnails, // Danh sách thumbnails → Hiện tại rỗng
            'url' => asset('storage/' . $originalPath), // URL công khai → Hiển thị trong UI
            'filename' => $filename, // Tên file đã lưu → Dùng để xóa
            'original_name' => $originalName, // Tên file gốc → Hiển thị cho user
            'size' => $fileSize, // Kích thước file → Hiển thị thông tin
            'mime_type' => $mimeType // MIME type → Xác định loại file
        ];
    }

    /**
     * Upload ảnh đơn (backward compatibility)
     * 
     * MỤC ĐÍCH:
     * Upload một ảnh - wrapper cho uploadFile với basePath='images' để backward compatibility
     * 
     * INPUT:
     * - file: UploadedFile (ảnh)
     * - folder: Tên thư mục
     * 
     * OUTPUT:
     * - array: Thông tin file đã upload
     */
    public function uploadImage(UploadedFile $file, string $folder = 'general'): array
    {
        return $this->uploadFile($file, $folder, $this->basePath); // Upload file với basePath='images' → Backward compatibility
    }

    /**
     * Upload nhiều files (ảnh hoặc documents)
     * 
     * MỤC ĐÍCH:
     * Upload nhiều files cùng lúc - duyệt qua từng file và upload
     * 
     * INPUT:
     * - files: Mảng các UploadedFile
     * - folder: Tên thư mục
     * - basePath: Base path tùy chỉnh
     * 
     * OUTPUT:
     * - array: Mảng thông tin các files đã upload
     * 
     * LUỒNG XỬ LÝ:
     * 1. Duyệt qua từng file trong mảng
     * 2. Kiểm tra file có phải UploadedFile không
     * 3. Upload file và thêm vào mảng kết quả
     * 4. Trả về mảng kết quả
     */
    public function uploadMultipleFiles(array $files, string $folder = 'general', ?string $basePath = null): array
    {
        $uploadedFiles = []; // Khởi tạo mảng rỗng → Lưu thông tin files đã upload
        
        foreach ($files as $file) { // Duyệt qua từng file
            if ($file instanceof UploadedFile) { // Kiểm tra có phải UploadedFile không
                $uploadedFiles[] = $this->uploadFile($file, $folder, $basePath); // Upload file → Thêm vào mảng kết quả
            }
        }
        
        return $uploadedFiles; // Trả về mảng files đã upload → Dùng để hiển thị
    }

    /**
     * Upload nhiều ảnh (backward compatibility)
     * 
     * MỤC ĐÍCH:
     * Upload nhiều ảnh - wrapper cho uploadMultipleFiles với basePath='images'
     * 
     * INPUT:
     * - files: Mảng các UploadedFile (ảnh)
     * - folder: Tên thư mục
     * 
     * OUTPUT:
     * - array: Mảng thông tin các ảnh đã upload
     */
    public function uploadMultipleImages(array $files, string $folder = 'general'): array
    {
        return $this->uploadMultipleFiles($files, $folder, $this->basePath); // Upload files với basePath='images' → Backward compatibility
    }

    /**
     * Xóa ảnh và thumbnails
     * 
     * MỤC ĐÍCH:
     * Xóa ảnh và tất cả thumbnails liên quan - xóa file gốc và các thumbnails (small, medium, large)
     * 
     * INPUT:
     * - imagePath: Đường dẫn ảnh trong storage (không có storage/ prefix)
     * 
     * OUTPUT:
     * - bool: true nếu xóa thành công, false nếu có lỗi
     * - Storage: File và thumbnails được xóa khỏi storage
     * 
     * LUỒNG XỬ LÝ:
     * 1. Xác định đường dẫn đầy đủ của file gốc
     * 2. Xóa file gốc nếu tồn tại
     * 3. Xóa tất cả thumbnails
     * 4. Trả về kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Storage: Kiểm tra file có tồn tại không
     * 
     * DỮ LIỆU GHI VÀO:
     * - Storage: Xóa file và thumbnails
     * - Logs: Ghi log nếu có lỗi
     */
    public function deleteImage(string $imagePath): bool
    {
        try {
            $fullPath = public_path('storage/' . $imagePath); // Đường dẫn đầy đủ của file gốc → Xóa file
            if (file_exists($fullPath)) { // Kiểm tra file có tồn tại không
                @unlink($fullPath); // Xóa file → Giải phóng dung lượng
            }
            
            $this->deleteThumbnails($imagePath); // Xóa thumbnails → Xóa các ảnh nhỏ (small, medium, large)
            
            return true; // Trả về true → Xóa thành công
        } catch (\Exception $e) {
            Log::error('Error deleting image: ' . $e->getMessage(), [
                'path' => $imagePath,
                'full_path' => $fullPath ?? null
            ]); // Ghi log lỗi → Dùng để debug
            return false; // Trả về false → Xóa thất bại
        }
    }

    /**
     * Xóa nhiều ảnh
     * 
     * MỤC ĐÍCH:
     * Xóa nhiều ảnh cùng lúc - duyệt qua từng ảnh và xóa
     * 
     * INPUT:
     * - imagePaths: Mảng đường dẫn ảnh cần xóa
     * 
     * OUTPUT:
     * - bool: true nếu tất cả xóa thành công, false nếu có ít nhất một lỗi
     * 
     * LUỒNG XỬ LÝ:
     * 1. Duyệt qua từng đường dẫn ảnh
     * 2. Xóa từng ảnh
     * 3. Nếu có lỗi: đánh dấu success = false
     * 4. Trả về kết quả
     */
    public function deleteMultipleImages(array $imagePaths): bool
    {
        $success = true; // Khởi tạo success = true → Đánh dấu tất cả thành công
        
        foreach ($imagePaths as $path) { // Duyệt qua từng đường dẫn ảnh
            if (!$this->deleteImage($path)) { // Xóa ảnh → Kiểm tra có thành công không
                $success = false; // Đánh dấu có lỗi → Có ít nhất một ảnh xóa thất bại
            }
        }
        
        return $success; // Trả về kết quả → true nếu tất cả thành công, false nếu có lỗi
    }

    /**
     * Tạo filename unique
     * 
     * MỤC ĐÍCH:
     * Tạo filename unique từ tên file gốc - slug hóa tên file, thêm timestamp và random string
     * 
     * INPUT:
     * - file: UploadedFile
     * 
     * OUTPUT:
     * - string: Filename unique
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension(); // Lấy extension → Giữ nguyên extension
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Lấy tên file không có extension → Slug hóa
        $name = Str::slug($name); // Slug hóa tên file → Loại bỏ ký tự đặc biệt
        
        return $name . '_' . time() . '_' . Str::random(8) . '.' . $extension; // Tạo filename unique → Tránh trùng lặp
    }

    /**
     * Tạo thumbnails (tạm thời disabled)
     * 
     * MỤC ĐÍCH:
     * Tạo thumbnails cho ảnh với các kích thước khác nhau (small, medium, large) - hiện tại disabled
     * 
     * LƯU Ý:
     * - Tạm thời disabled để tránh dependency Intervention Image
     * - TODO: Cài đặt intervention/image package và enable lại chức năng này
     */
    private function createThumbnails(UploadedFile $file, string $path, string $filename): array
    {
        // Tạm thời disabled để tránh dependency Intervention Image
        // TODO: Cài đặt intervention/image package và enable lại chức năng này
        return []; // Trả về mảng rỗng → Không tạo thumbnails
        
        /*
        Code để tạo thumbnails (đã comment):
        - Tạo thumbnails với 3 kích thước: small (150x150), medium (300x300), large (600x600)
        - Lưu vào thư mục thumbnails/
        */
    }

    /**
     * Xóa thumbnails
     * 
     * MỤC ĐÍCH:
     * Xóa tất cả thumbnails của ảnh (small, medium, large) - xóa các file trong thư mục thumbnails/
     * 
     * INPUT:
     * - imagePath: Đường dẫn ảnh gốc
     * 
     * LUỒNG XỬ LÝ:
     * 1. Parse path info để lấy directory và filename
     * 2. Duyệt qua các kích thước (small, medium, large)
     * 3. Xác định đường dẫn thumbnail
     * 4. Xóa thumbnail nếu tồn tại
     */
    private function deleteThumbnails(string $imagePath): void
    {
        $pathInfo = pathinfo($imagePath); // Parse path info → Lấy directory, filename, extension
        $filename = $pathInfo['filename'] . '.' . $pathInfo['extension']; // Tên file đầy đủ → Xác định thumbnails
        $directory = $pathInfo['dirname']; // Thư mục chứa ảnh → Xác định thư mục thumbnails
        
        $sizes = ['small', 'medium', 'large']; // Danh sách kích thước thumbnails → Xóa từng thumbnail
        
        foreach ($sizes as $size) { // Duyệt qua từng kích thước
            $thumbnailPath = $directory . '/thumbnails/' . $size . '_' . $filename; // Đường dẫn thumbnail → Xóa thumbnail
            $thumbnailFullPath = public_path('storage/' . $thumbnailPath); // Đường dẫn đầy đủ → Xóa file
            if (file_exists($thumbnailFullPath)) { // Kiểm tra thumbnail có tồn tại không
                @unlink($thumbnailFullPath); // Xóa thumbnail → Giải phóng dung lượng
            }
        }
    }

    /**
     * Lấy URL của ảnh
     * 
     * MỤC ĐÍCH:
     * Lấy URL công khai của ảnh hoặc thumbnail - trả về URL ảnh gốc hoặc thumbnail theo size
     * 
     * INPUT:
     * - path: Đường dẫn ảnh trong storage (không có storage/ prefix)
     * - size: Kích thước cần lấy (original, small, medium, large)
     * 
     * OUTPUT:
     * - string: URL công khai của ảnh hoặc thumbnail
     * 
     * LUỒNG XỬ LÝ:
     * 1. Nếu size = 'original': trả về URL ảnh gốc
     * 2. Xác định đường dẫn thumbnail
     * 3. Kiểm tra thumbnail có tồn tại không
     * 4. Trả về URL thumbnail nếu có, URL ảnh gốc nếu không
     */
    public function getImageUrl(string $path, string $size = 'original'): string
    {
        if ($size === 'original') { // Nếu cần ảnh gốc
            return asset('storage/' . $path); // Trả về URL ảnh gốc → Hiển thị ảnh gốc
        }
        
        $pathInfo = pathinfo($path); // Parse path info → Lấy directory, filename
        $filename = $pathInfo['filename'] . '.' . $pathInfo['extension']; // Tên file đầy đủ → Xác định thumbnail
        $directory = $pathInfo['dirname']; // Thư mục chứa ảnh → Xác định thư mục thumbnails
        $thumbnailPath = $directory . '/thumbnails/' . $size . '_' . $filename; // Đường dẫn thumbnail → Kiểm tra có tồn tại không
        
        $thumbnailFullPath = public_path('storage/' . $thumbnailPath); // Đường dẫn đầy đủ → Kiểm tra file
        if (file_exists($thumbnailFullPath)) { // Nếu thumbnail tồn tại
            return asset('storage/' . $thumbnailPath); // Trả về URL thumbnail → Hiển thị thumbnail
        }
        
        return asset('storage/' . $path); // Trả về URL ảnh gốc → Fallback nếu không có thumbnail
    }

    /**
     * Validate file ảnh
     * 
     * MỤC ĐÍCH:
     * Kiểm tra file ảnh có hợp lệ không - kiểm tra size, MIME type, dimensions (tạm thời disabled)
     * 
     * INPUT:
     * - file: UploadedFile (ảnh)
     * 
     * OUTPUT:
     * - array: Mảng các lỗi (rỗng nếu hợp lệ)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra file size (tối đa 5MB)
     * 2. Kiểm tra MIME type (chỉ cho phép JPEG, PNG, GIF, WebP)
     * 3. Kiểm tra dimensions (tạm thời disabled, cần intervention/image)
     * 4. Trả về mảng lỗi
     * 
     * LƯU Ý:
     * - File size tối đa: 5MB
     * - Chỉ cho phép: JPEG, PNG, GIF, WebP
     * - Dimension checking tạm thời disabled
     */
    public function validateImage(UploadedFile $file): array
    {
        $errors = []; // Khởi tạo mảng lỗi rỗng → Lưu các lỗi validation
        
        // Kiểm tra kích thước file (tối đa 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) { // Nếu file lớn hơn 5MB
            $errors[] = 'File size must be less than 5MB'; // Thêm lỗi → Báo lỗi size
        }
        
        // Kiểm tra MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; // Danh sách MIME types được phép → Kiểm tra loại file
        if (!in_array($file->getMimeType(), $allowedMimes)) { // Nếu MIME type không được phép
            $errors[] = 'File type must be JPEG, PNG, GIF, or WebP'; // Thêm lỗi → Báo lỗi loại file
        }
        
        // Kiểm tra dimensions (tạm thời disabled)
        // TODO: Cài đặt intervention/image package và enable lại dimension checking
        /*
        Code để kiểm tra dimensions (đã comment):
        - Kiểm tra width và height không vượt quá 4000x4000 pixels
        */
        
        return $errors; // Trả về mảng lỗi → Rỗng nếu hợp lệ, có lỗi nếu không hợp lệ
    }

    /**
     * Lấy thống kê storage
     * 
     * MỤC ĐÍCH:
     * Tính toán thống kê storage - tổng kích thước và số lượng files trong thư mục images
     * 
     * OUTPUT:
     * - array: Thống kê {total_size: int (bytes), total_size_mb: float, file_count: int}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Xác định đường dẫn basePath
     * 2. Duyệt đệ quy qua tất cả files trong thư mục
     * 3. Tính tổng kích thước và số lượng files
     * 4. Trả về thống kê
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Storage: Đọc thông tin files từ filesystem
     */
    public function getStorageStats(): array
    {
        $totalSize = 0; // Khởi tạo tổng kích thước = 0 → Tính tổng size
        $fileCount = 0; // Khởi tạo số lượng files = 0 → Đếm số files
        
        $basePath = public_path('storage/' . $this->basePath); // Đường dẫn đầy đủ của basePath → Duyệt files
        
        if (is_dir($basePath)) { // Kiểm tra thư mục có tồn tại không
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS)
            ); // Tạo iterator đệ quy → Duyệt qua tất cả files và thư mục con
            
            foreach ($iterator as $file) { // Duyệt qua từng file/thư mục
                if ($file->isFile()) { // Nếu là file (không phải thư mục)
                    $totalSize += $file->getSize(); // Cộng kích thước file → Tính tổng size
                    $fileCount++; // Tăng số lượng files → Đếm files
                }
            }
        }
        
        return [
            'total_size' => $totalSize, // Tổng kích thước (bytes) → Hiển thị thống kê
            'total_size_mb' => round($totalSize / 1024 / 1024, 2), // Tổng kích thước (MB) → Hiển thị dễ đọc
            'file_count' => $fileCount // Số lượng files → Hiển thị thống kê
        ];
    }
}

