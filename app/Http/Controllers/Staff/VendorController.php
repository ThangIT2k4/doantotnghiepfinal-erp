<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\CashOutflow;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Controller: VendorController
 *
 * MỤC ĐÍCH:
 * Quản lý nhà cung cấp (vendors) trong module Finance - cho phép tạo, xem, sửa, xóa và quản lý thông tin nhà cung cấp, tích hợp với hệ thống thanh toán SePay
 *
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. index(): Hiển thị danh sách vendors với filter, search, sort, pagination và statistics
 * 2. create(): Hiển thị form tạo vendor mới
 * 3. store(): Tạo vendor mới với validation và transaction
 * 4. show(): Hiển thị chi tiết vendor kèm company invoices và cash outflows
 * 5. edit(): Hiển thị form chỉnh sửa vendor
 * 6. update(): Cập nhật thông tin vendor với validation và transaction
 * 7. updateStatus(): Cập nhật trạng thái vendor (active/inactive/suspended)
 * 8. destroy(): Xóa vendor (soft delete) với transaction
 * 9. getVendors(): API trả về danh sách vendors cho autocomplete/select2
 * 10. getBankInfo(): API trả về thông tin ngân hàng của vendor để thanh toán
 *
 * ENDPOINTS:
 * - GET /staff/vendors: Hiển thị danh sách vendors
 * - GET /staff/vendors/create: Hiển thị form tạo mới
 * - POST /staff/vendors: Tạo vendor mới
 * - GET /staff/vendors/{id}: Hiển thị chi tiết vendor
 * - GET /staff/vendors/{id}/edit: Hiển thị form chỉnh sửa
 * - PUT /staff/vendors/{id}: Cập nhật vendor
 * - DELETE /staff/vendors/{id}: Xóa vendor
 * - POST /staff/vendors/{id}/update-status: Cập nhật trạng thái
 * - GET /staff/api/vendors: API lấy danh sách vendors
 * - GET /staff/api/vendors/{id}/bank-info: API lấy thông tin ngân hàng
 *
 * DỮ LIỆU ĐỌC TỪ:
 * - Model Vendor (bảng vendors): Lấy danh sách và chi tiết vendors
 * - Model SepayBank (bảng sepay_banks): Lấy danh sách ngân hàng hỗ trợ SePay
 * - Model CompanyInvoice (bảng company_invoices): Lấy hóa đơn liên quan đến vendor
 * - Model CashOutflow (bảng cash_outflows): Lấy các khoản thanh toán cho vendor
 * - Trait ChecksCapabilities: Kiểm tra quyền truy cập
 *
 * DỮ LIỆU GHI VÀO:
 * - Bảng vendors: Tạo, cập nhật, xóa vendors
 * - Logs: Ghi log lỗi khi có exception
 *
 * LƯU Ý:
 * - Yêu cầu user phải đăng nhập (middleware auth)
 * - Yêu cầu organization phải có quyền finance.access
 * - Chỉ manager có quyền create/update/delete, agent chỉ có quyền view
 * - Vendor được soft delete (ghi deleted_by và deleted_at)
 * - Hỗ trợ HTMX cho filter, sort, pagination không reload trang
 * - Statistics (total, active, inactive, suspended) không bị ảnh hưởng bởi filter status
 */
class VendorController extends Controller
{
    use ChecksCapabilities;
    
