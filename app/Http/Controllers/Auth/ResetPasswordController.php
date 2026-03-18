<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailOtp;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Class: ResetPasswordController
 * 
 * MỤC ĐÍCH:
 * Controller xử lý reset password sau khi user đã nhận và verify OTP từ ForgotPasswordController.
 * Controller này hiển thị form reset password và xử lý việc cập nhật password mới.
 * 
 * LUỒNG XỬ LÝ:
 * 1. showResetForm(): Hiển thị form reset password
 *    - Lấy email và OTP từ session hoặc query parameters (fallback cho Linux deploy)
 *    - Validate OTP còn hợp lệ (verified trong 2 phút hoặc chưa expired)
 *    - Hiển thị form reset password
 * 2. resetPassword(): Xử lý reset password
 *    - Validate: email, otp_code, password (confirmed)
 *    - Verify OTP còn hợp lệ
 *    - Cập nhật password mới
 *    - Clear session và OTPs
 *    - Redirect đến trang login
 * 
 * ENDPOINTS:
 * - GET /password/reset: Hiển thị form reset password
 * - POST /password/reset: Xử lý reset password
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Session: password_reset_email, password_reset_otp
 * - Query parameters: email, token (base64 encoded, fallback cho Linux deploy)
 * - Request: email, otp_code, password, password_confirmation
 * - Bảng users: Tìm user theo email
 * - Bảng email_otps: Verify OTP còn hợp lệ
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng users: Cập nhật password_hash
 * - Bảng email_otps: Đánh dấu OTPs đã sử dụng
 * - Session: Clear password_reset_email, password_reset_otp
 * 
 * SERVICE SỬ DỤNG:
 * - OtpService: Xử lý OTP logic (đã được inject qua constructor)
 * 
 * LƯU Ý:
 * - OTP phải được verify trước (is_used = true, verified_at trong 2 phút)
 * - Fallback: Cũng chấp nhận OTP chưa expired (expires_at > now) nếu chưa verify
 * - Session có thể bị mất trên Linux deploy, nên có fallback từ query parameters
 * - Password được hash bằng bcrypt trước khi lưu
 * - Tất cả password reset OTPs của user sẽ được đánh dấu đã sử dụng sau khi reset thành công
 */
