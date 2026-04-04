<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PaymentCycle;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Controller: BookingDepositSettingController
 * 
 * MỤC ĐÍCH:
 * Controller quản lý cài đặt cho booking deposits, đặc biệt là thời gian chờ thanh toán (payment_due_hours).
 * Controller này cho phép manager cấu hình thời gian chờ thanh toán cho booking deposits trong organization.
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị trang cài đặt booking deposit
 *    - Kiểm tra capability
 *    - Lấy organization info
 *    - Hiển thị form cài đặt
 * 2. updatePaymentDueHours(): Cập nhật thời gian chờ thanh toán
 *    - Validate input (payment_due_hours: 1-43200 minutes = 1 minute - 30 days)
 *    - Tìm hoặc tạo default PaymentCycle
 *    - Cập nhật payment_due_hours
 *    - Ghi log
 * 
 * ENDPOINTS:
 * - GET /staff/booking-deposit-settings: Hiển thị trang cài đặt
 * - POST /staff/booking-deposit-settings/payment-due-hours: Cập nhật payment_due_hours
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Request: payment_due_hours (integer, 1-43200)
 * - Model: Organization (bảng organizations)
 * - Model: PaymentCycle (bảng payment_cycles)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng payment_cycles: Tạo mới hoặc cập nhật default PaymentCycle
 * - Logs: Ghi log khi cập nhật payment_due_hours
 * 
 * VALIDATION:
 * - payment_due_hours: required, integer, min:1, max:43200 (30 days in minutes)
 * 
 * CAPABILITY CHECKING:
 * - contract.lease.view: Cần có quyền này để truy cập settings
 * 
 * LƯU Ý:
 * - Chỉ manager có thể truy cập settings
 * - payment_due_hours được lưu trong PaymentCycle với is_default = true
 * - Nếu chưa có default PaymentCycle, sẽ tự động tạo mới
 * - payment_due_hours được tính bằng phút (minutes), không phải giờ
 * - Max: 43200 minutes = 720 hours = 30 days
 */
class BookingDepositSettingController extends Controller
{
    /**
     * ChecksCapabilities trait instance
     * 
     * Trait này cung cấp các methods:
     * - requireCapability(): Kiểm tra và abort nếu không có capability
     * - checkCapability(): Kiểm tra capability (trả về boolean)
     * - getCurrentOrganizationId(): Lấy organization ID từ session
     */
    use ChecksCapabilities;
    