    /**
     * Hiển thị danh sách vendors
     *
     * MỤC ĐÍCH:
     * Hiển thị danh sách nhà cung cấp với khả năng filter, search, sort, pagination và statistics, hỗ trợ HTMX để cập nhật không reload trang
     *
     * INPUT:
     * - Request: search (tìm kiếm), status (trạng thái), vendor_type (loại), sort_by, sort_order (sắp xếp)
     * - Session: organization_id (từ user context)
     * - Database: vendors (lấy danh sách vendors)
     *
     * OUTPUT:
     * - View: staff.finance.vendors.index (trả về view đầy đủ)
     * - HTML: Partial HTML với table và stats (nếu HTMX request)
     * - Data: vendors (paginated), stats (total, active, inactive, suspended), canManage, sortBy, sortOrder
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.access và finance.vendor.view
     * 2. Lấy organization ID từ user context
     * 3. Tính statistics (total, active, inactive, suspended) - không bị ảnh hưởng bởi filter status
     * 4. Build query với filters (search, status, vendor_type)
     * 5. Sort và paginate results (20 items/page)
     * 6. Nếu HTMX request: Trả về partial HTML với table và stats update
     * 7. Nếu request thường: Trả về view đầy đủ
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng vendors: Lấy danh sách vendors theo organization với filters
     *
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     *
     * LƯU Ý:
     * - Statistics được tính riêng biệt, không bị ảnh hưởng bởi filter status (chỉ bị ảnh hưởng bởi search và vendor_type)
     * - Hỗ trợ HTMX để cập nhật table và stats không reload trang
     * - Sort fields được validate để tránh SQL injection
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và lấy organization ID
        
        $hasFinanceAccess = $this->checkCapability('finance.access'); // Kiểm tra quyền truy cập module Finance → Dừng nếu không có quyền
        if (!$hasFinanceAccess) {
            abort(403, 'Bạn không có quyền truy cập module Finance.'); // Dừng request và trả về lỗi 403
        }
        
        $this->requireCapability('finance.vendor.view', 'Bạn không có quyền xem Vendors.'); // Kiểm tra quyền xem vendors → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter vendors theo organization
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        $query = Vendor::byOrganization($organizationId); // Tạo query lấy vendors của organization → Dùng để filter và paginate

        // Tính statistics trước khi áp dụng filters
        // Total: chỉ áp dụng search và vendor_type filters (không áp dụng status) → Để statistics không bị ảnh hưởng bởi filter status
        $totalQuery = Vendor::byOrganization($organizationId); // Query riêng để tính total → Không bị ảnh hưởng bởi filter status
        if ($request->filled('search')) { // Nếu có search
            $totalQuery->search($request->search); // Áp dụng filter search → Tìm kiếm theo name, tax_code, phone, email
        }
        if ($request->filled('vendor_type')) { // Nếu có filter vendor_type
            $totalQuery->byType($request->vendor_type); // Áp dụng filter vendor_type → Lọc theo loại (individual/company)
        }

        // Query tính stats cho active/inactive/suspended: chỉ áp dụng search và vendor_type filters (không áp dụng status)
        $statsQuery = Vendor::byOrganization($organizationId); // Query riêng để tính stats → Không bị ảnh hưởng bởi filter status
        if ($request->filled('search')) { // Nếu có search
            $statsQuery->search($request->search); // Áp dụng filter search → Tìm kiếm theo name, tax_code, phone, email
        }
        if ($request->filled('vendor_type')) { // Nếu có filter vendor_type
            $statsQuery->byType($request->vendor_type); // Áp dụng filter vendor_type → Lọc theo loại (individual/company)
        }

        // Tính statistics - Total không bị ảnh hưởng bởi filter status
        $stats = [
            'total' => (clone $totalQuery)->count(), // Đếm tổng số vendors → Hiển thị trong statistics card
            'active' => (clone $statsQuery)->byStatus('active')->count(), // Đếm số vendors đang hoạt động → Hiển thị trong statistics card
            'inactive' => (clone $statsQuery)->byStatus('inactive')->count(), // Đếm số vendors không hoạt động → Hiển thị trong statistics card
            'suspended' => (clone $statsQuery)->byStatus('suspended')->count(), // Đếm số vendors tạm ngưng → Hiển thị trong statistics card
        ];

        // Áp dụng filters cho query chính
        if ($request->filled('search')) { // Nếu có search
            $query->search($request->search); // Áp dụng filter search → Tìm kiếm theo name, tax_code, phone, email
        }

        if ($request->filled('status')) { // Nếu có filter status
            $query->byStatus($request->status); // Áp dụng filter status → Lọc theo trạng thái (active/inactive/suspended)
        }

        if ($request->filled('vendor_type')) { // Nếu có filter vendor_type
            $query->byType($request->vendor_type); // Áp dụng filter vendor_type → Lọc theo loại (individual/company)
        }

        // Sắp xếp - mặc định theo ID giảm dần
        $sortBy = $request->get('sort_by', 'id'); // Lấy field sắp xếp từ request → Mặc định là 'id'
        $sortOrder = $request->get('sort_order', 'desc'); // Lấy thứ tự sắp xếp từ request → Mặc định là 'desc'
        
        $allowedSortFields = ['id', 'name', 'created_at', 'status', 'vendor_type']; // Danh sách fields được phép sort → Tránh SQL injection
        if (!in_array($sortBy, $allowedSortFields)) { // Nếu field không hợp lệ
            $sortBy = 'id'; // Đặt về 'id' → Tránh SQL injection
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) { // Nếu sort order không hợp lệ
            $sortOrder = 'asc'; // Đặt về 'asc' → Tránh SQL injection
        }

        $vendors = $query->orderBy($sortBy, $sortOrder)->paginate(20); // Sắp xếp và phân trang 20 items/trang → Dùng để hiển thị danh sách
        
        $canManage = $this->checkCapability('finance.vendor.create'); // Kiểm tra quyền tạo vendor → Dùng để hiển thị/ẩn nút tạo mới

        $isHtmx = $request->header('HX-Request') === 'true'; // Kiểm tra có phải HTMX request không → Dùng để trả về partial HTML
        
        // Xử lý HTMX requests
        if ($isHtmx) { // Nếu là HTMX request
            try {
                $tableHtml = view('staff.finance.vendors.partials.table', [ // Render partial table → Dùng để cập nhật table không reload trang
                    'vendors' => $vendors,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder,
                ])->render();
                
                // Format stats cho response
                $statsFormatted = [
                    'total' => [
                        'value' => $stats['total'] ?? 0,
                        'label' => 'Tổng cộng',
                        'icon' => 'fa-list',
                        'color' => 'primary',
                        'filter' => '',
                    ],
                    'active' => [
                        'value' => $stats['active'] ?? 0,
                        'label' => 'Hoạt động',
                        'icon' => 'fa-check-circle',
                        'color' => 'success',
                        'filter' => 'active',
                    ],
                    'inactive' => [
                        'value' => $stats['inactive'] ?? 0,
                        'label' => 'Không hoạt động',
                        'icon' => 'fa-times-circle',
                        'color' => 'danger',
                        'filter' => 'inactive',
                    ],
                    'suspended' => [
                        'value' => $stats['suspended'] ?? 0,
                        'label' => 'Tạm ngưng',
                        'icon' => 'fa-pause-circle',
                        'color' => 'warning',
                        'filter' => 'suspended',
                    ],
                ];
                
                $statsHtml = view('staff.components.statistics-cards', [ // Render statistics cards → Dùng để cập nhật stats không reload trang
                    'stats' => $statsFormatted,
                    'currentFilter' => request('status', ''),
                    'filterKey' => 'status',
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'tableContainerId' => 'vendors-table-container',
                    'action' => route('staff.vendors.index'),
                    'columns' => 4
                ])->render();
                
                // Xử lý HTMX request - trả về HTML trực tiếp
                // Để swap innerHTML, cần extract nội dung bên trong từ tableHtml
                // Table partial bắt đầu với <div class="col-12">, cần extract nội dung bên trong
                $innerTableHtml = $tableHtml; // Mặc định dùng toàn bộ tableHtml
                // Tìm và extract nội dung bên trong div class="col-12"
                if (preg_match('/<div[^>]*class="col-12"[^>]*>(.*?)<\/div>\s*$/s', $tableHtml, $matches)) { // Tìm div class="col-12" và extract nội dung
                    $innerTableHtml = trim($matches[1]); // Lấy nội dung bên trong → Dùng để swap innerHTML
                }
                
                // Trả về inner HTML với stats update qua hx-swap-oob
                $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>"; // Kết hợp table và stats → Dùng để cập nhật cả table và stats
                
                return response($html) // Trả về HTML → HTMX sẽ swap vào container
                    ->header('HX-Push-Url', $request->fullUrl()); // Push URL vào browser history → Để back/forward hoạt động đúng
            } catch (\Exception $e) {
                Log::error('VendorController HTMX Error: ' . $e->getMessage()); // Ghi log lỗi → Để debug
                return response('<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu: ' . htmlspecialchars($e->getMessage()) . '</div>', 500); // Trả về lỗi → Hiển thị cho user
            }
        }

        return view('staff.finance.vendors.index', compact('vendors', 'canManage', 'stats', 'sortBy', 'sortOrder')); // Trả về view đầy đủ → Hiển thị trang danh sách
    }

    /**
     * Hiển thị form tạo vendor mới
     *
     * MỤC ĐÍCH:
     * Hiển thị form để tạo nhà cung cấp mới với đầy đủ thông tin cơ bản, ngân hàng và liên hệ
     *
     * INPUT:
     * - Session: organization_id (từ user context)
     * - Database: sepay_banks (lấy danh sách ngân hàng hỗ trợ SePay)
     *
     * OUTPUT:
     * - View: staff.finance.vendors.create
     * - Data: sepayBanks (danh sách ngân hàng hỗ trợ SePay)
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.vendor.create (chỉ manager)
     * 2. Lấy danh sách ngân hàng hỗ trợ SePay
     * 3. Trả về view form tạo mới
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng sepay_banks: Lấy danh sách ngân hàng được hỗ trợ (supported = 1)
     *
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     *
     * LƯU Ý:
     * - Chỉ manager có quyền tạo vendor
     */
    public function create()
    {
        $this->requireCapability('finance.vendor.create', 'Bạn không có quyền tạo Vendors.'); // Kiểm tra quyền tạo vendor → Dừng nếu không có quyền
        
        $sepayBanks = \App\Models\SepayBank::where('supported', 1)->orderBy('name')->get(); // Lấy danh sách ngân hàng hỗ trợ SePay → Dùng để hiển thị trong dropdown
        return view('staff.finance.vendors.create', compact('sepayBanks')); // Trả về view form tạo mới → Hiển thị form
    }

