<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\BookingDeposit;
use App\Models\Unit;
use App\Models\Lead;
use App\Models\User;
use App\Models\Property;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Controller: BookingDepositController
 * 
 * MỤC ĐÍCH:
 * Controller quản lý booking deposits (đặt cọc) trong hệ thống.
 * Controller này xử lý việc tạo, xem, cập nhật, duyệt, thanh toán, hủy, và hoàn tiền booking deposits.
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị danh sách booking deposits
 *    - Auto-cancel overdue deposits (chạy mỗi 5 phút)
 *    - Filter theo ownership (view_all vs view_own)
 *    - Tính statistics (total, pending, paid, cancelled, etc.)
 *    - Support HTMX/AJAX requests
 * 2. create(): Hiển thị form tạo booking deposit mới
 * 3. store(): Tạo booking deposit mới
 *    - Validate input
 *    - Tạo booking deposit với status 'pending_approval'
 *    - Tự động gán agent_id cho agent
 * 4. show(): Hiển thị chi tiết booking deposit
 * 5. edit(): Hiển thị form chỉnh sửa (chỉ khi status = 'pending_approval')
 * 6. update(): Cập nhật booking deposit
 * 7. approve(): Duyệt booking deposit (pending_approval -> pending)
 *    - Tính payment_due_date từ payment_due_hours
 *    - Update unit status to 'reserved'
 * 8. markPaid(): Đánh dấu đã thanh toán
 * 9. cancel(): Hủy booking deposit
 * 10. refund(): Hoàn tiền booking deposit
 * 11. createInvoice(): Tạo invoice từ booking deposit
 * 12. createLease(): Tạo lease từ booking deposit (đã thanh toán)
 * 
 * ENDPOINTS:
 * - GET /staff/booking-deposits: Danh sách booking deposits
 * - GET /staff/booking-deposits/create: Form tạo mới
 * - POST /staff/booking-deposits: Tạo booking deposit
 * - GET /staff/booking-deposits/{id}: Chi tiết booking deposit
 * - GET /staff/booking-deposits/{id}/edit: Form chỉnh sửa
 * - PUT/PATCH /staff/booking-deposits/{id}: Cập nhật booking deposit
 * - DELETE /staff/booking-deposits/{id}: Xóa booking deposit
 * - POST /staff/booking-deposits/{id}/approve: Duyệt booking deposit
 * - POST /staff/booking-deposits/{id}/mark-paid: Đánh dấu đã thanh toán
 * - POST /staff/booking-deposits/{id}/cancel: Hủy booking deposit
 * - POST /staff/booking-deposits/{id}/refund: Hoàn tiền booking deposit
 * - POST /staff/booking-deposits/{id}/create-invoice: Tạo invoice
 * - POST /staff/booking-deposits/{id}/create-lease: Tạo lease
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: BookingDeposit (bảng booking_deposits)
 * - Model: Unit (bảng units)
 * - Model: Property (bảng properties)
 * - Model: Lead (bảng leads)
 * - Model: User (bảng users)
 * - Model: Invoice (bảng invoices)
 * - Model: Lease (bảng leases)
 * - Cache: booking_deposits_last_auto_cancel_check_{orgId}
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng booking_deposits: Tạo, cập nhật, xóa booking deposits
 * - Bảng invoices: Tạo/cập nhật invoices liên quan
 * - Bảng units: Cập nhật status (available <-> reserved)
 * - Cache: Lưu thời gian check overdue deposits
 * - Logs: Ghi log các thao tác quan trọng
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (contract.access, contract.booking_deposit.*)
 * - FiltersByOwnership: Filter data theo ownership (view_all vs view_own)
 * 
 * CAPABILITY CHECKING:
 * - contract.access: Cần có để truy cập module Hợp đồng
 * - contract.booking_deposit.view: Xem booking deposits
 * - contract.booking_deposit.create: Tạo booking deposit
 * - contract.booking_deposit.update: Cập nhật booking deposit
 * - contract.booking_deposit.delete: Xóa booking deposit
 * 
 * OWNERSHIP FILTERING:
 * - Manager có view_all: Xem tất cả booking deposits trong organization
 * - Agent có view_own: Chỉ xem booking deposits của chính mình (agent_id = user_id)
 * - Agent không có view_own: Filter theo assigned properties (backward compatibility)
 * 
 * AUTO-CANCEL OVERDUE DEPOSITS:
 * - Tự động hủy booking deposits quá hạn thanh toán (payment_due_date < now)
 * - Chạy mỗi 5 phút (sử dụng Cache để tránh chạy quá thường xuyên)
 * - Hủy invoices liên quan (nếu chưa thanh toán)
 * - Update unit status về 'available' nếu không có active deposits/leases khác
 * 
 * PAYMENT STATUS FLOW:
 * - pending_approval -> pending (khi approve)
 * - pending -> paid (khi mark paid)
 * - pending -> expired (tự động khi quá hạn)
 * - pending -> cancelled (khi cancel)
 * - paid -> refunded (khi refund)
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng JOINs để lấy related data (units, properties, leads, tenants)
 * - Sử dụng indexes: idx_bd_organization_id, idx_units_deleted_at_property, idx_properties_deleted_at_org
 * - Tính statistics từ base query trước khi apply filters
 * - Eager loading relationships để tránh N+1 queries
 * 
 * HTMX/AJAX SUPPORT:
 * - Support HTMX requests (HX-Request header)
 * - Support legacy AJAX requests
 * - Return partial HTML với hx-swap-oob cho statistics cards
 * 
 * LƯU Ý:
 * - Agent chỉ có thể tạo booking deposit cho chính mình (agent_id tự động gán)
 * - Manager có thể gán booking deposit cho agent khác
 * - Chỉ có thể chỉnh sửa booking deposit khi status = 'pending_approval'
 * - Không thể xóa booking deposit đã thanh toán
 * - payment_due_date được tính từ payment_due_hours (Property Cycle > Organization Default)
 * - Unit status tự động update: available -> reserved (khi approve), reserved -> available (khi cancel)
 */
class BookingDepositController extends Controller
{
    /**
     * ChecksCapabilities và FiltersByOwnership traits
     * 
     * ChecksCapabilities trait cung cấp:
     * - requireCapability(): Kiểm tra và abort nếu không có capability
     * - checkCapability(): Kiểm tra capability (trả về boolean)
     * - canViewAll(): Kiểm tra user có view_all capability không
     * - getCurrentOrganizationId(): Lấy organization ID từ session
     * 
     * FiltersByOwnership trait cung cấp:
     * - shouldFilterByOwnership(): Kiểm tra có cần filter theo ownership không
     * - enforceAgentId(): Tự động gán agent_id cho agent
     */
    use ChecksCapabilities, FiltersByOwnership;

    /**
     * Hiển thị danh sách booking deposits
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Kiểm tra capability (contract.access)
     * 2. Auto-cancel overdue deposits (chạy mỗi 5 phút)
     * 3. Build query với JOINs và filters
     * 4. Filter theo ownership (view_all vs view_own)
     * 5. Tính statistics từ base query (trước khi apply filters)
     * 6. Apply search và filters
     * 7. Paginate results
     * 8. Support HTMX/AJAX requests
     * 
     * AUTO-CANCEL OVERDUE DEPOSITS:
     * - Tự động hủy booking deposits quá hạn thanh toán
     * - Chạy mỗi 5 phút (sử dụng Cache để tránh chạy quá thường xuyên)
     * - Hủy invoices liên quan và update unit status
     * 
     * OWNERSHIP FILTERING:
     * - Manager có view_all: Xem tất cả
     * - Agent có view_own: Chỉ xem của chính mình
     * - Agent không có view_own: Filter theo assigned properties
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng JOINs để lấy related data
     * - Sử dụng indexes để tối ưu performance
     * - Tính statistics từ base query trước khi apply filters
     * 
     * @param Request $request HTTP request (có thể chứa search, filters, HTMX headers)
     * @return \Illuminate\View\View|\Illuminate\Http\Response View hoặc HTMX/AJAX response
     */
    public function index(Request $request)
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
         *   - Sẽ được sử dụng để check capabilities, filter theo ownership, và lấy assigned properties
         */
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        /**
         * Kiểm tra capability contract.access
         * 
         * $this->checkCapability('contract.access') - Kiểm tra user có capability contract.access không
         *   - checkCapability() là method từ ChecksCapabilities trait
         *   - Tham số: 'contract.access' - Capability key cần kiểm tra
         *   - Trả về true nếu user có capability
         *   - Trả về false nếu user không có capability
         * 
         * $hasContractAccess - Biến lưu kết quả kiểm tra capability
         *   - true nếu user có quyền truy cập module Hợp đồng
         *   - false nếu user không có quyền
         */
        $hasContractAccess = $this->checkCapability('contract.access');
        
        /**
         * Kiểm tra và abort nếu không có capability
         * 
         * if (!$hasContractAccess) - Kiểm tra xem user có capability không
         *   - ! là NOT operator, đảo ngược giá trị boolean
         *   - Nếu $hasContractAccess = false, !false = true, vào block if
         *   - Nếu $hasContractAccess = true, !true = false, không vào block if
         * 
         * abort(403, '...') - Abort request với HTTP 403 Forbidden
         *   - abort() là helper function của Laravel
         *   - 403 là HTTP status code cho Forbidden
         *   - 'Bạn không có quyền truy cập module Hợp đồng.' - Error message tiếng Việt
         *   - Sẽ trả về HTTP 403 response với error message
         */
        if (!$hasContractAccess) {
            abort(403, 'Bạn không có quyền truy cập module Hợp đồng.');
        }

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
         *   - Sẽ được sử dụng để filter booking deposits theo organization
         */
        $organizationId = $this->getCurrentOrganizationId();
        
        /**
         * Kiểm tra organization ID có hợp lệ không
         * 
         * if (!$organizationId) - Kiểm tra xem organization ID có null không
         *   - ! là NOT operator
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
         * Kiểm tra user có quyền xem tất cả booking deposits không
         * 
         * $this->canViewAll('contract.booking_deposit') - Kiểm tra user có view_all capability không
         *   - canViewAll() là method từ ChecksCapabilities trait
         *   - Tham số: 'contract.booking_deposit' - Capability key để check view_all
         *   - Method này sẽ:
         *     1. Kiểm tra user có capability 'contract.booking_deposit.view_all' không
         *     2. Hoặc kiểm tra user có role là manager (manager có tất cả quyền)
         *     3. Trả về true nếu user có quyền xem tất cả
         *     4. Trả về false nếu user chỉ có quyền xem của chính mình (view_own)
         * 
         * $canViewAll - Biến lưu kết quả kiểm tra
         *   - true nếu user có quyền xem tất cả booking deposits trong organization (manager)
         *   - false nếu user chỉ có quyền xem booking deposits của chính mình (agent với view_own)
         *   - Sẽ được sử dụng để filter data và tính statistics
         */
        $canViewAll = $this->canViewAll('contract.booking_deposit');

        /**
         * Tự động kiểm tra và hủy booking deposits quá hạn thanh toán
         * 
         * Logic: Chỉ chạy check nếu lần check trước đó đã hơn 5 phút (tránh chạy quá thường xuyên)
         * 
         * LUỒNG XỬ LÝ:
         * 1. Lấy thời gian check lần cuối từ Cache
         * 2. Nếu chưa có hoặc đã quá 5 phút: Chạy check
         * 3. Tìm booking deposits quá hạn (payment_status = 'pending', payment_due_date < now)
         * 4. Hủy deposits và invoices liên quan
         * 5. Update unit status về 'available' nếu không có active deposits/leases khác
         * 6. Lưu thời gian check vào Cache (expire sau 1 giờ)
         */
        
        /**
         * Tạo cache key để lưu thời gian check lần cuối
         * 
         * 'booking_deposits_last_auto_cancel_check_' . $organizationId - Tạo cache key unique cho mỗi organization
         *   - 'booking_deposits_last_auto_cancel_check_' là prefix
         *   - $organizationId là organization ID (integer)
         *   - Dấu . là string concatenation operator
         *   - Ví dụ: 'booking_deposits_last_auto_cancel_check_1'
         * 
         * $lastCheckKey - Biến lưu cache key
         *   - Sẽ được sử dụng để get/put cache value
         */
        $lastCheckKey = 'booking_deposits_last_auto_cancel_check_' . $organizationId;
        
        /**
         * Lấy thời gian check lần cuối từ Cache
         * 
         * Cache::get($lastCheckKey) - Lấy giá trị từ cache
         *   - get() là method của Cache facade
         *   - Tham số: $lastCheckKey - Cache key
         *   - Trả về giá trị nếu có (string datetime)
         *   - Trả về null nếu không có
         * 
         * $lastCheck - Biến lưu thời gian check lần cuối (hoặc null)
         *   - String datetime nếu đã check trước đó
         *   - null nếu chưa check lần nào
         */
        $lastCheck = Cache::get($lastCheckKey);
        