    /**
     * Hiển thị trang cài đặt booking deposit
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra capability (chỉ manager có thể truy cập)
     * 2. Lấy organization ID từ session
     * 3. Lấy organization info
     * 4. Hiển thị view cài đặt
     * 
     * CAPABILITY:
     * - contract.lease.view: Cần có quyền này để truy cập settings
     * 
     * @return \Illuminate\View\View View cài đặt booking deposit
     */
    public function index()
    {
        /**
         * Lấy authenticated user
         * 
         * Auth::user() - Lấy authenticated user instance
         *   - user() là method của Auth facade
         *   - Trả về User model instance của user đã đăng nhập
         *   - Trả về null nếu chưa đăng nhập (nhưng ở đây đã authenticate)
         * 
         * PHPDoc comment @var \App\Models\User $user được đặt trước để IDE biết type
         *   - Giúp IDE autocomplete và type checking
         * 
         * $user - Biến lưu User model instance
         *   - Sẽ được sử dụng để check capabilities và lấy organization
         */
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        /**
         * Kiểm tra capability - chỉ manager có thể truy cập settings
         * 
         * $this->requireCapability('contract.lease.view', '...') - Kiểm tra và abort nếu không có capability
         *   - requireCapability() là method từ ChecksCapabilities trait
         *   - Tham số 1: 'contract.lease.view' - Capability key cần kiểm tra
         *   - Tham số 2: 'Bạn không có quyền truy cập cài đặt đặt cọc.' - Error message
         *   - Nếu user không có capability, sẽ abort(403) với error message
         *   - Nếu user có capability, tiếp tục
         * 
         * Lý do sử dụng 'contract.lease.view':
         * - Booking deposit settings liên quan đến contract management
         * - Chỉ manager có quyền này mới có thể truy cập settings
         */
        $this->requireCapability('contract.lease.view', 'Bạn không có quyền truy cập cài đặt đặt cọc.');
        
        /**
         * Lấy organization ID từ session
         * 
         * $this->getCurrentOrganizationId() - Lấy organization ID từ session
         *   - getCurrentOrganizationId() là method từ ChecksCapabilities trait
         *   - Lấy từ session key: 'auth_organization_id'
         *   - Trả về integer (organization ID) nếu có
         *   - Trả về null nếu không có
         * 
         * $organizationId - Biến lưu organization ID (hoặc null)
         *   - Sẽ được sử dụng để query organization và payment cycles
         */
        $organizationId = $this->getCurrentOrganizationId();
        
        /**
         * Kiểm tra organization ID có hợp lệ không
         * 
         * if (!$organizationId) - Kiểm tra xem organization ID có null không
         *   - ! là NOT operator, đảo ngược giá trị boolean
         *   - Nếu $organizationId = null, !null = true, vào block if
         * 
         * abort(403, '...') - Abort request với HTTP 403 Forbidden
         *   - abort() là helper function của Laravel
         *   - 403 là HTTP status code cho Forbidden
         *   - 'Bạn không thuộc tổ chức nào.' - Error message tiếng Việt
         *   - Sẽ trả về HTTP 403 response với error message
         */
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        /**
         * Lấy organization info từ database
         * 
         * Organization::find($organizationId) - Tìm organization theo ID
         *   - find() là method của Eloquent model
         *   - Trả về Organization model instance nếu tìm thấy
         *   - Trả về null nếu không tìm thấy
         * 
         * $organization - Biến lưu Organization model instance (hoặc null)
         *   - Sẽ được truyền vào view để hiển thị thông tin organization
         */
        $organization = Organization::find($organizationId);
        
        /**
         * Kiểm tra organization có tồn tại không
         * 
         * if (!$organization) - Kiểm tra xem organization có null không
         *   - ! là NOT operator
         *   - Nếu $organization = null, !null = true, vào block if
         * 
         * abort(404, '...') - Abort request với HTTP 404 Not Found
         *   - abort() là helper function của Laravel
         *   - 404 là HTTP status code cho Not Found
         *   - 'Organization not found.' - Error message tiếng Anh
         *   - Sẽ trả về HTTP 404 response với error message
         */
        if (!$organization) {
            abort(404, 'Organization not found.');
        }

        /**
         * Hiển thị view cài đặt booking deposit
         * 
         * view('staff.contract.booking-deposit-settings.index', [...]) - Tạo view response
         *   - view() là helper function của Laravel
         *   - 'staff.contract.booking-deposit-settings.index' là path đến view file
         *   - Array chứa data: ['organization' => $organization]
         *   - Trả về View instance
         * 
         * View sẽ hiển thị form cài đặt payment_due_hours cho booking deposits
         */
        return view('staff.contract.booking-deposit-settings.index', [
            'organization' => $organization
        ]);
    }

