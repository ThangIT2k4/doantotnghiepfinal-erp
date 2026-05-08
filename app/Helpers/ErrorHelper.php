<?php

namespace App\Helpers;

use Illuminate\Database\QueryException;
use PDOException;

class ErrorHelper
{
    /**
     * Lấy thông báo lỗi an toàn để hiển thị cho người dùng
     * 
     * Function này sẽ kiểm tra loại exception và trả về thông báo generic
     * để tránh lộ thông tin nhạy cảm như SQL queries, tên bảng, cột database
     * 
     * @param \Exception $exception Exception object
     * @param string|null $defaultMessage Thông báo mặc định nếu không xác định được loại lỗi
     * @return string Thông báo lỗi an toàn
     */
    public static function getSafeErrorMessage(\Exception $exception, ?string $defaultMessage = null): string
    {
        // Nếu là database exception (QueryException hoặc PDOException)
        // Trả về thông báo generic để tránh lộ thông tin database
        if ($exception instanceof QueryException || $exception instanceof PDOException) {
            return $defaultMessage ?? 'Có lỗi xảy ra khi xử lý dữ liệu. Vui lòng thử lại sau hoặc liên hệ quản trị viên.';
        }

        // Kiểm tra nếu message chứa thông tin SQL hoặc database
        $message = $exception->getMessage();
        
        // Các pattern có thể chứa thông tin nhạy cảm
        $sensitivePatterns = [
            '/SQLSTATE/i',
            '/SQL syntax/i',
            '/Table.*doesn\'t exist/i',
            '/Column.*doesn\'t exist/i',
            '/Unknown column/i',
            '/Unknown table/i',
            '/Access denied/i',
            '/Connection refused/i',
            '/PDOException/i',
            '/QueryException/i',
            '/SELECT.*FROM/i',
            '/INSERT.*INTO/i',
            '/UPDATE.*SET/i',
            '/DELETE.*FROM/i',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return $defaultMessage ?? 'Có lỗi xảy ra khi xử lý dữ liệu. Vui lòng thử lại sau hoặc liên hệ quản trị viên.';
            }
        }

        // Nếu không phải lỗi database, có thể trả về message gốc
        // nhưng vẫn nên dùng thông báo generic để đảm bảo bảo mật
        return $defaultMessage ?? 'Có lỗi xảy ra. Vui lòng thử lại sau hoặc liên hệ quản trị viên.';
    }

    /**
     * Lấy thông báo lỗi an toàn với context cụ thể
     * 
     * @param \Exception $exception Exception object
     * @param string $action Mô tả hành động đang thực hiện (ví dụ: "tạo lead", "cập nhật hóa đơn")
     * @return string Thông báo lỗi an toàn với context
     */
    public static function getSafeErrorMessageWithContext(\Exception $exception, string $action): string
    {
        $baseMessage = self::getSafeErrorMessage($exception);
        
        // Thêm context nếu có thể
        if (strpos($baseMessage, 'Có lỗi xảy ra') !== false) {
            return "Có lỗi xảy ra khi {$action}. Vui lòng thử lại sau hoặc liên hệ quản trị viên.";
        }
        
        return $baseMessage;
    }
}