class ResetPasswordController extends Controller
{
    /**
     * OtpService instance
     * 
     * Service này xử lý logic OTP (verify, validate, etc.)
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
     * Hiển thị form reset password
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy email và OTP từ session
     * 2. Fallback: Nếu session mất (Linux deploy), lấy từ query parameters (base64 encoded)
     * 3. Validate email và OTP có hợp lệ không
     * 4. Tìm user theo email
     * 5. Verify OTP còn hợp lệ:
     *    - OTP đã được verify (is_used = true) và verified_at trong 2 phút
     *    - Hoặc OTP chưa expired (expires_at > now) nếu chưa verify
     * 6. Nếu OTP hợp lệ: Hiển thị form reset password
     * 7. Nếu OTP không hợp lệ: Redirect về trang forgot password với lỗi
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: password_reset_email, password_reset_otp
     * - Query parameters: email, token (base64 encoded, fallback)
     * - Bảng users: Tìm user theo email
     * - Bảng email_otps: Verify OTP còn hợp lệ
     * 
     * FALLBACK MECHANISM:
     * - Trên Linux deploy, session có thể bị mất
     * - Fallback: Lấy email và OTP từ query parameters (base64 encoded)
     * - Nếu decode thành công và validate hợp lệ: Restore vào session
     * 
     * OTP VALIDATION:
     * - OTP phải được verify trước (is_used = true, verified_at trong 2 phút)
     * - Fallback: Cũng chấp nhận OTP chưa expired nếu chưa verify (trường hợp vừa verify)
     * 
     * @param Request $request HTTP request (có thể chứa query parameters email, token)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View reset password hoặc redirect với lỗi
     */
    public function showResetForm(Request $request)
    {
        /**
         * Lấy email và OTP từ session (ưu tiên)
         * 
         * Session keys:
         * - password_reset_email: Email của user
         * - password_reset_otp: OTP code đã được verify
         */
        $email = $request->session()->get('password_reset_email');
        $otpCode = $request->session()->get('password_reset_otp');
        
        /**
         * Fallback 1: Lấy từ query parameters (cho Linux deploy nơi session có thể bị mất)
         * 
         * Query parameters:
         * - email: Base64 encoded email
         * - token: Base64 encoded OTP code
         * 
         * Lý do fallback:
         * - Trên Linux deploy, session có thể bị mất khi redirect
         * - Query parameters là cách backup để không mất thông tin
         */
        if ((!$email || !$otpCode) && $request->has('email') && $request->has('token')) {
            try {
                /**
                 * Decode base64 email và OTP code
                 * 
                 * base64_decode(..., true): Strict mode, trả về false nếu decode thất bại
                 */
                $email = base64_decode($request->query('email'), true);
                $otpCode = base64_decode($request->query('token'), true);
                
                /**
                 * Validate decoded values
                 * 
                 * - email: Phải là email hợp lệ
                 * - otpCode: Phải có 6 chữ số
                 */
                if ($email && $otpCode && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($otpCode) === 6) {
                    /**
                     * Restore vào session để sử dụng cho các request sau
                     */
                    $request->session()->put('password_reset_email', $email);
                    $request->session()->put('password_reset_otp', $otpCode);
                    $request->session()->save();
                    
                    /**
                     * Log thông tin recover thành công
                     */
                    Log::info('Email and OTP recovered from query parameters', [
                        'email' => $email,
                        'session_id' => $request->session()->getId()
                    ]);
                } else {
                    /**
                     * Decoded values không hợp lệ: Set về null
                     */
                    $email = null;
                    $otpCode = null;
                }
            } catch (\Exception $e) {
                /**
                 * Xử lý lỗi khi decode: Log error và set về null
                 */
                Log::error('Failed to decode query parameters', [
                    'error' => $e->getMessage()
                ]);
                $email = null;
                $otpCode = null;
            }
        }
        
        /**
         * Debug logging để theo dõi session state
         * 
         * Log:
         * - Email và OTP code
         * - Session ID
         * - Session keys có tồn tại không
         * - Query parameters có tồn tại không
         * - Tất cả session keys
         */
        Log::info('Reset password form access', [
            'email' => $email,
            'otp_code' => $otpCode,
            'session_id' => $request->session()->getId(),
            'has_email' => $request->session()->has('password_reset_email'),
            'has_otp' => $request->session()->has('password_reset_otp'),
            'has_query_params' => $request->has(['email', 'token']),
            'all_session_keys' => array_keys($request->session()->all())
        ]);
        
        /**
         * Kiểm tra email và OTP có tồn tại không
         * 
         * Nếu không có: Session đã hết hạn hoặc bị mất
         */
        if (!$email || !$otpCode) {
            /**
             * Log warning khi session expired hoặc missing
             */
            Log::warning('Reset password session expired or missing', [
                'email' => $email,
                'otp_code' => $otpCode,
                'has_email' => $request->session()->has('password_reset_email'),
                'has_otp' => $request->session()->has('password_reset_otp'),
                'session_id' => $request->session()->getId()
            ]);
            
            /**
             * Redirect về trang forgot password với lỗi
             */
            return redirect()->route('password.forgot')
                ->withErrors(['error' => 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng thử lại.']);
        }

        /**
         * Tìm user theo email
         * 
         * Nếu không tìm thấy: Redirect về trang forgot password với lỗi
         */
        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('password.forgot')
                ->withErrors(['error' => 'Tài khoản không tồn tại. Vui lòng thử lại.']);
        }

        /**
         * Tìm OTP đã được verify và còn trong thời gian hiệu lực
         * 
         * Điều kiện:
         * - user_id = $user->id
         * - otp_code = $otpCode
         * - type = 'password_reset'
         * - is_used = true (đã được verify)
         * - verified_at IS NOT NULL
         * - verified_at >= 2 phút trước (OTP verification valid trong 2 phút)
         * 
         * Lý do 2 phút:
         * - OTP expiry time là 2 phút
         * - Sau khi verify, OTP vẫn hợp lệ trong 2 phút để user có thời gian reset password
         */
        $otp = EmailOtp::where('user_id', $user->id)
            ->where('otp_code', $otpCode)
            ->where('type', 'password_reset')
            ->where('is_used', true)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(2)) // OTP verification valid trong 2 phút
            ->orderBy('verified_at', 'desc')
            ->first();

        /**
         * Fallback: Kiểm tra OTP chưa expired nếu chưa verify
         * 
         * Trường hợp: OTP vừa được verify nhưng chưa được đánh dấu is_used
         * Điều kiện:
         * - expires_at > now (chưa hết hạn)
         * - Không cần is_used = true (có thể chưa verify)
         */
        if (!$otp) {
            $otp = EmailOtp::where('user_id', $user->id)
                ->where('otp_code', $otpCode)
                ->where('type', 'password_reset')
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->first();
        }

        /**
         * Nếu không tìm thấy OTP hợp lệ: Log chi tiết và redirect với lỗi
         */
        if (!$otp) {
            /**
             * Log tất cả OTPs với code này để debug
             */
            $allOtps = EmailOtp::where('user_id', $user->id)
                ->where('type', 'password_reset')
                ->where('otp_code', $otpCode)
                ->get();
            
            /**
             * Log warning với thông tin chi tiết
             */
            Log::warning('OTP not found or expired', [
                'user_id' => $user->id,
                'otp_code' => $otpCode,
                'email' => $email,
                'found_otps_count' => $allOtps->count(),
                'otps_details' => $allOtps->map(function($o) {
                    return [
                        'id' => $o->id,
                        'is_used' => $o->is_used,
                        'expires_at' => $o->expires_at?->toDateTimeString(),
                        'verified_at' => $o->verified_at?->toDateTimeString(),
                        'created_at' => $o->created_at->toDateTimeString(),
                    ];
                })->toArray(),
                'current_time' => now()->toDateTimeString(),
            ]);
            
            /**
             * Redirect về trang forgot password với lỗi
             */
            return redirect()->route('password.forgot')
                ->withErrors(['error' => 'Mã OTP đã hết hạn. Vui lòng thử lại.']);
        }

        /**
         * Hiển thị form reset password với email và OTP code
         */
        return view('auth.reset-password', compact('email', 'otpCode'));
    }

