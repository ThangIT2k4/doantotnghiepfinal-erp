<?php

namespace App\Services;

use App\Models\EmailOtp;
use App\Models\User;
use App\Mail\OtpVerificationMail;
use App\Support\MailHelper;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service: OtpService
 * 
 * MỤC ĐÍCH:
 * Service quản lý OTP (One-Time Password) cho email verification và email change - gửi OTP, verify OTP,
 * resend OTP, và quản lý lifecycle của OTP (cleanup, status check)
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. sendEmailVerificationOtp(): Gửi OTP cho email verification → Tạo OTP, gửi email
 * 2. sendEmailChangeOtp(): Gửi OTP cho email change → Tạo OTP, gửi email đến email mới
 * 3. verifyOtp(): Verify OTP code → Kiểm tra OTP hợp lệ, đánh dấu đã sử dụng
 * 4. verifyEmailChangeOtp(): Verify OTP cho email change → Wrapper cho verifyOtp với type 'email_change'
 * 5. resendEmailVerificationOtp(): Gửi lại OTP → Kiểm tra rate limit, gửi OTP mới
 * 6. getOtpStatus(): Lấy trạng thái OTP của user → Kiểm tra có OTP hợp lệ không
 * 7. isEmailVerified(): Kiểm tra email đã được verify chưa → Kiểm tra OTP đã được sử dụng
 * 8. isEmailChangeVerified(): Kiểm tra email change đã được verify chưa → Kiểm tra OTP đã được sử dụng
 * 9. cleanupExpiredOtps(): Dọn dẹp OTP đã hết hạn → Dùng cho maintenance
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: EmailOtp (bảng email_otps) - Lấy thông tin OTP
 * - Model: User (bảng users) - Lấy thông tin user
 * - Model: OrganizationUser (bảng organization_users) - Lấy tên organization
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng email_otps: Tạo OTP mới, đánh dấu đã sử dụng
 * - Email: Gửi email OTP đến user
 * - Logs: Ghi log quá trình gửi và verify OTP
 * 
 * LƯU Ý:
 * - OTP có thời gian hết hạn (mặc định 2 phút cho email verification, 2 phút cho email change)
 * - Có rate limiting: không cho gửi lại OTP trong vòng 1 phút
 * - OTP chỉ được sử dụng 1 lần (đánh dấu is_used sau khi verify)
 * - SMTP theo tổ chức (nếu có) qua MailHelper / queue worker
 * - Email verification valid trong 5 phút sau khi verify, email change valid trong 2 phút
 */