        /**
         * Kiểm tra có cần chạy check không
         * 
         * if (!$lastCheck || Carbon::parse($lastCheck)->addMinutes(5)->isPast()) - Kiểm tra điều kiện
         *   - !$lastCheck: Nếu chưa có thời gian check lần cuối (lần đầu chạy)
         *   - ||: OR operator, nếu điều kiện đầu true thì không cần check điều kiện sau
         *   - Carbon::parse($lastCheck): Parse string datetime thành Carbon instance
         *     - parse() là static method của Carbon để parse datetime string
         *     - $lastCheck là string datetime (ví dụ: '2025-01-15 10:30:00')
         *   - ->addMinutes(5): Thêm 5 phút vào thời gian check lần cuối
         *     - addMinutes() là method của Carbon để thêm số phút
         *     - 5 là số phút cần thêm
         *   - ->isPast(): Kiểm tra xem thời gian đã qua chưa
         *     - isPast() là method của Carbon để kiểm tra thời gian đã qua chưa
         *     - Trả về true nếu thời gian (lastCheck + 5 phút) < now
         *     - Trả về false nếu thời gian (lastCheck + 5 phút) >= now
         * 
         * Điều kiện vào block if:
         * - Chưa có thời gian check lần cuối (lần đầu chạy)
         * - HOẶC đã quá 5 phút kể từ lần check cuối
         */
        if (!$lastCheck || Carbon::parse($lastCheck)->addMinutes(5)->isPast()) {
            /**
             * Xử lý auto-cancel overdue deposits
             * 
             * try { ... } catch { ... } - Xử lý exception
             * - Nếu thành công: Tiếp tục
             * - Nếu có lỗi: Ghi log và tiếp tục (không block request)
             */
            try {
                /**
                 * Tìm booking deposits quá hạn thanh toán
                 * 
                 * BookingDeposit::where('organization_id', $organizationId) - Filter theo organization
                 *   - where() là method của Eloquent model để thêm điều kiện WHERE
                 *   - 'organization_id' là tên column trong bảng booking_deposits
                 *   - $organizationId là organization ID cần filter
                 * 
                 * ->where('payment_status', 'pending') - Chỉ lấy deposits đang chờ thanh toán
                 *   - 'payment_status' là tên column
                 *   - 'pending' là giá trị cần tìm (chờ thanh toán)
                 *   - Chỉ deposits ở trạng thái 'pending' mới có thể quá hạn
                 * 
                 * ->whereNotNull('payment_due_date') - Chỉ lấy deposits có payment_due_date
                 *   - whereNotNull() là method để kiểm tra column không null
                 *   - 'payment_due_date' là tên column
                 *   - Cần có payment_due_date để so sánh với now()
                 * 
                 * ->where('payment_due_date', '<', now()) - Lấy deposits quá hạn
                 *   - where() với operator '<' để so sánh nhỏ hơn
                 *   - 'payment_due_date' là tên column
                 *   - '<' là operator nhỏ hơn
                 *   - now() là helper function của Laravel trả về Carbon instance của thời gian hiện tại
                 *   - Điều kiện: payment_due_date < now() (đã quá hạn)
                 * 
                 * ->whereNull('deleted_at') - Chỉ lấy deposits chưa bị xóa (soft delete)
                 *   - whereNull() là method để kiểm tra column là null
                 *   - 'deleted_at' là tên column cho soft delete
                 *   - null nghĩa là chưa bị xóa
                 * 
                 * ->get() - Lấy tất cả bản ghi thỏa mãn điều kiện
                 *   - get() là method của query builder
                 *   - Trả về Collection chứa các BookingDeposit model instances
                 * 
                 * $overdueDeposits - Biến lưu Collection chứa booking deposits quá hạn
                 *   - Sẽ được sử dụng để hủy từng deposit
                 */
                $overdueDeposits = BookingDeposit::where('organization_id', $organizationId)
                    ->where('payment_status', 'pending')
                    ->whereNotNull('payment_due_date')
                    ->where('payment_due_date', '<', now())
                    ->whereNull('deleted_at')
                    ->get();
                
                /**
                 * Kiểm tra có deposits quá hạn không
                 * 
                 * if ($overdueDeposits->count() > 0) - Kiểm tra số lượng deposits
                 *   - count() là method của Collection để đếm số phần tử
                 *   - > 0 nghĩa là có ít nhất 1 deposit quá hạn
                 *   - Nếu có, vào block if để xử lý
                 */
                if ($overdueDeposits->count() > 0) {
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
                     * Xử lý từng deposit quá hạn
                     * 
                     * try { ... } catch { ... } - Xử lý exception trong transaction
                     * - Nếu thành công: Commit transaction
                     * - Nếu có lỗi: Rollback transaction và ghi log
                     */
                    try {
                        /**
                         * Duyệt qua từng deposit quá hạn
                         * 
                         * foreach ($overdueDeposits as $deposit) - Loop qua Collection
                         *   - foreach là PHP loop construct
                         *   - $overdueDeposits là Collection chứa BookingDeposit instances
                         *   - $deposit là biến lưu từng BookingDeposit instance trong mỗi iteration
                         */
                        foreach ($overdueDeposits as $deposit) {
                            /**
                             * Cập nhật trạng thái deposit thành 'cancelled'
                             * 
                             * $deposit->update([...]) - Cập nhật bản ghi trong database
                             *   - update() là method của Eloquent model để cập nhật bản ghi
                             *   - Tham số là associative array chứa data cần cập nhật
                             * 
                             * Array chứa:
                             * - 'payment_status' => 'cancelled' - Chuyển trạng thái thành đã hủy
                             *   - 'cancelled' là trạng thái đã hủy
                             *   - Deposit quá hạn thanh toán sẽ bị hủy tự động
                             * - 'expired_at' => now() - Lưu thời gian hết hạn
                             *   - now() là helper function của Laravel trả về Carbon instance của thời gian hiện tại
                             *   - expired_at là column lưu thời gian deposit hết hạn
                             *   - Sử dụng để tracking và reporting
                             * 
                             * update() sẽ execute SQL query: UPDATE booking_deposits SET payment_status = 'cancelled', expired_at = ? WHERE id = ?
                             *   - Trả về boolean (true nếu thành công)
                             */
                            $deposit->update([
                                'payment_status' => 'cancelled',
                                'expired_at' => now(),
                            ]);
                            
                            /**
                             * Lấy invoices liên quan chưa thanh toán
                             * 
                             * $deposit->invoices() - Lấy relationship invoices của deposit
                             *   - invoices() là method relationship trong BookingDeposit model
                             *   - Trả về HasMany relationship instance
                             *   - Relationship này query từ bảng invoices với booking_deposit_id
                             * 
                             * ->where('status', '!=', 'paid') - Loại bỏ invoices đã thanh toán
                             *   - where() với operator '!=' để so sánh khác
                             *   - 'status' là tên column trong bảng invoices
                             *   - '!=' là operator khác
                             *   - 'paid' là giá trị cần loại bỏ (đã thanh toán)
                             *   - Chỉ lấy invoices chưa thanh toán
                             * 
                             * ->where('status', '!=', 'cancelled') - Loại bỏ invoices đã hủy
                             *   - where() với operator '!=' để so sánh khác
                             *   - 'status' là tên column
                             *   - '!=' là operator khác
                             *   - 'cancelled' là giá trị cần loại bỏ (đã hủy)
                             *   - Chỉ lấy invoices chưa hủy
                             * 
                             * ->get() - Lấy tất cả invoices thỏa mãn điều kiện
                             *   - get() là method của query builder
                             *   - Trả về Collection chứa các Invoice model instances
                             * 
                             * $invoices - Biến lưu Collection chứa invoices cần hủy
                             *   - Sẽ được sử dụng để hủy từng invoice
                             */
                            $invoices = $deposit->invoices()
                                ->where('status', '!=', 'paid')
                                ->where('status', '!=', 'cancelled')
                                ->get();
                            
                            /**
                             * Hủy từng invoice liên quan
                             * 
                             * foreach ($invoices as $invoice) - Loop qua Collection invoices
                             *   - foreach là PHP loop construct
                             *   - $invoices là Collection chứa Invoice instances
                             *   - $invoice là biến lưu từng Invoice instance trong mỗi iteration
                             */
                            foreach ($invoices as $invoice) {
                                /**
                                 * Cập nhật trạng thái invoice thành 'cancelled'
                                 * 
                                 * $invoice->update(['status' => 'cancelled']) - Cập nhật invoice
                                 *   - update() là method của Eloquent model
                                 *   - Array chứa: ['status' => 'cancelled']
                                 *   - Chuyển trạng thái invoice thành đã hủy
                                 *   - Invoice liên quan đến deposit quá hạn cũng sẽ bị hủy
                                 * 
                                 * Lý do: Deposit đã bị hủy, invoice liên quan cũng phải hủy để đảm bảo data consistency
                                 */
                                $invoice->update([
                                    'status' => 'cancelled',
                                ]);
                            }
                            
                            /**
                             * Cập nhật trạng thái unit về 'available' nếu cần
                             * 
                             * $deposit->unit - Lấy relationship unit của deposit
                             *   - unit là relationship trong BookingDeposit model
                             *   - Trả về BelongsTo relationship instance
                             *   - Lazy load unit từ database
                             * 
                             * $unit - Biến lưu Unit model instance (hoặc null)
                             *   - Sẽ được sử dụng để kiểm tra và cập nhật status
                             */
                            $unit = $deposit->unit;
                            
                            /**
                             * Kiểm tra unit có tồn tại và đang ở trạng thái 'reserved' không
                             * 
                             * if ($unit && $unit->status === 'reserved') - Kiểm tra điều kiện
                             *   - $unit: Kiểm tra unit có tồn tại không (không null)
                             *   - &&: AND operator, cả hai điều kiện phải true
                             *   - $unit->status === 'reserved': Kiểm tra status của unit
                             *     - status là property của Unit model
             *     - === là strict comparison operator (so sánh cả type và value)
                             *     - 'reserved' là giá trị cần so sánh (đã đặt cọc)
                             *   - Nếu cả hai điều kiện đều true, vào block if
                             */
                            if ($unit && $unit->status === 'reserved') {
                                /**
                                 * Kiểm tra có deposits khác đang active cho unit này không
                                 * 
                                 * BookingDeposit::where('unit_id', $unit->id) - Filter theo unit_id
                                 *   - where() là method của Eloquent model
                                 *   - 'unit_id' là tên column trong bảng booking_deposits
                                 *   - $unit->id là ID của unit cần kiểm tra
                                 * 
                                 * ->where('id', '!=', $deposit->id) - Loại bỏ deposit hiện tại
                                 *   - where() với operator '!='
                                 *   - 'id' là tên column
                                 *   - '!=' là operator khác
                                 *   - $deposit->id là ID của deposit đang xử lý
                                 *   - Chỉ kiểm tra deposits khác (không phải deposit đang hủy)
                                 * 
                                 * ->whereIn('payment_status', ['pending_approval', 'pending', 'paid']) - Chỉ lấy deposits active
                                 *   - whereIn() là method để kiểm tra column có trong array không
                                 *   - 'payment_status' là tên column
                                 *   - ['pending_approval', 'pending', 'paid'] là array chứa các trạng thái active
                                 *     - 'pending_approval': Chờ duyệt (active)
                                 *     - 'pending': Chờ thanh toán (active)
                                 *     - 'paid': Đã thanh toán (active)
                                 *   - Chỉ lấy deposits ở các trạng thái này (active)
                                 * 
                                 * ->whereNull('deleted_at') - Chỉ lấy deposits chưa bị xóa
                                 *   - whereNull() là method để kiểm tra column là null
                                 *   - 'deleted_at' là tên column cho soft delete
                                 * 
                                 * ->exists() - Kiểm tra có bản ghi nào thỏa mãn điều kiện không
                                 *   - exists() là method của query builder
                                 *   - Trả về true nếu có ít nhất 1 bản ghi
                                 *   - Trả về false nếu không có bản ghi nào
                                 *   - Không load data, chỉ kiểm tra existence (tối ưu performance)
                                 * 
                                 * $hasOtherActiveDeposits - Biến lưu kết quả kiểm tra
                                 *   - true nếu có deposits khác đang active cho unit này
                                 *   - false nếu không có deposits khác active
                                 */
                                $hasOtherActiveDeposits = BookingDeposit::where('unit_id', $unit->id)
                                    ->where('id', '!=', $deposit->id)
                                    ->whereIn('payment_status', ['pending_approval', 'pending', 'paid'])
                                    ->whereNull('deleted_at')
                                    ->exists();
                                
                                /**
                                 * Kiểm tra có leases đang active cho unit này không
                                 * 
                                 * $unit->leases() - Lấy relationship leases của unit
                                 *   - leases() là method relationship trong Unit model
                                 *   - Trả về HasMany relationship instance
                                 *   - Relationship này query từ bảng leases với unit_id
                                 * 
                                 * ->where('status', 'active') - Chỉ lấy leases đang active
                                 *   - where() là method của query builder
                                 *   - 'status' là tên column trong bảng leases
                                 *   - 'active' là giá trị cần tìm (đang hoạt động)
                                 * 
                                 * ->whereNull('deleted_at') - Chỉ lấy leases chưa bị xóa
                                 *   - whereNull() là method để kiểm tra column là null
                                 *   - 'deleted_at' là tên column cho soft delete
                                 * 
                                 * ->exists() - Kiểm tra có lease nào thỏa mãn điều kiện không
                                 *   - exists() là method của query builder
                                 *   - Trả về true nếu có ít nhất 1 lease active
                                 *   - Trả về false nếu không có lease active
                                 * 
                                 * $hasActiveLease - Biến lưu kết quả kiểm tra
                                 *   - true nếu có lease đang active cho unit này
                                 *   - false nếu không có lease active
                                 */
                                $hasActiveLease = $unit->leases()
                                    ->where('status', 'active')
                                    ->whereNull('deleted_at')
                                    ->exists();
                                
                                /**
                                 * Cập nhật unit status về 'available' nếu không có deposits/leases khác active
                                 * 
                                 * if (!$hasOtherActiveDeposits && !$hasActiveLease) - Kiểm tra điều kiện
                                 *   - !$hasOtherActiveDeposits: Không có deposits khác active
                                 *     - ! là NOT operator
                                 *     - Nếu $hasOtherActiveDeposits = false, !false = true
                                 *   - &&: AND operator, cả hai điều kiện phải true
                                 *   - !$hasActiveLease: Không có lease active
                                 *     - ! là NOT operator
                                 *     - Nếu $hasActiveLease = false, !false = true
                                 *   - Nếu cả hai điều kiện đều true, vào block if
                                 * 
                                 * $unit->update(['status' => 'available']) - Cập nhật unit status
                                 *   - update() là method của Eloquent model
                                 *   - Array chứa: ['status' => 'available']
                                 *   - Chuyển trạng thái unit từ 'reserved' về 'available'
                                 *   - Unit không còn được giữ bởi deposit này, có thể cho thuê lại
                                 */
                                if (!$hasOtherActiveDeposits && !$hasActiveLease) {
                                    $unit->update(['status' => 'available']);
                                }
                            }
                            
                            /**
                             * Ghi log khi tự động hủy deposit
                             * 
                             * Log::info() - Ghi log với level INFO
                             *   - Log được ghi vào: storage/logs/laravel.log
                             *   - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
                             * 
                             * 'Booking deposit automatically cancelled due to overdue payment' - Message mô tả sự kiện
                             * 
                             * Array chứa context data:
                             * - 'booking_deposit_id' => $deposit->id - ID của deposit đã hủy
                             * - 'reference_number' => $deposit->reference_number - Số tham chiếu deposit
                             * - 'payment_due_date' => $deposit->payment_due_date - Ngày hết hạn thanh toán
                             * - 'unit_id' => $deposit->unit_id - ID của unit liên quan
                             * 
                             * Log này giúp:
                             * - Theo dõi các deposits tự động hủy
                             * - Audit trail cho auto-cancel actions
                             * - Debug khi có vấn đề
                             */
                            Log::info('Booking deposit automatically cancelled due to overdue payment', [
                                'booking_deposit_id' => $deposit->id,
                                'reference_number' => $deposit->reference_number,
                                'payment_due_date' => $deposit->payment_due_date,
                                'unit_id' => $deposit->unit_id,
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
                         * Log::error() - Ghi log với level ERROR
                         *   - Log được ghi vào: storage/logs/laravel.log
                         *   - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message
                         * 
                         * 'Error auto-cancelling overdue deposits: ' . $e->getMessage() - Message mô tả lỗi
                         *   - $e->getMessage() trả về error message của exception
                         *   - Dấu . là string concatenation operator
                         */
                        Log::error('Error auto-cancelling overdue deposits: ' . $e->getMessage());
                    }
                }
                
                /**
                 * Lưu thời gian check vào Cache (expire sau 1 giờ)
                 * 
                 * Cache::put($lastCheckKey, now()->toDateTimeString(), now()->addHours(1)) - Lưu cache
                 *   - put() là method của Cache facade để lưu giá trị vào cache
                 *   - Tham số 1: $lastCheckKey - Cache key
                 *   - Tham số 2: now()->toDateTimeString() - Giá trị cần lưu
                 *     - now() là helper function của Laravel trả về Carbon instance
                 *     - toDateTimeString() là method của Carbon để format thành string datetime
                 *     - Format: 'YYYY-MM-DD HH:MM:SS' (ví dụ: '2025-01-15 10:30:00')
                 *   - Tham số 3: now()->addHours(1) - Thời gian expire
                 *     - addHours(1) là method của Carbon để thêm 1 giờ
                 *     - Cache sẽ expire sau 1 giờ
                 * 
                 * Mục đích: Lưu thời gian check để tránh chạy check quá thường xuyên
                 * - Check sẽ chỉ chạy lại sau ít nhất 5 phút
                 * - Cache expire sau 1 giờ (đảm bảo không lưu quá lâu)
                 */
                Cache::put($lastCheckKey, now()->toDateTimeString(), now()->addHours(1));
            } catch (\Exception $e) {
                /**
                 * Ghi log lỗi khi check overdue deposits
                 * 
                 * Log::error() - Ghi log với level ERROR
                 *   - Log được ghi vào: storage/logs/laravel.log
                 * 
                 * 'Error checking overdue deposits: ' . $e->getMessage() - Message mô tả lỗi
                 *   - $e->getMessage() trả về error message của exception
                 * 
                 * Lưu ý: Lỗi này không block request, chỉ ghi log để debug
                 */
                Log::error('Error checking overdue deposits: ' . $e->getMessage());
            }
        }

        /**
         * Build query tối ưu với JOINs và proper index order
         * 
         * QUERY OPTIMIZATION:
         * - Sử dụng JOINs để lấy related data trong một query (tránh N+1 queries)
         * - Sử dụng indexes: idx_bd_organization_id, idx_units_deleted_at_property, idx_properties_deleted_at_org
         * - Select chỉ các columns cần thiết
         * - LEFT JOIN cho optional relationships (properties, tenants, leads)
         */
        
        /**
         * Tạo query builder với select columns cụ thể
         * 
         * BookingDeposit::select([...]) - Tạo query builder và select columns cụ thể
         *   - select() là method của Eloquent model để chỉ định columns cần select
         *   - Tham số là array chứa tên columns
         *   - Tối ưu performance bằng cách chỉ select columns cần thiết
         * 
         * Array chứa columns:
         * - 'booking_deposits.*' - Tất cả columns từ bảng booking_deposits
         *   - * là wildcard để select tất cả columns
         *   - booking_deposits là table alias (sẽ được set sau)
         * - 'units.code as unit_code' - Code của unit (từ bảng units)
         *   - units.code là column từ bảng units
         *   - as unit_code là alias để tránh conflict với columns khác
         * - 'properties.name as property_name' - Tên property (từ bảng properties)
         * - 'tenant_profiles.full_name as tenant_name' - Tên tenant (từ bảng user_profiles)
         * - 'leads.name as lead_name' - Tên lead (từ bảng leads)
         * 
         * $query - Biến lưu query builder instance
         *   - Sẽ được sử dụng để thêm JOINs, WHERE clauses, và filters
         */
        $query = BookingDeposit::select([
            'booking_deposits.*',
            'units.code as unit_code',
            'properties.name as property_name',
            'tenant_profiles.full_name as tenant_name',
            'leads.name as lead_name'
        ])
        /**
         * INNER JOIN với bảng units
         * 
         * ->join('units', 'booking_deposits.unit_id', '=', 'units.id') - INNER JOIN
         *   - join() là method của query builder để thêm INNER JOIN
         *   - Tham số 1: 'units' - Tên bảng cần JOIN
         *   - Tham số 2: 'booking_deposits.unit_id' - Column từ bảng chính (booking_deposits)
         *   - Tham số 3: '=' - JOIN operator (equals)
         *   - Tham số 4: 'units.id' - Column từ bảng được JOIN (units)
         *   - INNER JOIN: Chỉ lấy booking deposits có unit (bắt buộc)
         *   - Sử dụng index: idx_units_id (primary key)
         */
        ->join('units', 'booking_deposits.unit_id', '=', 'units.id')
        /**
         * LEFT JOIN với bảng properties
         * 
         * ->leftJoin('properties', 'units.property_id', '=', 'properties.id') - LEFT JOIN
         *   - leftJoin() là method của query builder để thêm LEFT JOIN
         *   - Tham số 1: 'properties' - Tên bảng cần JOIN
         *   - Tham số 2: 'units.property_id' - Column từ bảng units
         *   - Tham số 3: '=' - JOIN operator
         *   - Tham số 4: 'properties.id' - Column từ bảng properties
         *   - LEFT JOIN: Lấy booking deposits ngay cả khi không có property (optional)
         *   - Sử dụng index: idx_properties_id (primary key)
         */
        ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
        /**
         * LEFT JOIN với bảng users (tenant users)
         * 
         * ->leftJoin('users as tenant_users', 'booking_deposits.tenant_user_id', '=', 'tenant_users.id') - LEFT JOIN
         *   - leftJoin() là method của query builder
         *   - Tham số 1: 'users as tenant_users' - Tên bảng với alias
         *     - 'users' là tên bảng
         *     - 'as tenant_users' là alias để tránh conflict với users table khác
         *   - Tham số 2: 'booking_deposits.tenant_user_id' - Column từ booking_deposits
         *   - Tham số 3: '=' - JOIN operator
         *   - Tham số 4: 'tenant_users.id' - Column từ bảng users (với alias)
         *   - LEFT JOIN: Lấy booking deposits ngay cả khi không có tenant_user_id (optional)
         */
        ->leftJoin('users as tenant_users', 'booking_deposits.tenant_user_id', '=', 'tenant_users.id')
        /**
         * LEFT JOIN với bảng user_profiles (tenant profiles)
         * 
         * ->leftJoin('user_profiles as tenant_profiles', 'tenant_users.id', '=', 'tenant_profiles.user_id') - LEFT JOIN
         *   - leftJoin() là method của query builder
         *   - Tham số 1: 'user_profiles as tenant_profiles' - Tên bảng với alias
         *     - 'user_profiles' là tên bảng
         *     - 'as tenant_profiles' là alias để tránh conflict
         *   - Tham số 2: 'tenant_users.id' - Column từ bảng users (đã JOIN ở trên)
         *   - Tham số 3: '=' - JOIN operator
         *   - Tham số 4: 'tenant_profiles.user_id' - Column từ bảng user_profiles
         *   - LEFT JOIN: Lấy tenant name ngay cả khi không có user_profile (optional)
         */
        ->leftJoin('user_profiles as tenant_profiles', 'tenant_users.id', '=', 'tenant_profiles.user_id')
        /**
         * LEFT JOIN với bảng leads
         * 
         * ->leftJoin('leads', 'booking_deposits.lead_id', '=', 'leads.id') - LEFT JOIN
         *   - leftJoin() là method của query builder
         *   - Tham số 1: 'leads' - Tên bảng
         *   - Tham số 2: 'booking_deposits.lead_id' - Column từ booking_deposits
         *   - Tham số 3: '=' - JOIN operator
         *   - Tham số 4: 'leads.id' - Column từ bảng leads
         *   - LEFT JOIN: Lấy booking deposits ngay cả khi không có lead_id (optional)
         */
        ->leftJoin('leads', 'booking_deposits.lead_id', '=', 'leads.id')
        /**
         * Filter theo organization_id (sử dụng index)
         * 
         * ->where('booking_deposits.organization_id', $organizationId) - Thêm WHERE clause
         *   - where() là method của query builder để thêm điều kiện WHERE
         *   - 'booking_deposits.organization_id' là tên column (với table prefix)
         *   - $organizationId là giá trị cần so sánh
         *   - Sử dụng index: idx_bd_organization_id (đã được tạo để tối ưu query này)
         *   - Chỉ lấy booking deposits của organization hiện tại
         */
        ->where('booking_deposits.organization_id', $organizationId) // Uses idx_bd_organization_id
        /**
         * Filter chỉ lấy booking deposits chưa bị xóa (soft delete)
         * 
         * ->whereNull('booking_deposits.deleted_at') - Thêm WHERE clause
         *   - whereNull() là method của query builder để kiểm tra column là null
         *   - 'booking_deposits.deleted_at' là tên column (với table prefix)
         *   - null nghĩa là chưa bị xóa (soft delete)
         *   - Chỉ lấy booking deposits chưa bị xóa
         */
        ->whereNull('booking_deposits.deleted_at');
        
        /**
         * Tự động filter theo ownership nếu agent chỉ có view_own
         * 
         * OWNERSHIP FILTERING LOGIC:
         * - Manager có view_all: Không filter (xem tất cả)
         * - Agent có view_own: Chỉ xem booking deposits của chính mình (agent_id = user_id)
         * - Agent không có view_own: Filter theo assigned properties (backward compatibility)
         */
        
        /**
         * Kiểm tra có cần filter theo ownership không
         * 
         * $this->shouldFilterByOwnership('contract.booking_deposit') - Kiểm tra có cần filter không
         *   - shouldFilterByOwnership() là method từ FiltersByOwnership trait
         *   - Tham số: 'contract.booking_deposit' - Capability key để check view_own
         *   - Method này sẽ:
         *     1. Kiểm tra user có capability 'contract.booking_deposit.view_own' không
         *     2. Trả về true nếu user chỉ có view_own (cần filter)
         *     3. Trả về false nếu user có view_all (không cần filter)
         * 
         * if ($this->shouldFilterByOwnership(...)) - Kiểm tra điều kiện
         *   - Nếu true, vào block if để filter theo agent_id
         */
        if ($this->shouldFilterByOwnership('contract.booking_deposit')) {
            /**
             * Filter chỉ lấy booking deposits của chính agent (view_own)
             * 
             * $query->where('booking_deposits.agent_id', $user->id) - Thêm WHERE clause
             *   - where() là method của query builder
             *   - 'booking_deposits.agent_id' là tên column
             *   - $user->id là ID của user hiện tại (agent)
             *   - Chỉ lấy booking deposits mà agent này là người tạo/quản lý
             * 
             * Lý do: Agent với view_own chỉ có thể xem booking deposits của chính mình
             */
            $query->where('booking_deposits.agent_id', $user->id);
        } elseif (!$canViewAll) {
            /**
             * Fallback: Filter theo assigned properties (backward compatibility)
             * 
             * Logic này dành cho agent không có view_own capability
             * - Agent sẽ chỉ xem booking deposits của các properties được assign
             * - Đây là backward compatibility cho các agent cũ
             */
            
            /**
             * Lấy danh sách property IDs được assign cho agent
             * 
             * $user->assignedProperties() - Lấy relationship assignedProperties của user
             *   - assignedProperties() là method relationship trong User model
             *   - Trả về BelongsToMany relationship instance
             *   - Relationship này query từ bảng property_user (pivot table)
             * 
             * ->pluck('properties.id') - Lấy chỉ column 'properties.id'
             *   - pluck() là method của query builder để lấy một column
             *   - 'properties.id' là tên column cần lấy (với table prefix)
             *   - Trả về Collection chứa các property IDs
             * 
             * $assignedPropertyIds - Biến lưu Collection chứa property IDs
             *   - Sẽ được sử dụng để filter booking deposits theo properties
             */
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            
            /**
             * Kiểm tra agent có properties được assign không
             * 
             * if ($assignedPropertyIds->isEmpty()) - Kiểm tra Collection có rỗng không
             *   - isEmpty() là method của Collection để kiểm tra có rỗng không
             *   - Trả về true nếu Collection rỗng (không có properties)
             *   - Trả về false nếu Collection có phần tử
             * 
             * Nếu agent không có properties được assign:
             * - Trả về view với empty data (không có booking deposits để hiển thị)
             */
            if ($assignedPropertyIds->isEmpty()) {
                /**
                 * Trả về view với empty data
                 * 
                 * view('staff.contract.booking-deposits.index', [...]) - Tạo view response
                 *   - view() là helper function của Laravel
                 *   - 'staff.contract.booking-deposits.index' là path đến view file
                 *   - Array chứa data với empty collections
                 * 
                 * Array chứa:
                 * - 'bookingDeposits' => collect() - Empty collection cho booking deposits
                 *   - collect() là helper function của Laravel để tạo empty Collection
                 * - 'properties' => collect() - Empty collection cho properties
                 * - 'agents' => collect() - Empty collection cho agents
                 * 
                 * View sẽ hiển thị empty state (không có dữ liệu)
                 */
                return view('staff.contract.booking-deposits.index', [
                    'bookingDeposits' => collect(),
                    'properties' => collect(),
                    'agents' => collect()
                ]);
            }
            
            /**
             * Filter booking deposits theo assigned properties
             * 
             * $query->whereIn('properties.id', $assignedPropertyIds) - Thêm WHERE IN clause
             *   - whereIn() là method của query builder để kiểm tra column có trong array không
             *   - 'properties.id' là tên column (từ bảng properties đã JOIN)
             *   - $assignedPropertyIds là Collection chứa property IDs
             *   - Chỉ lấy booking deposits của các properties được assign cho agent
             * 
             * Lý do: Agent chỉ có thể xem booking deposits của properties được assign
             */
            $query->whereIn('properties.id', $assignedPropertyIds);
        }
        
        /**
         * Filter chỉ lấy units và properties chưa bị xóa (soft delete)
         * 
         * $query->whereNull('units.deleted_at') - Filter units chưa bị xóa
         *   - whereNull() là method của query builder
         *   - 'units.deleted_at' là tên column từ bảng units (đã JOIN)
         *   - Sử dụng index: idx_units_deleted_at_property (đã được tạo để tối ưu)
         *   - Chỉ lấy booking deposits của units chưa bị xóa
         * 
         * ->whereNull('properties.deleted_at') - Filter properties chưa bị xóa
         *   - whereNull() là method của query builder
         *   - 'properties.deleted_at' là tên column từ bảng properties (đã JOIN)
         *   - Sử dụng index: idx_properties_deleted_at_org (đã được tạo để tối ưu)
         *   - Chỉ lấy booking deposits của properties chưa bị xóa
         */
        $query->whereNull('units.deleted_at') // Uses idx_units_deleted_at_property
              ->whereNull('properties.deleted_at'); // Uses idx_properties_deleted_at_org

        /**
         * Tính statistics từ base query (trước khi apply filters)
         * 
         * QUERY OPTIMIZATION:
         * - Tính statistics từ base query để đảm bảo accuracy
         * - Sử dụng clone query để tính nhiều statistics mà không ảnh hưởng đến query chính
         * - Query trực tiếp từ BookingDeposit model (không qua JOINs) để tối ưu performance
         */
        
        /**
         * Tạo base query để tính statistics
         * 
         * BookingDeposit::where('organization_id', $organizationId) - Filter theo organization
         *   - where() là method của Eloquent model
         *   - 'organization_id' là tên column
         *   - $organizationId là organization ID
         *   - Chỉ tính statistics cho booking deposits của organization hiện tại
         * 
         * ->whereNull('deleted_at') - Chỉ lấy deposits chưa bị xóa
         *   - whereNull() là method của query builder
         *   - 'deleted_at' là tên column cho soft delete
         *   - Chỉ tính statistics cho deposits chưa bị xóa
         * 
         * $statsQuery - Biến lưu query builder instance
         *   - Sẽ được sử dụng để tính các statistics khác nhau
         *   - Sử dụng clone để tạo query mới cho mỗi statistic (không ảnh hưởng query gốc)
         */
        $statsQuery = BookingDeposit::where('organization_id', $organizationId)
            ->whereNull('deleted_at');
        
        /**
         * Filter statistics query cho agent (chỉ đếm deposits của assigned properties)
         * 
         * if (!$canViewAll) - Kiểm tra user có quyền xem tất cả không
         *   - ! là NOT operator
         *   - Nếu $canViewAll = false (agent), vào block if
         *   - Nếu $canViewAll = true (manager), không vào block if
         */
        if (!$canViewAll) {
            /**
             * Lấy danh sách property IDs được assign cho agent
             * 
             * $user->assignedProperties()->pluck('properties.id') - Lấy property IDs
             *   - assignedProperties() là relationship trong User model
             *   - pluck('properties.id') lấy chỉ column 'properties.id'
             *   - Trả về Collection chứa property IDs
             * 
             * $assignedPropertyIds - Biến lưu Collection chứa property IDs
             */
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            
            /**
             * Kiểm tra agent có properties được assign không
             * 
             * if ($assignedPropertyIds->isEmpty()) - Kiểm tra Collection có rỗng không
             *   - isEmpty() là method của Collection
             *   - Trả về true nếu Collection rỗng
             * 
             * $statsQuery->whereRaw('1 = 0') - Thêm điều kiện luôn false
             *   - whereRaw() là method để thêm raw SQL condition
             *   - '1 = 0' là điều kiện luôn false
             *   - Kết quả: Query sẽ không trả về bản ghi nào (0 results)
             *   - Mục đích: Statistics sẽ là 0 cho tất cả các trạng thái
             */
            if ($assignedPropertyIds->isEmpty()) {
                $statsQuery->whereRaw('1 = 0'); // No results
            } else {
                /**
                 * Filter statistics query theo assigned properties
                 * 
                 * $statsQuery->whereHas('unit.property', function($q) use ($assignedPropertyIds) {...}) - Filter theo relationship
                 *   - whereHas() là method để filter dựa trên relationship
                 *   - 'unit.property' là nested relationship path
                 *     - unit: Relationship từ BookingDeposit đến Unit
                 *     - property: Relationship từ Unit đến Property
                 *   - Closure function nhận query builder $q làm tham số
                 *   - use ($assignedPropertyIds): Import biến từ scope ngoài vào closure
                 * 
                 * function($q) use ($assignedPropertyIds) {...} - Closure function
                 *   - $q là query builder cho relationship query
                 *   - $assignedPropertyIds là biến được import từ scope ngoài
                 * 
                 * $q->whereIn('properties.id', $assignedPropertyIds) - Filter properties
                 *   - whereIn() là method để kiểm tra column có trong array không
                 *   - 'properties.id' là tên column
                 *   - $assignedPropertyIds là Collection chứa property IDs
                 *   - Chỉ đếm deposits của properties được assign cho agent
                 */
                $statsQuery->whereHas('unit.property', function($q) use ($assignedPropertyIds) {
                    $q->whereIn('properties.id', $assignedPropertyIds);
                });
            }
        }
        
        /**
         * Tính statistics bằng database aggregation (tối ưu performance)
         * 
         * STATISTICS CALCULATION:
         * - Sử dụng clone query để tính nhiều statistics mà không ảnh hưởng query gốc
         * - Sử dụng database aggregation (count) thay vì load data vào memory
         * - Tối ưu performance cho large datasets
         */
        
        /**
         * Tạo array chứa statistics
         * 
         * $stats - Associative array chứa các statistics
         *   - Mỗi statistic được tính bằng cách clone $statsQuery và thêm filter
         *   - (int) cast để đảm bảo giá trị là integer
         */
        $stats = [
            /**
             * Tổng số booking deposits
             * 
             * (clone $statsQuery)->count() - Đếm tổng số deposits
             *   - clone là PHP keyword để tạo copy của object
             *   - $statsQuery là query builder instance
             *   - clone tạo query builder mới (không ảnh hưởng $statsQuery gốc)
             *   - count() là method của query builder để đếm số bản ghi
             *   - Trả về integer (số lượng deposits)
             *   - (int) cast để đảm bảo type là integer
             * 
             * 'total' => ... - Key trong array
             *   - Lưu tổng số booking deposits
             */
            'total' => (int) (clone $statsQuery)->count(),
            /**
             * Số deposits đang chờ duyệt
             * 
             * (clone $statsQuery)->where('payment_status', 'pending_approval')->count() - Đếm deposits chờ duyệt
             *   - clone tạo query builder mới
             *   - where('payment_status', 'pending_approval') thêm filter cho trạng thái 'pending_approval'
             *   - count() đếm số bản ghi thỏa mãn điều kiện
             *   - Trả về integer
             */
            'pending_approval' => (int) (clone $statsQuery)->where('payment_status', 'pending_approval')->count(),
            /**
             * Số deposits đang chờ thanh toán
             * 
             * (clone $statsQuery)->where('payment_status', 'pending')->count() - Đếm deposits chờ thanh toán
             *   - clone tạo query builder mới
             *   - where('payment_status', 'pending') thêm filter cho trạng thái 'pending'
             *   - count() đếm số bản ghi
             */
            'pending' => (int) (clone $statsQuery)->where('payment_status', 'pending')->count(),
            /**
             * Số deposits đã thanh toán
             * 
             * (clone $statsQuery)->where('payment_status', 'paid')->count() - Đếm deposits đã thanh toán
             *   - clone tạo query builder mới
             *   - where('payment_status', 'paid') thêm filter cho trạng thái 'paid'
             *   - count() đếm số bản ghi
             */
            'paid' => (int) (clone $statsQuery)->where('payment_status', 'paid')->count(),
            /**
             * Số deposits đã hủy
             * 
             * (clone $statsQuery)->where('payment_status', 'cancelled')->count() - Đếm deposits đã hủy
             *   - clone tạo query builder mới
             *   - where('payment_status', 'cancelled') thêm filter cho trạng thái 'cancelled'
             *   - count() đếm số bản ghi
             */
            'cancelled' => (int) (clone $statsQuery)->where('payment_status', 'cancelled')->count(),
            /**
             * Số deposits đã hoàn tiền
             * 
             * (clone $statsQuery)->where('payment_status', 'refunded')->count() - Đếm deposits đã hoàn tiền
             *   - clone tạo query builder mới
             *   - where('payment_status', 'refunded') thêm filter cho trạng thái 'refunded'
             *   - count() đếm số bản ghi
             */
            'refunded' => (int) (clone $statsQuery)->where('payment_status', 'refunded')->count(),
            /**
             * Số deposits đã hết hạn
             * 
             * (clone $statsQuery)->where('payment_status', 'expired')->count() - Đếm deposits đã hết hạn
             *   - clone tạo query builder mới
             *   - where('payment_status', 'expired') thêm filter cho trạng thái 'expired'
             *   - count() đếm số bản ghi
             */
            'expired' => (int) (clone $statsQuery)->where('payment_status', 'expired')->count(),
            /**
             * Số deposits đã thanh toán nhưng chưa có hợp đồng (tạm thời = 0, sẽ tính sau)
             * 
             * 'paid_without_lease' => 0 - Khởi tạo giá trị 0
             *   - Statistic này cần tính riêng vì phải kiểm tra relationship với lease
             *   - Sẽ được tính sau bằng cách load deposits và kiểm tra lease
             */
            'paid_without_lease' => 0,
        ];
        
        /**
         * Tính statistic 'paid_without_lease' - deposits đã thanh toán nhưng chưa có hợp đồng
         * 
         * LUỒNG XỬ LÝ:
         * 1. Lấy tất cả deposits đã thanh toán (payment_status = 'paid')
         * 2. Eager load relationship 'lease' để tránh N+1 queries
         * 3. Duyệt qua từng deposit và kiểm tra có active lease không
         * 4. Đếm số deposits không có active lease
         */
        
        /**
         * Lấy tất cả deposits đã thanh toán với relationship lease
         * 
         * (clone $statsQuery)->where('payment_status', 'paid') - Filter chỉ lấy deposits đã thanh toán
         *   - clone tạo query builder mới
         *   - where('payment_status', 'paid') thêm filter cho trạng thái 'paid'
         * 
         * ->with('lease') - Eager load relationship lease
         *   - with() là method của Eloquent để eager load relationships
         *   - 'lease' là tên relationship trong BookingDeposit model
         *   - Eager load để tránh N+1 queries (load tất cả leases trong một query)
         * 
         * ->get() - Lấy tất cả deposits thỏa mãn điều kiện
         *   - get() là method của query builder
         *   - Trả về Collection chứa các BookingDeposit model instances
         *   - Mỗi deposit đã có relationship 'lease' được load sẵn
         * 
         * $paidDeposits - Biến lưu Collection chứa deposits đã thanh toán
         *   - Sẽ được sử dụng để kiểm tra có active lease không
         */
        $paidDeposits = (clone $statsQuery)->where('payment_status', 'paid')
            ->with('lease')
            ->get();
        
        /**
         * Duyệt qua từng deposit và kiểm tra có active lease không
         * 
         * foreach ($paidDeposits as $deposit) - Loop qua Collection
         *   - foreach là PHP loop construct
         *   - $paidDeposits là Collection chứa BookingDeposit instances
         *   - $deposit là biến lưu từng BookingDeposit instance trong mỗi iteration
         */
        foreach ($paidDeposits as $deposit) {
            /**
             * Kiểm tra deposit có active lease không
             * 
             * $deposit->lease - Lấy relationship lease của deposit
             *   - lease là relationship trong BookingDeposit model
             *   - Trả về Lease model instance (hoặc null nếu không có)
             *   - Đã được eager load ở trên (không cần query thêm)
             * 
             * $deposit->lease && ... - Kiểm tra lease có tồn tại không
             *   - && là AND operator (short-circuit evaluation)
             *   - Nếu $deposit->lease = null, không kiểm tra điều kiện sau
             * 
             * $deposit->lease->status === 'active' - Kiểm tra lease có status 'active' không
             *   - status là property của Lease model
             *   - === là strict comparison operator
             *   - 'active' là giá trị cần so sánh
             * 
             * !$deposit->lease->deleted_at - Kiểm tra lease chưa bị xóa
             *   - ! là NOT operator
             *   - deleted_at là property cho soft delete
             *   - null nghĩa là chưa bị xóa
             * 
             * $hasActiveLease - Biến lưu kết quả kiểm tra
             *   - true nếu deposit có active lease
             *   - false nếu deposit không có active lease
             */
            $hasActiveLease = $deposit->lease && $deposit->lease->status === 'active' && !$deposit->lease->deleted_at;
            
            /**
             * Tăng counter nếu deposit không có active lease
             * 
             * if (!$hasActiveLease) - Kiểm tra deposit không có active lease
             *   - ! là NOT operator
             *   - Nếu $hasActiveLease = false, !false = true, vào block if
             * 
             * $stats['paid_without_lease']++ - Tăng counter
             *   - ++ là increment operator
             *   - Tăng giá trị của $stats['paid_without_lease'] lên 1
             *   - Đếm số deposits đã thanh toán nhưng chưa có hợp đồng
             */
            if (!$hasActiveLease) {
                $stats['paid_without_lease']++;
            }
        }

        /**
         * Eager load relationships để tránh N+1 queries
         * 
         * QUERY OPTIMIZATION:
         * - Eager loading giúp load tất cả related data trong một query thay vì nhiều queries
         * - Tránh N+1 query problem (1 query cho deposits + N queries cho relationships)
         * - Tối ưu performance cho large datasets
         */
        
        /**
         * Eager load các relationships cần thiết
         * 
         * $query->with([...]) - Eager load relationships
         *   - with() là method của Eloquent để eager load relationships
         *   - Tham số là array chứa tên relationships
         *   - Tất cả relationships sẽ được load trong một query (sử dụng JOINs hoặc separate queries)
         * 
         * Array chứa relationships:
         * - 'unit.property' - Nested relationship: unit -> property
         *   - unit: Relationship từ BookingDeposit đến Unit
         *   - property: Relationship từ Unit đến Property
         *   - Load cả unit và property của unit đó
         * - 'tenantUser.userProfile' - Nested relationship: tenantUser -> userProfile
         *   - tenantUser: Relationship từ BookingDeposit đến User (tenant)
         *   - userProfile: Relationship từ User đến UserProfile
         *   - Load cả tenant user và profile của tenant
         * - 'lead' - Relationship từ BookingDeposit đến Lead
         *   - Load lead liên quan đến deposit
         * - 'agent.userProfile' - Nested relationship: agent -> userProfile
         *   - agent: Relationship từ BookingDeposit đến User (agent)
         *   - userProfile: Relationship từ User đến UserProfile
         *   - Load cả agent và profile của agent
         * - 'viewing' - Relationship từ BookingDeposit đến Viewing
         *   - Load viewing liên quan đến deposit
         * - 'lease' => function($q) {...} - Relationship với constraint
         *   - lease: Relationship từ BookingDeposit đến Lease
         *   - Closure function để thêm constraint cho relationship query
         *   - function($q) {...}: Closure nhận query builder $q
         *     - $q->where('status', 'active'): Chỉ load leases có status 'active'
         *     - $q->whereNull('deleted_at'): Chỉ load leases chưa bị xóa
         *   - Chỉ load active leases (không load expired/cancelled leases)
         */
        $query->with([
            'unit.property',
            'tenantUser.userProfile',
            'lead',
            'agent.userProfile',
            'viewing',
            'lease' => function($q) {
                $q->where('status', 'active')->whereNull('deleted_at');
            }
        ]);

        /**
         * Tìm kiếm booking deposits
         * 
         * SEARCH FUNCTIONALITY:
         * - Tìm kiếm trong nhiều fields: reference_number, notes, tenant info, lead info, property name
         * - Sử dụng LIKE với wildcard % để tìm kiếm partial match
         * - Tất cả điều kiện tìm kiếm được kết hợp bằng OR (tìm trong bất kỳ field nào)
         */
        
        /**
         * Kiểm tra request có search parameter không
         * 
         * $request->filled('search') - Kiểm tra field 'search' có giá trị không
         *   - filled() là method của Request để kiểm tra field có giá trị và không rỗng
         *   - 'search' là tên field trong request
         *   - Trả về true nếu field có giá trị (không null, không empty string)
         *   - Trả về false nếu field không có hoặc rỗng
         * 
         * if ($request->filled('search')) - Kiểm tra điều kiện
         *   - Nếu có search parameter, vào block if để thêm search conditions
         */
        if ($request->filled('search')) {
            /**
             * Lấy search term từ request
             * 
             * $request->search - Lấy giá trị của field 'search'
             *   - search là property của Request (magic property)
             *   - Tương đương với $request->input('search')
             *   - Trả về string chứa search term
             * 
             * $search - Biến lưu search term
             *   - Sẽ được sử dụng trong LIKE queries
             */
            $search = $request->search;
            
            /**
             * Thêm search conditions vào query
             * 
             * $query->where(function($q) use ($search) {...}) - Thêm WHERE clause với closure
             *   - where() với closure tạo một nhóm điều kiện WHERE
             *   - function($q) use ($search): Closure nhận query builder $q và import $search từ scope ngoài
             *   - use ($search): Import biến $search vào closure scope
             *   - Tất cả điều kiện trong closure sẽ được kết hợp bằng OR
             * 
             * $q->where(...) - Thêm điều kiện tìm kiếm
             *   - $q là query builder instance trong closure
             *   - where() với 'like' operator để tìm kiếm partial match
             *   - "%{$search}%" là pattern với wildcard % ở đầu và cuối
             *     - % là SQL wildcard để match bất kỳ ký tự nào
             *     - {$search} là search term (string interpolation)
             *     - Ví dụ: Nếu $search = 'ABC', pattern sẽ là '%ABC%'
             *     - Tìm kiếm 'ABC' ở bất kỳ vị trí nào trong string
             * 
             * Các fields được tìm kiếm:
             * - 'booking_deposits.reference_number': Số tham chiếu deposit
             * - 'booking_deposits.notes': Ghi chú deposit
             * - 'tenant_users.full_name': Tên tenant (từ bảng users đã JOIN)
             * - 'tenant_users.phone': Số điện thoại tenant
             * - 'tenant_users.email': Email tenant
             * - 'leads.name': Tên lead
             * - 'leads.phone': Số điện thoại lead
             * - 'leads.email': Email lead
             * - 'properties.name': Tên property
             * 
             * ->orWhere(...) - Thêm điều kiện OR
             *   - orWhere() là method để thêm điều kiện OR
             *   - Tất cả các orWhere() sẽ được kết hợp bằng OR
             *   - Tìm kiếm trong bất kỳ field nào thỏa mãn điều kiện
             */
            $query->where(function($q) use ($search) {
                $q->where('booking_deposits.reference_number', 'like', "%{$search}%")
                  ->orWhere('booking_deposits.notes', 'like', "%{$search}%")
                  ->orWhere('tenant_users.full_name', 'like', "%{$search}%")
                  ->orWhere('tenant_users.phone', 'like', "%{$search}%")
                  ->orWhere('tenant_users.email', 'like', "%{$search}%")
                  ->orWhere('leads.name', 'like', "%{$search}%")
                  ->orWhere('leads.phone', 'like', "%{$search}%")
                  ->orWhere('leads.email', 'like', "%{$search}%")
                  ->orWhere('properties.name', 'like', "%{$search}%");
            });
        }

        /**
         * Filter theo payment status
         * 
         * if ($request->filled('payment_status')) - Kiểm tra có payment_status parameter không
         *   - filled() là method của Request
         *   - 'payment_status' là tên field trong request
         *   - Trả về true nếu field có giá trị
         * 
         * $query->where('booking_deposits.payment_status', $request->payment_status) - Thêm WHERE clause
         *   - where() là method của query builder
         *   - 'booking_deposits.payment_status' là tên column
         *   - $request->payment_status là giá trị từ request (ví dụ: 'pending', 'paid', 'cancelled')
         *   - Chỉ lấy deposits có payment_status khớp với giá trị từ request
         */
        if ($request->filled('payment_status')) {
            $query->where('booking_deposits.payment_status', $request->payment_status);
        }

        /**
         * Filter deposits đã thanh toán nhưng chưa có hợp đồng
         * 
         * if ($request->filled('paid_without_lease') && $request->paid_without_lease == '1') - Kiểm tra điều kiện
         *   - filled('paid_without_lease'): Kiểm tra field có giá trị không
         *   - &&: AND operator
         *   - $request->paid_without_lease == '1': Kiểm tra giá trị là '1' (string)
         *     - == là loose comparison operator (so sánh value, không so sánh type)
         *     - '1' là string '1' (từ query parameter)
         *   - Nếu cả hai điều kiện đều true, vào block if
         * 
         * $query->where('booking_deposits.payment_status', 'paid') - Filter chỉ lấy deposits đã thanh toán
         *   - where() là method của query builder
         *   - 'booking_deposits.payment_status' là tên column
         *   - 'paid' là giá trị cần tìm (đã thanh toán)
         * 
         * ->whereDoesntHave('lease', function($q) {...}) - Filter deposits không có active lease
         *   - whereDoesntHave() là method để filter dựa trên relationship không tồn tại
         *   - 'lease' là tên relationship trong BookingDeposit model
         *   - Closure function nhận query builder $q làm tham số
         *   - function($q) {...}: Closure để thêm constraint cho relationship query
         *     - $q->where('status', 'active'): Chỉ kiểm tra leases có status 'active'
         *     - $q->whereNull('deleted_at'): Chỉ kiểm tra leases chưa bị xóa
         *   - Kết quả: Chỉ lấy deposits đã thanh toán và không có active lease
         */
        if ($request->filled('paid_without_lease') && $request->paid_without_lease == '1') {
            $query->where('booking_deposits.payment_status', 'paid')
                ->whereDoesntHave('lease', function($q) {
                    $q->where('status', 'active')->whereNull('deleted_at');
                });
        }

        /**
         * Filter theo deposit type
         * 
         * if ($request->filled('deposit_type')) - Kiểm tra có deposit_type parameter không
         *   - filled() là method của Request
         * 
         * $query->where('booking_deposits.deposit_type', $request->deposit_type) - Thêm WHERE clause
         *   - where() là method của query builder
         *   - 'booking_deposits.deposit_type' là tên column
         *   - $request->deposit_type là giá trị từ request (ví dụ: 'booking', 'security', 'advance')
         *   - Chỉ lấy deposits có deposit_type khớp với giá trị từ request
         */
        if ($request->filled('deposit_type')) {
            $query->where('booking_deposits.deposit_type', $request->deposit_type);
        }

        /**
         * Filter theo property
         * 
         * if ($request->filled('property_id')) - Kiểm tra có property_id parameter không
         *   - filled() là method của Request
         * 
         * $query->where('properties.id', $request->property_id) - Thêm WHERE clause
         *   - where() là method của query builder
         *   - 'properties.id' là tên column từ bảng properties (đã JOIN)
         *   - $request->property_id là property ID từ request (integer)
         *   - Chỉ lấy deposits của property cụ thể
         */
        if ($request->filled('property_id')) {
            $query->where('properties.id', $request->property_id);
        }

        /**
         * Filter theo tenant_id
         * 
         * if ($request->filled('tenant_id')) - Kiểm tra có tenant_id parameter không
         *   - filled() là method của Request
         * 
         * $query->where('booking_deposits.tenant_user_id', $request->tenant_id) - Thêm WHERE clause
         *   - where() là method của query builder
         *   - 'booking_deposits.tenant_user_id' là tên column
         *   - $request->tenant_id là tenant user ID từ request (integer)
         *   - Chỉ lấy deposits của tenant cụ thể
         */
        if ($request->filled('tenant_id')) {
            $query->where('booking_deposits.tenant_user_id', $request->tenant_id);
        }

        /**
         * Filter theo lead_id
         * 
         * if ($request->filled('lead_id')) - Kiểm tra có lead_id parameter không
         *   - filled() là method của Request
         * 
         * $query->where('booking_deposits.lead_id', $request->lead_id) - Thêm WHERE clause
         *   - where() là method của query builder
         *   - 'booking_deposits.lead_id' là tên column
         *   - $request->lead_id là lead ID từ request (integer)
         *   - Chỉ lấy deposits của lead cụ thể
         */
        if ($request->filled('lead_id')) {
            $query->where('booking_deposits.lead_id', $request->lead_id);
        }

        /**
         * Filter theo viewing_id
         * 
         * if ($request->filled('viewing_id')) - Kiểm tra có viewing_id parameter không
         *   - filled() là method của Request
         * 
         * $query->where('booking_deposits.viewing_id', $request->viewing_id) - Thêm WHERE clause
         *   - where() là method của query builder
         *   - 'booking_deposits.viewing_id' là tên column
         *   - $request->viewing_id là viewing ID từ request (integer)
         *   - Chỉ lấy deposits của viewing cụ thể
         */
        if ($request->filled('viewing_id')) {
            $query->where('booking_deposits.viewing_id', $request->viewing_id);
        }

        /**
         * Execute query và paginate results
         * 
         * $query->orderBy('booking_deposits.created_at', 'desc') - Sắp xếp theo created_at giảm dần
         *   - orderBy() là method của query builder để sắp xếp kết quả
         *   - 'booking_deposits.created_at' là tên column (với table prefix)
         *   - 'desc' là thứ tự giảm dần (mới nhất trước)
         *   - Sắp xếp deposits mới nhất trước
         * 
         * ->paginate(20) - Phân trang kết quả
         *   - paginate() là method của query builder để phân trang
         *   - 20 là số bản ghi mỗi trang
         *   - Trả về LengthAwarePaginator instance chứa:
         *     - Collection chứa 20 deposits đầu tiên
         *     - Pagination metadata (current_page, last_page, total, etc.)
         *     - Links để navigate giữa các trang
         * 
         * $bookingDeposits - Biến lưu LengthAwarePaginator instance
         *   - Sẽ được truyền vào view để hiển thị
         */
        $bookingDeposits = $query->orderBy('booking_deposits.created_at', 'desc')->paginate(20); // Sắp xếp mới nhất trước, phân trang 20 bản ghi/trang
        
        $organization = \App\Models\Organization::find($organizationId); // Lấy organization để hiển thị payment_due_hours setting
        
        // Lấy danh sách properties cho filter dropdown
        if ($canViewAll) {
            $properties = Property::where('organization_id', $organizationId) // Filter theo organization
                ->where('status', 1) // Chỉ lấy properties đang active
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->orderBy('name') // Sắp xếp theo tên
                ->get(); // Lấy tất cả properties của organization
        } else {
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho agent
            $properties = Property::whereIn('id', $assignedPropertyIds) // Chỉ lấy properties được assign
                ->where('status', 1) // Chỉ lấy đang active
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->orderBy('name') // Sắp xếp theo tên
                ->get(); // Lấy tất cả properties được assign
        }

        // Lấy danh sách managers và agents trong organization (loại trừ tenants)
        $allUsers = collect(); // Khởi tạo empty collection
        try {
            $allUsers = User::with('userProfile') // Eager load userProfile để tránh N+1 queries
                ->whereHas('organizationUsers', function($q) use ($organizationId) { // Chỉ lấy users thuộc organization
                    $q->where('organization_id', $organizationId) // Filter theo organization ID
                      ->where('status', 'active'); // Chỉ lấy users có status active
                })
                ->whereDoesntHave('userRoles', function($q) { // Loại trừ users có role tenant
                    $q->where('key_code', 'tenant'); // Tìm role có key_code = 'tenant'
                })
                ->whereNull('deleted_at') // Chỉ lấy users chưa bị xóa
                ->get() // Lấy tất cả users
                ->sortBy(function($user) { // Sắp xếp theo tên
                    return $user->userProfile->full_name ?? $user->full_name ?? ''; // Ưu tiên full_name từ profile, fallback về full_name từ user
                })->values(); // Reset keys về 0, 1, 2...
        } catch (\Exception $e) {
            Log::error('Error loading agents/managers in index: ' . $e->getMessage()); // Ghi log lỗi nếu có
        }
        
        $agents = $allUsers; // Gán vào biến $agents để truyền vào view

        // Lấy danh sách viewings cho filter dropdown
        $viewings = collect(); // Khởi tạo empty collection
        try {
            $viewingsQuery = \App\Models\Viewing::whereHas('property', function($q) use ($organizationId) { // Chỉ lấy viewings có property thuộc organization
                $q->where('organization_id', $organizationId); // Filter theo organization ID
            });
            
            // Nếu là agent, chỉ hiển thị viewings của properties được assign
            if (!$canViewAll) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy property IDs được assign
                if ($assignedPropertyIds->isNotEmpty()) { // Nếu có properties được assign
                    $viewingsQuery->whereIn('property_id', $assignedPropertyIds); // Filter theo assigned properties
                } else {
                    $viewingsQuery->whereRaw('1 = 0'); // Không có kết quả (điều kiện luôn false)
                }
            }
            
            $viewings = $viewingsQuery->with(['property', 'unit', 'lead']) // Eager load relationships
                ->orderBy('created_at', 'desc') // Sắp xếp mới nhất trước
                ->limit(100) // Giới hạn 100 viewings gần nhất
                ->get(); // Lấy kết quả
        } catch (\Exception $e) {
            Log::error('Error loading viewings in index: ' . $e->getMessage()); // Ghi log lỗi nếu có
        }

        $isHtmx = $request->header('HX-Request') === 'true'; // Kiểm tra có phải HTMX request không (check header HX-Request)
        
        // Format statistics cho statistics-cards component
        $statsFormatted = [
            'total' => [
                'value' => $stats['total'] ?? 0, // Tổng số deposits (fallback về 0 nếu null)
                'label' => 'Tổng cộng', // Label hiển thị
                'icon' => 'fa-list', // Icon FontAwesome
                'color' => 'primary', // Màu sắc (Bootstrap color)
                'filter' => '', // Không có filter cho total
            ],
            'pending_approval' => [
                'value' => $stats['pending_approval'] ?? 0, // Số deposits chờ duyệt
                'label' => 'Chờ duyệt', // Label hiển thị
                'icon' => 'fa-clock', // Icon đồng hồ
                'color' => 'warning', // Màu vàng (warning)
                'filter' => 'pending_approval', // Filter value khi click
                'filterKey' => 'payment_status', // Key của filter parameter
            ],
            'pending' => [
                'value' => $stats['pending'] ?? 0, // Số deposits chờ thanh toán
                'label' => 'Chờ thanh toán', // Label hiển thị
                'icon' => 'fa-hourglass-half', // Icon đồng hồ cát
                'color' => 'warning', // Màu vàng
                'filter' => 'pending', // Filter value
                'filterKey' => 'payment_status', // Filter key
            ],
            'paid' => [
                'value' => $stats['paid'] ?? 0, // Số deposits đã thanh toán
                'label' => 'Đã thanh toán', // Label hiển thị
                'icon' => 'fa-money-bill-wave', // Icon tiền
                'color' => 'success', // Màu xanh (success)
                'filter' => 'paid', // Filter value
                'filterKey' => 'payment_status', // Filter key
            ],
            'paid_without_lease' => [
                'value' => $stats['paid_without_lease'] ?? 0, // Số deposits đã thanh toán nhưng chưa có hợp đồng
                'label' => 'Đã thanh toán nhưng chưa có hợp đồng', // Label hiển thị
                'icon' => 'fa-exclamation-triangle', // Icon cảnh báo
                'color' => 'info', // Màu xanh dương (info)
                'filter' => 'paid_without_lease', // Filter value
                'filterKey' => 'paid_without_lease', // Filter key riêng
            ],
            'cancelled' => [
                'value' => $stats['cancelled'] ?? 0, // Số deposits đã hủy
                'label' => 'Đã hủy', // Label hiển thị
                'icon' => 'fa-times', // Icon X
                'color' => 'danger', // Màu đỏ (danger)
                'filter' => 'cancelled', // Filter value
                'filterKey' => 'payment_status', // Filter key
            ],
        ];
        
        // Nếu là HTMX request, trả về HTML partial với hx-swap-oob cho stats
        if ($isHtmx) {
            try {
                $tableHtml = view('staff.contract.booking-deposits.partials.table', [ // Render table partial view
                    'bookingDeposits' => $bookingDeposits, // Truyền paginated deposits
                    'sortBy' => $request->get('sort_by', 'created_at'), // Lấy sort_by từ request (default: created_at)
                    'sortOrder' => $request->get('sort_order', 'desc') // Lấy sort_order từ request (default: desc)
                ])->render(); // Render thành HTML string
                
                // Trích xuất inner HTML từ table (loại bỏ wrapper div nếu có)
                $dom = new \DOMDocument(); // Tạo DOMDocument để parse HTML
                libxml_use_internal_errors(true); // Bật internal errors để tránh warning khi parse HTML không hợp lệ
                $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // Load HTML với encoding UTF-8, không thêm <html> và <body> tags
                libxml_clear_errors(); // Xóa errors sau khi parse
                
                $container = $dom->getElementsByTagName('body')->item(0); // Lấy body element (nếu có)
                if ($container) { // Nếu có body element
                    $innerTableHtml = ''; // Khởi tạo biến lưu inner HTML
                    foreach ($container->childNodes as $child) { // Duyệt qua các child nodes
                        $innerTableHtml .= $dom->saveHTML($child); // Lưu HTML của từng child node
                    }
                } else {
                    $innerTableHtml = $tableHtml; // Nếu không có body, dùng HTML gốc
                }
                
                // Xác định filter hiện tại để highlight stats card
                $currentFilter = ''; // Khởi tạo biến filter hiện tại
                if (request('paid_without_lease') == '1') { // Nếu đang filter paid_without_lease
                    $currentFilter = 'paid_without_lease'; // Set current filter
                } elseif (request('payment_status')) { // Nếu có payment_status filter
                    $currentFilter = request('payment_status'); // Lấy payment_status làm current filter
                }
                
                $statsHtml = view('staff.components.statistics-cards', [ // Render statistics cards component
                    'stats' => $statsFormatted, // Truyền formatted stats
                    'currentFilter' => $currentFilter, // Truyền filter hiện tại để highlight
                    'filterKey' => 'payment_status', // Key của filter parameter
                    'onFilterClick' => 'htmx-filter', // Event handler khi click filter
                    'onClearClick' => 'htmx-clear', // Event handler khi clear filter
                    'columns' => 6, // Số cột hiển thị (6 cards)
                    'action' => route('staff.booking-deposits.index'), // Route action cho form
                    'tableContainerId' => 'booking-deposits-table-container' // ID của table container
                ])->render(); // Render thành HTML string
                
                // Kết hợp HTML với hx-swap-oob cho stats (HTMX out-of-band swap)
                $responseHtml = $innerTableHtml . "\n<div id=\"stats-container\" hx-swap-oob=\"true\">\n" . $statsHtml . "\n</div>"; // Kết hợp table HTML và stats HTML với hx-swap-oob attribute
                
                return response($responseHtml) // Trả về response với HTML
                    ->header('HX-Push-Url', $request->fullUrl()); // Push URL vào browser history (HTMX feature)
            } catch (\Exception $e) {
                Log::error('BookingDepositController HTMX Error: ' . $e->getMessage()); // Ghi log lỗi
                return response('<div class="alert alert-danger">Lỗi khi tải dữ liệu: ' . $e->getMessage() . '</div>', 500); // Trả về error message với HTTP 500
            }
        }
        
        // Đảm bảo stats được khởi tạo (fallback nếu chưa có)
        $stats = $stats ?? [ // Nếu $stats chưa được set, khởi tạo với giá trị mặc định
            'total' => 0, // Tổng số = 0
            'pending_approval' => 0, // Chờ duyệt = 0
            'pending' => 0, // Chờ thanh toán = 0
            'paid' => 0, // Đã thanh toán = 0
            'cancelled' => 0, // Đã hủy = 0
            'refunded' => 0, // Đã hoàn tiền = 0
            'expired' => 0, // Hết hạn = 0
            'paid_without_lease' => 0, // Đã thanh toán nhưng chưa có hợp đồng = 0
        ];

        return view('staff.contract.booking-deposits.index', compact('bookingDeposits', 'properties', 'agents', 'organization', 'stats', 'viewings')); // Trả về full view với tất cả data
    }

    /**
     * Hiển thị form tạo booking deposit mới
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để tạo booking deposit mới với các dropdown (properties, units, agents, leads, viewings)
     * 
     * INPUT:
     * - Request: property_id, unit_id, lead_id, viewing_id (query parameters để pre-fill form)
     * - Session: organization_id
     * - Database: properties, units, users, leads, viewings
     * 
     * OUTPUT:
     * - View: staff.contract.booking-deposits.create
     * - Data: properties, units, agents, leads, viewings, selectedPropertyId, selectedUnitId, selectedLeadId, viewingId
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Lấy properties, units (chỉ available, chưa có lease active), agents, leads, viewings
     * 3. Pre-fill form từ query parameters hoặc viewing data
     * 4. Trả về view với data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng properties: Lấy danh sách properties của organization
     * - Bảng units: Lấy units available chưa có lease active
     * - Bảng users: Lấy agents/managers (loại trừ tenants)
     * - Bảng leads: Lấy leads của organization
     * - Bảng viewings: Lấy viewings done/confirmed/completed chưa có booking deposit
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Units chỉ hiển thị phòng available, chưa có lease active
     * - Cho phép phòng có booking deposit (để tạo booking deposit mới)
     * - Pre-fill từ viewing nếu có viewing_id
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và hiển thị thông tin
        
        $this->requireCapability('contract.booking_deposit.create', 'Bạn không có quyền tạo đặt cọc.'); // Kiểm tra quyền tạo → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }

        $properties = Property::where('organization_id', $organizationId) // Tìm properties của organization
            ->where('status', 1) // Chỉ lấy properties đang active
            ->orderBy('name') // Sắp xếp theo tên → Dễ tìm kiếm trong dropdown
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị trong dropdown

        $selectedPropertyId = $request->get('property_id'); // Lấy property_id từ query parameter → Pre-fill form
        $selectedUnitId = $request->get('unit_id'); // Lấy unit_id từ query parameter → Pre-fill form
        $selectedLeadId = $request->get('lead_id'); // Lấy lead_id từ query parameter → Pre-fill form
        $viewingId = $request->get('viewing_id'); // Lấy viewing_id từ query parameter → Lấy thông tin từ viewing
        
        // Nếu có viewing_id, lấy lead_id từ viewing để pre-fill
        if ($viewingId && !$selectedLeadId) { // Nếu có viewing_id và chưa có selectedLeadId
            try {
                $viewing = \App\Models\Viewing::where('id', $viewingId) // Tìm viewing theo ID
                    ->whereHas('property', function($q) use ($organizationId) { // Chỉ lấy viewing có property thuộc organization → Bảo mật
                        $q->where('organization_id', $organizationId); // Filter theo organization ID
                    })
                    ->first(); // Lấy bản ghi đầu tiên → Dùng để lấy thông tin pre-fill
                
                if ($viewing && $viewing->lead_id) { // Nếu có viewing và có lead_id
                    $selectedLeadId = $viewing->lead_id; // Pre-fill lead_id từ viewing → Tự động điền form
                }
                
                // Pre-fill property_id và unit_id từ viewing nếu chưa có
                if (!$selectedPropertyId && $viewing && $viewing->property_id) { // Nếu chưa có property_id và viewing có property_id
                    $selectedPropertyId = $viewing->property_id; // Pre-fill property_id → Tự động điền form
                }
                if (!$selectedUnitId && $viewing && $viewing->unit_id) { // Nếu chưa có unit_id và viewing có unit_id
                    $selectedUnitId = $viewing->unit_id; // Pre-fill unit_id → Tự động điền form
                }
            } catch (\Exception $e) {
                Log::error('Error loading viewing in booking deposit create: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            }
        }

        // Lấy danh sách units: chỉ hiển thị phòng trống (available) chưa có hợp đồng thuê đang hoạt động, cho phép phòng có booking deposit
        $unitsQuery = Unit::whereHas('property', function($q) use ($organizationId) { // Chỉ lấy units có property thuộc organization → Bảo mật
            $q->where('organization_id', $organizationId); // Filter theo organization ID
        })
        ->where('status', 'available') // Chỉ lấy phòng có status 'available' → Phòng trống
        ->whereDoesntHave('leases', function($q) { // Loại bỏ phòng có hợp đồng thuê đang hoạt động → Tránh conflict
            $q->where('status', 'active') // Chỉ kiểm tra leases có status 'active'
              ->whereNull('deleted_at'); // Chỉ kiểm tra leases chưa bị xóa
        });
        
        if ($selectedPropertyId) { // Nếu có selectedPropertyId
            $unitsQuery->where('property_id', $selectedPropertyId); // Filter theo property_id → Chỉ lấy units của property đã chọn
        }
        // Lưu ý: KHÔNG loại bỏ phòng có booking deposit - cho phép tạo booking deposit mới

        $units = $unitsQuery->with('property') // Eager load property relationship → Tránh N+1 queries
            ->orderBy('code') // Sắp xếp theo code → Dễ tìm kiếm
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị trong dropdown

        // Lấy danh sách managers và agents trong organization (loại trừ tenants)
        $allUsers = collect(); // Khởi tạo empty collection → Fallback nếu có lỗi
        try {
            $allUsers = User::with('userProfile') // Eager load userProfile → Tránh N+1 queries
                ->whereHas('organizationUsers', function($q) use ($organizationId) { // Chỉ lấy users thuộc organization → Bảo mật
                    $q->where('organization_id', $organizationId) // Filter theo organization ID
                      ->where('status', 'active'); // Chỉ lấy users có status active → Chỉ users đang hoạt động
                })
                ->whereDoesntHave('userRoles', function($q) { // Loại trừ users có role tenant → Chỉ lấy agents/managers
                    $q->where('key_code', 'tenant'); // Tìm role có key_code = 'tenant'
                })
                ->whereNull('deleted_at') // Chỉ lấy users chưa bị xóa
                ->get() // Lấy tất cả kết quả
                ->sortBy(function($user) { // Sắp xếp theo tên → Dễ tìm kiếm
                    return $user->userProfile->full_name ?? $user->full_name ?? ''; // Ưu tiên full_name từ profile
                })->values(); // Reset keys về 0,1,2... → Đảm bảo array index đúng
        } catch (\Exception $e) {
            Log::error('Error loading agents/managers in create: ' . $e->getMessage()); // Ghi log lỗi → Để debug
        }
        
        $agents = $allUsers; // Gán vào biến $agents → Truyền vào view

        $leads = Lead::where('organization_id', $organizationId) // Tìm leads của organization
            ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
            ->orderBy('name') // Sắp xếp theo tên → Dễ tìm kiếm
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị trong dropdown

        $viewings = \App\Models\Viewing::where('organization_id', $organizationId) // Tìm viewings của organization
            ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
            ->whereIn('status', ['done', 'confirmed', 'completed']) // Chỉ lấy viewings đã hoàn thành → Có thể tạo deposit
            ->whereDoesntHave('bookingDeposits', function($q) { // Loại bỏ viewings đã có booking deposit → Tránh trùng lặp
                $q->whereNull('deleted_at'); // Chỉ kiểm tra booking deposits chưa bị xóa
            })
            ->with(['property', 'unit', 'lead', 'agent']) // Eager load relationships → Tránh N+1 queries
            ->orderBy('schedule_at', 'desc') // Sắp xếp theo schedule_at giảm dần → Mới nhất trước
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị trong dropdown

        return view('staff.contract.booking-deposits.create', compact('properties', 'units', 'agents', 'leads', 'viewings', 'user', 'selectedPropertyId', 'selectedUnitId', 'selectedLeadId', 'viewingId')); // Trả về view với tất cả data → Hiển thị form
    }

    /**
     * Tạo booking deposit mới
     * 
     * MỤC ĐÍCH:
     * Tạo booking deposit mới với trạng thái "Chờ duyệt", kiểm tra unit có sẵn không
     * 
     * INPUT:
     * - Request: unit_id, agent_id, amount, deposit_type, hold_until, lead_id, viewing_id, notes
     * - Session: organization_id, user_id
     * - Database: units, users, leads, properties
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "...", redirect: "..."}
     * - Database: Tạo bản ghi mới trong booking_deposits
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate input (unit_id, amount, deposit_type, hold_until, lead_id, ...)
     * 2. Tự động gán agent_id cho agent (Manager có thể gán cho agent khác)
     * 3. Kiểm tra unit thuộc organization và chưa có lease/deposit active
     * 4. Kiểm tra agent thuộc organization
     * 5. Kiểm tra lead thuộc organization và lấy tenant_user_id nếu có
     * 6. Tạo booking deposit với status "pending_approval"
     * 7. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng units: Kiểm tra unit có sẵn không
     * - Bảng users: Kiểm tra agent thuộc organization
     * - Bảng leads: Kiểm tra lead thuộc organization và lấy tenant_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng booking_deposits: Tạo bản ghi mới
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Trạng thái mặc định là "pending_approval" (chờ duyệt)
     * - Agent chỉ có thể tạo deposit cho chính mình (tự động gán agent_id)
     * - Không cho phép tạo deposit nếu unit đã có lease active hoặc deposit active
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu created_by
        
        $this->requireCapability('contract.booking_deposit.create', 'Bạn không có quyền tạo đặt cọc.'); // Kiểm tra quyền tạo → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter và validate
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Bạn không thuộc tổ chức nào.' // Thông báo lỗi
            ], 403); // HTTP 403 Forbidden
        }

        $data = $request->all(); // Lấy tất cả data từ request → Dùng để xử lý
        
        $this->enforceAgentId($data, 'agent_id'); // Tự động gán agent_id cho agent → Manager có thể gán cho agent khác, Agent phải gán cho chính mình
        
        $request->merge($data); // Merge data đã xử lý lại vào request → Để validation sử dụng
        
        $validator = Validator::make($request->all(), [ // Tạo validator với rules → Kiểm tra dữ liệu đầu vào
            'unit_id' => 'required|exists:units,id', // unit_id: bắt buộc, phải tồn tại → Đảm bảo unit hợp lệ
            'agent_id' => 'required|exists:users,id', // agent_id: bắt buộc, phải tồn tại → Đảm bảo agent hợp lệ
            'amount' => 'required|numeric|min:0', // amount: bắt buộc, số, >= 0 → Đảm bảo số tiền hợp lệ
            'deposit_type' => 'required|in:booking,security,advance', // deposit_type: bắt buộc, một trong 3 loại → Đảm bảo loại hợp lệ
            'hold_until' => 'required|date|after:now', // hold_until: bắt buộc, date, sau hiện tại → Đảm bảo ngày hợp lệ
            'notes' => 'nullable|string|max:1000', // notes: không bắt buộc, string, tối đa 1000 ký tự → Ghi chú tùy chọn
            'lead_id' => 'required|exists:leads,id', // lead_id: bắt buộc, phải tồn tại → Đảm bảo lead hợp lệ
        ]);

        if ($validator->fails()) { // Nếu validation thất bại
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Dữ liệu không hợp lệ.', // Thông báo lỗi
                'errors' => $validator->errors() // Chi tiết lỗi validation → Hiển thị lỗi cụ thể
            ], 422); // HTTP 422 Unprocessable Entity
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            $unit = Unit::where('id', $request->unit_id) // Tìm unit theo ID
                ->whereHas('property', function($q) use ($organizationId) { // Chỉ lấy unit có property thuộc organization → Bảo mật
                    $q->where('organization_id', $organizationId); // Filter theo organization ID
                })->first(); // Lấy bản ghi đầu tiên → Kiểm tra unit có tồn tại không

            if (!$unit) { // Nếu không tìm thấy unit
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Phòng không thuộc tổ chức của bạn.' // Thông báo lỗi
                ], 422); // HTTP 422
            }

            $agent = User::where('id', $request->agent_id) // Tìm user (agent) theo ID
                ->whereHas('organizations', function($q) use ($organizationId) { // Chỉ lấy user thuộc organization → Bảo mật
                    $q->where('organization_id', $organizationId); // Filter theo organization ID
                })->first(); // Lấy bản ghi đầu tiên → Kiểm tra agent có tồn tại không

            if (!$agent) { // Nếu không tìm thấy agent
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Agent không thuộc tổ chức của bạn.' // Thông báo lỗi
                ], 422); // HTTP 422
            }

            // Kiểm tra unit có hợp đồng thuê hoặc booking deposit đang hoạt động không
            if ($unit->leases()->where('status', 'active')->exists()) { // Nếu unit có lease đang active
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Phòng đã có hợp đồng thuê đang hoạt động.' // Thông báo lỗi → Không cho tạo deposit
                ], 422); // HTTP 422
            }

            if ($unit->bookingDeposits()->where('payment_status', '!=', 'cancelled') // Lấy deposits chưa bị hủy
                ->where('hold_until', '>', now())->exists()) { // Và hold_until > now (còn hiệu lực)
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Phòng đã có đặt cọc đang hoạt động.' // Thông báo lỗi → Không cho tạo deposit mới
                ], 422); // HTTP 422
            }

            // Kiểm tra lead thuộc organization
            if (!$request->lead_id) { // Nếu không có lead_id
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Vui lòng chọn lead.' // Thông báo lỗi
                ], 422); // HTTP 422
            }
            
            $lead = Lead::where('id', $request->lead_id) // Tìm lead theo ID
                ->where('organization_id', $organizationId) // Filter theo organization ID → Bảo mật
                ->first(); // Lấy bản ghi đầu tiên → Kiểm tra lead có tồn tại không

            if (!$lead) { // Nếu không tìm thấy lead
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Lead không thuộc tổ chức của bạn.' // Thông báo lỗi
                ], 422); // HTTP 422
            }

            // Kiểm tra lead có tenant_id không, nếu có thì set tenant_user_id
            $tenantUserId = null; // Khởi tạo tenant_user_id = null → Mặc định không có tenant
            if ($lead->tenant_id) { // Nếu lead có tenant_id
                $tenantOrgIds = collect([$organizationId, 3]); // Tạo collection chứa organization ID hiện tại và Default Organization (ID=3) → Cho phép tenant từ 2 org
                $tenant = User::where('id', $lead->tenant_id) // Tìm user (tenant) theo ID
                    ->whereHas('organizations', function($q) use ($tenantOrgIds) { // Chỉ lấy user thuộc một trong các organizations → Bảo mật
                        $q->whereIn('organization_id', $tenantOrgIds); // Filter theo organization IDs
                    })->first(); // Lấy bản ghi đầu tiên → Kiểm tra tenant có tồn tại không

                if ($tenant) { // Nếu tìm thấy tenant
                    $tenantUserId = $lead->tenant_id; // Set tenant_user_id từ lead → Liên kết deposit với tenant
                }
            }

            $paymentStatus = 'pending_approval'; // Trạng thái mặc định là 'pending_approval' → Chờ duyệt
            
            // Nếu payment_status là 'pending' (auto-approved), tính payment_due_date
            $paymentDueDate = null; // Khởi tạo payment_due_date = null → Sẽ được tính khi approve
            $approvedAt = null; // Khởi tạo approved_at = null → Sẽ được set khi approve
            $approvedBy = null; // Khởi tạo approved_by = null → Sẽ được set khi approve
            
            if ($paymentStatus === 'pending') { // Nếu status là 'pending' (auto-approved) → Trường hợp đặc biệt
                $approvedAt = now(); // Set approved_at = thời gian hiện tại → Đánh dấu đã duyệt
                $approvedBy = Auth::id(); // Set approved_by = user ID hiện tại → Lưu người duyệt
                
                // Lấy payment_due_hours với priority: Property Cycle > Organization Default Cycle
                $unit = \App\Models\Unit::find($request->unit_id); // Tìm unit theo ID → Lấy property
                $property = $unit ? $unit->property : null; // Lấy property từ unit → Dùng để lấy payment_due_hours
                
                if ($property) { // Nếu có property
                    $paymentDueMinutes = $property->getEffectivePaymentDueHours(); // Lấy payment_due_hours từ property → Property cycle có priority cao nhất
                } else { // Nếu không có property
                    $organization = \App\Models\Organization::find($organizationId); // Tìm organization → Fallback
                    $paymentDueMinutes = $organization ? $organization->getEffectivePaymentDueHours() : 4320; // Lấy từ organization hoặc default 4320 phút (3 ngày) → Fallback
                }
                
                $paymentDueDate = $approvedAt->copy()->addMinutes($paymentDueMinutes); // Tính payment_due_date = approved_at + payment_due_minutes → Hạn chót thanh toán
            }
            
            // Tạo booking deposit (payment_due_date sẽ được set tự động khi approve hoặc nếu status là 'pending' khi tạo)
            $bookingDeposit = BookingDeposit::create([
                'organization_id' => $organizationId, // Organization ID → Liên kết với organization
                'unit_id' => $request->unit_id, // Unit ID → Phòng được đặt cọc
                'agent_id' => $request->agent_id, // Agent ID → Agent quản lý deposit
                'amount' => $request->amount, // Số tiền đặt cọc → Số tiền cần thanh toán
                'deposit_type' => $request->deposit_type, // Loại đặt cọc → booking/security/advance
                'payment_status' => $paymentStatus, // Trạng thái thanh toán → pending_approval (chờ duyệt)
                'hold_until' => $request->hold_until, // Ngày hết hạn giữ chỗ → Hạn chót giữ phòng
                'payment_due_date' => $paymentDueDate, // Ngày hết hạn thanh toán → null nếu chưa approve
                'approved_at' => $approvedAt, // Thời gian duyệt → null nếu chưa approve
                'approved_by' => $approvedBy, // User ID duyệt → null nếu chưa approve
                'notes' => $request->notes, // Ghi chú → Thông tin bổ sung
                'tenant_user_id' => $tenantUserId, // Tenant user ID → Liên kết với tenant (nếu có)
                'lead_id' => $request->lead_id, // Lead ID → Liên kết với lead
                'viewing_id' => $request->get('viewing_id'), // Viewing ID → Liên kết với viewing (nếu có)
                'reference_number' => 'BD' . time() . rand(100, 999), // Tạo reference number → BD + timestamp + random → Số tham chiếu unique
            ]);
            
            // Nếu status là 'pending' (auto-approved), update unit status thành 'reserved'
            if ($paymentStatus === 'pending' && $bookingDeposit->unit) { // Nếu status là 'pending' và có unit → Auto-approved
                $unit = $bookingDeposit->unit; // Lấy unit từ booking deposit → Cập nhật status
                if (!in_array($unit->status, ['reserved', 'occupied'])) { // Nếu unit status không phải 'reserved' hoặc 'occupied' → Chưa được giữ
                    $unit->update(['status' => 'reserved']); // Update status thành 'reserved' → Đánh dấu phòng đã được giữ
                }
            }

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi vào database

            return response()->json([ // Trả về JSON thành công
                'success' => true, // Thành công
                'message' => 'Đặt cọc đã được tạo thành công!', // Thông báo thành công
                    'redirect' => route('staff.booking-deposits.show', $bookingDeposit->id) // URL chuyển đến trang chi tiết → Hiển thị deposit vừa tạo
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
            Log::error('Error creating booking deposit: ' . $e->getMessage(), [ // Ghi log lỗi → Để debug
                'trace' => $e->getTraceAsString(), // Stack trace → Chi tiết lỗi
                'request_data' => $request->all() // Request data → Dữ liệu đầu vào để debug
            ]);
            
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Có lỗi xảy ra khi tạo đặt cọc. Vui lòng thử lại sau.' // Thông báo lỗi
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Hiển thị chi tiết booking deposit
     * 
     * MỤC ĐÍCH:
     * Hiển thị thông tin chi tiết của booking deposit, bao gồm invoice và lease liên quan
     * 
     * INPUT:
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id
     * - Database: booking_deposits, invoices, leases
     * 
     * OUTPUT:
     * - View: staff.contract.booking-deposits.show
     * - Data: bookingDeposit, hasInvoice, invoice, hasLease, lease, canTransitionTo
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Lấy booking deposit với relationships (unit, property, agent, tenant, lead)
     * 3. Kiểm tra có invoice và lease liên quan không
     * 4. Tính toán các trạng thái có thể chuyển đổi
     * 5. Trả về view với data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Lấy booking deposit theo ID
     * - Bảng invoices: Kiểm tra invoice liên quan
     * - Bảng leases: Kiểm tra lease liên quan
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Chỉ hiển thị invoice và lease chưa bị hủy/xóa
     * - Tính toán allowed transitions để hiển thị buttons chuyển trạng thái
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }

        $this->requireCapability('contract.booking_deposit.view', 'Bạn không có quyền xem chi tiết đặt cọc.'); // Kiểm tra quyền xem → Dừng nếu không có quyền

        $bookingDeposit = BookingDeposit::with(['unit.property', 'agent', 'tenantUser', 'lead', 'approvedBy']) // Eager load relationships → Tránh N+1 queries
            ->where('organization_id', $organizationId) // Filter theo organization → Bảo mật
            ->findOrFail($id); // Tìm booking deposit theo ID → Throw 404 nếu không tìm thấy

        $hasInvoice = \App\Models\Invoice::where('booking_deposit_id', $bookingDeposit->id) // Tìm invoices liên kết với deposit
            ->where('status', '!=', 'cancelled') // Chỉ kiểm tra invoices chưa bị hủy → Loại bỏ invoices đã hủy
            ->exists(); // Kiểm tra có tồn tại không → Dùng để hiển thị button tạo invoice
        
        $invoice = null; // Khởi tạo invoice = null → Mặc định không có invoice
        if ($hasInvoice) { // Nếu có invoice
            $invoice = \App\Models\Invoice::where('booking_deposit_id', $bookingDeposit->id) // Tìm invoice liên kết
                ->where('status', '!=', 'cancelled') // Chỉ lấy invoices chưa bị hủy
                ->orderBy('created_at', 'desc') // Sắp xếp mới nhất trước → Lấy invoice mới nhất
                ->first(); // Lấy invoice mới nhất → Hiển thị thông tin invoice
        }

        $hasLease = \App\Models\Lease::where('booking_id', $bookingDeposit->id) // Tìm leases liên kết với deposit
            ->whereNull('deleted_at') // Chỉ kiểm tra leases chưa bị xóa → Loại bỏ leases đã xóa
            ->exists(); // Kiểm tra có tồn tại không → Dùng để hiển thị button tạo lease
        
        $lease = null; // Khởi tạo lease = null → Mặc định không có lease
        if ($hasLease) { // Nếu có lease
            $lease = \App\Models\Lease::where('booking_id', $bookingDeposit->id) // Tìm lease liên kết
                ->whereNull('deleted_at') // Chỉ lấy leases chưa bị xóa
                ->first(); // Lấy lease đầu tiên → Hiển thị thông tin lease
        }

        // Tính toán các trạng thái có thể chuyển đổi (cancelled có thể chuyển sang các trạng thái khác, paid có thể chuyển sang expired/cancelled/refunded)
        $allowedTransitions = [
            'pending_approval' => ['pending', 'cancelled'], // pending_approval có thể chuyển sang pending hoặc cancelled → Business rule
            'pending' => ['paid', 'expired', 'cancelled'], // pending có thể chuyển sang paid, expired, hoặc cancelled → Business rule
            'paid' => ['expired', 'cancelled', 'refunded'], // paid có thể chuyển sang expired, cancelled, hoặc refunded → Business rule
            'refunded' => [], // refunded không thể chuyển sang trạng thái nào → Final state
            'expired' => ['cancelled', 'paid'], // expired có thể chuyển lại sang paid hoặc cancelled → Cho phép khôi phục
            'cancelled' => ['pending_approval', 'pending', 'paid'], // cancelled có thể chuyển sang các trạng thái khác → Cho phép khôi phục
        ];

        $currentStatus = $bookingDeposit->payment_status; // Lấy trạng thái hiện tại → Dùng để xác định transitions
        $canTransitionTo = $allowedTransitions[$currentStatus] ?? []; // Lấy danh sách trạng thái có thể chuyển đổi → Hiển thị buttons

        $qrUrl = null;
        $bankInfo = null;

        if ($invoice && $bookingDeposit->payment_status === 'pending') {
            $webhooksPermissionService = app(\App\Services\WebhooksPermissionService::class);
            $canUseSepay = $webhooksPermissionService->canUseSepay($organizationId);
            
            if ($canUseSepay) {
                $bankName = config('services.sepay.bank_name', 'TPBank');
                $accountNumber = config('services.sepay.account_number', '46166378666');
                $accountName = config('services.sepay.account_name', 'Le Xuan Thanh Quan');
            } else {
                $bankingAccount = \App\Models\OrganizationBanking::with('sepayBank')
                    ->where('organization_id', $organizationId)
                    ->where('is_active', true)
                    ->where('is_default', true)
                    ->first();
                    
                if (!$bankingAccount) {
                    $bankingAccount = \App\Models\OrganizationBanking::with('sepayBank')
                        ->where('organization_id', $organizationId)
                        ->where('is_active', true)
                        ->first();
                }
                
                if ($bankingAccount) {
                    $bankName = $bankingAccount->bank_name ?? $bankingAccount->sepayBank->sepay_name ?? $bankingAccount->sepayBank->short_name ?? null;
                    $accountNumber = $bankingAccount->account_number;
                    $accountName = $bankingAccount->account_name;
                }
            }
            
            if (isset($bankName) && isset($accountNumber)) {
                $bankInfo = [
                    'bank_name' => $bankName,
                    'account_number' => $accountNumber,
                    'account_name' => $accountName ?? '',
                    'amount' => $invoice->total_amount,
                    'content' => 'BD' . $bookingDeposit->id, // Sử dụng BD + ID làm nội dung chuyển khoản để đối soát tự động
                ];
                
                $params = [
                    'acc' => $bankInfo['account_number'],
                    'bank' => $bankInfo['bank_name'],
                    'amount' => $bankInfo['amount'],
                    'des' => $bankInfo['content']
                ];
                $qrUrl = 'https://qr.sepay.vn/img?' . http_build_query($params);
            }
        }

        return view('staff.contract.booking-deposits.show', compact('bookingDeposit', 'hasInvoice', 'invoice', 'canTransitionTo', 'hasLease', 'lease', 'qrUrl', 'bankInfo')); // Trả về view với tất cả data → Hiển thị chi tiết
    }

    /**
     * Hiển thị form chỉnh sửa booking deposit
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để chỉnh sửa booking deposit (chỉ khi status = 'pending_approval')
     * 
     * INPUT:
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id
     * - Database: booking_deposits, properties, units, users, leads
     * 
     * OUTPUT:
     * - View: staff.contract.booking-deposits.edit
     * - Data: bookingDeposit, properties, units, agents, leads
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Lấy booking deposit theo ID
     * 3. Kiểm tra deposit phải ở trạng thái 'pending_approval'
     * 4. Lấy properties, units, agents, leads cho dropdowns
     * 5. Trả về view với data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Lấy booking deposit theo ID
     * - Bảng properties: Lấy properties của organization
     * - Bảng units: Lấy units của organization
     * - Bảng users: Lấy agents/managers
     * - Bảng leads: Lấy leads của organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Chỉ cho phép chỉnh sửa khi payment_status = 'pending_approval'
     * - Redirect về show page nếu không đủ điều kiện
     */
    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $this->requireCapability('contract.booking_deposit.update', 'Bạn không có quyền chỉnh sửa đặt cọc.'); // Kiểm tra quyền cập nhật → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        if ($bookingDeposit->payment_status !== 'pending_approval') { // Nếu deposit không ở trạng thái chờ duyệt
            return redirect()->route('staff.booking-deposits.show', $id) // Chuyển đến trang chi tiết
                ->with('warning', 'Chỉ có thể chỉnh sửa đặt cọc ở trạng thái "Chờ duyệt".'); // Thông báo cảnh báo
        }