    /**
     * Tạo vendor mới
     *
     * MỤC ĐÍCH:
     * Tạo nhà cung cấp mới với đầy đủ thông tin cơ bản, ngân hàng và liên hệ, sử dụng transaction để đảm bảo data consistency
     *
     * INPUT:
     * - Request: name, tax_code, phone, email, address, sepay_bank_id, account_number, account_holder_name, branch_name, branch_code, swift_code, banking_notes, contact_person, contact_phone, contact_email, business_license, vendor_type, status
     * - Session: organization_id, user_id
     * - Database: sepay_banks (validate sepay_bank_id)
     *
     * OUTPUT:
     * - JSON: {success: true, message: "...", vendor_id: ...} (nếu AJAX)
     * - Redirect: Chuyển đến trang danh sách với success message (nếu request thường)
     * - Database: Tạo bản ghi mới trong bảng vendors
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.vendor.create
     * 2. Lấy organization ID từ user context
     * 3. Validate input (name, email, vendor_type, status, ...)
     * 4. Bắt đầu transaction
     * 5. Tạo vendor mới với tất cả thông tin
     * 6. Commit transaction
     * 7. Trả về JSON hoặc redirect với success message
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng sepay_banks: Validate sepay_bank_id tồn tại
     *
     * DỮ LIỆU GHI VÀO:
     * - Bảng vendors: Tạo bản ghi mới với organization_id, name, tax_code, phone, email, address, banking info, contact info, vendor_type, status
     * - Logs: Ghi log nếu có lỗi
     *
     * LƯU Ý:
     * - Chỉ manager có quyền tạo vendor
     * - Sử dụng transaction để đảm bảo data consistency
     * - Hỗ trợ cả AJAX và request thường
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu created_by (nếu cần)
        
        $this->requireCapability('finance.vendor.create', 'Bạn không có quyền tạo Vendors.'); // Kiểm tra quyền tạo vendor → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để gán vendor vào organization
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        $validator = Validator::make($request->all(), [ // Validate input → Đảm bảo dữ liệu hợp lệ
            'name' => 'required|string|max:255', // name: bắt buộc, string, tối đa 255 ký tự
            'tax_code' => 'nullable|string|max:50', // tax_code: không bắt buộc, string, tối đa 50 ký tự
            'phone' => 'nullable|string|max:30', // phone: không bắt buộc, string, tối đa 30 ký tự
            'email' => 'nullable|email|max:150', // email: không bắt buộc, phải là email hợp lệ, tối đa 150 ký tự
            'address' => 'nullable|string|max:255', // address: không bắt buộc, string, tối đa 255 ký tự
            'sepay_bank_id' => 'nullable|exists:sepay_banks,id', // sepay_bank_id: không bắt buộc, phải tồn tại trong bảng sepay_banks
            'account_number' => 'nullable|string|max:50', // account_number: không bắt buộc, string, tối đa 50 ký tự
            'account_holder_name' => 'nullable|string|max:255', // account_holder_name: không bắt buộc, string, tối đa 255 ký tự
            'branch_name' => 'nullable|string|max:255', // branch_name: không bắt buộc, string, tối đa 255 ký tự
            'branch_code' => 'nullable|string|max:20', // branch_code: không bắt buộc, string, tối đa 20 ký tự
            'swift_code' => 'nullable|string|max:20', // swift_code: không bắt buộc, string, tối đa 20 ký tự
            'banking_notes' => 'nullable|string', // banking_notes: không bắt buộc, string
            'contact_person' => 'nullable|string|max:255', // contact_person: không bắt buộc, string, tối đa 255 ký tự
            'contact_phone' => 'nullable|string|max:30', // contact_phone: không bắt buộc, string, tối đa 30 ký tự
            'contact_email' => 'nullable|email|max:150', // contact_email: không bắt buộc, phải là email hợp lệ, tối đa 150 ký tự
            'business_license' => 'nullable|string|max:100', // business_license: không bắt buộc, string, tối đa 100 ký tự
            'vendor_type' => 'required|in:individual,company', // vendor_type: bắt buộc, phải là individual hoặc company
            'status' => 'required|in:active,inactive,suspended', // status: bắt buộc, phải là active, inactive hoặc suspended
        ]);

        if ($validator->fails()) { // Nếu validation thất bại
            if ($request->ajax()) { // Nếu là AJAX request
                return response()->json([ // Trả về JSON lỗi → Frontend sẽ hiển thị lỗi
                    'success' => false,
                    'message' => 'Thông tin không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back() // Chuyển về trang trước → Hiển thị lỗi validation
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
            
            $vendor = Vendor::create([ // Tạo vendor mới → Lưu vào database
                'organization_id' => $organizationId, // Organization ID
                'name' => $request->name, // Tên nhà cung cấp
                'tax_code' => $request->tax_code, // Mã số thuế
                'phone' => $request->phone, // Số điện thoại
                'email' => $request->email, // Email
                'address' => $request->address, // Địa chỉ
                'sepay_bank_id' => $request->sepay_bank_id, // ID ngân hàng SePay
                'account_number' => $request->account_number, // Số tài khoản
                'account_holder_name' => $request->account_holder_name, // Tên chủ tài khoản
                'branch_name' => $request->branch_name, // Tên chi nhánh
                'branch_code' => $request->branch_code, // Mã chi nhánh
                'swift_code' => $request->swift_code, // Mã SWIFT
                'banking_notes' => $request->banking_notes, // Ghi chú ngân hàng
                'contact_person' => $request->contact_person, // Người liên hệ
                'contact_phone' => $request->contact_phone, // Số điện thoại liên hệ
                'contact_email' => $request->contact_email, // Email liên hệ
                'business_license' => $request->business_license, // Giấy phép kinh doanh
                'vendor_type' => $request->vendor_type, // Loại nhà cung cấp (individual/company)
                'status' => $request->status, // Trạng thái (active/inactive/suspended)
            ]);

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            if ($request->ajax()) { // Nếu là AJAX request
                return response()->json([ // Trả về JSON success → Frontend sẽ hiển thị thông báo
                    'success' => true,
                    'message' => 'Nhà cung cấp đã được tạo thành công!',
                    'vendor_id' => $vendor->id
                ]);
            }

            return redirect()->route('staff.vendors.index') // Chuyển đến trang danh sách → Hiển thị thông báo success
                ->with('success', 'Nhà cung cấp đã được tạo thành công.');

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
            Log::error('Error creating vendor: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            if ($request->ajax()) { // Nếu là AJAX request
                return response()->json([ // Trả về JSON lỗi → Frontend sẽ hiển thị lỗi
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back() // Chuyển về trang trước → Hiển thị lỗi
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Hiển thị chi tiết vendor
     *
     * MỤC ĐÍCH:
     * Hiển thị thông tin chi tiết của nhà cung cấp bao gồm thông tin cơ bản, ngân hàng, liên hệ, danh sách hóa đơn và các khoản thanh toán
     *
     * INPUT:
     * - Route Parameter: vendor (Vendor model instance)
     * - Session: organization_id (từ user context)
     * - Database: vendors, sepay_banks, company_invoices, cash_outflows
     *
     * OUTPUT:
     * - View: staff.finance.vendors.show
     * - Data: vendor (với sepayBank, cashOutflows), companyInvoices, canManage
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.vendor.view
     * 2. Kiểm tra vendor thuộc organization của user
     * 3. Load relationship sepayBank
     * 4. Load cash outflows qua company_invoices
     * 5. Load company invoices của vendor
     * 6. Kiểm tra quyền manage (để hiển thị/ẩn nút edit/delete)
     * 7. Trả về view chi tiết
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng vendors: Lấy thông tin vendor
     * - Bảng sepay_banks: Lấy thông tin ngân hàng qua relationship
     * - Bảng company_invoices: Lấy hóa đơn của vendor
     * - Bảng cash_outflows: Lấy các khoản thanh toán qua company_invoices
     *
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     *
     * LƯU Ý:
     * - Cash outflows được load qua company_invoices vì relationship là query builder
     * - Company invoices được sắp xếp theo created_at giảm dần (mới nhất trước)
     */
    public function show(Vendor $vendor)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $this->requireCapability('finance.vendor.view', 'Bạn không có quyền xem Vendors.'); // Kiểm tra quyền xem vendor → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để kiểm tra vendor thuộc organization
        