class OtpService
{
    /**
     * Gửi OTP verification email cho user
     * 
     * MỤC ĐÍCH:
     * Gửi mã OTP đến email của user để xác thực email - tạo OTP record và gửi email (MailHelper / queue)
     * 
     * INPUT:
     * - user: User model cần gửi OTP
     * - email: Email address cần gửi OTP đến
     * - expiryMinutes: Thời gian hết hạn OTP (mặc định 2 phút)
     * - Database: email_otps, organization_users
     * 
     * OUTPUT:
     * - bool: true nếu gửi thành công, false nếu thất bại
     * - Database: Tạo OTP record mới
     * - Email: Gửi email OTP đến user
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo OTP record trong database với expiry time
     * 2. Lấy tên organization và organization_id cho SMTP (fallback tên 'ZoroRMS Team')
     * 3. Gửi email OTP (queue hoặc sync qua MailHelper)
     * 4. Ghi log và trả về kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng email_otps: Kiểm tra OTP cũ (nếu có)
     * - Bảng organization_users: Lấy tên organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng email_otps: Tạo OTP record mới
     * - Email: Gửi email OTP
     * - Logs: Ghi log quá trình gửi
     */
    public function sendEmailVerificationOtp(User $user, string $email, int $expiryMinutes = 2): bool
    {
        try {
            $otp = EmailOtp::createForEmailVerification($user->id, $email, $expiryMinutes); // Tạo OTP record → Lưu mã OTP vào database

            $organizationName = 'ZoroRMS Team'; // Mặc định tên organization → Fallback nếu không tìm thấy
            $organizationIdForMail = null;
            try {
                $organizationUser = \App\Models\OrganizationUser::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->first(); // Tìm organization user → Lấy tên organization
                if ($organizationUser && $organizationUser->organization) {
                    $organizationName = $organizationUser->organization->name ?? 'ZoroRMS Team'; // Lấy tên organization → Hiển thị trong email
                    $organizationIdForMail = $organizationUser->organization_id;
                }
            } catch (\Exception $e) {
                // Dùng tên mặc định nếu có lỗi
            }

            MailHelper::sendWithOptionalOrgMail(
                new OtpVerificationMail(
                    $otp->otp_code,
                    $user->full_name ?? 'User',
                    $expiryMinutes,
                    'email_verification',
                    $organizationName
                ),
                $email,
                $organizationIdForMail
            );

            Log::info(MailHelper::wantsQueuedDispatch() ? 'OTP email queued successfully' : 'OTP email sent successfully', [
                'user_id' => $user->id,
                'email' => $email,
                'otp_id' => $otp->id
            ]); // Ghi log thành công → Dùng để theo dõi
            
            return true; // Trả về true → Gửi thành công
            
        } catch (Exception $e) {
            Log::error('Failed to send OTP email', [
                'user_id' => $user->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Dùng để debug
            
            return false; // Trả về false → Gửi thất bại
        }
    }
    
    /**
     * Verify OTP code
     * 
     * MỤC ĐÍCH:
     * Xác thực mã OTP của user - kiểm tra OTP hợp lệ, chưa hết hạn, chưa sử dụng, sau đó đánh dấu đã sử dụng
     * 
     * INPUT:
     * - userId: ID của user
     * - otpCode: Mã OTP cần verify
     * - type: Loại OTP (mặc định 'email_verification')
     * - Database: email_otps
     * 
     * OUTPUT:
     * - array: Kết quả verify {success: bool, message: string, otp?: EmailOtp}
     * - Database: Cập nhật OTP (đánh dấu is_used = true)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm OTP theo userId, otpCode, type, và valid (chưa hết hạn, chưa sử dụng)
     * 2. Nếu không tìm thấy: return false với message lỗi
     * 3. Đánh dấu OTP đã sử dụng (markAsUsed)
     * 4. Ghi log và trả về success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng email_otps: Tìm OTP hợp lệ
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng email_otps: Đánh dấu OTP đã sử dụng
     * - Logs: Ghi log quá trình verify
     */
    public function verifyOtp(int $userId, string $otpCode, string $type = 'email_verification'): array
    {
        try {
            $otp = EmailOtp::where('user_id', $userId)
                ->where('otp_code', $otpCode)
                ->where('type', $type)
                ->valid() // Chỉ lấy OTP hợp lệ (chưa hết hạn, chưa sử dụng)
                ->first(); // Tìm OTP → Kiểm tra có tồn tại không
            
            if (!$otp) { // Nếu không tìm thấy OTP hợp lệ
                return [
                    'success' => false,
                    'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'
                ]; // Trả về lỗi → OTP không hợp lệ
            }
            
            $otp->markAsUsed(); // Đánh dấu OTP đã sử dụng → Không thể dùng lại
            
            Log::info('OTP verified successfully', [
                'user_id' => $userId,
                'otp_id' => $otp->id,
                'type' => $type
            ]); // Ghi log thành công → Dùng để theo dõi
            
            return [
                'success' => true,
                'message' => 'Xác thực OTP thành công.',
                'otp' => $otp
            ]; // Trả về success → Verify thành công
            
        } catch (Exception $e) {
            Log::error('Failed to verify OTP', [
                'user_id' => $userId,
                'otp_code' => $otpCode,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Dùng để debug
            
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xác thực OTP.'
            ]; // Trả về lỗi → Có exception
        }
    }
    
    /**
     * Gửi lại OTP cho email verification
     * 
     * MỤC ĐÍCH:
     * Gửi lại mã OTP mới cho user - kiểm tra rate limit (không cho gửi lại trong vòng 1 phút),
     * sau đó gửi OTP mới
     * 
     * INPUT:
     * - user: User model cần gửi lại OTP
     * - email: Email address cần gửi OTP đến
     * - expiryMinutes: Thời gian hết hạn OTP (mặc định 2 phút)
     * - Database: email_otps
     * 
     * OUTPUT:
     * - array: Kết quả {success: bool, message: string}
     * - Database: Tạo OTP record mới (nếu thành công)
     * - Email: Gửi email OTP (nếu thành công)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra user có OTP request gần đây không (trong vòng 1 phút)
     * 2. Nếu có: return false với message rate limit
     * 3. Gửi OTP mới qua sendEmailVerificationOtp
     * 4. Trả về kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng email_otps: Kiểm tra OTP request gần đây
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng email_otps: Tạo OTP record mới (nếu thành công)
     * - Email: Gửi email OTP (nếu thành công)
     * - Logs: Ghi log quá trình
     * 
     * LƯU Ý:
     * - Rate limit: không cho gửi lại OTP trong vòng 1 phút
     * - Mỗi lần gửi lại sẽ tạo OTP mới (OTP cũ vẫn còn trong database)
     */
    public function resendEmailVerificationOtp(User $user, string $email, int $expiryMinutes = 2): array
    {
        try {
            // Kiểm tra user có OTP request gần đây không (trong vòng 1 phút)
            $recentOtp = EmailOtp::where('user_id', $user->id)
                ->where('type', 'email_verification')
                ->where('created_at', '>', now()->subMinute()) // Trong vòng 1 phút gần đây
                ->first(); // Tìm OTP request gần đây → Kiểm tra rate limit
            
            if ($recentOtp) { // Nếu có OTP request gần đây
                return [
                    'success' => false,
                    'message' => 'Vui lòng đợi ít nhất 1 phút trước khi yêu cầu mã OTP mới.'
                ]; // Trả về lỗi rate limit → Chặn spam
            }
            
            $success = $this->sendEmailVerificationOtp($user, $email, $expiryMinutes); // Gửi OTP mới → Tạo OTP và gửi email
            
            if ($success) { // Nếu gửi thành công
                return [
                    'success' => true,
                    'message' => 'Mã OTP mới đã được gửi đến email của bạn.'
                ]; // Trả về success → Gửi thành công
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể gửi mã OTP. Vui lòng thử lại sau.'
                ]; // Trả về lỗi → Gửi thất bại
            }
            
        } catch (Exception $e) {
            Log::error('Failed to resend OTP', [
                'user_id' => $user->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Dùng để debug
            
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi lại mã OTP.'
            ]; // Trả về lỗi → Có exception
        }
    }
    
    /**
     * Dọn dẹp OTP đã hết hạn
     * 
     * MỤC ĐÍCH:
     * Xóa các OTP đã hết hạn và đã sử dụng để giải phóng dung lượng database (dùng cho maintenance)
     * 
     * INPUT:
     * - Database: email_otps
     * 
     * OUTPUT:
     * - int: Số lượng OTP đã xóa
     * - Database: Xóa các OTP đã hết hạn và đã sử dụng
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm các OTP đã hết hạn (expires_at < now) và đã sử dụng (is_used = true)
     * 2. Xóa các OTP này
     * 3. Ghi log và trả về số lượng đã xóa
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng email_otps: Tìm OTP đã hết hạn
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng email_otps: Xóa các OTP đã hết hạn và đã sử dụng
     * - Logs: Ghi log quá trình cleanup
     * 
     * LƯU Ý:
     * - Chỉ xóa OTP đã hết hạn VÀ đã sử dụng
     * - OTP chưa sử dụng nhưng đã hết hạn vẫn được giữ lại (có thể dùng để audit)
     */
    public function cleanupExpiredOtps(): int
    {
        try {
            $deletedCount = EmailOtp::where('expires_at', '<', now()) // OTP đã hết hạn
                ->where('is_used', true) // Và đã sử dụng
                ->delete(); // Xóa các OTP → Giải phóng dung lượng database
            
            Log::info('Cleaned up expired OTPs', ['deleted_count' => $deletedCount]); // Ghi log → Dùng để theo dõi
            
            return $deletedCount; // Trả về số lượng đã xóa → Báo cáo kết quả
            
        } catch (Exception $e) {
            Log::error('Failed to cleanup expired OTPs', [
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Dùng để debug
            
            return 0; // Trả về 0 → Không xóa được gì
        }
    }
    
    /**
     * Lấy trạng thái OTP của user
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin về OTP hợp lệ của user (nếu có) - kiểm tra có OTP hợp lệ, thời gian còn lại, email
     * 
     * INPUT:
     * - userId: ID của user
     * - type: Loại OTP (mặc định 'email_verification')
     * - Database: email_otps
     * 
     * OUTPUT:
     * - array: Thông tin OTP {has_valid_otp: bool, expires_at?: datetime, remaining_seconds?: int, email?: string, message?: string}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm OTP hợp lệ của user (chưa hết hạn, chưa sử dụng)
     * 2. Nếu không có: return has_valid_otp = false
     * 3. Tính thời gian còn lại (remaining_seconds)
     * 4. Trả về thông tin OTP
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng email_otps: Lấy OTP hợp lệ
     */
    public function getOtpStatus(int $userId, string $type = 'email_verification'): array
    {
        try {
            $otp = EmailOtp::where('user_id', $userId)
                ->where('type', $type)
                ->valid() // Chỉ lấy OTP hợp lệ (chưa hết hạn, chưa sử dụng)
                ->first(); // Tìm OTP → Kiểm tra có tồn tại không
            
            if (!$otp) { // Nếu không có OTP hợp lệ
                return [
                    'has_valid_otp' => false,
                    'message' => 'Không có mã OTP hợp lệ.'
                ]; // Trả về không có OTP → User cần gửi OTP mới
            }
            
            $remainingTime = now()->diffInSeconds($otp->expires_at, false); // Tính thời gian còn lại → Hiển thị countdown
            $remainingSeconds = max(0, (int) floor((float) $remainingTime)); // Số nguyên giây (tránh float từ Carbon)

            return [
                'has_valid_otp' => true,
                'expires_at' => $otp->expires_at, // Thời gian hết hạn → Hiển thị cho user
                'remaining_seconds' => $remainingSeconds, // Countdown JSON không bị số lẻ
                'email' => $otp->email // Email đã gửi OTP → Xác nhận email
            ]; // Trả về thông tin OTP → Dùng để hiển thị UI
            
        } catch (Exception $e) {
            Log::error('Failed to get OTP status', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Dùng để debug
            
            return [
                'has_valid_otp' => false,
                'message' => 'Có lỗi xảy ra khi kiểm tra trạng thái OTP.'
            ]; // Trả về lỗi → Có exception
        }
    }
    
    /**
     * Kiểm tra email đã được verify chưa
     * 
     * MỤC ĐÍCH:
     * Kiểm tra email của user đã được xác thực chưa - kiểm tra có OTP đã được verify trong vòng 5 phút gần đây không
     * 
     * INPUT:
     * - userId: ID của user
     * - email: Email address cần kiểm tra
     * - type: Loại OTP (mặc định 'email_verification')
     * - Database: email_otps
     * 
     * OUTPUT:
     * - bool: true nếu email đã được verify, false nếu chưa
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm OTP đã được sử dụng (is_used = true) và verified trong vòng 5 phút gần đây
     * 2. Trả về true nếu có, false nếu không
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng email_otps: Kiểm tra OTP đã verify
     * 
     * LƯU Ý:
     * - Email verification valid trong 5 phút sau khi verify
     * - Chỉ kiểm tra OTP đã được sử dụng (is_used = true)
     */
    public function isEmailVerified(int $userId, string $email, string $type = 'email_verification'): bool
    {
        try {
            $verifiedOtp = EmailOtp::where('user_id', $userId)
                ->where('email', $email)
                ->where('type', $type)
                ->where('is_used', true) // OTP đã được sử dụng
                ->where('verified_at', '>=', now()->subMinutes(5)) // Verified trong vòng 5 phút gần đây
                ->exists(); // Kiểm tra có tồn tại không → Email đã được verify
            
            return $verifiedOtp; // Trả về true/false → Email đã/chưa được verify
            
        } catch (Exception $e) {
            Log::error('Failed to check email verification status', [
                'user_id' => $userId,
                'email' => $email,
                'type' => $type,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Dùng để debug
            
            return false; // Trả về false → Có lỗi, coi như chưa verify
        }
    }
    
    /**
     * Lấy trạng thái email verification của user
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin chi tiết về trạng thái email verification của user - kiểm tra email đã được verify chưa,
     * và nếu có thì lấy thời gian verify
     * 
     * INPUT:
     * - userId: ID của user
     * - email: Email address cần kiểm tra
     * - Database: email_otps
     * 
     * OUTPUT:
     * - array: Thông tin verification {is_verified: bool, verified_at?: datetime, message: string}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra email đã được verify chưa
     * 2. Nếu đã verify: lấy OTP đã verify và trả về thông tin
     * 3. Nếu chưa verify: trả về is_verified = false
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng email_otps: Kiểm tra OTP đã verify
     */
    public function getEmailVerificationStatus(int $userId, string $email): array
    {
        try {
            $isVerified = $this->isEmailVerified($userId, $email); // Kiểm tra email đã được verify chưa → Lấy trạng thái
            
            if ($isVerified) { // Nếu đã verify
                $verifiedOtp = EmailOtp::where('user_id', $userId)
                    ->where('email', $email)
                    ->where('type', 'email_verification')
                    ->where('is_used', true)
                    ->where('verified_at', '>=', now()->subMinutes(5)) // Verified trong vòng 5 phút gần đây
                    ->orderBy('verified_at', 'desc')
                    ->first(); // Lấy OTP đã verify gần nhất → Lấy thời gian verify
                
                return [
                    'is_verified' => true,
                    'verified_at' => $verifiedOtp->verified_at, // Thời gian verify → Hiển thị cho user
                    'message' => 'Email đã được xác thực thành công.'
                ]; // Trả về thông tin verify → Email đã được verify
            }
            
            return [
                'is_verified' => false,
                'message' => 'Email chưa được xác thực hoặc đã hết hạn.'
            ]; // Trả về chưa verify → Email chưa được verify hoặc đã hết hạn
            
        } catch (Exception $e) {
            Log::error('Failed to get email verification status', [
                'user_id' => $userId,
                'email' => $email,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Dùng để debug
            
            return [
                'is_verified' => false,
                'message' => 'Có lỗi xảy ra khi kiểm tra trạng thái xác thực email.'
            ]; // Trả về lỗi → Có exception
        }
    }

    /**
     * Gửi OTP cho email change
     * 
     * MỤC ĐÍCH:
     * Gửi mã OTP đến email mới của user để xác thực email change - tạo OTP record và gửi email (MailHelper / queue)
     * 
     * INPUT:
     * - user: User model cần gửi OTP
     * - newEmail: Email mới cần gửi OTP đến
     * - expiryMinutes: Thời gian hết hạn OTP (mặc định 2 phút)
     * - Database: email_otps, organization_users
     * 
     * OUTPUT:
     * - bool: true nếu gửi thành công, false nếu thất bại
     * - Database: Tạo OTP record mới
     * - Email: Gửi email OTP đến email mới
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo OTP record với type='email_change'
     * 2. Lấy tên organization và organization_id cho SMTP
     * 3. Gửi email OTP (queue hoặc sync)
     * 4. Ghi log và trả về kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_users: Lấy tên organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng email_otps: Tạo OTP record mới
     * - Email: Gửi email OTP
     * - Logs: Ghi log quá trình gửi
     * 
     * LƯU Ý:
     * - OTP hết hạn sau 2 phút (mặc định)
     * - Email được gửi đến email mới, không phải email cũ
     */
    public function sendEmailChangeOtp(User $user, string $newEmail, int $expiryMinutes = 2): bool
    {
        try {
            $otp = EmailOtp::createForEmailChange($user->id, $newEmail, $expiryMinutes); // Tạo OTP record với type='email_change' → Lưu mã OTP vào database

            $organizationName = 'ZoroRMS Team'; // Mặc định tên organization → Fallback nếu không tìm thấy
            $organizationIdForMail = null;
            try {
                $organizationUser = \App\Models\OrganizationUser::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->first(); // Tìm organization user → Lấy tên organization
                if ($organizationUser && $organizationUser->organization) {
                    $organizationName = $organizationUser->organization->name ?? 'ZoroRMS Team'; // Lấy tên organization → Hiển thị trong email
                    $organizationIdForMail = $organizationUser->organization_id;
                }
            } catch (\Exception $e) {
                // Dùng tên mặc định nếu có lỗi
            }

            MailHelper::sendWithOptionalOrgMail(
                new OtpVerificationMail(
                    $otp->otp_code,
                    $user->full_name ?? 'User',
                    $expiryMinutes,
                    'email_change',
                    $organizationName
                ),
                $newEmail,
                $organizationIdForMail
            );

            Log::info(MailHelper::wantsQueuedDispatch() ? 'Email change OTP queued successfully' : 'Email change OTP sent successfully', [
                'user_id' => $user->id,
                'new_email' => $newEmail,
                'otp_id' => $otp->id
            ]); // Ghi log thành công → Dùng để theo dõi
            
            return true; // Trả về true → Gửi thành công
            
        } catch (Exception $e) {
            Log::error('Failed to send email change OTP', [
                'user_id' => $user->id,
                'new_email' => $newEmail,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Dùng để debug
            
            return false; // Trả về false → Gửi thất bại
        }
    }

    /**
     * Verify OTP cho email change
     * 
     * MỤC ĐÍCH:
     * Xác thực mã OTP cho email change - wrapper cho verifyOtp với type='email_change'
     * 
     * INPUT:
     * - userId: ID của user
     * - otpCode: Mã OTP cần verify
     * 
     * OUTPUT:
     * - array: Kết quả verify {success: bool, message: string, otp?: EmailOtp}
     */
    public function verifyEmailChangeOtp(int $userId, string $otpCode): array
    {
        return $this->verifyOtp($userId, $otpCode, 'email_change'); // Verify OTP với type='email_change' → Xác thực email change
    }

    /**
     * Kiểm tra email change đã được verify chưa
     * 
     * MỤC ĐÍCH:
     * Kiểm tra email change đã được xác thực chưa - kiểm tra có OTP đã được verify trong vòng 2 phút gần đây không
     * 
     * INPUT:
     * - userId: ID của user
     * - email: Email address cần kiểm tra
     * - Database: email_otps
     * 
     * OUTPUT:
     * - bool: true nếu email change đã được verify, false nếu chưa
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm OTP đã được sử dụng (is_used = true) với type='email_change' và verified trong vòng 2 phút gần đây
     * 2. Trả về true nếu có, false nếu không
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng email_otps: Kiểm tra OTP đã verify
     * 
     * LƯU Ý:
     * - Email change verification valid trong 2 phút sau khi verify
     * - Chỉ kiểm tra OTP đã được sử dụng (is_used = true)
     */
    public function isEmailChangeVerified(int $userId, string $email): bool
    {
        try {
            $verifiedOtp = EmailOtp::where('user_id', $userId)
                ->where('email', $email)
                ->where('type', 'email_change')
                ->where('is_used', true) // OTP đã được sử dụng
                ->whereNotNull('verified_at') // Đã được verify
                ->where('verified_at', '>=', now()->subMinutes(10)) // Verified trong vòng 2 phút gần đây
                ->orderBy('verified_at', 'desc')
                ->first(); // Tìm OTP đã verify gần nhất → Kiểm tra email change đã được verify chưa
            
            return $verifiedOtp !== null; // Trả về true/false → Email change đã/chưa được verify
            
        } catch (Exception $e) {
            Log::error('Failed to check email change verification status', [
                'user_id' => $userId,
                'email' => $email,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Dùng để debug
            
            return false; // Trả về false → Có lỗi, coi như chưa verify
        }
    }
}