        $properties = Property::where('organization_id', $organizationId) // Tìm properties của organization
            ->where('status', 1) // Chỉ lấy properties đang active
            ->orderBy('name') // Sắp xếp theo tên → Dễ tìm kiếm
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị trong dropdown

        $units = Unit::whereHas('property', function($q) use ($organizationId) { // Chỉ lấy units có property thuộc organization → Bảo mật
            $q->where('organization_id', $organizationId); // Filter theo organization ID
        })
            ->with('property') // Eager load property relationship → Tránh N+1 queries
            ->orderBy('code') // Sắp xếp theo code → Dễ tìm kiếm
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị trong dropdown

        $allUsers = collect(); // Khởi tạo empty collection → Fallback nếu có lỗi
        try {
            $allUsers = User::with('userProfile') // Eager load userProfile → Tránh N+1 queries
                ->whereHas('organizationUsers', function($q) use ($organizationId) { // Chỉ lấy users thuộc organization → Bảo mật
                    $q->where('organization_id', $organizationId) // Filter theo organization ID
                      ->where('status', 'active'); // Chỉ lấy users có status active
                })
                ->whereDoesntHave('userRoles', function($q) { // Loại trừ users có role tenant → Chỉ lấy agents/managers
                    $q->where('key_code', 'tenant'); // Tìm role có key_code = 'tenant'
                })
                ->whereNull('deleted_at') // Chỉ lấy users chưa bị xóa
                ->get() // Lấy tất cả kết quả
                ->sortBy(function($user) { // Sắp xếp theo tên → Dễ tìm kiếm
                    return $user->userProfile->full_name ?? $user->full_name ?? ''; // Ưu tiên full_name từ profile
                })->values(); // Reset keys về 0,1,2... → Đảm bảo array index đúng
        } catch (\Exception $e) {
            Log::error('Error loading agents/managers in edit: ' . $e->getMessage()); // Ghi log lỗi → Để debug
        }
        