        $this->checkOrganizationAccess(
            $vendor->organization_id,
            'Unauthorized access to vendor.',
            'vendor',
            $vendor->id
        );
        
        $vendor->load('sepayBank'); // Load relationship sepayBank → Dùng để hiển thị thông tin ngân hàng
        
        // Load cash outflows riêng vì relationship là query builder
        // Lấy cash outflows qua company_invoices nơi vendor này là vendor (vendor_id)
        $vendor->setRelation('cashOutflows', CashOutflow::whereHas('companyInvoice', function($query) use ($vendor) { // Tìm cash outflows qua company_invoices
            $query->where('vendor_id', $vendor->id); // Lọc theo vendor_id
        })->orderBy('paid_at', 'desc')->get()); // Sắp xếp theo paid_at giảm dần → Dùng để hiển thị danh sách thanh toán
        
        // Load company invoices của vendor này
        $companyInvoices = \App\Models\CompanyInvoice::where('vendor_id', $vendor->id) // Tìm hóa đơn của vendor
            ->where('organization_id', $organizationId) // Chỉ lấy hóa đơn của organization
            ->with(['vendor', 'cashOutflows']) // Eager load vendor và cashOutflows → Tránh N+1 query
            ->orderBy('created_at', 'desc') // Sắp xếp theo created_at giảm dần → Mới nhất trước
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị danh sách hóa đơn
        