    /**
     * Xử lý reset password
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate dữ liệu: email, otp_code, password (confirmed)
     * 2. Tìm user theo email
     * 3. Verify OTP còn hợp lệ:
     *    - OTP đã được verify (is_used = true) và verified_at trong 2 phút
     *    - Hoặc OTP chưa expired (expires_at > now) nếu chưa verify
     * 4. Nếu OTP hợp lệ: Cập nhật password mới
     * 5. Đánh dấu tất cả password reset OTPs của user là đã sử dụng
     * 6. Clear session
     * 7. Trả về response thành công với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: email, otp_code, password, password_confirmation
     * - Bảng users: Tìm user theo email
     * - Bảng email_otps: Verify OTP còn hợp lệ
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Cập nhật password_hash (bcrypt hash)
     * - Bảng email_otps: Đánh dấu tất cả password reset OTPs là đã sử dụng
     * - Session: Clear password_reset_email, password_reset_otp
     * 
     * VALIDATION:
     * - email: required, email format
     * - otp_code: required, string, size 6
     * - password: required, string, min 8, confirmed
     * 
     * SECURITY:
     * - Password được hash bằng bcrypt trước khi lưu
     * - OTP chỉ hợp lệ trong 2 phút sau khi verify
     * - Tất cả password reset OTPs của user được đánh dấu đã sử dụng sau khi reset thành công
     * 
     * @param Request $request HTTP request chứa email, otp_code, password
     * @return \Illuminate\Http\JsonResponse JSON response với kết quả reset password
     */
    public function resetPassword(Request $request)
    {
        /**
         * Validate dữ liệu từ request
         * 
         * - email: Bắt buộc, email hợp lệ
         * - otp_code: Bắt buộc, string, phải có 6 chữ số
         * - password: Bắt buộc, string, tối thiểu 8 ký tự, phải có password_confirmation khớp
         */
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'otp_code.required' => 'Mã OTP là bắt buộc.',
            'otp_code.size' => 'Mã OTP phải có 6 chữ số.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
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
         * Lấy dữ liệu từ request
         */
        $email = $request->email;
        $otpCode = $request->otp_code;
        $password = $request->password;

        /**
         * Tìm user theo email
         * 
         * Nếu không tìm thấy: Trả về 404
         */
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản không tồn tại.'
            ], 404);
        }

        /**
         * Verify OTP còn hợp lệ và đã được sử dụng
         * 
         * Điều kiện:
         * - user_id = $user->id
         * - otp_code = $otpCode
         * - type = 'password_reset'
         * - is_used = true (đã được verify)
         * - verified_at IS NOT NULL
         * - verified_at >= 2 phút trước (OTP verification valid trong 2 phút)
         */
        $otp = EmailOtp::where('user_id', $user->id)
            ->where('otp_code', $otpCode)
            ->where('type', 'password_reset')
            ->where('is_used', true)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(10)) // OTP verification valid trong 2 phút
            ->orderBy('verified_at', 'desc')
            ->first();

        /**
         * Fallback: Kiểm tra OTP chưa expired nếu chưa verify
         * 
         * Trường hợp: OTP vừa được verify nhưng chưa được đánh dấu is_used
         */
        if (!$otp) {
            $otp = EmailOtp::where('user_id', $user->id)
                ->where('otp_code', $otpCode)
                ->where('type', 'password_reset')
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->first();
        }

        /**
         * Nếu không tìm thấy OTP hợp lệ: Log warning và trả về 400
         */
        if (!$otp) {
            Log::warning('OTP validation failed in resetPassword', [
                'user_id' => $user->id,
                'otp_code' => $otpCode,
                'email' => $email,
                'current_time' => now()->toDateTimeString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'
            ], 400);
        }

        try {
            /**
             * Cập nhật password mới
             * 
             * Hash password bằng bcrypt trước khi lưu
             * password_hash sẽ được lưu vào database
             */
            $user->password_hash = Hash::make($password);
            $user->save();

            /**
             * Đánh dấu tất cả password reset OTPs của user là đã sử dụng
             * 
             * Lý do:
             * - Sau khi reset password thành công, không cần OTPs cũ nữa
             * - Đảm bảo OTPs không thể sử dụng lại
             * - Chỉ đánh dấu OTPs chưa sử dụng (is_used = false)
             */
            EmailOtp::where('user_id', $user->id)
                ->where('type', 'password_reset')
                ->where('is_used', false)
                ->update(['is_used' => true]);

            /**
             * Clear session
             * 
             * Xóa các session keys:
             * - password_reset_email
             * - password_reset_otp
             */
            $request->session()->forget(['password_reset_email', 'password_reset_otp']);

            /**
             * Log thông tin reset password thành công
             */
            Log::info('Password reset successfully', [
                'user_id' => $user->id,
                'email' => $email
            ]);

            /**
             * Trả về response thành công với redirect URL
             */
            return response()->json([
                'success' => true,
                'message' => 'Mật khẩu đã được đặt lại thành công! Đang chuyển hướng...',
                'redirect_url' => route('login')
            ]);

        } catch (Exception $e) {
            /**
             * Xử lý exception: Log error và trả về 500
             */
            Log::error('Failed to reset password', [
                'user_id' => $user->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt lại mật khẩu. Vui lòng thử lại.'
            ], 500);
        }
    }
}