        $agents = $allUsers; // Gán vào biến $agents → Truyền vào view

        $leads = Lead::where('organization_id', $organizationId) // Tìm leads của organization
            ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
            ->orderBy('name') // Sắp xếp theo tên → Dễ tìm kiếm
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị trong dropdown

        return view('staff.contract.booking-deposits.edit', compact('bookingDeposit', 'properties', 'units', 'agents', 'leads')); // Trả về view với tất cả data → Hiển thị form
    }

    /**
     * Cập nhật booking deposit
     * 
     * MỤC ĐÍCH:
     * Cập nhật thông tin booking deposit (chỉ khi status = 'pending_approval')
     * 
     * INPUT:
     * - Request: unit_id, agent_id, amount, deposit_type, hold_until, lead_id, notes
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id
     * - Database: booking_deposits, units, users, leads
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "...", redirect: "..."}
     * - Database: Cập nhật bản ghi trong booking_deposits
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate input
     * 2. Kiểm tra deposit phải ở trạng thái 'pending_approval'
     * 3. Security check: Prevent organization manipulation
     * 4. Kiểm tra unit, agent, lead thuộc organization
     * 5. Lấy tenant_user_id từ lead nếu có
     * 6. Cập nhật booking deposit (giữ nguyên payment_status)
     * 7. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Lấy booking deposit theo ID
     * - Bảng units: Kiểm tra unit thuộc organization
     * - Bảng users: Kiểm tra agent thuộc organization
     * - Bảng leads: Kiểm tra lead thuộc organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng booking_deposits: Cập nhật thông tin
     * - Logs: Ghi log security alert nếu có
     * 
     * LƯU Ý:
     * - Chỉ cho phép cập nhật khi payment_status = 'pending_approval'
     * - Không cho phép thay đổi payment_status từ form
     * - Security check để prevent organization manipulation
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để tracking
        
        $this->requireCapability('contract.booking_deposit.update', 'Bạn không có quyền cập nhật đặt cọc.'); // Kiểm tra quyền cập nhật → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter và validate
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Bạn không thuộc tổ chức nào.' // Thông báo lỗi
            ], 403); // HTTP 403 Forbidden
        }

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        if ($bookingDeposit->payment_status !== 'pending_approval') { // Nếu deposit không ở trạng thái chờ duyệt
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Chỉ có thể chỉnh sửa đặt cọc ở trạng thái "Chờ duyệt"' // Thông báo lỗi
            ], 400); // HTTP 400 Bad Request
        }

        // SECURITY CHECK: Prevent organization manipulation
        $dangerousFields = ['organization_id', 'user_organization_id', 'org_id']; // Danh sách fields nguy hiểm → Ngăn chặn manipulation
        $requestData = $request->except($dangerousFields); // Loại bỏ dangerous fields khỏi request → Bảo mật
        
        if ($request->hasAny($dangerousFields)) { // Nếu request có dangerous fields
            $suspiciousFields = array_intersect($dangerousFields, array_keys($request->all())); // Tìm fields nguy hiểm có trong request → Kiểm tra manipulation
            
            foreach ($suspiciousFields as $field) { // Duyệt qua từng suspicious field
                if ($request->has($field) && $request->input($field) != $organizationId) { // Nếu user cố gắng thay đổi organization_id
                    Log::critical('SECURITY ALERT: Attempted booking deposit organization manipulation', [ // Ghi log critical → Cảnh báo bảo mật
                        'booking_deposit_id' => $id, // ID của deposit
                        'user_id' => Auth::id(), // ID của user
                        'ip_address' => $request->ip(), // IP address của user
                        'suspicious_field' => $field, // Field nguy hiểm
                        'attempted_value' => $request->input($field), // Giá trị cố gắng thay đổi
                        'current_organization_id' => $organizationId, // Organization ID hiện tại
                        'request_data' => $request->all() // Toàn bộ request data
                    ]);
                    
                    return response()->json([ // Trả về JSON lỗi
                        'success' => false, // Thất bại
                        'message' => 'Yêu cầu không hợp lệ. Hành động đã được ghi nhận.' // Thông báo lỗi → Không tiết lộ chi tiết
                    ], 403); // HTTP 403 Forbidden
                }
            }
        }
        
        $request->merge($requestData); // Merge cleaned data lại vào request → Để validation sử dụng

        // Basic validation
        $holdUntilRule = 'required|date'; // Rule cơ bản cho hold_until → Bắt buộc, phải là date
        if ($request->filled('hold_until')) { // Nếu có hold_until trong request
            $holdUntilDate = \Carbon\Carbon::parse($request->hold_until); // Parse thành Carbon instance → Kiểm tra ngày
            if ($holdUntilDate->isFuture()) { // Nếu ngày là tương lai
                $holdUntilRule .= '|after:now'; // Thêm rule after:now → Phải sau thời gian hiện tại
            }
        }
        
        $validator = Validator::make($request->all(), [ // Tạo validator với rules → Kiểm tra dữ liệu đầu vào
            'unit_id' => 'required|exists:units,id', // unit_id: bắt buộc, phải tồn tại → Đảm bảo unit hợp lệ
            'agent_id' => 'required|exists:users,id', // agent_id: bắt buộc, phải tồn tại → Đảm bảo agent hợp lệ
            'amount' => 'required|numeric|min:0', // amount: bắt buộc, số, >= 0 → Đảm bảo số tiền hợp lệ
            'deposit_type' => 'required|in:booking,security,advance', // deposit_type: bắt buộc, một trong 3 loại → Đảm bảo loại hợp lệ
            'hold_until' => $holdUntilRule, // hold_until: dynamic rule → Đảm bảo ngày hợp lệ
            'payment_due_date' => 'nullable|date', // payment_due_date: không bắt buộc, date → Cho phép null
            'notes' => 'nullable|string|max:1000', // notes: không bắt buộc, string, tối đa 1000 ký tự → Ghi chú tùy chọn
            'lead_id' => 'required|exists:leads,id', // lead_id: bắt buộc, phải tồn tại → Đảm bảo lead hợp lệ
        ]);

        if ($validator->fails()) { // Nếu validation thất bại
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Dữ liệu không hợp lệ.', // Thông báo lỗi
                'errors' => $validator->errors() // Chi tiết lỗi validation → Hiển thị lỗi cụ thể
            ], 422); // HTTP 422 Unprocessable Entity
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            $unit = Unit::where('id', $request->unit_id) // Tìm unit theo ID
                ->whereHas('property', function($q) use ($organizationId) { // Chỉ lấy unit có property thuộc organization → Bảo mật
                    $q->where('organization_id', $organizationId); // Filter theo organization ID
                })->first(); // Lấy bản ghi đầu tiên → Kiểm tra unit có tồn tại không

            if (!$unit) { // Nếu không tìm thấy unit
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Phòng không thuộc tổ chức của bạn.' // Thông báo lỗi
                ], 422); // HTTP 422
            }

            $agent = User::where('id', $request->agent_id) // Tìm user (agent) theo ID
                ->whereHas('organizations', function($q) use ($organizationId) { // Chỉ lấy user thuộc organization → Bảo mật
                    $q->where('organization_id', $organizationId); // Filter theo organization ID
                })->first(); // Lấy bản ghi đầu tiên → Kiểm tra agent có tồn tại không

            if (!$agent) { // Nếu không tìm thấy agent
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Agent không thuộc tổ chức của bạn.' // Thông báo lỗi
                ], 422); // HTTP 422
            }

            if (!$request->lead_id) { // Nếu không có lead_id
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Vui lòng chọn lead.' // Thông báo lỗi
                ], 422); // HTTP 422
            }
            
            $lead = Lead::where('id', $request->lead_id) // Tìm lead theo ID
                ->where('organization_id', $organizationId) // Filter theo organization ID → Bảo mật
                ->first(); // Lấy bản ghi đầu tiên → Kiểm tra lead có tồn tại không

            if (!$lead) { // Nếu không tìm thấy lead
                return response()->json([ // Trả về JSON lỗi
                    'success' => false, // Thất bại
                    'message' => 'Lead không thuộc tổ chức của bạn.' // Thông báo lỗi
                ], 422); // HTTP 422
            }

            $tenantUserId = null; // Khởi tạo tenant_user_id = null → Mặc định không có tenant
            if ($lead->tenant_id) { // Nếu lead có tenant_id
                $tenantOrgIds = collect([$organizationId, 3]); // Tạo collection chứa organization ID hiện tại và Default Organization (ID=3) → Cho phép tenant từ 2 org
                $tenant = User::where('id', $lead->tenant_id) // Tìm user (tenant) theo ID
                    ->whereHas('organizations', function($q) use ($tenantOrgIds) { // Chỉ lấy user thuộc một trong các organizations → Bảo mật
                        $q->whereIn('organization_id', $tenantOrgIds); // Filter theo organization IDs
                    })->first(); // Lấy bản ghi đầu tiên → Kiểm tra tenant có tồn tại không

                if ($tenant) { // Nếu tìm thấy tenant
                    $tenantUserId = $lead->tenant_id; // Set tenant_user_id từ lead → Liên kết deposit với tenant
                }
            }

            // Update booking deposit
            $bookingDeposit->update([ // Cập nhật booking deposit
                'unit_id' => $request->unit_id, // Unit ID → Phòng được đặt cọc
                'agent_id' => $request->agent_id, // Agent ID → Agent quản lý deposit
                'amount' => $request->amount, // Số tiền đặt cọc → Số tiền cần thanh toán
                'deposit_type' => $request->deposit_type, // Loại đặt cọc → booking/security/advance
                'payment_status' => $bookingDeposit->payment_status, // Giữ nguyên status hiện tại → Không cho phép thay đổi từ form
                'hold_until' => $request->hold_until, // Ngày hết hạn giữ chỗ → Hạn chót giữ phòng
                'notes' => $request->notes, // Ghi chú → Thông tin bổ sung
                'tenant_user_id' => $tenantUserId, // Tenant user ID → Liên kết với tenant (nếu có)
                'lead_id' => $request->lead_id, // Lead ID → Liên kết với lead
            ]);

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi vào database

            return response()->json([ // Trả về JSON thành công
                'success' => true, // Thành công
                'message' => 'Đặt cọc đã được cập nhật thành công!', // Thông báo thành công
                    'redirect' => route('staff.booking-deposits.show', $bookingDeposit->id) // URL chuyển đến trang chi tiết → Hiển thị deposit đã cập nhật
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
            Log::error('Error updating booking deposit: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Có lỗi xảy ra khi cập nhật đặt cọc.' // Thông báo lỗi
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Xóa booking deposit
     * 
     * MỤC ĐÍCH:
     * Xóa (soft delete) booking deposit (không cho phép xóa nếu đã thanh toán)
     * 
     * INPUT:
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id
     * - Database: booking_deposits
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "..."}
     * - Database: Soft delete booking deposit (cập nhật deleted_at)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Lấy booking deposit theo ID
     * 3. Kiểm tra deposit chưa thanh toán (payment_status != 'paid')
     * 4. Soft delete booking deposit
     * 5. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Lấy booking deposit theo ID
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng booking_deposits: Cập nhật deleted_at (soft delete)
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Không cho phép xóa deposit đã thanh toán (payment_status = 'paid')
     * - Sử dụng soft delete (không xóa vĩnh viễn)
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để tracking
        
        $this->requireCapability('contract.booking_deposit.delete', 'Bạn không có quyền xóa đặt cọc.'); // Kiểm tra quyền xóa → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Bạn không thuộc tổ chức nào.' // Thông báo lỗi
            ], 403); // HTTP 403 Forbidden
        }

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        if ($bookingDeposit->payment_status === 'paid') { // Nếu deposit đã thanh toán
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Không thể xóa đặt cọc đã thanh toán' // Thông báo lỗi → Bảo vệ dữ liệu đã thanh toán
            ], 400); // HTTP 400 Bad Request
        }

        try {
            $bookingDeposit->delete(); // Soft delete booking deposit → Cập nhật deleted_at

            return response()->json([ // Trả về JSON thành công
                'success' => true, // Thành công
                'message' => 'Đặt cọc đã được xóa thành công!' // Thông báo thành công
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting booking deposit: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Có lỗi xảy ra khi xóa đặt cọc.' // Thông báo lỗi
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Duyệt booking deposit - Chuyển từ "Chờ duyệt" sang "Chờ thanh toán"
     * 
     * MỤC ĐÍCH:
     * Manager duyệt booking deposit đang ở trạng thái "Chờ duyệt", chuyển sang "Chờ thanh toán"
     * và tính toán hạn chót thanh toán dựa trên payment_due_hours (Property Cycle > Organization Default)
     * 
     * INPUT:
     * - URL Parameter: $id (ID của booking deposit cần duyệt)
     * - Session: organization_id
     * - Database: booking_deposits, units, properties, organizations
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "...", ...}
     * - Database: Cập nhật booking_deposits (payment_status, approved_at, approved_by, payment_due_date)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Tìm booking deposit theo ID và organization
     * 3. Kiểm tra deposit phải ở trạng thái "pending_approval"
     * 4. Lấy payment_due_hours (ưu tiên Property Cycle > Organization Default)
     * 5. Tính payment_due_date = now() + payment_due_hours
     * 6. Cập nhật deposit: payment_status = 'pending', approved_at, approved_by, payment_due_date
     * 7. Trả về JSON success với message
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Đọc booking deposit theo ID
     * - Bảng units: Đọc unit để lấy property (qua relationship)
     * - Bảng properties: Đọc property để lấy payment_due_hours (qua getEffectivePaymentDueHours())
     * - Bảng organizations: Đọc organization để lấy payment_due_hours mặc định (nếu property không có)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng booking_deposits: Cập nhật payment_status, approved_at, approved_by, payment_due_date
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Chỉ có thể duyệt deposit ở trạng thái "pending_approval"
     * - payment_due_date được tính tự động từ payment_due_hours
     * - Priority: Property Cycle > Organization Default Cycle > 4320 phút (3 ngày)
     */
    public function approve($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu approved_by
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Bạn không thuộc tổ chức nào.' // Thông báo lỗi
            ], 403); // HTTP 403 Forbidden
        }

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        if ($bookingDeposit->payment_status !== 'pending_approval') { // Nếu deposit không ở trạng thái chờ duyệt
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Chỉ có thể duyệt đặt cọc ở trạng thái "Chờ duyệt".' // Thông báo lỗi
            ], 422); // HTTP 422 Unprocessable Entity
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            $property = $bookingDeposit->unit ? $bookingDeposit->unit->property : null; // Lấy property từ unit → Dùng để lấy payment_due_hours
            
            if ($property) { // Nếu có property
                $paymentDueMinutes = $property->getEffectivePaymentDueHours(); // Lấy payment_due_hours từ property → Property cycle có priority cao nhất
            } else { // Nếu không có property
                $organization = $bookingDeposit->organization; // Lấy organization từ deposit → Fallback
                $paymentDueMinutes = $organization ? $organization->getEffectivePaymentDueHours() : 4320; // Lấy từ organization hoặc default 4320 phút (3 ngày) → Fallback
            }
            
            $approvedAt = now(); // Lấy thời gian hiện tại → Dùng làm approved_at và tính payment_due_date
            $paymentDueDate = $approvedAt->copy()->addMinutes($paymentDueMinutes); // Tính hạn chót thanh toán = thời gian duyệt + số phút cho phép

            $bookingDeposit->update([ // Cập nhật booking deposit
                'payment_status' => 'pending', // Chuyển trạng thái từ "Chờ duyệt" sang "Chờ thanh toán"
                'approved_at' => $approvedAt, // Lưu thời gian duyệt
                'approved_by' => $user->id, // Lưu user ID duyệt
                'payment_due_date' => $paymentDueDate, // Lưu hạn chót thanh toán
            ]);

            // Tự động tạo Invoice
            $prefillData = $this->calculateInvoiceDataForBookingDeposit($bookingDeposit);
            
            $invoiceNo = \App\Models\Invoice::generateInvoiceNumber($organizationId);
            
            $invoice = \App\Models\Invoice::create([
                'organization_id' => $organizationId,
                'is_auto_created' => true,
                'booking_deposit_id' => $bookingDeposit->id,
                'invoice_no' => $invoiceNo,
                'invoice_type' => \App\Models\Invoice::TYPE_BOOKING_DEPOSIT,
                'issue_date' => $prefillData['issue_date'],
                'due_date' => $prefillData['due_date'],
                'status' => 'issued', // Phát hành luôn để khách có thể quét mã thanh toán
                'subtotal' => $prefillData['subtotal'],
                'tax_amount' => $prefillData['tax_amount'],
                'discount_amount' => $prefillData['discount_amount'],
                'total_amount' => $prefillData['total_amount'],
                'currency' => $prefillData['currency'],
                'note' => $prefillData['note'],
                'created_by' => $user->id,
            ]);

            // Add invoice items
            foreach ($prefillData['items'] as $itemData) {
                $invoice->items()->create([
                    'item_type' => $itemData['item_type'],
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'amount' => $itemData['amount'],
                ]);
            }

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi vào database

            $paymentDueHours = floor($paymentDueMinutes / 60); // Tính số giờ (chia 60, làm tròn xuống) → Format hiển thị
            $paymentDueMins = $paymentDueMinutes % 60; // Tính số phút còn lại (chia dư 60) → Format hiển thị
            $timeDisplay = $paymentDueHours . ' giờ'; // Tạo chuỗi hiển thị giờ → Format message
            if ($paymentDueMins > 0) { // Nếu có phút
                $timeDisplay .= ' ' . $paymentDueMins . ' phút'; // Thêm phút vào chuỗi → Format message
            }
            
            return response()->json([ // Trả về JSON thành công
                'success' => true, // Thành công
                'message' => 'Đặt cọc đã được duyệt thành công! Trạng thái chuyển sang "Chờ thanh toán". Phòng đã được chuyển sang trạng thái "Đã đặt cọc". Hạn chót thanh toán: ' . $paymentDueDate->format('d/m/Y H:i') . ' (sau ' . $timeDisplay . ')' // Thông báo thành công với thông tin chi tiết
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
            Log::error('Error approving booking deposit: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Có lỗi xảy ra khi duyệt đặt cọc.' // Thông báo lỗi
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Đánh dấu booking deposit đã thanh toán
     * 
     * MỤC ĐÍCH:
     * Đánh dấu booking deposit đã thanh toán (chuyển từ "Chờ thanh toán" sang "Đã thanh toán")
     * 
     * INPUT:
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id
     * - Database: booking_deposits, invoices
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "..."}
     * - Database: Cập nhật booking_deposits (payment_status, paid_at, paid_by)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Tìm booking deposit theo ID
     * 3. Kiểm tra deposit phải ở trạng thái "pending"
     * 4. Kiểm tra không có invoice liên kết chưa thanh toán
     * 5. Cập nhật payment_status = 'paid', paid_at, paid_by
     * 6. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Đọc booking deposit theo ID
     * - Bảng invoices: Kiểm tra invoices liên kết chưa thanh toán
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng booking_deposits: Cập nhật payment_status, paid_at, paid_by
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Chỉ có thể đánh dấu thanh toán khi payment_status = 'pending'
     * - Không cho phép đánh dấu thanh toán nếu có invoice liên kết chưa thanh toán
     */
    public function markPaid($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu paid_by
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Bạn không thuộc tổ chức nào.' // Thông báo lỗi
            ], 403); // HTTP 403 Forbidden
        }

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        if ($bookingDeposit->payment_status !== 'pending') { // Nếu deposit không ở trạng thái chờ thanh toán
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Chỉ có thể đánh dấu thanh toán cho đặt cọc ở trạng thái "Chờ thanh toán".' // Thông báo lỗi
            ], 422); // HTTP 422 Unprocessable Entity
        }

        $unpaidInvoices = $bookingDeposit->invoices() // Lấy invoices liên kết với deposit
            ->where('status', '!=', 'paid') // Loại bỏ invoices đã thanh toán
            ->where('status', '!=', 'cancelled') // Loại bỏ invoices đã hủy
            ->get(); // Lấy tất cả invoices chưa thanh toán → Kiểm tra có invoice chưa thanh toán không
        
        if ($unpaidInvoices->count() > 0) { // Nếu có invoice chưa thanh toán
            $invoiceNumbers = $unpaidInvoices->pluck('invoice_no')->join(', '); // Lấy danh sách số invoice → Hiển thị trong message
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => "Không thể đánh dấu thanh toán đặt cọc khi hóa đơn liên kết chưa thanh toán. Vui lòng thanh toán hóa đơn trước: {$invoiceNumbers}" // Thông báo lỗi với số invoice
            ], 422); // HTTP 422 Unprocessable Entity
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            $bookingDeposit->update([ // Cập nhật booking deposit
                'payment_status' => 'paid', // Chuyển trạng thái từ "Chờ thanh toán" sang "Đã thanh toán"
                'paid_at' => now(), // Lưu thời gian thanh toán
                'paid_by' => $user->id // Lưu user ID đánh dấu thanh toán
            ]);

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi vào database

            return response()->json([ // Trả về JSON thành công
                'success' => true, // Thành công
                'message' => 'Đặt cọc đã được đánh dấu thanh toán thành công!' // Thông báo thành công
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
            Log::error('Error marking booking deposit as paid: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Có lỗi xảy ra khi đánh dấu thanh toán.' // Thông báo lỗi
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Hủy booking deposit
     * 
     * MỤC ĐÍCH:
     * Hủy booking deposit (chuyển sang trạng thái "Đã hủy") và cập nhật unit status về 'available' nếu cần
     * 
     * INPUT:
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id
     * - Database: booking_deposits, units, invoices
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "..."}
     * - Database: Cập nhật booking_deposits (payment_status, expired_at), invoices (status), units (status)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Tìm booking deposit theo ID
     * 3. Kiểm tra deposit chưa thanh toán (payment_status != 'paid')
     * 4. Kiểm tra deposit có thể hủy (pending_approval, pending, expired)
     * 5. Cập nhật payment_status = 'cancelled', expired_at
     * 6. Hủy invoices liên quan (nếu chưa thanh toán)
     * 7. Cập nhật unit status về 'available' nếu không có deposits/leases khác active
     * 8. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Đọc booking deposit theo ID
     * - Bảng units: Đọc unit để cập nhật status
     * - Bảng invoices: Đọc invoices liên quan để hủy
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng booking_deposits: Cập nhật payment_status, expired_at
     * - Bảng invoices: Cập nhật status = 'cancelled' (nếu chưa thanh toán)
     * - Bảng units: Cập nhật status = 'available' (nếu không có deposits/leases khác)
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Không cho phép hủy deposit đã thanh toán (phải dùng refund)
     * - Chỉ có thể hủy deposit ở trạng thái pending_approval, pending, expired
     * - Unit status tự động update về 'available' nếu không có deposits/leases khác active
     */
    public function cancel($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để tracking
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Bạn không thuộc tổ chức nào.' // Thông báo lỗi
            ], 403); // HTTP 403 Forbidden
        }

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        if ($bookingDeposit->payment_status === 'paid') { // Nếu deposit đã thanh toán
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Không thể hủy đặt cọc đã thanh toán. Vui lòng sử dụng chức năng hoàn tiền nếu cần.' // Thông báo lỗi → Phải dùng refund
            ], 422); // HTTP 422 Unprocessable Entity
        }

        if (!in_array($bookingDeposit->payment_status, ['pending_approval', 'pending', 'expired'])) { // Nếu deposit không ở trạng thái có thể hủy
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Chỉ có thể hủy đặt cọc ở trạng thái "Chờ duyệt", "Chờ thanh toán" hoặc "Hết hạn".' // Thông báo lỗi
            ], 422); // HTTP 422 Unprocessable Entity
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            $bookingDeposit->update([ // Cập nhật booking deposit
                'payment_status' => 'cancelled', // Chuyển trạng thái thành "Đã hủy"
                'expired_at' => now(), // Lưu thời gian hết hạn/hủy
            ]);

            $unit = $bookingDeposit->unit; // Lấy unit từ booking deposit → Cập nhật status
            if ($unit && $unit->status === 'reserved') { // Nếu unit đang ở trạng thái reserved
                $hasOtherActiveDeposits = BookingDeposit::where('unit_id', $unit->id) // Tìm deposits khác của unit
                    ->where('id', '!=', $bookingDeposit->id) // Loại bỏ deposit hiện tại
                    ->whereIn('payment_status', ['pending_approval', 'pending', 'paid']) // Chỉ lấy deposits active
                    ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                    ->exists(); // Kiểm tra có tồn tại không → Có deposits khác đang active không

                $hasActiveLease = $unit->leases() // Lấy leases của unit
                    ->where('status', 'active') // Chỉ lấy leases active
                    ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                    ->exists(); // Kiểm tra có tồn tại không → Có lease active không

                if (!$hasOtherActiveDeposits && !$hasActiveLease) { // Nếu không có deposits/leases khác active
                    $unit->update(['status' => 'available']); // Cập nhật unit status về 'available' → Phòng trở lại trống
                }
            }

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi vào database

            return response()->json([ // Trả về JSON thành công
                'success' => true, // Thành công
                'message' => 'Đặt cọc đã được hủy thành công!' // Thông báo thành công
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
            Log::error('Error cancelling booking deposit: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Có lỗi xảy ra khi hủy đặt cọc.' // Thông báo lỗi
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Hoàn tiền booking deposit
     * 
     * MỤC ĐÍCH:
     * Hoàn tiền booking deposit đã thanh toán (chuyển sang trạng thái "Đã hoàn tiền")
     * 
     * INPUT:
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id
     * - Database: booking_deposits
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "..."}
     * - Database: Cập nhật booking_deposits (payment_status, refunded_at, refunded_by)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Tìm booking deposit theo ID
     * 3. Cập nhật payment_status = 'refunded', refunded_at, refunded_by
     * 4. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Đọc booking deposit theo ID
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng booking_deposits: Cập nhật payment_status, refunded_at, refunded_by
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Thường dùng cho deposit đã thanh toán (payment_status = 'paid')
     * - Không có validation chặt chẽ về trạng thái (có thể refund từ bất kỳ trạng thái nào)
     */
    public function refund($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu refunded_by
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Bạn không thuộc tổ chức nào.' // Thông báo lỗi
            ], 403); // HTTP 403 Forbidden
        }

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        try {
            $bookingDeposit->update([ // Cập nhật booking deposit
                'payment_status' => 'refunded', // Chuyển trạng thái thành "Đã hoàn tiền"
                'refunded_at' => now(), // Lưu thời gian hoàn tiền
                'refunded_by' => $user->id // Lưu user ID thực hiện hoàn tiền
            ]);

            return response()->json([ // Trả về JSON thành công
                'success' => true, // Thành công
                'message' => 'Đặt cọc đã được hoàn tiền thành công!' // Thông báo thành công
            ]);

        } catch (\Exception $e) {
            Log::error('Error refunding booking deposit: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Có lỗi xảy ra khi hoàn tiền.' // Thông báo lỗi
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Lấy thống kê booking deposits
     * 
     * MỤC ĐÍCH:
     * Lấy thống kê số lượng booking deposits theo các trạng thái
     * 
     * INPUT:
     * - Session: organization_id
     * - Database: booking_deposits
     * 
     * OUTPUT:
     * - JSON: {total: ..., pending: ..., approved: ..., paid: ..., cancelled: ..., refunded: ...}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra organization ID
     * 2. Đếm số lượng deposits theo từng trạng thái
     * 3. Trả về JSON với statistics
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Đếm số lượng theo payment_status
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function statistics()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }

        $stats = [
            'total' => BookingDeposit::where('organization_id', $organizationId)->count(), // Đếm tổng số deposits → Thống kê tổng
            'pending' => BookingDeposit::where('organization_id', $organizationId) // Tìm deposits của organization
                ->where('payment_status', 'pending')->count(), // Đếm deposits chờ thanh toán → Thống kê pending
            'approved' => BookingDeposit::where('organization_id', $organizationId) // Tìm deposits của organization
                ->where('payment_status', 'approved')->count(), // Đếm deposits đã duyệt → Thống kê approved
            'paid' => BookingDeposit::where('organization_id', $organizationId) // Tìm deposits của organization
                ->where('payment_status', 'paid')->count(), // Đếm deposits đã thanh toán → Thống kê paid
            'cancelled' => BookingDeposit::where('organization_id', $organizationId) // Tìm deposits của organization
                ->where('payment_status', 'cancelled')->count(), // Đếm deposits đã hủy → Thống kê cancelled
            'refunded' => BookingDeposit::where('organization_id', $organizationId) // Tìm deposits của organization
                ->where('payment_status', 'refunded')->count(), // Đếm deposits đã hoàn tiền → Thống kê refunded
        ];

        return response()->json($stats); // Trả về JSON với statistics → Dùng cho API/dashboard
    }

    /**
     * Lấy danh sách tenants cho organization
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách tenants từ organization hiện tại và Default Organization (ID=3) để hiển thị trong dropdown
     * 
     * INPUT:
     * - Session: organization_id
     * - Database: users, user_profiles, user_roles, organization_users
     * 
     * OUTPUT:
     * - JSON: {tenants: [...]}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra organization ID
     * 2. Lấy tenants từ organization hiện tại và Default Organization (ID=3)
     * 3. Filter chỉ lấy users có role 'tenant'
     * 4. Trả về JSON với danh sách tenants
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy users có role tenant
     * - Bảng user_profiles: Lấy thông tin profile (full_name)
     * - Bảng user_roles: Filter role 'tenant'
     * - Bảng organization_users: Filter theo organization IDs
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * - Logs: Ghi log nếu có lỗi
     */
    public function getTenants()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
                return response()->json(['tenants' => []], 401); // Trả về JSON rỗng với HTTP 401
            }

            $tenantOrgIds = collect([$organizationId, 3]); // Tạo collection chứa organization ID hiện tại và Default Organization (ID=3) → Cho phép tenant từ 2 org
            
            $tenants = User::select([ // Select chỉ các columns cần thiết → Tối ưu performance
                    'users.id', // ID của user
                    'user_profiles.full_name', // Tên đầy đủ từ profile
                    'users.phone', // Số điện thoại
                    'users.email' // Email
                ])
                ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id') // JOIN với user_profiles → Lấy full_name
                ->whereHas('userRoles', function($q) { // Chỉ lấy users có role tenant → Filter role
                    $q->where('key_code', 'tenant'); // Tìm role có key_code = 'tenant'
                })
                ->whereHas('organizations', function($q) use ($tenantOrgIds) { // Chỉ lấy users thuộc một trong các organizations → Bảo mật
                    $q->whereIn('organization_id', $tenantOrgIds); // Filter theo organization IDs
                })
                ->whereNull('users.deleted_at') // Chỉ lấy users chưa bị xóa
                ->orderBy('user_profiles.full_name') // Sắp xếp theo tên → Dễ tìm kiếm
                ->get(); // Lấy tất cả kết quả → Dùng để hiển thị trong dropdown

            return response()->json(['tenants' => $tenants]); // Trả về JSON với danh sách tenants → Dùng cho AJAX dropdown
            
        } catch (\Exception $e) {
            Log::error('Error getting tenants for manager', [ // Ghi log lỗi → Để debug
                'user_id' => Auth::id(), // ID của user
                'error' => $e->getMessage() // Error message
            ]);
            
            return response()->json(['tenants' => []], 500); // Trả về JSON rỗng với HTTP 500 → Fallback
        }
    }

    /**
     * Lấy danh sách units cho property
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách units available của property để hiển thị trong dropdown (chưa có lease active)
     * 
     * INPUT:
     * - URL Parameter: $propertyId (ID của property)
     * - Session: organization_id
     * - Database: units, properties, leases
     * 
     * OUTPUT:
     * - JSON: {units: [...]}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra organization ID
     * 2. Lấy units của property (status = 'available')
     * 3. Loại bỏ units có lease active
     * 4. Trả về JSON với danh sách units
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng units: Lấy units của property
     * - Bảng properties: Kiểm tra property thuộc organization
     * - Bảng leases: Kiểm tra units có lease active không
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Chỉ lấy units có status = 'available'
     * - Loại bỏ units có lease active
     * - Cho phép units có booking deposit (để tạo booking deposit mới)
     */
    public function getUnits($propertyId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
            
            if (!$organizationId) { // Nếu không có organization ID
                return response()->json(['units' => []], 401); // Trả về JSON rỗng với HTTP 401
            }

            $units = Unit::where('property_id', $propertyId) // Tìm units của property
                ->where('status', 'available') // Chỉ lấy phòng có status 'available' → Phòng trống
                ->whereHas('property', function($q) use ($organizationId) { // Chỉ lấy units có property thuộc organization → Bảo mật
                    $q->where('organization_id', $organizationId); // Filter theo organization ID
                })
                ->whereDoesntHave('leases', function($q) { // Loại bỏ phòng có hợp đồng thuê đang hoạt động → Tránh conflict
                    $q->where('status', 'active') // Chỉ kiểm tra leases có status 'active'
                      ->whereNull('deleted_at'); // Chỉ kiểm tra leases chưa bị xóa
                })
                ->with('property') // Eager load property relationship → Tránh N+1 queries
                ->select(['id', 'code', 'property_id', 'floor', 'area_m2']) // Select chỉ columns cần thiết → Tối ưu performance
                ->orderBy('code') // Sắp xếp theo code → Dễ tìm kiếm
                ->get() // Lấy tất cả kết quả
                ->map(function($unit) { // Map data để format response → Chỉ trả về data cần thiết
                    return [
                        'id' => $unit->id, // ID của unit
                        'code' => $unit->code, // Mã phòng
                        'property_id' => $unit->property_id, // ID của property
                        'floor' => $unit->floor, // Tầng
                        'area_m2' => $unit->area_m2, // Diện tích (m2)
                        'property_name' => $unit->property ? $unit->property->name : 'Unknown Property' // Tên property → Fallback nếu không có
                    ];
                });

            return response()->json(['units' => $units]); // Trả về JSON với danh sách units → Dùng cho AJAX dropdown
            
        } catch (\Exception $e) {
            Log::error('Error getting units for property', [ // Ghi log lỗi → Để debug
                'user_id' => Auth::id(), // ID của user
                'property_id' => $propertyId, // ID của property
                'error' => $e->getMessage() // Error message
            ]);
            
            return response()->json(['units' => []], 500); // Trả về JSON rỗng với HTTP 500 → Fallback
        }
    }

    /**
     * Cập nhật trạng thái thanh toán booking deposit
     * 
     * MỤC ĐÍCH:
     * Cập nhật trạng thái thanh toán của booking deposit với validation transitions và xử lý logic đặc biệt
     * 
     * INPUT:
     * - Request: payment_status (pending_approval, pending, paid, refunded, expired, cancelled)
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id
     * - Database: booking_deposits, units, invoices
     * 
     * OUTPUT:
     * - Redirect: Redirect về show page với success/error message
     * - Database: Cập nhật booking_deposits, invoices, units
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate payment_status
     * 2. Kiểm tra status transition hợp lệ
     * 3. Xử lý logic đặc biệt cho từng transition:
     *    - pending_approval -> pending: Tính payment_due_date, update unit status
     *    - pending -> paid: Set paid_at
     *    - -> cancelled: Hủy invoices, update unit status
     *    - -> expired: Expire invoices
     * 4. Cập nhật booking deposit
     * 5. Redirect về show page
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Đọc booking deposit theo ID
     * - Bảng units: Đọc unit để cập nhật status
     * - Bảng invoices: Đọc invoices liên quan để hủy/expire
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng booking_deposits: Cập nhật payment_status và các fields liên quan
     * - Bảng invoices: Cập nhật status (nếu cần)
     * - Bảng units: Cập nhật status (nếu cần)
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Validate status transitions (không cho phép chuyển trạng thái không hợp lệ)
     * - Không cho phép chuyển sang 'paid' nếu có invoice chưa thanh toán
     * - Tự động tính payment_due_date khi approve (pending_approval -> pending)
     */
    public function updateStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu approved_by/paid_by
        
        $this->requireCapability('contract.booking_deposit.update', 'Bạn không có quyền cập nhật trạng thái đặt cọc.'); // Kiểm tra quyền cập nhật → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON lỗi
                'success' => false, // Thất bại
                'message' => 'Bạn không thuộc tổ chức nào.' // Thông báo lỗi
            ], 403); // HTTP 403 Forbidden
        }

        $validated = $request->validate([ // Validate request data → Đảm bảo payment_status hợp lệ
            'payment_status' => 'required|in:pending_approval,pending,paid,refunded,expired,cancelled', // payment_status: bắt buộc, một trong 6 trạng thái → Đảm bảo giá trị hợp lệ
        ]);

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        $oldStatus = $bookingDeposit->payment_status; // Lấy trạng thái hiện tại → Dùng để validate transition
        $newStatus = $validated['payment_status']; // Lấy trạng thái mới từ request → Dùng để cập nhật

        $allowedTransitions = [ // Định nghĩa các transitions hợp lệ → Business rules
            'pending_approval' => ['pending', 'cancelled'], // pending_approval có thể chuyển sang pending hoặc cancelled
            'pending' => ['paid', 'expired', 'cancelled'], // pending có thể chuyển sang paid, expired, hoặc cancelled
            'paid' => ['expired', 'refunded'], // paid có thể chuyển sang expired hoặc refunded → Không cho phép chuyển sang cancelled
            'refunded' => [], // refunded không thể chuyển sang trạng thái nào → Final state
            'expired' => ['cancelled', 'paid'], // expired có thể chuyển lại sang paid hoặc cancelled → Cho phép khôi phục
            'cancelled' => ['pending_approval', 'pending', 'paid'], // cancelled có thể chuyển sang các trạng thái khác → Cho phép khôi phục
        ];

        if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) { // Nếu transition không hợp lệ
            return back()->with('error', "Không thể chuyển từ trạng thái '{$oldStatus}' sang '{$newStatus}'."); // Trả về lỗi → Business rule validation
        }

        if ($newStatus === 'paid') { // Nếu chuyển sang trạng thái 'paid'
            $unpaidInvoices = $bookingDeposit->invoices() // Lấy invoices liên kết với deposit
                ->where('status', '!=', 'paid') // Loại bỏ invoices đã thanh toán
                ->where('status', '!=', 'cancelled') // Loại bỏ invoices đã hủy
                ->get(); // Lấy tất cả invoices chưa thanh toán → Kiểm tra có invoice chưa thanh toán không
            
            if ($unpaidInvoices->count() > 0) { // Nếu có invoice chưa thanh toán
                $invoiceNumbers = $unpaidInvoices->pluck('invoice_no')->join(', '); // Lấy danh sách số invoice → Hiển thị trong message
                return back()->with('error', "Không thể chuyển đặt cọc sang trạng thái 'Đã thanh toán' khi hóa đơn liên kết chưa thanh toán. Vui lòng thanh toán hóa đơn trước: {$invoiceNumbers}"); // Trả về lỗi → Business rule validation
            }
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            $updateData = ['payment_status' => $newStatus]; // Khởi tạo array cập nhật → Sẽ thêm các fields khác tùy transition

            if ($newStatus === 'pending' && $oldStatus === 'pending_approval') { // Nếu transition: pending_approval -> pending (approve)
                $property = $bookingDeposit->unit ? $bookingDeposit->unit->property : null; // Lấy property từ unit → Dùng để lấy payment_due_hours
                
                if ($property) { // Nếu có property
                    $paymentDueMinutes = $property->getEffectivePaymentDueHours(); // Lấy payment_due_hours từ property → Property cycle có priority cao nhất
                } else { // Nếu không có property
                    $organization = $bookingDeposit->organization; // Lấy organization từ deposit → Fallback
                    $paymentDueMinutes = $organization ? $organization->getEffectivePaymentDueHours() : 4320; // Lấy từ organization hoặc default 4320 phút (3 ngày) → Fallback
                }
                
                $approvedAt = now(); // Lấy thời gian hiện tại → Dùng làm approved_at và tính payment_due_date
                $updateData['approved_at'] = $approvedAt; // Lưu thời gian duyệt
                $updateData['approved_by'] = $user->id; // Lưu user ID duyệt
                $updateData['payment_due_date'] = $approvedAt->copy()->addMinutes($paymentDueMinutes); // Tính hạn chót thanh toán = thời gian duyệt + số phút cho phép

                if ($bookingDeposit->unit && !in_array($bookingDeposit->unit->status, ['reserved', 'occupied'])) { // Nếu có unit và status không phải reserved/occupied
                    $bookingDeposit->unit->update(['status' => 'reserved']); // Cập nhật unit status thành 'reserved' → Đánh dấu phòng đã được giữ
                }
            } elseif ($newStatus === 'paid' && $oldStatus === 'pending') { // Nếu transition: pending -> paid
                $updateData['paid_at'] = now(); // Lưu thời gian thanh toán
            } elseif ($newStatus === 'cancelled') { // Nếu transition: -> cancelled
                $updateData['expired_at'] = now(); // Lưu thời gian hết hạn/hủy
                
                $invoices = $bookingDeposit->invoices() // Lấy invoices liên kết với deposit
                    ->where('status', '!=', 'paid') // Loại bỏ invoices đã thanh toán
                    ->where('status', '!=', 'cancelled') // Loại bỏ invoices đã hủy
                    ->get(); // Lấy tất cả invoices cần hủy
                
                foreach ($invoices as $invoice) { // Duyệt qua từng invoice
                    $invoice->update(['status' => 'cancelled']); // Hủy invoice → Đảm bảo data consistency
                }
                
                if ($bookingDeposit->unit && $bookingDeposit->unit->status === 'reserved') { // Nếu có unit và đang ở trạng thái reserved
                    $hasOtherActiveDeposits = BookingDeposit::where('unit_id', $bookingDeposit->unit_id) // Tìm deposits khác của unit
                        ->where('id', '!=', $bookingDeposit->id) // Loại bỏ deposit hiện tại
                        ->whereIn('payment_status', ['pending_approval', 'pending', 'paid']) // Chỉ lấy deposits active
                        ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                        ->exists(); // Kiểm tra có tồn tại không → Có deposits khác đang active không
                    
                    $hasActiveLease = $bookingDeposit->unit->leases() // Lấy leases của unit
                        ->where('status', 'active') // Chỉ lấy leases active
                        ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                        ->exists(); // Kiểm tra có tồn tại không → Có lease active không
                    
                    if (!$hasOtherActiveDeposits && !$hasActiveLease) { // Nếu không có deposits/leases khác active
                        $bookingDeposit->unit->update(['status' => 'available']); // Cập nhật unit status về 'available' → Phòng trở lại trống
                    }
                }
            } elseif ($newStatus === 'expired') { // Nếu transition: -> expired
                $updateData['expired_at'] = now(); // Lưu thời gian hết hạn
                
                $invoices = $bookingDeposit->invoices() // Lấy invoices liên kết với deposit
                    ->where('status', '!=', 'paid') // Loại bỏ invoices đã thanh toán
                    ->where('status', '!=', 'cancelled') // Loại bỏ invoices đã hủy
                    ->get(); // Lấy tất cả invoices cần expire
                
                foreach ($invoices as $invoice) { // Duyệt qua từng invoice
                    $invoice->update(['status' => 'expired']); // Expire invoice → Đánh dấu invoice hết hạn
                }
            }

            $bookingDeposit->update($updateData); // Cập nhật booking deposit với tất cả fields → Lưu thay đổi

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi vào database

            $statusLabels = [ // Định nghĩa labels cho các trạng thái → Hiển thị message
                'pending_approval' => 'Chờ duyệt', // Label cho pending_approval
                'pending' => 'Chờ thanh toán', // Label cho pending
                'paid' => 'Đã thanh toán', // Label cho paid
                'refunded' => 'Đã hoàn tiền', // Label cho refunded
                'expired' => 'Hết hạn', // Label cho expired
                'cancelled' => 'Đã hủy', // Label cho cancelled
            ];

            return redirect()->route('staff.booking-deposits.show', $bookingDeposit->id) // Chuyển đến trang chi tiết
                ->with('success', "Trạng thái đặt cọc đã được chuyển từ '{$statusLabels[$oldStatus]}' sang '{$statusLabels[$newStatus]}'."); // Thông báo thành công với labels
                
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
            Log::error('Error updating booking deposit status: ' . $e->getMessage(), [ // Ghi log lỗi → Để debug
                'booking_deposit_id' => $bookingDeposit->id, // ID của deposit
                'old_status' => $oldStatus, // Trạng thái cũ
                'new_status' => $newStatus, // Trạng thái mới
                'error' => $e->getTraceAsString() // Stack trace → Chi tiết lỗi
            ]);
            
            return back()->with('error', 'Có lỗi xảy ra khi thay đổi trạng thái đặt cọc: ' . $e->getMessage()); // Trả về lỗi → Hiển thị message
        }
    }

    /**
     * Tạo invoice từ booking deposit
     * 
     * MỤC ĐÍCH:
     * Tạo invoice từ booking deposit đã được phê duyệt (pending hoặc paid), pre-fill thông tin từ deposit
     * 
     * INPUT:
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id, booking_deposit_invoice_prefill
     * - Database: booking_deposits, invoices
     * 
     * OUTPUT:
     * - Redirect: Redirect đến trang tạo invoice với pre-fill data
     * - Session: Lưu pre-fill data vào session
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Lấy booking deposit theo ID
     * 3. Kiểm tra deposit đã được phê duyệt (pending hoặc paid)
     * 4. Kiểm tra chưa có invoice liên kết
     * 5. Tính toán pre-fill data (issue_date, due_date, items, totals)
     * 6. Lưu pre-fill data vào session
     * 7. Redirect đến trang tạo invoice
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Đọc booking deposit theo ID
     * - Bảng invoices: Kiểm tra invoice đã tồn tại chưa
     * 
     * DỮ LIỆU GHI VÀO:
     * - Session: Lưu booking_deposit_invoice_prefill
     * 
     * LƯU Ý:
     * - Chỉ cho phép tạo invoice khi deposit đã được phê duyệt (pending hoặc paid)
     * - Pre-fill data được tính từ deposit amount và payment_due_date
     * - Redirect đến invoice create page với booking_deposit_id parameter
     */
    public function createInvoice(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $this->requireCapability('billing.invoice.create', 'Bạn không có quyền tạo hóa đơn từ đặt cọc.'); // Kiểm tra quyền tạo invoice → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->with(['unit.property', 'tenantUser', 'lead', 'organization']) // Eager load relationships → Tránh N+1 queries
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        if (!in_array($bookingDeposit->payment_status, ['pending', 'paid'])) { // Nếu deposit chưa được phê duyệt
            return back()->with('error', 'Chỉ có thể tạo hóa đơn cho đặt cọc đã được phê duyệt (Chờ thanh toán hoặc Đã thanh toán).'); // Trả về lỗi → Business rule validation
        }

        $existingInvoice = \App\Models\Invoice::where('booking_deposit_id', $bookingDeposit->id) // Tìm invoice liên kết với deposit
            ->where('status', '!=', 'cancelled') // Chỉ kiểm tra invoices chưa bị hủy → Loại bỏ invoices đã hủy
            ->first(); // Lấy invoice đầu tiên → Kiểm tra đã có invoice chưa

        if ($existingInvoice) { // Nếu đã có invoice
            return redirect()->route('staff.invoices.show', $existingInvoice->id) // Chuyển đến trang chi tiết invoice
                ->with('info', 'Đặt cọc này đã có hóa đơn.'); // Thông báo thông tin
        }

        $prefillData = $this->calculateInvoiceDataForBookingDeposit($bookingDeposit); // Tính toán pre-fill data → Dữ liệu điền sẵn cho form

        session(['booking_deposit_invoice_prefill' => $prefillData]); // Lưu vào session → Truyền sang trang create invoice

        return redirect()->route('staff.invoices.create', ['booking_deposit_id' => $bookingDeposit->id]) // Chuyển đến trang tạo invoice
            ->with('info', 'Thông tin hóa đơn đã được điền sẵn từ đặt cọc. Vui lòng kiểm tra và tạo hóa đơn.'); // Thông báo thông tin
    }

    /**
     * Tạo lease từ booking deposit
     * 
     * MỤC ĐÍCH:
     * Tạo lease từ booking deposit đã thanh toán, pre-fill thông tin từ deposit
     * 
     * INPUT:
     * - URL Parameter: $id (ID của booking deposit)
     * - Session: organization_id, booking_deposit_id_for_lease
     * - Database: booking_deposits, leases
     * 
     * OUTPUT:
     * - Redirect: Redirect đến trang tạo lease với pre-fill parameters
     * - Session: Lưu booking_deposit_id_for_lease vào session
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền và organization ID
     * 2. Lấy booking deposit theo ID
     * 3. Kiểm tra deposit đã thanh toán (payment_status = 'paid')
     * 4. Kiểm tra chưa có lease liên kết
     * 5. Lưu booking_deposit_id vào session
     * 6. Redirect đến trang tạo lease với pre-fill parameters
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng booking_deposits: Đọc booking deposit theo ID
     * - Bảng leases: Kiểm tra lease đã tồn tại chưa
     * 
     * DỮ LIỆU GHI VÀO:
     * - Session: Lưu booking_deposit_id_for_lease
     * 
     * LƯU Ý:
     * - Chỉ cho phép tạo lease khi deposit đã thanh toán (payment_status = 'paid')
     * - Pre-fill property_id, unit_id từ deposit
     * - Redirect đến lease create page với booking_deposit_id parameter
     */
    public function createLease(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $this->requireCapability('contract.lease.create', 'Bạn không có quyền tạo hợp đồng từ đặt cọc.'); // Kiểm tra quyền tạo lease → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }

        $bookingDeposit = BookingDeposit::where('organization_id', $organizationId) // Tìm deposit của organization
            ->with(['unit.property', 'tenantUser', 'lead', 'organization', 'agent']) // Eager load relationships → Tránh N+1 queries
            ->findOrFail($id); // Tìm theo ID → Throw 404 nếu không tìm thấy

        if ($bookingDeposit->payment_status !== 'paid') { // Nếu deposit chưa thanh toán
            return back()->with('error', 'Chỉ có thể tạo hợp đồng từ đặt cọc đã thanh toán.'); // Trả về lỗi → Business rule validation
        }

        $existingLease = \App\Models\Lease::where('booking_id', $bookingDeposit->id) // Tìm lease liên kết với deposit
            ->whereNull('deleted_at') // Chỉ kiểm tra leases chưa bị xóa → Loại bỏ leases đã xóa
            ->first(); // Lấy lease đầu tiên → Kiểm tra đã có lease chưa

        if ($existingLease) { // Nếu đã có lease
            return redirect()->route('staff.leases.show', $existingLease->id) // Chuyển đến trang chi tiết lease
                ->with('info', 'Đặt cọc này đã có hợp đồng.'); // Thông báo thông tin
        }

        session(['booking_deposit_id_for_lease' => $bookingDeposit->id]); // Lưu booking_deposit_id vào session → Pre-fill trong trang create lease

        return redirect()->route('staff.leases.create', [ // Chuyển đến trang tạo lease
            'booking_deposit_id' => $bookingDeposit->id, // ID của deposit → Pre-fill form
            'property_id' => $bookingDeposit->unit->property_id ?? null, // ID của property → Pre-fill form
            'unit_id' => $bookingDeposit->unit_id ?? null, // ID của unit → Pre-fill form
        ])->with('info', 'Thông tin hợp đồng đã được điền sẵn từ đặt cọc. Vui lòng kiểm tra và tạo hợp đồng.'); // Thông báo thông tin
    }

    /**
     * Tính toán dữ liệu pre-fill cho invoice từ booking deposit
     * 
     * MỤC ĐÍCH:
     * Tính toán các thông tin cần thiết để pre-fill form tạo invoice từ booking deposit
     * 
     * INPUT:
     * - Parameter: $bookingDeposit (BookingDeposit model instance)
     * - Database: organizations, properties, units
     * 
     * OUTPUT:
     * - Array: Pre-fill data cho invoice (issue_date, due_date, items, totals, ...)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization từ deposit
     * 2. Tính issue_date = now()
     * 3. Tính due_date (ưu tiên payment_due_date từ deposit > property cycle > org default)
     * 4. Tạo invoice items từ deposit amount
     * 5. Tính subtotal, tax, discount, total
     * 6. Trả về array pre-fill data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model bookingDeposit: Đọc amount, payment_due_date, unit, property
     * - Model organization: Đọc payment_due_hours mặc định
     * - Model property: Đọc payment_due_hours (nếu có)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ tính toán)
     * 
     * LƯU Ý:
     * - Priority cho due_date: payment_due_date > property cycle > organization default
     * - Invoice item mô tả: "Tiền cọc - [Property Name] [Unit Code]"
     * - Tax và discount mặc định = 0
     */
    private function calculateInvoiceDataForBookingDeposit(BookingDeposit $bookingDeposit)
    {
        $organization = $bookingDeposit->organization; // Lấy organization từ deposit → Dùng để lấy payment_due_hours mặc định
        
        $issueDate = now()->format('Y-m-d'); // Lấy ngày hiện tại → Ngày phát hành invoice
        
        $dueDate = null; // Khởi tạo dueDate = null → Sẽ được tính sau
        if ($bookingDeposit->payment_due_date) { // Nếu deposit có payment_due_date
            $dueDate = $bookingDeposit->payment_due_date->format('Y-m-d'); // Dùng payment_due_date từ deposit → Priority cao nhất
        } else { // Nếu không có payment_due_date
            $property = $bookingDeposit->unit ? $bookingDeposit->unit->property : null; // Lấy property từ unit → Dùng để lấy payment_due_hours
            
            if ($property) { // Nếu có property
                $paymentDueMinutes = $property->getEffectivePaymentDueHours(); // Lấy payment_due_hours từ property → Property cycle có priority cao nhất
            } else { // Nếu không có property
                $paymentDueMinutes = $organization ? $organization->getEffectivePaymentDueHours() : 4320; // Lấy từ organization hoặc default 4320 phút (3 ngày) → Fallback
            }
            
            $dueDate = now()->addMinutes($paymentDueMinutes)->format('Y-m-d'); // Tính dueDate = now + payment_due_minutes → Hạn chót thanh toán
        }

        $items = [ // Tạo array chứa invoice items → Mô tả các khoản thanh toán
            [
                'description' => 'Tiền cọc - ' . ($bookingDeposit->unit->property->name ?? '') . ' ' . ($bookingDeposit->unit->code ?? ''), // Mô tả: Tiền cọc - [Property Name] [Unit Code] → Hiển thị trong invoice
                'quantity' => 1, // Số lượng = 1 → Đơn vị tính
                'unit_price' => $bookingDeposit->amount, // Đơn giá = số tiền deposit → Giá mỗi đơn vị
                'amount' => $bookingDeposit->amount, // Thành tiền = số tiền deposit → Tổng tiền của item
                'item_type' => 'deposit', // Loại item = deposit → Phân loại item
            ]
        ];

        $subtotal = $bookingDeposit->amount; // Tổng tiền trước thuế = số tiền deposit → Tổng tiền items
        $taxAmount = 0; // Thuế = 0 → Mặc định không có thuế
        $discountAmount = 0; // Giảm giá = 0 → Mặc định không có giảm giá
        $totalAmount = $subtotal + $taxAmount - $discountAmount; // Tổng tiền = subtotal + tax - discount → Tổng tiền cuối cùng

        return [ // Trả về array pre-fill data → Dùng để điền sẵn form invoice
            'booking_deposit_id' => $bookingDeposit->id, // ID của deposit → Liên kết invoice với deposit
            'issue_date' => $issueDate, // Ngày phát hành → Ngày tạo invoice
            'due_date' => $dueDate, // Ngày hết hạn → Hạn chót thanh toán
            'status' => 'draft', // Trạng thái = draft → Invoice ở trạng thái nháp
            'currency' => 'VND', // Đơn vị tiền tệ = VND → Đồng Việt Nam
            'subtotal' => $subtotal, // Tổng tiền trước thuế → Tổng tiền items
            'tax_amount' => $taxAmount, // Số tiền thuế → Thuế VAT
            'discount_amount' => $discountAmount, // Số tiền giảm giá → Chiết khấu
            'total_amount' => $totalAmount, // Tổng tiền cuối cùng → Tổng tiền phải thanh toán
            'note' => 'Hóa đơn cho đặt cọc #' . $bookingDeposit->reference_number, // Ghi chú → Mô tả invoice
            'items' => $items, // Danh sách items → Các khoản thanh toán
        ];
    }

}