    /**
     * Cập nhật thời gian chờ thanh toán (payment_due_hours)
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Kiểm tra capability (chỉ manager có thể cập nhật)
     * 2. Validate input (payment_due_hours: 1-43200 minutes)
     * 3. Bắt đầu database transaction
     * 4. Tìm hoặc tạo default PaymentCycle
     * 5. Cập nhật payment_due_hours
     * 6. Commit transaction
     * 7. Ghi log
     * 8. Trả về response thành công
     * 
     * VALIDATION:
     * - payment_due_hours: required, integer, min:1, max:43200
     *   - 43200 minutes = 720 hours = 30 days (tối đa)
     *   - 1 minute = tối thiểu
     * 
     * DATABASE TRANSACTIONS:
     * - Sử dụng DB::beginTransaction() và DB::commit() để đảm bảo data consistency
     * - Rollback nếu có lỗi
     * 
     * @param Request $request HTTP request chứa payment_due_hours
     * @return \Illuminate\Http\JsonResponse JSON response với kết quả cập nhật
     */
    public function updatePaymentDueHours(Request $request)
    {
        /**
         * Lấy authenticated user
         * 
         * Auth::user() - Lấy authenticated user instance
         *   - user() là method của Auth facade
         *   - Trả về User model instance của user đã đăng nhập
         * 
         * PHPDoc comment @var \App\Models\User $user được đặt trước để IDE biết type
         * 
         * $user - Biến lưu User model instance
         *   - Sẽ được sử dụng để ghi log (updated_by)
         */
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        /**
         * Kiểm tra capability - chỉ manager có thể cập nhật settings
         * 
         * $this->requireCapability('contract.lease.view', '...') - Kiểm tra và abort nếu không có capability
         *   - requireCapability() là method từ ChecksCapabilities trait
         *   - Tham số 1: 'contract.lease.view' - Capability key cần kiểm tra
         *   - Tham số 2: 'Bạn không có quyền cập nhật cài đặt.' - Error message
         *   - Nếu user không có capability, sẽ abort(403) với error message
         */
        $this->requireCapability('contract.lease.view', 'Bạn không có quyền cập nhật cài đặt.');
        
        /**
         * Lấy organization ID từ session
         * 
         * $this->getCurrentOrganizationId() - Lấy organization ID từ session
         *   - getCurrentOrganizationId() là method từ ChecksCapabilities trait
         *   - Lấy từ session key: 'auth_organization_id'
         *   - Trả về integer (organization ID) nếu có
         *   - Trả về null nếu không có
         * 
         * $organizationId - Biến lưu organization ID (hoặc null)
         */
        $organizationId = $this->getCurrentOrganizationId();
        
        /**
         * Kiểm tra organization ID có hợp lệ không
         * 
         * if (!$organizationId) - Kiểm tra xem organization ID có null không
         *   - ! là NOT operator
         *   - Nếu $organizationId = null, vào block if
         * 
         * response()->json([...], 403) - Trả về JSON response với status 403
         *   - response() là helper function của Laravel
         *   - json() là method để tạo JSON response
         *   - Array chứa: ['success' => false, 'message' => '...']
         *   - 403 là HTTP status code cho Forbidden
         */
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        /**
         * Validate input data từ request
         * 
         * Validator::make($request->all(), [...], [...]) - Tạo validator instance
         *   - make() là static method của Validator facade
         *   - Tham số 1: $request->all() - Tất cả input data từ request
         *   - Tham số 2: Array chứa validation rules
         *   - Tham số 3: Array chứa custom error messages (tiếng Việt)
         * 
         * Validation rules:
         * - 'payment_due_hours' => 'required|integer|min:1|max:43200'
         *   - required: Field bắt buộc phải có
         *   - integer: Phải là số nguyên
         *   - min:1: Giá trị tối thiểu là 1 (1 phút)
         *   - max:43200: Giá trị tối đa là 43200 (30 ngày = 720 giờ * 60 phút)
         *   - Lưu ý: payment_due_hours được tính bằng phút (minutes), không phải giờ
         * 
         * Custom error messages (tiếng Việt):
         * - 'payment_due_hours.required' => 'Vui lòng nhập thời gian chờ thanh toán.'
         * - 'payment_due_hours.integer' => 'Thời gian chờ thanh toán phải là số nguyên.'
         * - 'payment_due_hours.min' => 'Thời gian chờ thanh toán phải lớn hơn 0.'
         * - 'payment_due_hours.max' => 'Thời gian chờ thanh toán không được vượt quá 720 giờ (30 ngày).'
         * 
         * $validator - Validator instance để validate data
         */
        $validator = Validator::make($request->all(), [
            'payment_due_hours' => 'required|integer|min:1|max:43200', // Max 30 days in minutes (720 hours * 60)
        ], [
            'payment_due_hours.required' => 'Vui lòng nhập thời gian chờ thanh toán.',
            'payment_due_hours.integer' => 'Thời gian chờ thanh toán phải là số nguyên.',
            'payment_due_hours.min' => 'Thời gian chờ thanh toán phải lớn hơn 0.',
            'payment_due_hours.max' => 'Thời gian chờ thanh toán không được vượt quá 720 giờ (30 ngày).',
        ]);

        /**
         * Kiểm tra validation có thất bại không
         * 
         * $validator->fails() - Kiểm tra xem validation có thất bại không
         *   - fails() là method của Validator để kiểm tra validation
         *   - Trả về true nếu có lỗi validation
         *   - Trả về false nếu validation thành công
         * 
         * if ($validator->fails()) - Kiểm tra xem có lỗi validation không
         *   - Nếu có lỗi, vào block if
         */
        if ($validator->fails()) {
            /**
             * Trả về response 422 Unprocessable Entity với validation errors
             * 
             * response()->json([...], 422) - Tạo JSON response với status 422
             *   - 422 là HTTP status code cho Unprocessable Entity (validation failed)
             * 
             * Array chứa:
             * - 'success' => false - Trạng thái thất bại
             * - 'message' => 'Dữ liệu không hợp lệ.' - Thông báo lỗi chung
             * - 'errors' => $validator->errors() - Tất cả lỗi validation
             *   - errors() là method của Validator để lấy tất cả lỗi
             *   - Trả về MessageBag chứa các lỗi validation
             */
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        /**
         * Xử lý cập nhật payment_due_hours
         * 
         * try { ... } catch { ... } - Xử lý exception
         * - Nếu thành công: Commit transaction và trả về success
         * - Nếu có lỗi: Rollback transaction, ghi log, và trả về error
         */
        try {
            /**
             * Bắt đầu database transaction để đảm bảo data consistency
             * 
             * DB::beginTransaction() - Bắt đầu database transaction
             *   - beginTransaction() là method của Laravel DB facade
             *   - Tất cả các thao tác database sau đây sẽ được thực thi trong transaction
             *   - Nếu có lỗi, sẽ rollback tất cả thay đổi
             *   - Nếu thành công, sẽ commit tất cả thay đổi
             */
            DB::beginTransaction();
            
            /**
             * Lấy organization từ database
             * 
             * Organization::findOrFail($organizationId) - Tìm organization theo ID
             *   - findOrFail() là method của Eloquent model
             *   - Trả về Organization model instance nếu tìm thấy
             *   - Nếu không tìm thấy, sẽ throw ModelNotFoundException (sẽ được catch)
             * 
             * $organization - Biến lưu Organization model instance
             *   - Sẽ được sử dụng để verify organization tồn tại
             */
            $organization = Organization::findOrFail($organizationId);
            
            /**
             * Tìm default PaymentCycle của organization
             * 
             * PaymentCycle::where('organization_id', $organizationId) - Lọc theo organization_id
             *   - where() là method của Eloquent model để thêm điều kiện WHERE
             *   - 'organization_id' là tên column trong bảng payment_cycles
             *   - $organizationId là organization ID cần tìm
             * 
             * ->where('is_default', true) - Thêm điều kiện is_default = true
             *   - 'is_default' là tên column trong bảng payment_cycles
             *   - true nghĩa là đây là payment cycle mặc định của organization
             *   - Mỗi organization chỉ có 1 default payment cycle
             * 
             * ->first() - Lấy bản ghi đầu tiên (hoặc null nếu không tìm thấy)
             *   - first() là method của query builder
             *   - Trả về PaymentCycle model instance nếu tìm thấy
             *   - Trả về null nếu không tìm thấy
             * 
             * $defaultPaymentCycle - Biến lưu PaymentCycle model instance (hoặc null)
             *   - Nếu tìm thấy: $defaultPaymentCycle là instance của PaymentCycle
             *   - Nếu không tìm thấy: $defaultPaymentCycle = null (sẽ tạo mới)
             */
            $defaultPaymentCycle = PaymentCycle::where('organization_id', $organizationId)
                ->where('is_default', true)
                ->first();
            
            /**
             * Kiểm tra xem default PaymentCycle đã tồn tại chưa
             * 
             * if (!$defaultPaymentCycle) - Kiểm tra xem $defaultPaymentCycle có null không
             *   - ! là NOT operator
             *   - Nếu $defaultPaymentCycle = null, vào block if để tạo mới
             *   - Nếu $defaultPaymentCycle != null, vào block else để cập nhật
             */
            if (!$defaultPaymentCycle) {
                /**
                 * Tạo default PaymentCycle mới nếu chưa tồn tại
                 * 
                 * PaymentCycle::create([...]) - Tạo bản ghi mới trong bảng payment_cycles
                 *   - create() là method của Eloquent model để tạo và lưu bản ghi mới
                 *   - Tham số là associative array chứa data cần insert
                 * 
                 * Array chứa:
                 * - 'organization_id' => $organizationId - ID của organization
                 * - 'cycle_type' => 'monthly' - Loại chu kỳ thanh toán (hàng tháng)
                 * - 'billing_day' => 1 - Ngày billing (ngày 1 hàng tháng)
                 * - 'payment_due_hours' => $request->payment_due_hours - Thời gian chờ thanh toán (phút)
                 *   - $request->payment_due_hours là giá trị từ request (đã được validate)
                 *   - Lưu ý: Tên column là payment_due_hours nhưng giá trị là phút (minutes)
                 * - 'notes' => 'Chu kỳ thanh toán mặc định' - Ghi chú
                 * - 'name' => 'Hàng tháng - Ngày 1' - Tên payment cycle
                 * - 'is_default' => true - Đánh dấu đây là payment cycle mặc định
                 * 
                 * $defaultPaymentCycle - Biến lưu PaymentCycle model instance vừa tạo
                 *   - Sẽ được sử dụng để trả về payment_due_hours trong response
                 */
                $defaultPaymentCycle = PaymentCycle::create([
                    'organization_id' => $organizationId,
                    'cycle_type' => 'monthly',
                    'billing_day' => 1,
                    'payment_due_hours' => $request->payment_due_hours,
                    'notes' => 'Chu kỳ thanh toán mặc định',
                    'name' => 'Hàng tháng - Ngày 1',
                    'is_default' => true,
                ]);
            } else {
                /**
                 * Cập nhật default PaymentCycle đã tồn tại
                 * 
                 * $defaultPaymentCycle->update([...]) - Cập nhật bản ghi trong database
                 *   - update() là method của Eloquent model để cập nhật bản ghi
                 *   - Tham số là associative array chứa data cần cập nhật
                 * 
                 * Array chứa:
                 * - 'payment_due_hours' => $request->payment_due_hours - Cập nhật thời gian chờ thanh toán
                 *   - $request->payment_due_hours là giá trị mới từ request (đã được validate)
                 *   - Chỉ cập nhật payment_due_hours, giữ nguyên các field khác
                 * 
                 * update() sẽ execute SQL query: UPDATE payment_cycles SET payment_due_hours = ? WHERE id = ?
                 *   - Trả về boolean (true nếu thành công)
                 */
                $defaultPaymentCycle->update([
                    'payment_due_hours' => $request->payment_due_hours
                ]);
            }

            /**
             * Commit transaction để lưu tất cả thay đổi
             * 
             * DB::commit() - Commit database transaction
             *   - commit() là method của Laravel DB facade
             *   - Lưu tất cả thay đổi vào database
             *   - Giải phóng locks
             *   - Sau khi commit, không thể rollback được nữa
             */
            DB::commit();

            /**
             * Ghi log khi cập nhật thành công
             * 
             * Log::info() - Ghi log với level INFO (thông tin bình thường)
             *   - Log được ghi vào: storage/logs/laravel.log
             *   - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
             * 
             * 'Payment due hours updated' - Message mô tả sự kiện
             * 
             * Array chứa context data:
             * - 'organization_id' => $organizationId - ID của organization
             * - 'payment_cycle_id' => $defaultPaymentCycle->id - ID của payment cycle đã cập nhật
             * - 'payment_due_hours' => $request->payment_due_hours - Giá trị mới
             * - 'updated_by' => $user->id - ID của user đã cập nhật
             * 
             * Log này giúp:
             * - Theo dõi các thay đổi payment_due_hours
             * - Audit trail cho settings changes
             * - Debug khi có vấn đề
             */
            Log::info('Payment due hours updated', [
                'organization_id' => $organizationId,
                'payment_cycle_id' => $defaultPaymentCycle->id,
                'payment_due_hours' => $request->payment_due_hours,
                'updated_by' => $user->id,
            ]);

            /**
             * Trả về response thành công
             * 
             * response()->json([...]) - Tạo JSON response với status 200 (default)
             *   - response() là helper function của Laravel
             *   - json() là method để tạo JSON response
             * 
             * Array chứa:
             * - 'success' => true - Trạng thái thành công
             * - 'message' => '...' - Thông báo thành công tiếng Việt
             * - 'payment_due_hours' => $defaultPaymentCycle->payment_due_hours - Giá trị đã cập nhật
             *   - $defaultPaymentCycle->payment_due_hours truy cập giá trị từ model
             *   - Trả về cho frontend để hiển thị giá trị mới
             */
            return response()->json([
                'success' => true,
                'message' => 'Cài đặt thời gian chờ thanh toán đã được cập nhật thành công!',
                'payment_due_hours' => $defaultPaymentCycle->payment_due_hours
            ]);

        } catch (\Exception $e) {
            /**
             * Rollback transaction khi có lỗi
             * 
             * DB::rollBack() - Rollback database transaction
             *   - rollBack() là method của Laravel DB facade
             *   - Hủy bỏ tất cả thay đổi trong transaction
             *   - Khôi phục database về trạng thái trước khi beginTransaction()
             *   - Giải phóng locks
             */
            DB::rollBack();
            
            /**
             * Ghi log lỗi
             * 
             * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
             *   - Log được ghi vào: storage/logs/laravel.log
             *   - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message
             * 
             * 'Error updating payment due hours: ' . $e->getMessage() - Message mô tả lỗi
             *   - $e->getMessage() trả về error message của exception
             *   - Dấu . là string concatenation operator
             * 
             * Log này giúp:
             * - Debug lỗi hệ thống
             * - Theo dõi các exception
             * - Audit trail cho errors
             */
            Log::error('Error updating payment due hours: ' . $e->getMessage());
            
            /**
             * Trả về response lỗi
             * 
             * response()->json([...], 500) - Tạo JSON response với status 500
             *   - 500 là HTTP status code cho Internal Server Error
             * 
             * Array chứa:
             * - 'success' => false - Trạng thái thất bại
             * - 'message' => 'Có lỗi xảy ra khi cập nhật cài đặt.' - Thông báo lỗi chung
             *   - Không trả về error message chi tiết để bảo mật
             *   - Error message chi tiết đã được ghi vào log
             */
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật cài đặt.'
            ], 500);
        }
    }

}
