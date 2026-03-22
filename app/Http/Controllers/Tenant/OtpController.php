<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP for email verification.
     */
    public function sendEmailVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $email = $request->email;

        // Check if email is already verified and matches current user
        if ($user->email === $email) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã được xác thực cho tài khoản của bạn.'
            ], 400);
        }

        // Check if email is already verified via OTP
        if ($this->otpService->isEmailVerified($user->id, $email)) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã được xác thực thành công. Bạn có thể lưu thông tin ngay bây giờ.'
            ], 400);
        }

        $result = $this->otpService->sendEmailVerificationOtp($user, $email);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi mã OTP. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp_code' => 'required|string|size:6',
            'email' => 'required|email',
        ], [
            'otp_code.required' => 'Mã OTP là bắt buộc.',
            'otp_code.size' => 'Mã OTP phải có 6 chữ số.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $otpCode = $request->otp_code;
        $email = $request->email;

        $result = $this->otpService->verifyOtp($user->id, $otpCode, 'email_verification');

        if ($result['success']) {
            // Update user's email if this is from profile verification
            // Get the latest verified OTP for this user
            $otp = \App\Models\EmailOtp::where('user_id', $user->id)
                ->where('otp_code', $otpCode)
                ->where('type', 'email_verification')
                ->where('is_used', true)
                ->where('verified_at', '>=', now()->subMinutes(1)) // Recently verified
                ->first();
                
            if ($otp && $otp->email !== $user->email) {
                // Update user's email
                \App\Models\User::where('id', $user->id)->update(['email' => $otp->email]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Xác thực email thành công! Email đã được cập nhật.',
                'redirect_url' => route('tenant.profile.edit')
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $email = $request->email;

        $result = $this->otpService->resendEmailVerificationOtp($user, $email);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get OTP status.
     */
    public function getOtpStatus()
    {
        $user = Auth::user();
        $status = $this->otpService->getOtpStatus($user->id, 'email_verification');

        return response()->json($status);
    }

    /**
     * Show OTP verification page.
     */
    public function showVerificationPage()
    {
        $user = Auth::user();
        $otpStatus = $this->otpService->getOtpStatus($user->id, 'email_verification');
        
        // Get the email being verified from the latest OTP record
        $latestOtp = \App\Models\EmailOtp::where('user_id', $user->id)
            ->where('type', 'email_verification')
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();
            
        $verificationEmail = $latestOtp ? $latestOtp->email : $user->email;
        
        return view('tenant.profile.otp-verification', compact('user', 'otpStatus', 'verificationEmail'));
    }
    
    /**
     * Check email verification status.
     */
    public function checkEmailVerificationStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $email = $request->email;
        
        $status = $this->otpService->getEmailVerificationStatus($user->id, $email);
        
        return response()->json([
            'success' => true,
            'is_verified' => $status['is_verified'],
            'message' => $status['message'],
            'verified_at' => $status['verified_at'] ?? null
        ]);
    }
}
