<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailOtp;
use App\Services\OtpService;
use App\Mail\OtpVerificationMail;
use App\Support\MailHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Class: ForgotPasswordController
 * 
 * MỤC ĐÍCH:
 * Controller xử lý quên mật khẩu, gửi OTP qua email và verify OTP.
 * Controller này là bước đầu tiên trong flow reset password (trước ResetPasswordController).
 * 
 * LUỒNG XỬ LÝ:
 * 1. showForgotPasswordForm(): Hiển thị form quên mật khẩu
 * 2. sendResetOtp(): Gửi OTP cho password reset
 *    - Validate email
 *    - Kiểm tra user tồn tại và active
 *    - Rate limiting (chỉ cho phép gửi lại sau 2 phút)
 *    - Tạo OTP và gửi email
 *    - Lưu email vào session
 * 3. showOtpForm(): Hiển thị form nhập OTP
 * 4. verifyOtp(): Xác thực OTP
 *    - Validate OTP code
 *    - Lấy email từ session hoặc fallback từ OTP record
 *    - Verify OTP qua OtpService
 *    - Lưu email và OTP vào session
 *    - Redirect đến trang reset password
 * 5. resendOtp(): Gửi lại OTP
 *    - Rate limiting (chỉ cho phép gửi lại sau 2 phút)
 *    - Tạo OTP mới và gửi email
 * 6. getOtpStatus(): Lấy trạng thái OTP (còn hiệu lực không)
 * 
 * ENDPOINTS:
 * - GET /password/forgot: Hiển thị form quên mật khẩu
 * - POST /password/forgot: Gửi OTP
 * - GET /password/forgot-otp: Hiển thị form nhập OTP
 * - POST /password/forgot-otp: Verify OTP
 * - POST /password/forgot/resend: Gửi lại OTP
 * - GET /password/forgot/status: Lấy trạng thái OTP
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Request: email, otp_code
 * - Session: password_reset_email
 * - Bảng users: Kiểm tra user tồn tại, active
 * - Bảng email_otps: Tạo và verify OTP
 * - Bảng organization_users: Lấy tên organization để hiển thị trong email
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng email_otps: Tạo OTP mới
 * - Session: password_reset_email, password_reset_otp (sau khi verify)
 * - Email: Gửi OTP code qua email
 * 
 * SERVICE SỬ DỤNG:
 * - OtpService: Xử lý OTP logic (create, verify, get status)
 * - OtpVerificationMail: Email template chứa OTP code
 * 
 * RATE LIMITING:
 * - Chỉ cho phép gửi OTP mới sau 2 phút kể từ lần gửi trước
 * - Trả về 429 Too Many Requests nếu chưa đủ 2 phút
 * - Tính toán thời gian còn lại và trả về cho frontend
 * 
 * FALLBACK MECHANISM:
 * - Session có thể bị mất trên Linux deploy
 * - Fallback: Lấy email từ request hoặc từ OTP record
 * - Restore email vào session để sử dụng cho các request sau
 * 
 * LƯU Ý:
 * - OTP expiry time: 2 phút
 * - Rate limiting: 2 phút giữa các lần gửi
 * - Email được gửi với organization name (nếu có)
 * - Tất cả errors đều được log để debug
 */
class ForgotPasswordController extends Controller
{
    /**
     * OtpService instance
     * 
     * Service này xử lý logic OTP (create, verify, get status)
     */
    protected $otpService;

