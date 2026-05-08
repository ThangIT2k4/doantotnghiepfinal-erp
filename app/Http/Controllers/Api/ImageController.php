<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Upload một hình ảnh
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate request: image (required, image, mimes, max 5MB), folder (optional)
     * 2. Nếu validation thất bại: Trả về 422 với errors
     * 3. Lấy folder (mặc định: 'general')
     * 4. Gọi ImageService->uploadImage() để upload
     * 5. Trả về response thành công với data (path, url, etc.)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: File image, folder name
     * 
     * DỮ LIỆU GHI VÀO:
     * - File system: Lưu hình ảnh vào storage
     * 
     * VALIDATION:
     * - image: required, phải là image, mimes: jpeg,png,jpg,gif,webp, max: 5MB
     * - folder: optional, string, max 50 ký tự
     * 
     * @param Request $request HTTP request chứa file image và folder
     * @return JsonResponse JSON response với kết quả upload
     */
    public function upload(Request $request): JsonResponse
    {
        /**
         * Validate request
         * 
         * - image: Bắt buộc, phải là image, định dạng: jpeg,png,jpg,gif,webp, tối đa 5MB
         * - folder: Tùy chọn, string, tối đa 50 ký tự
         */
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'folder' => 'nullable|string|max:50'
        ]);

        /**
         * Nếu validation thất bại: Trả về 422 Unprocessable Entity với errors
         */
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /**
             * Lấy folder name (mặc định: 'general')
             */
            $folder = $request->input('folder', 'general');
            
            /**
             * Gọi ImageService để upload hình ảnh
             * 
             * Service sẽ:
             * - Validate hình ảnh
             * - Resize nếu cần
             * - Lưu vào storage
             * - Trả về path, url, size, etc.
             */
            $result = $this->imageService->uploadImage($request->file('image'), $folder);

            /**
             * Trả về response thành công với data
             * 
             * Data thường bao gồm:
             * - path: Đường dẫn file
             * - url: URL để truy cập
             * - size: Kích thước file
             * - etc.
             */
            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            /**
             * Xử lý exception
             * 
             * Trả về 500 Internal Server Error với error message
             */
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload nhiều hình ảnh (tối đa 10)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate request: images (required, array, max 10), mỗi image phải hợp lệ, folder (optional)
     * 2. Nếu validation thất bại: Trả về 422 với errors
     * 3. Lấy folder (mặc định: 'general')
     * 4. Gọi ImageService->uploadMultipleImages() để upload tất cả
     * 5. Trả về response thành công với data (array của results)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: Array of files, folder name
     * 
     * DỮ LIỆU GHI VÀO:
     * - File system: Lưu tất cả hình ảnh vào storage
     * 
     * VALIDATION:
     * - images: required, array, tối đa 10 files
     * - images.*: Mỗi file phải là image, mimes: jpeg,png,jpg,gif,webp, max: 5MB
     * - folder: optional, string, max 50 ký tự
     * 
     * @param Request $request HTTP request chứa array of files và folder
     * @return JsonResponse JSON response với kết quả upload (array)
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        /**
         * Validate request
         * 
         * - images: Bắt buộc, phải là array, tối đa 10 files
         * - images.*: Mỗi file phải là image, định dạng: jpeg,png,jpg,gif,webp, tối đa 5MB
         * - folder: Tùy chọn, string, tối đa 50 ký tự
         */
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'folder' => 'nullable|string|max:50'
        ]);

        /**
         * Nếu validation thất bại: Trả về 422 Unprocessable Entity với errors
         */
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /**
             * Lấy folder name (mặc định: 'general')
             */
            $folder = $request->input('folder', 'general');
            
            /**
             * Gọi ImageService để upload tất cả hình ảnh
             * 
             * Service sẽ:
             * - Validate từng hình ảnh
             * - Resize nếu cần
             * - Lưu vào storage
             * - Trả về array của results (path, url, size, etc. cho mỗi file)
             */
            $results = $this->imageService->uploadMultipleImages($request->file('images'), $folder);

            /**
             * Trả về response thành công với data (array)
             * 
             * Data là array, mỗi phần tử chứa:
             * - path: Đường dẫn file
             * - url: URL để truy cập
             * - size: Kích thước file
             * - etc.
             */
            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            /**
             * Xử lý exception
             * 
             * Trả về 500 Internal Server Error với error message
             */
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa một hình ảnh
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate request: path (required, string)
     * 2. Nếu validation thất bại: Trả về 422 với errors
     * 3. Gọi ImageService->deleteImage() để xóa
     * 4. Nếu thành công: Trả về 200 với success message
     * 5. Nếu thất bại: Trả về 500 với error message
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: Path của file cần xóa
     * 
     * DỮ LIỆU GHI VÀO:
     * - File system: Xóa file từ storage
     * 
     * VALIDATION:
     * - path: required, string (đường dẫn file cần xóa)
     * 
     * @param Request $request HTTP request chứa path của file
     * @return JsonResponse JSON response với kết quả xóa
     */
    public function delete(Request $request): JsonResponse
    {
        /**
         * Validate request
         * 
         * - path: Bắt buộc, string (đường dẫn file cần xóa)
         */
        $validator = Validator::make($request->all(), [
            'path' => 'required|string'
        ]);

        /**
         * Nếu validation thất bại: Trả về 422 Unprocessable Entity với errors
         */
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /**
             * Gọi ImageService để xóa hình ảnh
             * 
             * Service sẽ:
             * - Kiểm tra file tồn tại
             * - Xóa file từ storage
             * - Xóa các versions (small, medium, large) nếu có
             * - Trả về true nếu thành công, false nếu thất bại
             */
            $success = $this->imageService->deleteImage($request->input('path'));

            /**
             * Nếu xóa thành công: Trả về 200 OK
             */
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            } else {
                /**
                 * Nếu xóa thất bại: Trả về 500 Internal Server Error
                 * 
                 * Có thể do:
                 * - File không tồn tại
                 * - Không có quyền xóa
                 * - Lỗi file system
                 */
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete image'
                ], 500);
            }
        } catch (\Exception $e) {
            /**
             * Xử lý exception
             * 
             * Trả về 500 Internal Server Error với error message
             */
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy URL của hình ảnh với kích thước cụ thể
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate request: path (required, string), size (optional, in: original,small,medium,large)
     * 2. Nếu validation thất bại: Trả về 422 với errors
     * 3. Lấy size (mặc định: 'original')
     * 4. Gọi ImageService->getImageUrl() để lấy URL
     * 5. Trả về response thành công với URL và size
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: Path của file, size
     * - ImageService: Lấy URL từ storage
     * 
     * VALIDATION:
     * - path: required, string (đường dẫn file)
     * - size: optional, string, in: original,small,medium,large (mặc định: 'original')
     * 
     * @param Request $request HTTP request chứa path và size
     * @return JsonResponse JSON response với URL và size
     */
    public function getUrl(Request $request): JsonResponse
    {
        /**
         * Validate request
         * 
         * - path: Bắt buộc, string (đường dẫn file)
         * - size: Tùy chọn, string, phải là một trong: original,small,medium,large
         */
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'size' => 'nullable|string|in:original,small,medium,large'
        ]);

        /**
         * Nếu validation thất bại: Trả về 422 Unprocessable Entity với errors
         */
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /**
             * Lấy size (mặc định: 'original')
             * 
             * Các size có thể:
             * - original: Kích thước gốc
             * - small: Kích thước nhỏ
             * - medium: Kích thước trung bình
             * - large: Kích thước lớn
             */
            $size = $request->input('size', 'original');
            
            /**
             * Gọi ImageService để lấy URL
             * 
             * Service sẽ:
             * - Kiểm tra file tồn tại
             * - Tạo URL cho size tương ứng
             * - Trả về full URL để truy cập
             */
            $url = $this->imageService->getImageUrl($request->input('path'), $size);

            /**
             * Trả về response thành công với URL và size
             */
            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $url,
                    'size' => $size
                ]
            ]);
        } catch (\Exception $e) {
            /**
             * Xử lý exception
             * 
             * Trả về 500 Internal Server Error với error message
             */
            return response()->json([
                'success' => false,
                'message' => 'Failed to get image URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê storage
     * 
     * LUỒNG XỬ LÝ:
     * 1. Gọi ImageService->getStorageStats() để lấy thống kê
     * 2. Trả về response thành công với stats data
     * 
     * MỤC ĐÍCH:
     * - Cung cấp thông tin về storage: tổng số files, tổng dung lượng, etc.
     * - Giúp admin quản lý và theo dõi storage usage
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - ImageService: Tính toán thống kê từ storage
     * 
     * DỮ LIỆU TRẢ VỀ:
     * - Stats data: Tổng số files, tổng dung lượng, số files theo folder, etc.
     * 
     * @return JsonResponse JSON response với thống kê storage
     */
    public function stats(): JsonResponse
    {
        try {
            /**
             * Gọi ImageService để lấy thống kê storage
             * 
             * Service sẽ:
             * - Đếm tổng số files
             * - Tính tổng dung lượng
             * - Thống kê theo folder
             * - etc.
             */
            $stats = $this->imageService->getStorageStats();

            /**
             * Trả về response thành công với stats data
             * 
             * Stats thường bao gồm:
             * - total_files: Tổng số files
             * - total_size: Tổng dung lượng (bytes)
             * - files_by_folder: Số files theo từng folder
             * - etc.
             */
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            /**
             * Xử lý exception
             * 
             * Trả về 500 Internal Server Error với error message
             */
            return response()->json([
                'success' => false,
                'message' => 'Failed to get storage stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate hình ảnh trước khi upload
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate request: image (required, image, mimes, max 5MB)
     * 2. Nếu validation thất bại: Trả về 422 với errors
     * 3. Gọi ImageService->validateImage() để validate chi tiết
     * 4. Nếu không có lỗi: Trả về 200 với success message
     * 5. Nếu có lỗi: Trả về 422 với errors
     * 
     * MỤC ĐÍCH:
     * - Cho phép client validate hình ảnh trước khi upload
     * - Kiểm tra format, size, dimensions, etc.
     * - Tránh upload hình ảnh không hợp lệ
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: File image
     * 
     * VALIDATION:
     * - image: required, phải là image, định dạng: jpeg,png,jpg,gif,webp, tối đa 5MB
     * 
     * @param Request $request HTTP request chứa file image
     * @return JsonResponse JSON response với kết quả validation
     */
    public function validate(Request $request): JsonResponse
    {
        /**
         * Validate request cơ bản
         * 
         * - image: Bắt buộc, phải là image, định dạng: jpeg,png,jpg,gif,webp, tối đa 5MB
         */
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120'
        ]);

        /**
         * Nếu validation cơ bản thất bại: Trả về 422 Unprocessable Entity với errors
         */
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /**
             * Gọi ImageService để validate chi tiết
             * 
             * Service sẽ kiểm tra:
             * - Format file
             * - Kích thước file
             * - Dimensions (width, height)
             * - File integrity
             * - etc.
             * 
             * Trả về array errors (rỗng nếu hợp lệ)
             */
            $errors = $this->imageService->validateImage($request->file('image'));

            /**
             * Nếu không có lỗi: Trả về 200 OK với success message
             */
            if (empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image is valid'
                ]);
            } else {
                /**
                 * Nếu có lỗi: Trả về 422 Unprocessable Entity với errors
                 * 
                 * Errors có thể bao gồm:
                 * - File quá lớn
                 * - Dimensions không hợp lệ
                 * - File corrupted
                 * - etc.
                 */
                return response()->json([
                    'success' => false,
                    'message' => 'Image validation failed',
                    'errors' => $errors
                ], 422);
            }
        } catch (\Exception $e) {
            /**
             * Xử lý exception
             * 
             * Trả về 500 Internal Server Error với error message
             */
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}