        $canManage = $this->checkCapability('finance.vendor.create'); // Kiểm tra quyền tạo vendor → Dùng để hiển thị/ẩn nút edit/delete

        return view('staff.finance.vendors.show', compact('vendor', 'canManage', 'companyInvoices')); // Trả về view chi tiết → Hiển thị thông tin vendor
    }

    /**
     * Hiển thị form chỉnh sửa vendor
     *
     * MỤC ĐÍCH:
     * Hiển thị form để chỉnh sửa thông tin nhà cung cấp với đầy đủ thông tin cơ bản, ngân hàng và liên hệ
     *
     * INPUT:
     * - Route Parameter: vendor (Vendor model instance)
     * - Session: organization_id (từ user context)
     * - Database: sepay_banks (lấy danh sách ngân hàng hỗ trợ SePay)
     *
     * OUTPUT:
     * - View: staff.finance.vendors.edit
     * - Data: vendor (vendor cần chỉnh sửa), sepayBanks (danh sách ngân hàng hỗ trợ SePay)
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.vendor.update (chỉ manager)
     * 2. Kiểm tra vendor thuộc organization của user
     * 3. Lấy danh sách ngân hàng hỗ trợ SePay
     * 4. Trả về view form chỉnh sửa
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng vendors: Lấy thông tin vendor cần chỉnh sửa
     * - Bảng sepay_banks: Lấy danh sách ngân hàng được hỗ trợ (supported = 1)
     *
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     *
     * LƯU Ý:
     * - Chỉ manager có quyền chỉnh sửa vendor
     */
    public function edit(Vendor $vendor)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $this->requireCapability('finance.vendor.update', 'Bạn không có quyền chỉnh sửa Vendors.'); // Kiểm tra quyền cập nhật vendor → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để kiểm tra vendor thuộc organization
        
        if (!$organizationId) { // Nếu không có organization ID
            Log::warning('VendorController@edit: User không có organization ID', [
                'user_id' => $user->id,
                'vendor_id' => $vendor->id ?? null,
            ]);
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Kiểm tra vendor thuộc organization (sử dụng loose comparison để tránh type mismatch)
        // Check organization access using helper method
        $this->checkOrganizationAccess(
            $vendor->organization_id,
            'Bạn không có quyền thực hiện thao tác này với nhà cung cấp này.',
            'vendor',
            $vendor->id
        );
        
        
        $sepayBanks = \App\Models\SepayBank::where('supported', 1)->orderBy('name')->get(); // Lấy danh sách ngân hàng hỗ trợ SePay → Dùng để hiển thị trong dropdown
        return view('staff.finance.vendors.edit', compact('vendor', 'sepayBanks')); // Trả về view form chỉnh sửa → Hiển thị form với data vendor
    }

    /**
     * Cập nhật vendor
     *
     * MỤC ĐÍCH:
     * Cập nhật thông tin nhà cung cấp với đầy đủ thông tin cơ bản, ngân hàng và liên hệ, sử dụng transaction để đảm bảo data consistency
     *
     * INPUT:
     * - Route Parameter: vendor (Vendor model instance)
     * - Request: name, tax_code, phone, email, address, sepay_bank_id, account_number, account_holder_name, branch_name, branch_code, swift_code, banking_notes, contact_person, contact_phone, contact_email, business_license, vendor_type, status
     * - Session: organization_id, user_id
     * - Database: sepay_banks (validate sepay_bank_id)
     *
     * OUTPUT:
     * - JSON: {success: true, message: "..."} (nếu AJAX)
     * - Redirect: Chuyển đến trang chi tiết vendor với success message (nếu request thường)
     * - Database: Cập nhật bản ghi trong bảng vendors
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.vendor.update
     * 2. Kiểm tra vendor thuộc organization của user
     * 3. Validate input (name, email, vendor_type, status, ...)
     * 4. Bắt đầu transaction
     * 5. Cập nhật vendor với tất cả thông tin
     * 6. Commit transaction
     * 7. Trả về JSON hoặc redirect với success message
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng vendors: Lấy vendor cần cập nhật
     * - Bảng sepay_banks: Validate sepay_bank_id tồn tại
     *
     * DỮ LIỆU GHI VÀO:
     * - Bảng vendors: Cập nhật thông tin vendor (name, tax_code, phone, email, address, banking info, contact info, vendor_type, status)
     * - Logs: Ghi log nếu có lỗi
     *
     * LƯU Ý:
     * - Chỉ manager có quyền cập nhật vendor
     * - Sử dụng transaction để đảm bảo data consistency
     * - Hỗ trợ cả AJAX và request thường
     */
    public function update(Request $request, Vendor $vendor)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu updated_by (nếu cần)
        
        $this->requireCapability('finance.vendor.update', 'Bạn không có quyền cập nhật Vendors.'); // Kiểm tra quyền cập nhật vendor → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để kiểm tra vendor thuộc organization
        
        if (!$organizationId) { // Nếu không có organization ID
            Log::warning('VendorController@update: User không có organization ID', [
                'user_id' => $user->id,
                'vendor_id' => $vendor->id ?? null,
            ]);
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Kiểm tra vendor thuộc organization (sử dụng loose comparison để tránh type mismatch)
        // Check organization access using helper method
        $this->checkOrganizationAccess(
            $vendor->organization_id,
            'Bạn không có quyền thực hiện thao tác này với nhà cung cấp này.',
            'vendor',
            $vendor->id
        );
        
        
        $validator = Validator::make($request->all(), [ // Validate input → Đảm bảo dữ liệu hợp lệ
            'name' => 'required|string|max:255', // name: bắt buộc, string, tối đa 255 ký tự
            'tax_code' => 'nullable|string|max:50', // tax_code: không bắt buộc, string, tối đa 50 ký tự
            'phone' => 'nullable|string|max:30', // phone: không bắt buộc, string, tối đa 30 ký tự
            'email' => 'nullable|email|max:150', // email: không bắt buộc, phải là email hợp lệ, tối đa 150 ký tự
            'address' => 'nullable|string|max:255', // address: không bắt buộc, string, tối đa 255 ký tự
            'sepay_bank_id' => 'nullable|exists:sepay_banks,id', // sepay_bank_id: không bắt buộc, phải tồn tại trong bảng sepay_banks
            'account_number' => 'nullable|string|max:50', // account_number: không bắt buộc, string, tối đa 50 ký tự
            'account_holder_name' => 'nullable|string|max:255', // account_holder_name: không bắt buộc, string, tối đa 255 ký tự
            'branch_name' => 'nullable|string|max:255', // branch_name: không bắt buộc, string, tối đa 255 ký tự
            'branch_code' => 'nullable|string|max:20', // branch_code: không bắt buộc, string, tối đa 20 ký tự
            'swift_code' => 'nullable|string|max:20', // swift_code: không bắt buộc, string, tối đa 20 ký tự
            'banking_notes' => 'nullable|string', // banking_notes: không bắt buộc, string
            'contact_person' => 'nullable|string|max:255', // contact_person: không bắt buộc, string, tối đa 255 ký tự
            'contact_phone' => 'nullable|string|max:30', // contact_phone: không bắt buộc, string, tối đa 30 ký tự
            'contact_email' => 'nullable|email|max:150', // contact_email: không bắt buộc, phải là email hợp lệ, tối đa 150 ký tự
            'business_license' => 'nullable|string|max:100', // business_license: không bắt buộc, string, tối đa 100 ký tự
            'vendor_type' => 'required|in:individual,company', // vendor_type: bắt buộc, phải là individual hoặc company
            'status' => 'required|in:active,inactive,suspended', // status: bắt buộc, phải là active, inactive hoặc suspended
        ]);

        if ($validator->fails()) { // Nếu validation thất bại
            if ($request->ajax()) { // Nếu là AJAX request
                return response()->json([ // Trả về JSON lỗi → Frontend sẽ hiển thị lỗi
                    'success' => false,
                    'message' => 'Thông tin không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back() // Chuyển về trang trước → Hiển thị lỗi validation
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
            
            $vendor->update([ // Cập nhật vendor → Lưu vào database
                'name' => $request->name, // Tên nhà cung cấp
                'tax_code' => $request->tax_code, // Mã số thuế
                'phone' => $request->phone, // Số điện thoại
                'email' => $request->email, // Email
                'address' => $request->address, // Địa chỉ
                'sepay_bank_id' => $request->sepay_bank_id, // ID ngân hàng SePay
                'account_number' => $request->account_number, // Số tài khoản
                'account_holder_name' => $request->account_holder_name, // Tên chủ tài khoản
                'branch_name' => $request->branch_name, // Tên chi nhánh
                'branch_code' => $request->branch_code, // Mã chi nhánh
                'swift_code' => $request->swift_code, // Mã SWIFT
                'banking_notes' => $request->banking_notes, // Ghi chú ngân hàng
                'contact_person' => $request->contact_person, // Người liên hệ
                'contact_phone' => $request->contact_phone, // Số điện thoại liên hệ
                'contact_email' => $request->contact_email, // Email liên hệ
                'business_license' => $request->business_license, // Giấy phép kinh doanh
                'vendor_type' => $request->vendor_type, // Loại nhà cung cấp (individual/company)
                'status' => $request->status, // Trạng thái (active/inactive/suspended)
            ]);

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            if ($request->ajax()) { // Nếu là AJAX request
                return response()->json([ // Trả về JSON success → Frontend sẽ hiển thị thông báo
                    'success' => true,
                    'message' => 'Nhà cung cấp đã được cập nhật thành công!'
                ]);
            }

            return redirect()->route('staff.vendors.show', $vendor) // Chuyển đến trang chi tiết → Hiển thị thông báo success
                ->with('success', 'Nhà cung cấp đã được cập nhật thành công.');

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
            Log::error('Error updating vendor: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            if ($request->ajax()) { // Nếu là AJAX request
                return response()->json([ // Trả về JSON lỗi → Frontend sẽ hiển thị lỗi
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back() // Chuyển về trang trước → Hiển thị lỗi
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Cập nhật trạng thái vendor
     *
     * MỤC ĐÍCH:
     * Cập nhật trạng thái của nhà cung cấp (active/inactive/suspended) qua AJAX request, sử dụng transaction để đảm bảo data consistency
     *
     * INPUT:
     * - Route Parameter: vendor (Vendor model instance)
     * - Request: status (active/inactive/suspended)
     * - Session: organization_id, user_id
     *
     * OUTPUT:
     * - JSON: {success: true, message: "..."} hoặc {success: false, message: "..."}
     * - Database: Cập nhật trạng thái vendor trong bảng vendors
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.vendor.update
     * 2. Kiểm tra organization ID tồn tại
     * 3. Kiểm tra vendor thuộc organization của user
     * 4. Validate status (active/inactive/suspended)
     * 5. Bắt đầu transaction
     * 6. Cập nhật status và save
     * 7. Commit transaction
     * 8. Trả về JSON với message tiếng Việt
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng vendors: Lấy vendor cần cập nhật
     *
     * DỮ LIỆU GHI VÀO:
     * - Bảng vendors: Cập nhật status
     * - Logs: Ghi log nếu có lỗi
     *
     * LƯU Ý:
     * - Chỉ manager có quyền cập nhật trạng thái
     * - Chỉ trả về JSON (API endpoint)
     * - Sử dụng transaction để đảm bảo data consistency
     */
    public function updateStatus(Request $request, Vendor $vendor)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $this->requireCapability('finance.vendor.update', 'Bạn không có quyền cập nhật trạng thái Vendors.'); // Kiểm tra quyền cập nhật vendor → Dừng nếu không có quyền
        
        // Check organization access
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa được gắn vào tổ chức nào.'
            ], 403);
        }
        
        // Convert both to int for comparison to avoid type mismatch issues
        $vendorOrgId = (int) $vendor->organization_id;
        $userOrgId = (int) $organizationId;
        
        if ($vendorOrgId !== $userOrgId) {
            Log::warning('Unauthorized access attempt to vendor', [
                'user_id' => $user->id,
                'user_organization_id' => $userOrgId,
                'vendor_id' => $vendor->id,
                'vendor_organization_id' => $vendorOrgId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật trạng thái nhà cung cấp này.'
            ], 403);
        }
        
        $request->validate([ // Validate status → Đảm bảo status hợp lệ
            'status' => 'required|in:active,inactive,suspended' // status: bắt buộc, phải là active, inactive hoặc suspended
        ]);
        
        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
            
            $vendor->status = $request->status; // Cập nhật trạng thái → Lưu vào model
            $vendor->save(); // Lưu vào database → Cập nhật bản ghi
            
            DB::commit(); // Commit transaction → Lưu thay đổi
            
            $statusLabels = [ // Mapping status sang label tiếng Việt → Dùng để hiển thị message
                'active' => 'Hoạt động',
                'inactive' => 'Không hoạt động',
                'suspended' => 'Tạm ngưng'
            ];
            
            return response()->json([ // Trả về JSON success → Frontend sẽ hiển thị thông báo
                'success' => true,
                'message' => 'Trạng thái đã được cập nhật thành "' . $statusLabels[$request->status] . '"!'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi khi có lỗi
            Log::error('Error updating vendor status: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            return response()->json([ // Trả về JSON lỗi → Frontend sẽ hiển thị lỗi
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa vendor (soft delete)
     *
     * MỤC ĐÍCH:
     * Xóa nhà cung cấp bằng soft delete (ghi deleted_by và deleted_at), sử dụng transaction để đảm bảo data consistency
     *
     * INPUT:
     * - Route Parameter: vendor (Vendor model instance)
     * - Session: organization_id, user_id
     *
     * OUTPUT:
     * - JSON: {success: true, message: "..."} (nếu AJAX)
     * - Redirect: Chuyển đến trang danh sách với success message (nếu request thường)
     * - Database: Cập nhật deleted_by và deleted_at trong bảng vendors
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.vendor.delete
     * 2. Kiểm tra vendor thuộc organization của user
     * 3. Bắt đầu transaction
     * 4. Gán deleted_by = user->id
     * 5. Soft delete vendor (set deleted_at)
     * 6. Commit transaction
     * 7. Trả về JSON hoặc redirect với success message
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng vendors: Lấy vendor cần xóa
     *
     * DỮ LIỆU GHI VÀO:
     * - Bảng vendors: Cập nhật deleted_by và deleted_at (soft delete)
     * - Logs: Ghi log nếu có lỗi
     *
     * LƯU Ý:
     * - Chỉ manager có quyền xóa vendor
     * - Sử dụng soft delete (không xóa vĩnh viễn)
     * - Ghi deleted_by trước khi soft delete
     * - Hỗ trợ cả AJAX và request thường
     */
    public function destroy(Vendor $vendor)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu deleted_by
        
        $this->requireCapability('finance.vendor.delete', 'Bạn không có quyền xóa Vendors.'); // Kiểm tra quyền xóa vendor → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để kiểm tra vendor thuộc organization
        
        if (!$organizationId) { // Nếu không có organization ID
            Log::warning('VendorController@destroy: User không có organization ID', [
                'user_id' => $user->id,
                'vendor_id' => $vendor->id ?? null,
            ]);
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Kiểm tra vendor thuộc organization (sử dụng loose comparison để tránh type mismatch)
        // Check organization access using helper method
        $this->checkOrganizationAccess(
            $vendor->organization_id,
            'Bạn không có quyền thực hiện thao tác này với nhà cung cấp này.',
            'vendor',
            $vendor->id
        );
        
        
        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
            
            $vendor->deleted_by = $user->id; // Gán deleted_by = user ID → Lưu người xóa
            $vendor->save(); // Lưu deleted_by → Trước khi soft delete
            $vendor->delete(); // Soft delete vendor → Set deleted_at

            DB::commit(); // Commit transaction → Lưu thay đổi

            if (request()->ajax()) { // Nếu là AJAX request
                return response()->json([ // Trả về JSON success → Frontend sẽ hiển thị thông báo
                    'success' => true,
                    'message' => 'Nhà cung cấp đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.vendors.index') // Chuyển đến trang danh sách → Hiển thị thông báo success
                ->with('success', 'Nhà cung cấp đã được xóa thành công.');

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi khi có lỗi
            Log::error('Error deleting vendor: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            if (request()->ajax()) { // Nếu là AJAX request
                return response()->json([ // Trả về JSON lỗi → Frontend sẽ hiển thị lỗi
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back() // Chuyển về trang trước → Hiển thị lỗi
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API lấy danh sách vendors (cho autocomplete/select2)
     *
     * MỤC ĐÍCH:
     * Trả về danh sách vendors dạng JSON để sử dụng trong autocomplete hoặc select2, hỗ trợ search và giới hạn 10 items
     *
     * INPUT:
     * - Request: search (tìm kiếm - optional)
     * - Session: organization_id (từ user context)
     * - Database: vendors
     *
     * OUTPUT:
     * - JSON: Array of vendors [{id, name, tax_code, phone, email}, ...]
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.access
     * 2. Lấy organization ID từ user context
     * 3. Query vendors theo organization với search (nếu có)
     * 4. Limit 10 items và chỉ lấy các fields cần thiết
     * 5. Trả về JSON
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng vendors: Lấy danh sách vendors theo organization với search
     *
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     *
     * LƯU Ý:
     * - Chỉ trả về JSON (API endpoint)
     * - Giới hạn 10 items để tối ưu performance
     * - Chỉ lấy các fields cần thiết (id, name, tax_code, phone, email)
     */
    public function getVendors(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $hasFinanceAccess = $this->checkCapability('finance.access'); // Kiểm tra quyền truy cập module Finance → Dừng nếu không có quyền
        if (!$hasFinanceAccess) {
            return response()->json(['error' => 'Unauthorized'], 403); // Trả về JSON lỗi → Frontend sẽ xử lý
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter vendors
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json(['error' => 'Unauthorized'], 403); // Trả về JSON lỗi → Frontend sẽ xử lý
        }
        
        $search = $request->search; // Lấy search từ request → Dùng để filter vendors
        
        $vendors = Vendor::byOrganization($organizationId) // Tìm vendors của organization
            ->when($search, function($q) use ($search) { // Nếu có search
                $q->search($search); // Áp dụng filter search → Tìm kiếm theo name, tax_code, phone, email
            })
            ->limit(10) // Giới hạn 10 items → Tối ưu performance
            ->get(['id', 'name', 'tax_code', 'phone', 'email']); // Chỉ lấy các fields cần thiết → Tối ưu performance

        return response()->json($vendors); // Trả về JSON → Frontend sẽ sử dụng cho autocomplete/select2
    }

    /**
     * API lấy thông tin ngân hàng của vendor (cho thanh toán)
     *
     * MỤC ĐÍCH:
     * Trả về thông tin ngân hàng của vendor dạng JSON để sử dụng trong thanh toán SePay, bao gồm bank code, account number, account holder name
     *
     * INPUT:
     * - Route Parameter: vendor (Vendor model instance)
     * - Session: organization_id (từ user context)
     * - Database: vendors, sepay_banks
     *
     * OUTPUT:
     * - JSON: {success: true, bank_info: {...}} hoặc {success: false, message: "..."}
     *
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền finance.access
     * 2. Kiểm tra vendor thuộc organization của user
     * 3. Load relationship sepayBank
     * 4. Kiểm tra vendor có thông tin ngân hàng không
     * 5. Trả về JSON với thông tin ngân hàng
     *
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng vendors: Lấy thông tin vendor và banking info
     * - Bảng sepay_banks: Lấy thông tin ngân hàng qua relationship
     *
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log nếu có lỗi
     *
     * LƯU Ý:
     * - Chỉ trả về JSON (API endpoint)
     * - Yêu cầu vendor phải có sepayBank và account_number
     * - Dùng để tích hợp với hệ thống thanh toán SePay
     */
    public function getBankInfo(Vendor $vendor)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $hasFinanceAccess = $this->checkCapability('finance.access'); // Kiểm tra quyền truy cập module Finance → Dừng nếu không có quyền
        if (!$hasFinanceAccess) {
            return response()->json([ // Trả về JSON lỗi → Frontend sẽ xử lý
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để kiểm tra vendor thuộc organization
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON lỗi → Frontend sẽ xử lý
                'success' => false,
                'message' => 'Bạn chưa được gắn vào tổ chức nào.'
            ], 403);
        }
        
        // Check organization access using helper method
        // Note: This will abort if unauthorized, so we need to handle JSON response differently
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa được gắn vào tổ chức nào.'
            ], 403);
        }
        
        // Convert both to int for comparison to avoid type mismatch issues
        $vendorOrgId = (int) $vendor->organization_id;
        $userOrgId = (int) $organizationId;
        
        if ($vendorOrgId !== $userOrgId) {
            Log::warning('Unauthorized access attempt to vendor bank info', [
                'user_id' => $user->id,
                'user_organization_id' => $userOrgId,
                'vendor_id' => $vendor->id,
                'vendor_organization_id' => $vendorOrgId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền truy cập thông tin nhà cung cấp này'
            ], 403);
        }
        
        try {
            $vendor->load('sepayBank'); // Load relationship sepayBank → Dùng để lấy thông tin ngân hàng

            if (!$vendor->sepayBank || !$vendor->account_number) { // Nếu vendor chưa có thông tin ngân hàng
                return response()->json([ // Trả về JSON lỗi → Frontend sẽ xử lý
                    'success' => false,
                    'message' => 'Nhà cung cấp chưa có thông tin ngân hàng'
                ], 400);
            }

            return response()->json([ // Trả về JSON success với bank info → Frontend sẽ sử dụng cho thanh toán
                'success' => true,
                'bank_info' => [
                    'vendor_id' => $vendor->id, // ID vendor
                    'vendor_name' => $vendor->name, // Tên vendor
                    'bank_code' => $vendor->sepayBank->code, // Mã ngân hàng
                    'bank_name' => $vendor->sepayBank->name, // Tên ngân hàng
                    'bank_short_name' => $vendor->sepayBank->short_name, // Tên viết tắt ngân hàng
                    'bank_bin' => $vendor->sepayBank->bin, // BIN code ngân hàng
                    'account_number' => $vendor->account_number, // Số tài khoản
                    'account_holder_name' => $vendor->account_holder_name ?? $vendor->name, // Tên chủ tài khoản (mặc định là tên vendor)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in VendorController@getBankInfo: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            return response()->json([ // Trả về JSON lỗi → Frontend sẽ xử lý
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải thông tin ngân hàng'
            ], 500);
        }
    }
}