    /**
     * Constructor: Inject OtpService
     * 
     * @param OtpService $otpService Service xử lý OTP
     */
    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Hiển thị form quên mật khẩu
     * 
     * LUỒNG XỬ LÝ:
     * 1. Hiển thị form quên mật khẩu
     * 
     * @return \Illuminate\View\View View form quên mật khẩu
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Gửi OTP cho password reset
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate email
     * 2. Kiểm tra user tồn tại
     * 3. Kiểm tra user active (status = 1)
     * 4. Kiểm tra rate limiting (chỉ cho phép gửi lại sau 2 phút)
     * 5. Tạo OTP record (2 phút expiry)
     * 6. Lấy tên organization (nếu có) để hiển thị trong email
     * 7. Gửi email chứa OTP code
     * 8. Lưu email vào session
     * 9. Trả về response thành công với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: email
     * - Bảng users: Kiểm tra user tồn tại, active
     * - Bảng email_otps: Kiểm tra rate limiting
     * - Bảng organization_users: Lấy tên organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng email_otps: Tạo OTP mới
     * - Session: password_reset_email
     * - Email: Gửi OTP code qua email
     * 
     * VALIDATION:
     * - email: required, email format, max 255
     * 
     * RATE LIMITING:
     * - Chỉ cho phép gửi OTP mới sau 2 phút kể từ lần gửi trước
     * - Trả về 429 nếu chưa đủ 2 phút
     * - Tính toán thời gian còn lại và trả về cho frontend
     * 
     * ERROR HANDLING:
     * - Transport errors (SMTP): Xử lý riêng với error message cụ thể
     * - General errors: Log và trả về error message chung
     * 
     * @param Request $request HTTP request chứa email
     * @return \Illuminate\Http\JsonResponse JSON response với kết quả gửi OTP
     */
    public function sendResetOtp(Request $request)
    {
        /**
         * Validate email từ request
         * 
         * - email: Bắt buộc, email hợp lệ, tối đa 255 ký tự
         */
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
        ]);

        /**
         * Nếu validation thất bại: Trả về 422 với errors
         */
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        /**
         * Lấy email từ request
         */
        $email = $request->email;

        /**
         * Kiểm tra user tồn tại
         * 
         * Nếu không tìm thấy: Trả về 404
         */
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email này chưa được đăng ký trong hệ thống.'
            ], 404);
        }

        /**
         * Kiểm tra user active (status = 1)
         * 
         * Nếu user bị khóa: Trả về 403
         */
        if (!$user->status) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị tạm dừng. Vui lòng liên hệ quản trị viên.'
            ], 403);
        }

        /**
         * Kiểm tra rate limiting: Chỉ cho phép gửi lại sau 2 phút
         * 
         * Tìm OTP được tạo trong 2 phút gần đây
         * Nếu có: Tính toán thời gian còn lại và trả về 429
         */
        $recentOtp = EmailOtp::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->where('created_at', '>', now()->subMinutes(2))
            ->first();
        
        /**
         * Nếu có OTP gần đây: Tính toán thời gian còn lại và trả về 429
         */
        if ($recentOtp) {
            /**
             * Tính toán thời gian còn lại
             * 
             * - elapsedSeconds: Số giây đã trôi qua kể từ khi tạo OTP
             * - remainingSeconds: Số giây còn lại (120 - elapsedSeconds)
             * - remainingMinutes: Số phút còn lại (làm tròn lên, tối thiểu 1 phút)
             */
            $elapsedSeconds = (float) $recentOtp->created_at->diffInSeconds(now(), false);
            $remainingSeconds = max(0, (int) floor(120 - $elapsedSeconds)); // 2 phút = 120 giây, số nguyên
            $remainingMinutes = max(1, ceil($remainingSeconds / 60));
            
            /**
             * Trả về 429 Too Many Requests với thông tin rate limit
             */
            return response()->json([
                'success' => false,
                'message' => "Vui lòng đợi {$remainingMinutes} phút trước khi yêu cầu mã OTP mới.",
                'rate_limit' => true,
                'remaining_seconds' => $remainingSeconds
            ], 429);
        }

        try {
            /**
             * Tạo OTP record cho password reset (2 phút expiry)
             * 
             * EmailOtp::createForPasswordReset() sẽ:
             * - Tạo OTP code ngẫu nhiên (6 chữ số)
             * - Lưu vào bảng email_otps với type = 'password_reset'
             * - Set expires_at = now() + 2 phút
             * - Trả về OTP record
             */
            $otp = EmailOtp::createForPasswordReset($user->id, $email, 10);
            
            /**
             * Lấy tên organization để hiển thị trong email
             * 
             * - Tìm organization_user active của user
             * - Lấy tên organization
             * - Mặc định: 'ZoroRMS Team' nếu không có
             */
            $organizationName = 'ZoroRMS Team';
            $organizationIdForMail = null;
            try {
                $organizationUser = \App\Models\OrganizationUser::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->first();
                if ($organizationUser && $organizationUser->organization) {
                    $organizationName = $organizationUser->organization->name ?? 'ZoroRMS Team';
                    $organizationIdForMail = $organizationUser->organization_id;
                }
            } catch (\Exception $e) {
                /**
                 * Nếu có lỗi: Sử dụng tên mặc định
                 */
            }

            MailHelper::sendWithOptionalOrgMail(
                new OtpVerificationMail(
                    $otp->otp_code,
                    $user->full_name ?? 'User',
                    10,
                    'password_reset',
                    $organizationName
                ),
                $email,
                $organizationIdForMail
            );
            
            /**
             * Lưu email vào session cho bước tiếp theo
             * 
             * Session key: password_reset_email
             * Session này sẽ được sử dụng ở trang verify OTP và reset password
             */
            $request->session()->put('password_reset_email', $email);
            /**
             * Đảm bảo session được lưu ngay lập tức
             */
            $request->session()->save();
            
            /**
             * Log thông tin gửi OTP thành công
             */
            Log::info('Password reset OTP sent successfully', [
                'user_id' => $user->id,
                'email' => $email,
                'otp_id' => $otp->id,
                'session_id' => $request->session()->getId(),
                'session_has_email' => $request->session()->has('password_reset_email')
            ]);
            
            /**
             * Trả về response thành công với redirect URL
             */
            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.',
                'redirect_url' => route('password.forgot-otp')
            ]);
            
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $transportException) {
            /**
             * Xử lý lỗi SMTP/Transport
             * 
             * TransportExceptionInterface: Lỗi liên quan đến kết nối email server
             * Các lỗi phổ biến:
             * - Authentication failed: Lỗi xác thực email
             * - Connection timeout: Không thể kết nối đến email server
             * - Gmail 530/5.7.0: Lỗi xác thực Gmail App Password
             */
            Log::error('Failed to send password reset OTP - Transport Error', [
                'user_id' => $user->id,
                'email' => $email,
                'error' => $transportException->getMessage(),
                'code' => $transportException->getCode(),
                'trace' => $transportException->getTraceAsString()
            ]);
            
            /**
             * Xác định error message cụ thể dựa trên error type
             */
            $errorMessage = $transportException->getMessage();
            $userMessage = 'Không thể gửi mã OTP. Vui lòng thử lại sau.';
            
            /**
             * Cung cấp error message hữu ích cho các lỗi phổ biến
             */
            if (str_contains($errorMessage, 'Authentication') || str_contains($errorMessage, 'BadCredentials')) {
                $userMessage = 'Lỗi xác thực email. Vui lòng kiểm tra cấu hình email trong hệ thống.';
            } elseif (str_contains($errorMessage, 'Connection') || str_contains($errorMessage, 'timeout')) {
                $userMessage = 'Không thể kết nối đến máy chủ email. Vui lòng thử lại sau.';
            } elseif (str_contains($errorMessage, '530') || str_contains($errorMessage, '5.7.0')) {
                $userMessage = 'Lỗi xác thực Gmail. Vui lòng kiểm tra App Password đã được cấu hình đúng chưa.';
            }
            
            /**
             * Trả về 500 với error message cụ thể
             */
            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'error_type' => 'transport_error'
            ], 500);
            
        } catch (Exception $e) {
            /**
             * Xử lý lỗi chung
             * 
             * Log error và trả về 500 với error message chung
             */
            Log::error('Failed to send password reset OTP', [
                'user_id' => $user->id,
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi mã OTP. Vui lòng thử lại sau.',
                'error_type' => 'general_error'
            ], 500);
        }
    }

    /**
     * Hiển thị form nhập OTP
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy email từ session
     * 2. Nếu không có email: Redirect về trang forgot password với lỗi
     * 3. Nếu có email: Hiển thị form nhập OTP
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: password_reset_email
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View form OTP hoặc redirect với lỗi
     */
    public function showOtpForm(Request $request)
    {
        /**
         * Lấy email từ session
         */
        $email = $request->session()->get('password_reset_email');
        
        /**
         * Nếu không có email: Session đã hết hạn hoặc bị mất
         * Redirect về trang forgot password với lỗi
         */
        if (!$email) {
            return redirect()->route('password.forgot')
                ->withErrors(['error' => 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng thử lại.']);
        }

        /**
         * Hiển thị form nhập OTP với email
         */
        return view('auth.forgot-password-otp', compact('email'));
    }

    /**
     * Xác thực OTP cho password reset
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate OTP code (6 chữ số)
     * 2. Lấy email từ session (ưu tiên)
     * 3. Fallback 1: Lấy email từ request (nếu session mất)
     * 4. Fallback 2: Lấy email từ OTP record (nếu session và request đều không có)
     * 5. Tìm user theo email
     * 6. Verify OTP qua OtpService
     * 7. Nếu verify thành công:
     *    - Lưu email và OTP vào session
     *    - Tạo redirect URL với query parameters (fallback cho Linux deploy)
     *    - Trả về response thành công với redirect URL
     * 8. Nếu verify thất bại: Trả về lỗi
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: otp_code, email (optional, fallback)
     * - Session: password_reset_email
     * - Bảng email_otps: Tìm OTP record để lấy email (fallback)
     * - Bảng users: Tìm user theo email
     * 
     * DỮ LIỆU GHI VÀO:
     * - Session: password_reset_email, password_reset_otp (sau khi verify thành công)
     * - Bảng email_otps: Cập nhật is_used = true, verified_at (qua OtpService)
     * 
     * FALLBACK MECHANISM:
     * - Fallback 1: Lấy email từ request (nếu frontend gửi kèm)
     * - Fallback 2: Lấy email từ OTP record (nếu session mất hoàn toàn)
     * - Restore email vào session để sử dụng cho các request sau
     * 
     * VALIDATION:
     * - otp_code: required, string, size 6
     * 
     * @param Request $request HTTP request chứa otp_code, email (optional)
     * @return \Illuminate\Http\JsonResponse JSON response với kết quả verify OTP
     */
    public function verifyOtp(Request $request)
    {
        /**
         * Validate OTP code từ request
         * 
         * - otp_code: Bắt buộc, string, phải có 6 chữ số
         */
        $validator = Validator::make($request->all(), [
            'otp_code' => 'required|string|size:6',
        ], [
            'otp_code.required' => 'Mã OTP là bắt buộc.',
            'otp_code.size' => 'Mã OTP phải có 6 chữ số.',
        ]);

        /**
         * Nếu validation thất bại: Trả về 422 với errors
         */
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        /**
         * Lấy OTP code từ request
         */
        $otpCode = $request->otp_code;
        
        /**
         * Lấy email từ session (ưu tiên)
         * 
         * Session key: password_reset_email
         */
        $email = $request->session()->get('password_reset_email');
        
        /**
         * Fallback 1: Lấy email từ request (nếu frontend gửi kèm)
         * 
         * Trường hợp: Session mất nhưng frontend vẫn gửi email trong request
         * Validate email format và restore vào session
         */
        if (!$email && $request->has('email')) {
            $email = $request->email;
            /**
             * Validate email format
             */
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                /**
                 * Restore email vào session để sử dụng cho các request sau
                 */
                $request->session()->put('password_reset_email', $email);
                $request->session()->save();
                
                /**
                 * Log thông tin recover email từ request
                 */
                Log::info('Email recovered from request', [
                    'email' => $email,
                    'session_id' => $request->session()->getId()
                ]);
            } else {
                /**
                 * Email không hợp lệ: Set về null
                 */
                $email = null;
            }
        }
        
        /**
         * Fallback 2: Lấy email từ OTP record (nếu session mất hoàn toàn)
         * 
         * Trường hợp: Session mất và request không có email
         * Tìm OTP record theo code và lấy email từ đó
         * 
         * Điều kiện tìm OTP:
         * - otp_code = $otpCode
         * - type = 'password_reset'
         * - is_used = false (chưa sử dụng)
         * - expires_at > now() (chưa hết hạn)
         */
        if (!$email) {
            /**
             * Log warning khi session email không tìm thấy
             */
            Log::warning('Session email not found, attempting fallback from OTP record', [
                'otp_code' => $otpCode,
                'session_id' => $request->session()->getId()
            ]);
            
            /**
             * Tìm OTP record theo code và type
             */
            $otpRecord = EmailOtp::where('otp_code', $otpCode)
                ->where('type', 'password_reset')
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->first();
            
            /**
             * Nếu tìm thấy OTP record: Lấy email và restore vào session
             */
            if ($otpRecord) {
                $email = $otpRecord->email;
                /**
                 * Restore email vào session để sử dụng cho các request sau
                 */
                $request->session()->put('password_reset_email', $email);
                $request->session()->save();
                
                /**
                 * Log thông tin recover email từ OTP record
                 */
                Log::info('Email recovered from OTP record', [
                    'email' => $email,
                    'otp_id' => $otpRecord->id,
                    'user_id' => $otpRecord->user_id
                ]);
            }
        }
        
        /**
         * Nếu vẫn không tìm thấy email: Log error và trả về 400
         */
        if (!$email) {
            Log::error('Cannot find email for OTP verification', [
                'otp_code' => $otpCode,
                'session_id' => $request->session()->getId(),
                'has_session_email' => $request->session()->has('password_reset_email')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng thử lại.'
            ], 400);
        }

        /**
         * Tìm user theo email
         * 
         * Nếu không tìm thấy: Log error và trả về 400
         */
        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::error('User not found for email', ['email' => $email]);
            
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản không tồn tại. Vui lòng thử lại.'
            ], 400);
        }

        /**
         * Verify OTP qua OtpService
         * 
         * OtpService->verifyOtp() sẽ:
         * - Tìm OTP record với user_id, otp_code, type
         * - Kiểm tra OTP chưa expired (expires_at > now)
         * - Kiểm tra OTP chưa sử dụng (is_used = false)
         * - Cập nhật is_used = true, verified_at = now()
         * - Trả về ['success' => bool, 'message' => string]
         */
        $result = $this->otpService->verifyOtp($user->id, $otpCode, 'password_reset');

        /**
         * Nếu verify thành công: Lưu vào session và tạo redirect URL
         */
        if ($result['success']) {
            /**
             * Đảm bảo email có trong session
             * 
             * Nếu chưa có: Lưu email vào session
             */
            if (!$request->session()->has('password_reset_email')) {
                $request->session()->put('password_reset_email', $email);
            }
            
            /**
             * Lưu OTP code vào session cho bước reset password
             * 
             * Session key: password_reset_otp
             * OTP này sẽ được sử dụng ở trang reset password để verify lại
             */
            $request->session()->put('password_reset_otp', $otpCode);
            
            /**
             * Đảm bảo session được lưu trước khi trả về response
             */
            $request->session()->save();
            
            /**
             * Tạo redirect URL với query parameters (fallback cho Linux deploy)
             * 
             * Query parameters:
             * - email: Base64 encoded email
             * - token: Base64 encoded OTP code
             * 
             * Lý do: Trên Linux deploy, session có thể bị mất khi redirect
             * Query parameters là cách backup để không mất thông tin
             */
            $redirectUrl = route('password.reset', [
                'email' => base64_encode($email),
                'token' => base64_encode($otpCode)
            ]);
            
            /**
             * Debug logging để theo dõi session state
             */
            Log::info('OTP verification successful, storing in session', [
                'email' => $email,
                'otp_code' => $otpCode,
                'session_id' => $request->session()->getId(),
                'redirect_url' => $redirectUrl,
                'session_has_otp' => $request->session()->has('password_reset_otp'),
                'session_has_email' => $request->session()->has('password_reset_email')
            ]);
            
            /**
             * Trả về response thành công với redirect URL
             */
            return response()->json([
                'success' => true,
                'message' => 'Xác thực OTP thành công! Đang chuyển hướng...',
                'redirect_url' => $redirectUrl
            ]);
        } else {
            /**
             * Nếu verify thất bại: Log warning và trả về lỗi
             */
            Log::warning('OTP verification failed', [
                'email' => $email,
                'user_id' => $user->id,
                'otp_code' => $otpCode,
                'message' => $result['message']
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Gửi lại OTP cho password reset
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy email từ session
     * 2. Kiểm tra email có tồn tại không
     * 3. Tìm user theo email
     * 4. Kiểm tra rate limiting (chỉ cho phép gửi lại sau 2 phút)
     * 5. Tạo OTP mới (2 phút expiry)
     * 6. Lấy tên organization (nếu có)
     * 7. Gửi email chứa OTP code mới
     * 8. Trả về response thành công
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: password_reset_email
     * - Bảng users: Tìm user theo email
     * - Bảng email_otps: Kiểm tra rate limiting
     * - Bảng organization_users: Lấy tên organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng email_otps: Tạo OTP mới
     * - Email: Gửi OTP code mới qua email
     * 
     * RATE LIMITING:
     * - Chỉ cho phép gửi lại sau 2 phút kể từ lần gửi trước
     * - Trả về 429 nếu chưa đủ 2 phút
     * 
     * ERROR HANDLING:
     * - Transport errors (SMTP): Xử lý riêng với error message cụ thể
     * - General errors: Log và trả về error message chung
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response với kết quả gửi lại OTP
     */
    public function resendOtp(Request $request)
    {
        /**
         * Lấy email từ session
         * 
         * Nếu không có: Session đã hết hạn, trả về 400
         */
        $email = $request->session()->get('password_reset_email');
        
        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng thử lại.'
            ], 400);
        }

        /**
         * Tìm user theo email
         * 
         * Nếu không tìm thấy: Trả về 400
         */
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản không tồn tại. Vui lòng thử lại.'
            ], 400);
        }

        /**
         * Kiểm tra rate limiting: Chỉ cho phép gửi lại sau 2 phút
         * 
         * Tìm OTP được tạo trong 2 phút gần đây
         * Nếu có: Tính toán thời gian còn lại và trả về 429
         */
        $recentOtp = EmailOtp::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->where('created_at', '>', now()->subMinutes(2))
            ->first();
        
        /**
         * Nếu có OTP gần đây: Tính toán thời gian còn lại và trả về 429
         */
        if ($recentOtp) {
            /**
             * Tính toán thời gian còn lại
             * 
             * - elapsedSeconds: Số giây đã trôi qua kể từ khi tạo OTP
             * - remainingSeconds: Số giây còn lại (120 - elapsedSeconds)
             * - remainingMinutes: Số phút còn lại (làm tròn lên, tối thiểu 1 phút)
             */
            $elapsedSeconds = (float) $recentOtp->created_at->diffInSeconds(now(), false);
            $remainingSeconds = max(0, (int) floor(120 - $elapsedSeconds)); // 2 phút = 120 giây, số nguyên
            $remainingMinutes = max(1, ceil($remainingSeconds / 60));
            
            /**
             * Trả về 429 Too Many Requests với thông tin rate limit
             */
            return response()->json([
                'success' => false,
                'message' => "Vui lòng đợi {$remainingMinutes} phút trước khi gửi lại mã OTP.",
                'rate_limit' => true,
                'remaining_seconds' => $remainingSeconds
            ], 429);
        }

        try {
            /**
             * Tạo OTP record mới cho password reset (2 phút expiry)
             * 
             * EmailOtp::createForPasswordReset() sẽ:
             * - Tạo OTP code ngẫu nhiên mới (6 chữ số)
             * - Lưu vào bảng email_otps với type = 'password_reset'
             * - Set expires_at = now() + 2 phút
             * - Trả về OTP record mới
             */
            $otp = EmailOtp::createForPasswordReset($user->id, $email, 2);
            
            /**
             * Lấy tên organization để hiển thị trong email
             * 
             * - Tìm organization_user active của user
             * - Lấy tên organization
             * - Mặc định: 'ZoroRMS Team' nếu không có
             */
            $organizationName = 'ZoroRMS Team';
            $organizationIdForMail = null;
            try {
                $organizationUser = \App\Models\OrganizationUser::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->first();
                if ($organizationUser && $organizationUser->organization) {
                    $organizationName = $organizationUser->organization->name ?? 'ZoroRMS Team';
                    $organizationIdForMail = $organizationUser->organization_id;
                }
            } catch (\Exception $e) {
                /**
                 * Nếu có lỗi: Sử dụng tên mặc định
                 */
            }

            MailHelper::sendWithOptionalOrgMail(
                new OtpVerificationMail(
                    $otp->otp_code,
                    $user->full_name ?? 'User',
                    10,
                    'password_reset',
                    $organizationName
                ),
                $email,
                $organizationIdForMail
            );
            
            /**
             * Log thông tin gửi lại OTP thành công
             */
            Log::info('Password reset OTP resent successfully', [
                'user_id' => $user->id,
                'email' => $email,
                'otp_id' => $otp->id
            ]);
            
            /**
             * Trả về response thành công
             */
            return response()->json([
                'success' => true,
                'message' => 'Mã OTP mới đã được gửi đến email của bạn.'
            ]);
            
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $transportException) {
            /**
             * Xử lý lỗi SMTP/Transport
             * 
             * TransportExceptionInterface: Lỗi liên quan đến kết nối email server
             * Xử lý tương tự như sendResetOtp()
             */
            Log::error('Failed to resend password reset OTP - Transport Error', [
                'user_id' => $user->id,
                'email' => $email,
                'error' => $transportException->getMessage(),
                'code' => $transportException->getCode(),
                'trace' => $transportException->getTraceAsString()
            ]);
            
            /**
             * Xác định error message cụ thể dựa trên error type
             */
            $errorMessage = $transportException->getMessage();
            $userMessage = 'Không thể gửi lại mã OTP. Vui lòng thử lại sau.';
            
            /**
             * Cung cấp error message hữu ích cho các lỗi phổ biến
             */
            if (str_contains($errorMessage, 'Authentication') || str_contains($errorMessage, 'BadCredentials')) {
                $userMessage = 'Lỗi xác thực email. Vui lòng kiểm tra cấu hình email trong hệ thống.';
            } elseif (str_contains($errorMessage, 'Connection') || str_contains($errorMessage, 'timeout')) {
                $userMessage = 'Không thể kết nối đến máy chủ email. Vui lòng thử lại sau.';
            } elseif (str_contains($errorMessage, '530') || str_contains($errorMessage, '5.7.0')) {
                $userMessage = 'Lỗi xác thực Gmail. Vui lòng kiểm tra App Password đã được cấu hình đúng chưa.';
            }
            
            /**
             * Trả về 500 với error message cụ thể
             */
            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'error_type' => 'transport_error'
            ], 500);
            
        } catch (Exception $e) {
            /**
             * Xử lý lỗi chung
             * 
             * Log error và trả về 500 với error message chung
             */
            Log::error('Failed to resend password reset OTP', [
                'user_id' => $user->id,
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi lại mã OTP. Vui lòng thử lại sau.',
                'error_type' => 'general_error'
            ], 500);
        }
    }

    /**
     * Lấy trạng thái OTP (còn hiệu lực không)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy email từ session
     * 2. Nếu không có email: Trả về has_valid_otp = false
     * 3. Tìm user theo email
     * 4. Nếu không tìm thấy user: Trả về has_valid_otp = false
     * 5. Gọi OtpService->getOtpStatus() để lấy trạng thái OTP
     * 6. Trả về status (has_valid_otp, expires_at, etc.)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: password_reset_email
     * - Bảng users: Tìm user theo email
     * - OtpService: Lấy trạng thái OTP (còn hiệu lực, thời gian hết hạn, etc.)
     * 
     * DỮ LIỆU TRẢ VỀ:
     * - has_valid_otp: boolean (còn OTP hợp lệ không)
     * - expires_at: datetime (thời gian hết hạn)
     * - message: string (thông báo)
     * 
     * MỤC ĐÍCH:
     * - Cho phép frontend kiểm tra OTP còn hiệu lực không
     * - Hiển thị countdown timer cho user
     * - Tự động redirect nếu OTP hết hạn
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response với trạng thái OTP
     */
    public function getOtpStatus(Request $request)
    {
        /**
         * Lấy email từ session
         * 
         * Nếu không có: Session đã hết hạn
         */
        $email = $request->session()->get('password_reset_email');
        
        /**
         * Nếu không có email: Trả về has_valid_otp = false
         */
        if (!$email) {
            return response()->json([
                'has_valid_otp' => false,
                'message' => 'Phiên đặt lại mật khẩu đã hết hạn.'
            ]);
        }

        /**
         * Tìm user theo email
         * 
         * Nếu không tìm thấy: Trả về has_valid_otp = false
         */
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'has_valid_otp' => false,
                'message' => 'Tài khoản không tồn tại.'
            ]);
        }

        /**
         * Lấy trạng thái OTP qua OtpService
         * 
         * OtpService->getOtpStatus() sẽ:
         * - Tìm OTP chưa expired và chưa sử dụng
         * - Trả về has_valid_otp, expires_at, message, etc.
         */
        $status = $this->otpService->getOtpStatus($user->id, 'password_reset');
        
        /**
         * Trả về status
         */
        return response()->json($status);
    }
}
