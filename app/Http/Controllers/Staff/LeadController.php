<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\ErrorHelper;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Property;
use App\Models\User;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller: LeadController
 * 
 * MỤC ĐÍCH:
 * Quản lý leads (khách hàng tiềm năng) trong module CRM - cho phép tạo, xem, sửa, xóa và quản lý thông tin leads, theo dõi viewings và booking deposits
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. index(): Hiển thị danh sách leads với filter, search, sort, pagination và statistics
 * 2. create(): Hiển thị form tạo lead mới
 * 3. store(): Tạo lead mới với validation và duplicate check
 * 4. show(): Hiển thị chi tiết lead kèm viewings và booking deposits
 * 5. edit(): Hiển thị form chỉnh sửa lead
 * 6. update(): Cập nhật thông tin lead với validation và duplicate check
 * 7. destroy(): Xóa lead (soft delete) với kiểm tra related data
 * 8. updateStatus(): Cập nhật trạng thái lead (new, contacted, qualified, converted, lost)
 * 9. statistics(): Hiển thị trang thống kê leads theo source, month, status
 * 10. getLeadStatistics(): Tính toán statistics cho organization (private method)
 * 
 * ENDPOINTS:
 * - GET /staff/leads: Hiển thị danh sách leads
 * - GET /staff/leads/create: Hiển thị form tạo mới
 * - POST /staff/leads: Tạo lead mới
 * - GET /staff/leads/{id}: Hiển thị chi tiết lead
 * - GET /staff/leads/{id}/edit: Hiển thị form chỉnh sửa
 * - PUT /staff/leads/{id}: Cập nhật lead
 * - DELETE /staff/leads/{id}: Xóa lead
 * - POST /staff/leads/{id}/status: Cập nhật trạng thái
 * - GET /staff/leads/statistics: Hiển thị thống kê
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model Lead (bảng leads): Lấy danh sách và chi tiết leads
 * - Model Viewing (bảng viewings): Lấy lịch hẹn xem nhà của lead
 * - Model BookingDeposit (bảng booking_deposits): Lấy đặt cọc của lead
 * - Model Property (bảng properties): Lấy thông tin properties (cho ownership filter)
 * - Trait ChecksCapabilities: Kiểm tra quyền truy cập
 * - Trait FiltersByOwnership: Lọc dữ liệu theo ownership (view_all/view_own)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng leads: Tạo, cập nhật, xóa leads
 * - Logs: Ghi log lỗi khi có exception
 * 
 * LƯU Ý:
 * - Yêu cầu user phải đăng nhập (middleware auth)
 * - Yêu cầu organization phải có quyền crm.access
 * - Manager có quyền view_all (xem tất cả leads)
 * - Agent có quyền view_own (chỉ xem leads có viewings của assigned properties)
 * - Lead được soft delete (ghi deleted_by và deleted_at)
 * - Không cho xóa lead đã có viewings hoặc bookingDeposits
 * - Hỗ trợ HTMX cho filter, sort, pagination không reload trang
 * - Statistics (total, new, contacted, qualified, converted, lost) không bị ảnh hưởng bởi filter status
 */
class LeadController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership; // Trait kiểm tra quyền và filter theo ownership → Dùng để kiểm tra capabilities và lọc data
    
    /**
     * Hiển thị danh sách leads
     * 
     * MỤC ĐÍCH:
     * Hiển thị danh sách leads với filter, search, sort, pagination và statistics, hỗ trợ HTMX/AJAX cho dynamic updates
     * 
     * INPUT:
     * - Request: search, status, source, budget_min, budget_max, date_from, date_to, sort_by, sort_order (query parameters)
     * - Session: organization_id, user_id
     * - Database: leads, viewings, properties, users
     * 
     * OUTPUT:
     * - View: staff.crm.leads.index (với leads, stats, sources, request, sortBy, sortOrder)
     * - HTML/JSON: Table HTML và stats HTML (cho HTMX/AJAX requests)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.access
     * 2. Lấy organization ID từ session
     * 3. Tạo base query với ownership filter (view_all hoặc view_own)
     * 4. Tính statistics từ base query (trước khi apply filters)
     * 5. Áp dụng filters: search, status, source, budget range, date range
     * 6. Sort và paginate
     * 7. Eager load relationships (viewings với unit.property, agent)
     * 8. Lấy unique sources cho filter
     * 9. Xử lý HTMX/AJAX request hoặc trả về view
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Lấy danh sách leads với filters
     * - Bảng viewings: Eager load viewings của leads
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * - Bảng users: Lấy agents (cho eager load)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Statistics được tính từ base query (trước filters) để hiển thị tổng số chính xác
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties
     * - Hỗ trợ HTMX (preferred) và AJAX (backward compatibility)
     * - Statistics update via hx-swap-oob cho HTMX requests
     * 
     * @param \Illuminate\Http\Request $request Request chứa query parameters (filters, search, sort, pagination)
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response View hoặc JSON/HTML response
     */
    public function index(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
            
            $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
            if (!$hasCrmAccess) {
                abort(403, 'Bạn không có quyền truy cập module CRM.'); // Dừng request và trả về lỗi 403
            }
            
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data theo organization
            if (!$organizationId) { // Nếu không có organization ID
                abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
            }
            
            // Optimized query with proper index order
            $query = Lead::where('organization_id', $organizationId); // Tạo base query filter theo organization → Sử dụng index idx_leads_organization_id nếu có
            
            $this->applyOwnershipFilter($query, 'crm.lead'); // Áp dụng ownership filter → Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties
            
            $query->whereNull('deleted_at'); // Chỉ lấy leads chưa bị xóa → Exclude soft-deleted leads

            // Calculate statistics FIRST from base query (before any filters)
            // Query directly from Lead model to ensure accurate statistics
            $statsQuery = Lead::where('organization_id', $organizationId) // Tạo query riêng cho statistics → Tính từ base query trước khi apply filters
                ->whereNull('deleted_at'); // Chỉ lấy leads chưa bị xóa → Exclude soft-deleted leads
            
            $this->applyOwnershipFilter($statsQuery, 'crm.lead'); // Áp dụng ownership filter cho statistics → Đảm bảo statistics chính xác theo quyền
            
            // Count by status using database aggregation for accuracy
            $stats = [
                'total' => (int) (clone $statsQuery)->count(), // Đếm tổng số leads → Hiển thị trong statistics card
                'new' => (int) (clone $statsQuery)->where('status', 'new')->count(), // Đếm leads mới → Hiển thị trong statistics card
                'contacted' => (int) (clone $statsQuery)->where('status', 'contacted')->count(), // Đếm leads đã liên hệ → Hiển thị trong statistics card
                'qualified' => (int) (clone $statsQuery)->where('status', 'qualified')->count(), // Đếm leads đủ điều kiện → Hiển thị trong statistics card
                'converted' => (int) (clone $statsQuery)->where('status', 'converted')->count(), // Đếm leads đã chuyển đổi → Hiển thị trong statistics card
                'lost' => (int) (clone $statsQuery)->where('status', 'lost')->count(), // Đếm leads đã mất → Hiển thị trong statistics card
            ];

            if ($request->filled('search')) { // Nếu có search query
                $search = $request->search; // Lấy search term → Dùng để tìm kiếm
                $query->where(function($q) use ($search) { // Tạo group where → Tìm trong nhiều fields
                    $q->where('name', 'like', "%{$search}%") // Tìm trong tên → Tìm lead theo tên
                      ->orWhere('phone', 'like', "%{$search}%") // Hoặc tìm trong số điện thoại → Tìm lead theo SĐT
                      ->orWhere('email', 'like', "%{$search}%") // Hoặc tìm trong email → Tìm lead theo email
                      ->orWhere('desired_city', 'like', "%{$search}%"); // Hoặc tìm trong thành phố mong muốn → Tìm lead theo thành phố
                });
            }

            if ($request->filled('status')) { // Nếu có filter status
                $query->where('status', $request->status); // Filter theo status → Chỉ lấy leads có status này
            }

            if ($request->filled('source')) { // Nếu có filter source
                $query->where('source', $request->source); // Filter theo source → Chỉ lấy leads từ nguồn này
            }

            // Filter by budget range
            if ($request->filled('budget_min')) { // Nếu có budget_min
                $query->where('budget_max', '>=', $request->budget_min); // Filter: budget_max >= budget_min → Tìm leads có budget_max >= budget_min (overlap)
            }
            if ($request->filled('budget_max')) { // Nếu có budget_max
                $query->where('budget_min', '<=', $request->budget_max); // Filter: budget_min <= budget_max → Tìm leads có budget_min <= budget_max (overlap)
            }

            // Filter by date range - uses idx_leads_created_at if available
            if ($request->filled('date_from')) { // Nếu có date_from
                $query->whereDate('created_at', '>=', $request->date_from); // Filter: created_at >= date_from → Chỉ lấy leads tạo từ ngày này trở đi
            }
            if ($request->filled('date_to')) { // Nếu có date_to
                $query->whereDate('created_at', '<=', $request->date_to); // Filter: created_at <= date_to → Chỉ lấy leads tạo đến ngày này
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id'); // Lấy sort_by từ request → Mặc định là 'id'
            $sortOrder = $request->get('sort_order', 'desc'); // Lấy sort_order từ request → Mặc định là 'desc'
            
            $allowedSortFields = ['id', 'created_at', 'name', 'phone', 'email', 'status', 'source', 'budget_min', 'budget_max']; // Danh sách fields được phép sort → Ngăn chặn SQL injection
            if (!in_array($sortBy, $allowedSortFields)) { // Nếu sort_by không hợp lệ
                $sortBy = 'id'; // Set mặc định là 'id' → Đảm bảo sort field hợp lệ
            }
            
            if (!in_array($sortOrder, ['asc', 'desc'])) { // Nếu sort_order không hợp lệ
                $sortOrder = 'desc'; // Set mặc định là 'desc' → Đảm bảo sort order hợp lệ
            }
            
            $leads = $query->with(['viewings' => function($query) { // Eager load viewings → Tránh N+1 query
                $query->with(['unit.property', 'agent'])->orderBy('schedule_at', 'desc'); // Eager load unit.property và agent → Tránh N+1 query, sắp xếp theo schedule_at giảm dần
            }])->orderBy($sortBy, $sortOrder)->paginate(15)->withQueryString(); // Sort, paginate 15 items/trang, giữ query string → Hiển thị danh sách leads

            // Get unique sources for filter - optimized
            $sources = Lead::where('organization_id', $organizationId) // Query từ bảng leads → Sử dụng index idx_leads_organization_id nếu có
                ->whereNull('deleted_at') // Chỉ lấy leads chưa bị xóa → Exclude soft-deleted leads
                ->distinct() // Lấy unique values → Tránh duplicate sources
                ->pluck('source') // Lấy chỉ cột source → Dùng để tạo filter options
                ->filter() // Loại bỏ null/empty values → Chỉ lấy sources có giá trị
                ->sort() // Sắp xếp alphabetically → Hiển thị trong dropdown
                ->values(); // Reset keys → Dùng để hiển thị trong view

            $isHtmx = $request->header('HX-Request') === 'true'; // Kiểm tra có phải HTMX request không → Xử lý HTMX khác với AJAX
            $isAjax = $request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest'); // Kiểm tra có phải AJAX request không → Backward compatibility
            
            // Prepare table HTML for both HTMX and AJAX requests
            if ($isHtmx || $isAjax) { // Nếu là HTMX hoặc AJAX request
                try {
                    $tableHtml = view('staff.crm.leads.partials.table', [ // Render table partial → Chỉ render table content, không render layout
                        'leads' => $leads, // Danh sách leads đã paginate → Hiển thị trong table
                        'sortBy' => $sortBy, // Sort field hiện tại → Dùng để highlight sort column
                        'sortOrder' => $sortOrder, // Sort order hiện tại → Dùng để hiển thị sort icon
                    ])->render(); // Render thành HTML string → Trả về cho HTMX/AJAX
                    
                    // Format stats for response
                    $statsFormatted = [
                        'total' => [
                            'value' => $stats['total'] ?? 0, // Tổng số leads → Hiển thị trong statistics card
                            'label' => 'Tổng cộng', // Label hiển thị → Hiển thị trong statistics card
                            'icon' => 'fa-list', // Icon → Hiển thị trong statistics card
                            'color' => 'primary', // Màu → Hiển thị trong statistics card
                            'filter' => '', // Filter value → Không filter khi click
                        ],
                        'new' => [
                            'value' => $stats['new'] ?? 0, // Số leads mới → Hiển thị trong statistics card
                            'label' => 'Mới', // Label hiển thị → Hiển thị trong statistics card
                            'icon' => 'fa-user-plus', // Icon → Hiển thị trong statistics card
                            'color' => 'info', // Màu → Hiển thị trong statistics card
                            'filter' => 'new', // Filter value → Filter theo status=new khi click
                        ],
                        'contacted' => [
                            'value' => $stats['contacted'] ?? 0, // Số leads đã liên hệ → Hiển thị trong statistics card
                            'label' => 'Đã liên hệ', // Label hiển thị → Hiển thị trong statistics card
                            'icon' => 'fa-phone', // Icon → Hiển thị trong statistics card
                            'color' => 'warning', // Màu → Hiển thị trong statistics card
                            'filter' => 'contacted', // Filter value → Filter theo status=contacted khi click
                        ],
                        'qualified' => [
                            'value' => $stats['qualified'] ?? 0, // Số leads đủ điều kiện → Hiển thị trong statistics card
                            'label' => 'Đủ điều kiện', // Label hiển thị → Hiển thị trong statistics card
                            'icon' => 'fa-check', // Icon → Hiển thị trong statistics card
                            'color' => 'primary', // Màu → Hiển thị trong statistics card
                            'filter' => 'qualified', // Filter value → Filter theo status=qualified khi click
                        ],
                        'converted' => [
                            'value' => $stats['converted'] ?? 0, // Số leads đã chuyển đổi → Hiển thị trong statistics card
                            'label' => 'Đã chuyển đổi', // Label hiển thị → Hiển thị trong statistics card
                            'icon' => 'fa-check-circle', // Icon → Hiển thị trong statistics card
                            'color' => 'success', // Màu → Hiển thị trong statistics card
                            'filter' => 'converted', // Filter value → Filter theo status=converted khi click
                        ],
                        'lost' => [
                            'value' => $stats['lost'] ?? 0, // Số leads đã mất → Hiển thị trong statistics card
                            'label' => 'Đã mất', // Label hiển thị → Hiển thị trong statistics card
                            'icon' => 'fa-times', // Icon → Hiển thị trong statistics card
                            'color' => 'danger', // Màu → Hiển thị trong statistics card
                            'filter' => 'lost', // Filter value → Filter theo status=lost khi click
                        ],
                    ];
                    
                    $statsHtml = view('staff.components.statistics-cards', [ // Render statistics cards component → Hiển thị statistics với HTMX filter
                        'stats' => $statsFormatted, // Statistics đã format → Hiển thị trong cards
                        'currentFilter' => request('status', ''), // Filter hiện tại → Highlight card đang được filter
                        'filterKey' => 'status', // Filter key → Dùng để tạo filter query parameter
                        'onFilterClick' => 'htmx-filter', // HTMX filter handler → Filter bằng HTMX khi click card
                        'onClearClick' => 'htmx-clear', // HTMX clear handler → Clear filter bằng HTMX
                        'tableContainerId' => 'leads-table-container', // Table container ID → Dùng để update table khi filter
                        'action' => route('staff.leads.index'), // Action URL → Dùng để gửi HTMX request
                        'columns' => 6 // Số cột → Hiển thị 6 statistics cards
                    ])->render(); // Render thành HTML string → Trả về cho HTMX/AJAX
                    
                    // Handle HTMX request - return HTML directly
                    if ($isHtmx) { // Nếu là HTMX request
                        $innerTableHtml = $tableHtml; // Khởi tạo inner HTML → Dùng để extract inner content
                        
                        // Try to extract using DOMDocument for better HTML parsing
                        if (class_exists('DOMDocument')) { // Nếu có DOMDocument class
                            libxml_use_internal_errors(true); // Bật internal errors → Tránh warning khi parse HTML
                            $dom = new \DOMDocument(); // Tạo DOMDocument → Dùng để parse HTML
                            $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // Load HTML → Parse table HTML
                            $xpath = new \DOMXPath($dom); // Tạo XPath → Dùng để query DOM
                            $container = $xpath->query('//div[@id="leads-table-container"]')->item(0); // Tìm container div → Extract inner content
                            if ($container) { // Nếu tìm thấy container
                                $innerHtml = ''; // Khởi tạo inner HTML string → Dùng để lưu inner content
                                foreach ($container->childNodes as $child) { // Loop qua child nodes → Extract tất cả children
                                    $innerHtml .= $dom->saveHTML($child); // Lưu HTML của child → Build inner HTML
                                }
                                $innerTableHtml = trim($innerHtml); // Trim whitespace → Clean inner HTML
                            }
                            libxml_clear_errors(); // Clear errors → Clean up
                        }
                        
                        // Fallback to regex if DOMDocument didn't work
                        if ($innerTableHtml === $tableHtml) { // Nếu DOMDocument không extract được
                            // Match the opening div with id="leads-table-container" and extract everything inside
                            // Use greedy match to get the last closing div (the container's closing tag)
                            if (preg_match('/<div[^>]*id=["\']leads-table-container["\'][^>]*>(.*)<\/div>\s*$/s', $tableHtml, $matches)) { // Regex match container div → Extract inner content
                                $innerTableHtml = trim($matches[1]); // Lấy inner content → Clean inner HTML
                            }
                        }
                        
                        // Return inner HTML with stats update via hx-swap-oob
                        $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>"; // Kết hợp inner table HTML và stats HTML với hx-swap-oob → Update cả table và stats trong một response
                        
                        return response($html) // Trả về HTML response → HTMX sẽ swap vào target
                            ->header('HX-Push-Url', $request->fullUrl()); // Push URL vào browser history → Update URL khi filter
                    }
                    
                    // Handle AJAX request - return JSON (backward compatibility)
                    return response()->json([
                        'success' => true,
                        'table_html' => $tableHtml, // Table HTML → Frontend sẽ insert vào DOM
                        'stats_html' => $statsHtml, // Stats HTML → Frontend sẽ insert vào DOM
                    ]); // Trả về JSON response → Backward compatibility với AJAX
                } catch (\Exception $e) {
                    Log::error('LeadController AJAX/HTMX Error: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
                    $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.');
                    if ($isHtmx) { // Nếu là HTMX request
                        return response('<div class="alert alert-danger">' . $safeMessage . '</div>', 500); // Trả về HTML error → HTMX sẽ hiển thị error
                    }
                    return response()->json([
                        'success' => false,
                        'message' => $safeMessage
                    ], 500); // Trả về JSON error → Frontend sẽ hiển thị error
                }
            }

            return view('staff.crm.leads.index', compact(
                'leads', // Danh sách leads đã paginate → Hiển thị trong table
                'stats', // Statistics → Hiển thị trong statistics cards
                'sources', // Unique sources → Dùng để tạo filter dropdown
                'request', // Request object → Dùng để giữ query parameters
                'sortBy', // Sort field → Dùng để highlight sort column
                'sortOrder' // Sort order → Dùng để hiển thị sort icon
            )); // Trả về view → Hiển thị trang danh sách leads

        } catch (\Exception $e) {
            Log::error('Error in LeadController@index: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'tải danh sách leads');
            return redirect()->back()->with('error', $safeMessage); // Redirect về trang trước với error message → Hiển thị thông báo lỗi
        }
    }

    /**
     * Hiển thị form tạo lead mới
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để tạo lead mới với các trường: source, name, phone, email, desired_city, budget, note, status
     * 
     * INPUT:
     * - Session: organization_id, user_id
     * 
     * OUTPUT:
     * - View: staff.crm.leads.create
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.lead.create
     * 2. Lấy organization ID từ session
     * 3. Trả về view form tạo mới
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: organization_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ hiển thị form)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.lead.create
     * - User phải thuộc một organization
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View form hoặc redirect nếu có lỗi
     */
    public function create()
    {
        $this->requireCapability('crm.lead.create', 'Bạn không có quyền tạo lead.'); // Kiểm tra quyền tạo lead → Dừng nếu không có quyền

        try {
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để validate
            if (!$organizationId) { // Nếu không có organization ID
                return redirect()->route('login')->with('error', 'Bạn chưa được gán vào tổ chức nào.'); // Redirect về login → User phải thuộc organization
            }

            return view('staff.crm.leads.create'); // Trả về view form tạo mới → Hiển thị form

        } catch (\Exception $e) {
            Log::error('Error in LeadController@create: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'tải form tạo lead');
            return redirect()->back()->with('error', $safeMessage); // Redirect về trang trước với error message → Hiển thị thông báo lỗi
        }
    }


    /**
     * Tạo lead mới
     * 
     * MỤC ĐÍCH:
     * Tạo lead mới với validation và duplicate check (phone/email trong cùng organization), clean budget format, transaction để đảm bảo data consistency
     * 
     * INPUT:
     * - Request: source, name, phone, email, desired_city, budget_min, budget_max, note, status
     * - Session: organization_id, user_id
     * - Database: leads (để check duplicate)
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", redirect: "..."} hoặc {success: false, message: "...", errors: {...}}
     * - Database: Tạo bản ghi mới trong bảng leads
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.lead.create
     * 2. Lấy organization ID từ session
     * 3. Clean budget format (remove dots/commas)
     * 4. Validate input (source, name, phone, email, budget, status)
     * 5. Validate budget range (min <= max)
     * 6. Transaction:
     *    - Kiểm tra duplicate (phone hoặc email trong cùng organization)
     *    - Nếu duplicate: Rollback và trả về lỗi
     *    - Nếu không: Tạo lead mới
     * 7. Commit transaction
     * 8. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Kiểm tra duplicate (phone/email)
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng leads: Tạo bản ghi mới
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.lead.create
     * - Không cho tạo lead trùng phone hoặc email trong cùng organization
     * - Budget được clean (remove dots/commas) trước khi validate
     * - Budget min phải <= budget max
     * - Sử dụng transaction để đảm bảo data consistency
     * 
     * @param \Illuminate\Http\Request $request Request chứa thông tin lead (source, name, phone, email, budget, note, status)
     * @return \Illuminate\Http\JsonResponse JSON response với success/error
     */
    public function store(Request $request)
    {
        $this->requireCapability('crm.lead.create', 'Bạn không có quyền tạo lead.'); // Kiểm tra quyền tạo lead → Dừng nếu không có quyền

        try {
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter và validate
            if (!$organizationId) { // Nếu không có organization ID
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gán vào tổ chức nào.'
                ], 403); // Trả về JSON error → User phải thuộc organization
            }

            // Clean and validate currency inputs
            $budgetMin = $request->budget_min ? str_replace(['.', ','], '', $request->budget_min) : null; // Clean budget_min → Remove dots/commas để convert sang số
            $budgetMax = $request->budget_max ? str_replace(['.', ','], '', $request->budget_max) : null; // Clean budget_max → Remove dots/commas để convert sang số
            
            // Validate request
            $request->validate([
                'source' => 'required|string|max:100', // source: bắt buộc, string, tối đa 100 ký tự
                'name' => 'required|string|max:255', // name: bắt buộc, string, tối đa 255 ký tự
                'phone' => 'required|string|max:20', // phone: bắt buộc, string, tối đa 20 ký tự
                'email' => 'nullable|email|max:255', // email: không bắt buộc, phải là email hợp lệ, tối đa 255 ký tự
                'desired_city' => 'nullable|string|max:100', // desired_city: không bắt buộc, string, tối đa 100 ký tự
                'budget_min' => 'nullable|string|regex:/^[\d.,]+$/', // budget_min: không bắt buộc, string, chỉ chứa số và dấu phẩy/chấm
                'budget_max' => 'nullable|string|regex:/^[\d.,]+$/', // budget_max: không bắt buộc, string, chỉ chứa số và dấu phẩy/chấm
                'note' => 'nullable|string|max:1000', // note: không bắt buộc, string, tối đa 1000 ký tự
                'status' => 'required|in:new,contacted,qualified,proposal,negotiation,converted,lost', // status: bắt buộc, phải là một trong các giá trị cho phép
            ], [
                'source.required' => 'Vui lòng chọn nguồn lead.',
                'name.required' => 'Vui lòng nhập tên khách hàng.',
                'phone.required' => 'Vui lòng nhập số điện thoại.',
                'email.email' => 'Email không hợp lệ.',
                'budget_min.regex' => 'Ngân sách tối thiểu chỉ được chứa số và dấu phẩy/chấm.',
                'budget_max.regex' => 'Ngân sách tối đa chỉ được chứa số và dấu phẩy/chấm.',
                'status.required' => 'Vui lòng chọn trạng thái.',
                'status.in' => 'Trạng thái không hợp lệ.',
            ]);

            // Additional validation for budget ranges
            if ($budgetMin && $budgetMax && (int)$budgetMin > (int)$budgetMax) { // Nếu có cả budget_min và budget_max và min > max
                return response()->json([
                    'success' => false,
                    'message' => 'Ngân sách tối đa phải lớn hơn hoặc bằng ngân sách tối thiểu.'
                ], 422); // Trả về lỗi validation → Budget min phải <= max
            }

            // Validate numeric values after cleaning
            if ($budgetMin && (!is_numeric($budgetMin) || (int)$budgetMin < 0)) { // Nếu có budget_min và không phải số hoặc < 0
                return response()->json([
                    'success' => false,
                    'message' => 'Ngân sách tối thiểu phải là số dương hợp lệ.'
                ], 422); // Trả về lỗi validation → Budget min phải là số dương
            }
            
            if ($budgetMax && (!is_numeric($budgetMax) || (int)$budgetMax < 0)) { // Nếu có budget_max và không phải số hoặc < 0
                return response()->json([
                    'success' => false,
                    'message' => 'Ngân sách tối đa phải là số dương hợp lệ.'
                ], 422); // Trả về lỗi validation → Budget max phải là số dương
            }

            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            // Kiểm tra xem đã có lead tồn tại với cùng SĐT hoặc email trong cùng organization chưa
            // Trùng nếu: cùng phone HOẶC cùng email (nếu có email)
            $duplicateByPhone = Lead::where('organization_id', $organizationId) // Tìm leads trong cùng organization → Kiểm tra duplicate
                ->whereNull('deleted_at') // Chỉ lấy leads chưa bị xóa → Exclude soft-deleted leads
                ->where('phone', $request->phone) // Filter theo phone → Tìm lead trùng phone
                ->first(); // Lấy lead đầu tiên → Nếu có thì là duplicate
            
            $duplicateByEmail = null; // Khởi tạo duplicateByEmail → Dùng để check duplicate email
            if ($request->filled('email') && $request->email) { // Nếu có email trong request
                $duplicateByEmail = Lead::where('organization_id', $organizationId) // Tìm leads trong cùng organization → Kiểm tra duplicate email
                    ->whereNull('deleted_at') // Chỉ lấy leads chưa bị xóa → Exclude soft-deleted leads
                    ->where('email', $request->email) // Filter theo email → Tìm lead trùng email
                    ->first(); // Lấy lead đầu tiên → Nếu có thì là duplicate
            }

            // Nếu đã có lead tồn tại → không cho tạo mới, hiển thị lỗi
            if ($duplicateByPhone || $duplicateByEmail) { // Nếu có duplicate (phone hoặc email)
                DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
                
                $duplicateInfo = []; // Khởi tạo mảng thông tin duplicate → Dùng để hiển thị lỗi
                if ($duplicateByPhone) { // Nếu có duplicate phone
                    $duplicateInfo[] = $duplicateByPhone->name . ' (ID: ' . $duplicateByPhone->id . ') - SĐT: ' . $duplicateByPhone->phone; // Thêm thông tin duplicate phone → Hiển thị trong error message
                }
                if ($duplicateByEmail && (!$duplicateByPhone || $duplicateByEmail->id != $duplicateByPhone->id)) { // Nếu có duplicate email và không phải cùng lead với duplicate phone
                    $duplicateInfo[] = $duplicateByEmail->name . ' (ID: ' . $duplicateByEmail->id . ') - Email: ' . $duplicateByEmail->email; // Thêm thông tin duplicate email → Hiển thị trong error message
                }
                
                $errorMessage = 'Đã có lead tồn tại với cùng email hoặc số điện thoại. '; // Tạo error message → Hiển thị cho user
                $errorMessage .= 'Lead đã tồn tại: ' . implode(', ', $duplicateInfo); // Thêm thông tin duplicate → Chi tiết lead đã tồn tại
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 422); // Trả về JSON error → Không cho tạo lead duplicate
            }

            $lead = Lead::create([
                'organization_id' => $organizationId, // Organization ID → Gán lead vào organization
                'source' => $request->source, // Nguồn lead → Lưu nguồn lead
                'name' => $request->name, // Tên khách hàng → Lưu tên lead
                'phone' => $request->phone, // Số điện thoại → Lưu SĐT lead
                'email' => $request->email, // Email → Lưu email lead
                'desired_city' => $request->desired_city, // Thành phố mong muốn → Lưu thành phố lead muốn thuê
                'budget_min' => $budgetMin ? (int)$budgetMin : null, // Ngân sách tối thiểu → Convert sang int, null nếu không có
                'budget_max' => $budgetMax ? (int)$budgetMax : null, // Ngân sách tối đa → Convert sang int, null nếu không có
                'note' => $request->note, // Ghi chú → Lưu ghi chú về lead
                'status' => $request->status, // Trạng thái → Lưu trạng thái lead
            ]); // Tạo lead mới → Lưu vào database

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            return response()->json([
                'success' => true,
                'message' => 'Lead đã được tạo thành công!',
                'redirect' => route('staff.leads.show', $lead->id) // URL chuyển đến trang chi tiết → Hiển thị lead vừa tạo
            ]); // Trả về JSON success → Frontend sẽ redirect

        } catch (\Illuminate\Validation\ValidationException $e) { // Nếu có lỗi validation
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors() // Validation errors → Hiển thị lỗi validation
            ], 422); // Trả về JSON error → Frontend sẽ hiển thị validation errors
        } catch (\Exception $e) { // Nếu có lỗi khác
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            Log::error('Error creating lead: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'tạo lead');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
        }
    }

    /**
     * Hiển thị chi tiết lead
     * 
     * MỤC ĐÍCH:
     * Hiển thị chi tiết lead kèm viewings và booking deposits, hỗ trợ AJAX request (trả về JSON)
     * 
     * INPUT:
     * - Route parameter: id (lead ID)
     * - Session: organization_id, user_id
     * - Database: leads, viewings, booking_deposits, properties, users
     * 
     * OUTPUT:
     * - View: staff.crm.leads.show (với lead, viewings, bookingDeposits)
     * - JSON: {success: true, lead: {...}} (cho AJAX requests)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.access
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter (nếu agent: filter theo viewings của assigned properties)
     * 5. Load lead
     * 6. Nếu AJAX request: Trả về JSON
     * 7. Nếu không: Load viewings và bookingDeposits, trả về view
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Lấy chi tiết lead
     * - Bảng viewings: Lấy lịch hẹn xem nhà của lead
     * - Bảng booking_deposits: Lấy đặt cọc của lead
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.access
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties
     * - Hỗ trợ AJAX request (trả về JSON)
     * - Viewings và bookingDeposits được eager load để tránh N+1 query
     * 
     * @param int $id Lead ID
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse View, JSON hoặc redirect nếu có lỗi
     */
    public function show($id)
    {
        try {
            $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
            if (!$hasCrmAccess) {
                abort(403, 'Bạn không có quyền truy cập module CRM.'); // Dừng request và trả về lỗi 403
            }
            
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
            if (!$organizationId) { // Nếu không có organization ID
                abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
            }
            
            // Check if user can view all leads or only own leads
            // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
            $canViewAll = $this->canViewAll('crm.lead'); // Kiểm tra user có thể xem tất cả leads không → Manager có view_all, Agent có view_own
            
            // Get lead
            $query = Lead::where('organization_id', $organizationId); // Tạo base query filter theo organization → Sử dụng index idx_leads_organization_id nếu có
            
            // For agent, filter by viewings of assigned properties
            if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
                /** @var \App\Models\User $user */
                $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lấy assigned properties
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
                
                if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                    $query->whereHas('viewings', function($q) use ($assignedPropertyIds) { // Filter: lead phải có viewings → Chỉ lấy leads có viewings của assigned properties
                        $q->whereIn('property_id', $assignedPropertyIds); // Filter viewings theo assigned properties → Agent chỉ xem leads có viewings của properties được assign
                    });
                } else { // Nếu không có assigned properties
                    abort(403, 'Bạn không có quyền xem lead này.'); // Dừng request và trả về lỗi 403 → Agent không có assigned properties thì không xem được
                }
            }
            
            $lead = $query->findOrFail($id); // Tìm lead theo ID → Throw 404 nếu không tìm thấy

            // If request is AJAX, return JSON
            if (request()->expectsJson() || request()->ajax()) { // Nếu là AJAX request
                return response()->json([
                    'success' => true,
                    'lead' => [
                        'id' => $lead->id, // Lead ID → Dùng để identify lead
                        'name' => $lead->name, // Tên lead → Hiển thị trong frontend
                        'phone' => $lead->phone, // Số điện thoại → Hiển thị trong frontend
                        'email' => $lead->email, // Email → Hiển thị trong frontend
                        'budget_min' => $lead->budget_min, // Ngân sách tối thiểu → Hiển thị trong frontend
                        'budget_max' => $lead->budget_max, // Ngân sách tối đa → Hiển thị trong frontend
                        'tenant_id' => $lead->tenant_id, // Tenant ID → Dùng để kiểm tra lead đã convert thành tenant chưa
                        'user_id' => $lead->tenant_id, // Alias tenant_id → Tương thích với frontend
                    ]
                ]); // Trả về JSON response → Frontend sẽ sử dụng data này
            }

            // Get viewings for this lead
            $viewings = $lead->viewings() // Lấy viewings của lead → Hiển thị lịch hẹn xem nhà
                ->with(['unit.property', 'agent']) // Eager load unit.property và agent → Tránh N+1 query
                ->orderBy('schedule_at', 'desc') // Sắp xếp theo schedule_at giảm dần → Hiển thị viewings mới nhất trước
                ->get(); // Lấy tất cả viewings → Hiển thị trong view

            // Get booking deposits for this lead
            $bookingDeposits = $lead->bookingDeposits() // Lấy booking deposits của lead → Hiển thị đặt cọc
                ->with(['unit.property', 'tenantUser']) // Eager load unit.property và tenantUser → Tránh N+1 query
                ->orderBy('created_at', 'desc') // Sắp xếp theo created_at giảm dần → Hiển thị deposits mới nhất trước
                ->get(); // Lấy tất cả booking deposits → Hiển thị trong view

            return view('staff.crm.leads.show', compact('lead', 'viewings', 'bookingDeposits')); // Trả về view → Hiển thị trang chi tiết lead

        } catch (\Exception $e) {
            Log::error('Error in LeadController@show: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'tải thông tin lead');
            return redirect()->back()->with('error', $safeMessage); // Redirect về trang trước với error message → Hiển thị thông báo lỗi
        }
    }

    /**
     * Hiển thị form chỉnh sửa lead
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để chỉnh sửa lead với các trường: source, name, phone, email, desired_city, budget, note, status
     * 
     * INPUT:
     * - Route parameter: id (lead ID)
     * - Session: organization_id, user_id
     * - Database: leads, properties, users
     * 
     * OUTPUT:
     * - View: staff.crm.leads.edit (với lead)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.lead.update
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter (nếu agent: filter theo viewings của assigned properties)
     * 5. Load lead
     * 6. Trả về view form chỉnh sửa
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Lấy chi tiết lead để hiển thị trong form
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ hiển thị form)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.lead.update
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties
     * 
     * @param int $id Lead ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View form hoặc redirect nếu có lỗi
     */
    public function edit($id)
    {
        $this->requireCapability('crm.lead.update', 'Bạn không có quyền cập nhật lead.'); // Kiểm tra quyền cập nhật lead → Dừng nếu không có quyền

        try {
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
            if (!$organizationId) { // Nếu không có organization ID
                abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
            }
            
            // Check if user can view all leads or only own leads
            // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
            $canViewAll = $this->canViewAll('crm.lead'); // Kiểm tra user có thể xem tất cả leads không → Manager có view_all, Agent có view_own
            
            // Get lead
            $query = Lead::where('organization_id', $organizationId); // Tạo base query filter theo organization → Sử dụng index idx_leads_organization_id nếu có
            
            // For agent, filter by viewings of assigned properties
            if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
                /** @var \App\Models\User $user */
                $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lấy assigned properties
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
                
                if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                    $query->whereHas('viewings', function($q) use ($assignedPropertyIds) { // Filter: lead phải có viewings → Chỉ lấy leads có viewings của assigned properties
                        $q->whereIn('property_id', $assignedPropertyIds); // Filter viewings theo assigned properties → Agent chỉ xem leads có viewings của properties được assign
                    });
                } else { // Nếu không có assigned properties
                    abort(403, 'Bạn không có quyền chỉnh sửa lead này.'); // Dừng request và trả về lỗi 403 → Agent không có assigned properties thì không chỉnh sửa được
                }
            }
            
            $lead = $query->findOrFail($id); // Tìm lead theo ID → Throw 404 nếu không tìm thấy

            return view('staff.crm.leads.edit', compact('lead')); // Trả về view form chỉnh sửa → Hiển thị form với data hiện tại

        } catch (\Exception $e) {
            Log::error('Error in LeadController@edit: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'tải form chỉnh sửa lead');
            return redirect()->back()->with('error', $safeMessage); // Redirect về trang trước với error message → Hiển thị thông báo lỗi
        }
    }

    /**
     * Cập nhật lead
     * 
     * MỤC ĐÍCH:
     * Cập nhật thông tin lead với validation và duplicate check (phone/email trong cùng organization, loại trừ lead hiện tại), security check (sanitize dangerous fields), transaction để đảm bảo data consistency
     * 
     * INPUT:
     * - Request: source, name, phone, email, desired_city, budget_min, budget_max, note, status
     * - Route parameter: id (lead ID)
     * - Session: organization_id, user_id
     * - Database: leads (để check duplicate)
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", redirect: "..."} hoặc {success: false, message: "...", errors: {...}}
     * - Database: Cập nhật bản ghi trong bảng leads
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.lead.update
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter (nếu agent: filter theo viewings của assigned properties)
     * 5. Load lead
     * 6. Security check: Sanitize dangerous fields (organization_id, user_organization_id, org_id)
     * 7. Clean budget format (remove dots/commas)
     * 8. Validate input (source, name, phone, email, budget, status)
     * 9. Validate budget range (min <= max)
     * 10. Transaction:
     *     - Kiểm tra duplicate (phone hoặc email trong cùng organization, loại trừ lead hiện tại)
     *     - Nếu duplicate: Rollback và trả về lỗi
     *     - Nếu không: Cập nhật lead
     * 11. Commit transaction
     * 12. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Kiểm tra duplicate (phone/email, loại trừ lead hiện tại)
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng leads: Cập nhật bản ghi
     * - Logs: Ghi log nếu có dangerous fields hoặc lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.lead.update
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties
     * - Security check: Sanitize dangerous fields (organization_id, user_organization_id, org_id) để ngăn chặn manipulation
     * - Không cho cập nhật lead trùng phone hoặc email trong cùng organization (loại trừ lead hiện tại)
     * - Budget được clean (remove dots/commas) trước khi validate
     * - Budget min phải <= budget max
     * - Sử dụng transaction để đảm bảo data consistency
     * 
     * @param \Illuminate\Http\Request $request Request chứa thông tin lead (source, name, phone, email, budget, note, status)
     * @param int $id Lead ID
     * @return \Illuminate\Http\JsonResponse JSON response với success/error
     */
    public function update(Request $request, $id)
    {
        $this->requireCapability('crm.lead.update', 'Bạn không có quyền cập nhật lead.'); // Kiểm tra quyền cập nhật lead → Dừng nếu không có quyền

        try {
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter và validate
            if (!$organizationId) { // Nếu không có organization ID
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thuộc tổ chức nào.'
                ], 403); // Trả về JSON error → User phải thuộc organization
            }
            
            // Check if user can view all leads or only own leads
            // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
            $canViewAll = $this->canViewAll('crm.lead'); // Kiểm tra user có thể xem tất cả leads không → Manager có view_all, Agent có view_own
            
            // Get lead
            $query = Lead::where('organization_id', $organizationId); // Tạo base query filter theo organization → Sử dụng index idx_leads_organization_id nếu có
            
            // For agent, filter by viewings of assigned properties
            if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
                /** @var \App\Models\User $user */
                $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lấy assigned properties
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
                
                if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                    $query->whereHas('viewings', function($q) use ($assignedPropertyIds) { // Filter: lead phải có viewings → Chỉ lấy leads có viewings của assigned properties
                        $q->whereIn('property_id', $assignedPropertyIds); // Filter viewings theo assigned properties → Agent chỉ xem leads có viewings của properties được assign
                    });
                } else { // Nếu không có assigned properties
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền cập nhật lead này.'
                    ], 403); // Trả về JSON error → Agent không có assigned properties thì không cập nhật được
                }
            }
            
            $lead = $query->findOrFail($id); // Tìm lead theo ID → Throw 404 nếu không tìm thấy

            // SECURITY CHECK: Sanitize dangerous fields instead of blocking
            // This prevents false positives while still logging suspicious activity
            $dangerousFields = ['organization_id', 'user_organization_id', 'org_id']; // Danh sách fields nguy hiểm → Ngăn chặn manipulation
            $providedDangerous = collect($request->only($dangerousFields)) // Lấy dangerous fields từ request → Kiểm tra có fields nguy hiểm không
                ->filter(function ($value) {
                    return !is_null($value) && $value !== ''; // Filter: chỉ lấy values không null và không rỗng → Loại bỏ null/empty values
                });

            if ($providedDangerous->isNotEmpty()) { // Nếu có dangerous fields
                // Log warning instead of critical - this might be false positive from middleware/auto-injection
                Log::warning('Sanitized dangerous fields on lead update', [
                    'lead_id' => $id, // Lead ID → Dùng để track
                    'user_id' => Auth::id(), // User ID → Dùng để track
                    'ip_address' => $request->ip(), // IP address → Dùng để track
                    'sanitized_fields' => $providedDangerous->keys()->all(), // Danh sách fields đã sanitize → Dùng để track
                    'sanitized_values' => $providedDangerous->toArray() // Values đã sanitize → Dùng để track
                ]); // Ghi log warning → Track suspicious activity
            }

            // Remove dangerous fields from request to prevent any manipulation
            foreach ($dangerousFields as $field) { // Loop qua dangerous fields → Remove từ request
                if ($request->has($field)) { // Nếu request có field này
                    $request->request->remove($field); // Remove field từ request → Ngăn chặn manipulation
                }
            }

            // Optional debug logging (can be removed in production)
            if (config('app.debug')) {
                Log::debug('Lead update request keys', [
                    'lead_id' => $id,
                    'request_keys' => array_keys($request->all()),
                    'request_data' => $request->except(['password', 'password_confirmation'])
                ]);
            }

            // Clean and validate currency inputs
            $budgetMin = $request->budget_min ? str_replace(['.', ','], '', $request->budget_min) : null; // Clean budget_min → Remove dots/commas để convert sang số
            $budgetMax = $request->budget_max ? str_replace(['.', ','], '', $request->budget_max) : null; // Clean budget_max → Remove dots/commas để convert sang số
            
            // Validate request
            $request->validate([
                'source' => 'required|string|max:100', // source: bắt buộc, string, tối đa 100 ký tự
                'name' => 'required|string|max:255', // name: bắt buộc, string, tối đa 255 ký tự
                'phone' => 'required|string|max:20', // phone: bắt buộc, string, tối đa 20 ký tự
                'email' => 'nullable|email|max:255', // email: không bắt buộc, phải là email hợp lệ, tối đa 255 ký tự
                'desired_city' => 'nullable|string|max:100', // desired_city: không bắt buộc, string, tối đa 100 ký tự
                'budget_min' => 'nullable|string|regex:/^[\d.,]+$/', // budget_min: không bắt buộc, string, chỉ chứa số và dấu phẩy/chấm
                'budget_max' => 'nullable|string|regex:/^[\d.,]+$/', // budget_max: không bắt buộc, string, chỉ chứa số và dấu phẩy/chấm
                'note' => 'nullable|string|max:1000', // note: không bắt buộc, string, tối đa 1000 ký tự
                'status' => 'required|in:new,contacted,qualified,proposal,negotiation,converted,lost', // status: bắt buộc, phải là một trong các giá trị cho phép
            ], [
                'source.required' => 'Vui lòng chọn nguồn lead.',
                'name.required' => 'Vui lòng nhập tên khách hàng.',
                'phone.required' => 'Vui lòng nhập số điện thoại.',
                'email.email' => 'Email không hợp lệ.',
                'budget_min.regex' => 'Ngân sách tối thiểu chỉ được chứa số và dấu phẩy/chấm.',
                'budget_max.regex' => 'Ngân sách tối đa chỉ được chứa số và dấu phẩy/chấm.',
                'status.required' => 'Vui lòng chọn trạng thái.',
                'status.in' => 'Trạng thái không hợp lệ.',
            ]);

            // Additional validation for budget ranges
            if ($budgetMin && $budgetMax && (int)$budgetMin > (int)$budgetMax) { // Nếu có cả budget_min và budget_max và min > max
                return response()->json([
                    'success' => false,
                    'message' => 'Ngân sách tối đa phải lớn hơn hoặc bằng ngân sách tối thiểu.'
                ], 422); // Trả về lỗi validation → Budget min phải <= max
            }

            // Validate numeric values after cleaning
            if ($budgetMin && (!is_numeric($budgetMin) || (int)$budgetMin < 0)) { // Nếu có budget_min và không phải số hoặc < 0
                return response()->json([
                    'success' => false,
                    'message' => 'Ngân sách tối thiểu phải là số dương hợp lệ.'
                ], 422); // Trả về lỗi validation → Budget min phải là số dương
            }
            
            if ($budgetMax && (!is_numeric($budgetMax) || (int)$budgetMax < 0)) { // Nếu có budget_max và không phải số hoặc < 0
                return response()->json([
                    'success' => false,
                    'message' => 'Ngân sách tối đa phải là số dương hợp lệ.'
                ], 422); // Trả về lỗi validation → Budget max phải là số dương
            }

            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            // Kiểm tra xem đã có lead khác tồn tại với cùng SĐT hoặc email trong cùng organization chưa
            // Trùng nếu: cùng phone HOẶC cùng email (nếu có email), nhưng loại trừ lead hiện tại
            $duplicateByPhone = Lead::where('organization_id', $organizationId) // Tìm leads trong cùng organization → Kiểm tra duplicate
                ->where('id', '!=', $lead->id) // Loại trừ lead hiện tại → Không tính lead đang update
                ->whereNull('deleted_at') // Chỉ lấy leads chưa bị xóa → Exclude soft-deleted leads
                ->where('phone', $request->phone) // Filter theo phone → Tìm lead trùng phone
                ->first(); // Lấy lead đầu tiên → Nếu có thì là duplicate
            
            $duplicateByEmail = null; // Khởi tạo duplicateByEmail → Dùng để check duplicate email
            if ($request->filled('email') && $request->email) { // Nếu có email trong request
                $duplicateByEmail = Lead::where('organization_id', $organizationId) // Tìm leads trong cùng organization → Kiểm tra duplicate email
                    ->where('id', '!=', $lead->id) // Loại trừ lead hiện tại → Không tính lead đang update
                    ->whereNull('deleted_at') // Chỉ lấy leads chưa bị xóa → Exclude soft-deleted leads
                    ->where('email', $request->email) // Filter theo email → Tìm lead trùng email
                    ->first(); // Lấy lead đầu tiên → Nếu có thì là duplicate
            }

            // Nếu đã có lead khác tồn tại → không cho update, hiển thị lỗi
            if ($duplicateByPhone || $duplicateByEmail) { // Nếu có duplicate (phone hoặc email)
                DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
                
                $duplicateInfo = []; // Khởi tạo mảng thông tin duplicate → Dùng để hiển thị lỗi
                if ($duplicateByPhone) { // Nếu có duplicate phone
                    $duplicateInfo[] = $duplicateByPhone->name . ' (ID: ' . $duplicateByPhone->id . ') - SĐT: ' . $duplicateByPhone->phone; // Thêm thông tin duplicate phone → Hiển thị trong error message
                }
                if ($duplicateByEmail && (!$duplicateByPhone || $duplicateByEmail->id != $duplicateByPhone->id)) { // Nếu có duplicate email và không phải cùng lead với duplicate phone
                    $duplicateInfo[] = $duplicateByEmail->name . ' (ID: ' . $duplicateByEmail->id . ') - Email: ' . $duplicateByEmail->email; // Thêm thông tin duplicate email → Hiển thị trong error message
                }
                
                $errorMessage = 'Đã có lead khác tồn tại với cùng email hoặc số điện thoại. '; // Tạo error message → Hiển thị cho user
                $errorMessage .= 'Lead đã tồn tại: ' . implode(', ', $duplicateInfo); // Thêm thông tin duplicate → Chi tiết lead đã tồn tại
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 422); // Trả về JSON error → Không cho cập nhật lead duplicate
            }

            $lead->update([
                'source' => $request->source, // Nguồn lead → Cập nhật nguồn lead
                'name' => $request->name, // Tên khách hàng → Cập nhật tên lead
                'phone' => $request->phone, // Số điện thoại → Cập nhật SĐT lead
                'email' => $request->email, // Email → Cập nhật email lead
                'desired_city' => $request->desired_city, // Thành phố mong muốn → Cập nhật thành phố lead muốn thuê
                'budget_min' => $budgetMin ? (int)$budgetMin : null, // Ngân sách tối thiểu → Convert sang int, null nếu không có
                'budget_max' => $budgetMax ? (int)$budgetMax : null, // Ngân sách tối đa → Convert sang int, null nếu không có
                'note' => $request->note, // Ghi chú → Cập nhật ghi chú về lead
                'status' => $request->status, // Trạng thái → Cập nhật trạng thái lead
            ]); // Cập nhật lead → Lưu thay đổi vào database

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            return response()->json([
                'success' => true,
                'message' => 'Lead đã được cập nhật thành công!',
                'redirect' => route('staff.leads.show', $lead->id) // URL chuyển đến trang chi tiết → Hiển thị lead vừa cập nhật
            ]); // Trả về JSON success → Frontend sẽ redirect

        } catch (\Illuminate\Validation\ValidationException $e) { // Nếu có lỗi validation
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors() // Validation errors → Hiển thị lỗi validation
            ], 422); // Trả về JSON error → Frontend sẽ hiển thị validation errors
        } catch (\Exception $e) { // Nếu có lỗi khác
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            Log::error('Error updating lead: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'cập nhật lead');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
        }
    }

    /**
     * Test delete lead (debug method)
     * 
     * MỤC ĐÍCH:
     * Method test để kiểm tra lead có viewings hoặc booking deposits không (dùng để debug)
     * 
     * LƯU Ý:
     * - Method này chỉ dùng để test/debug, không nên dùng trong production
     * - Trả về thông tin lead và kiểm tra related data
     * 
     * @param int $id Lead ID
     * @return \Illuminate\Http\JsonResponse JSON response với thông tin lead và related data
     */
    public function testDelete($id)
    {
        try {
            Log::info('Lead test delete request:', ['lead_id' => $id]);
            
            $organizationId = $this->getCurrentOrganizationId();
            if (!$organizationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thuộc tổ chức nào.'
                ], 403);
            }
            
            // Check if user can view all leads or only own leads
            // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
            $canViewAll = $this->canViewAll('crm.lead');
            
            // Get lead
            $query = Lead::where('organization_id', $organizationId);
            
            // For agent, filter by viewings of assigned properties
            if (!$canViewAll) {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                
                if ($assignedPropertyIds->isNotEmpty()) {
                    $query->whereHas('viewings', function($q) use ($assignedPropertyIds) {
                        $q->whereIn('property_id', $assignedPropertyIds);
                    });
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền cập nhật lead này.'
                    ], 403);
                }
            }
            
            $lead = $query->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Test delete successful',
                'lead' => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'has_viewings' => $lead->viewings()->exists(),
                    'has_deposits' => $lead->bookingDeposits()->exists()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in test delete: ' . $e->getMessage());
            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra. Vui lòng thử lại sau.');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500);
        }
    }

    /**
     * Xóa lead (soft delete)
     * 
     * MỤC ĐÍCH:
     * Xóa lead (soft delete) với kiểm tra không cho xóa lead đã có viewings hoặc booking deposits
     * 
     * INPUT:
     * - Route parameter: id (lead ID)
     * - Session: organization_id, user_id
     * - Database: leads, viewings, booking_deposits
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} hoặc {success: false, message: "..."}
     * - Database: Soft delete bản ghi trong bảng leads (ghi deleted_by và deleted_at)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.lead.delete
     * 2. Lấy organization ID từ session
     * 3. Load lead
     * 4. Kiểm tra lead có viewings không (không cho xóa nếu có)
     * 5. Kiểm tra lead có booking deposits không (không cho xóa nếu có)
     * 6. Transaction:
     *    - Soft delete lead (ghi deleted_by và deleted_at)
     * 7. Commit transaction
     * 8. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Lấy chi tiết lead
     * - Bảng viewings: Kiểm tra lead có viewings không
     * - Bảng booking_deposits: Kiểm tra lead có booking deposits không
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng leads: Soft delete (ghi deleted_by và deleted_at)
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.lead.delete
     * - Không cho xóa lead đã có viewings hoặc booking deposits
     * - Sử dụng soft delete (ghi deleted_by và deleted_at)
     * - Ghi log để track delete operations
     * 
     * @param int $id Lead ID
     * @return \Illuminate\Http\JsonResponse JSON response với success/error
     */
    public function destroy($id)
    {
        $this->requireCapability('crm.lead.delete', 'Bạn không có quyền xóa lead.'); // Kiểm tra quyền xóa lead → Dừng nếu không có quyền

        try {
            Log::info('Lead delete request:', ['lead_id' => $id]); // Ghi log delete request → Track delete operations
            
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
            if (!$organizationId) { // Nếu không có organization ID
                Log::warning('Lead delete failed: No organization'); // Ghi log warning → Track failed delete
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gán vào tổ chức nào.'
                ], 403); // Trả về JSON error → User phải thuộc organization
            }

            $lead = Lead::where('organization_id', $organizationId)->findOrFail($id); // Tìm lead theo ID và organization → Throw 404 nếu không tìm thấy
            Log::info('Lead found:', ['lead_id' => $lead->id, 'lead_name' => $lead->name]); // Ghi log lead found → Track delete operations

            // Check if lead has related data
            $hasViewings = $lead->viewings()->exists(); // Kiểm tra lead có viewings không → Không cho xóa nếu có
            $hasDeposits = $lead->bookingDeposits()->exists(); // Kiểm tra lead có booking deposits không → Không cho xóa nếu có

            Log::info('Lead delete check:', [
                'lead_id' => $lead->id,
                'has_viewings' => $hasViewings,
                'has_deposits' => $hasDeposits
            ]); // Ghi log delete check → Track related data

            if ($hasViewings || $hasDeposits) { // Nếu lead có viewings hoặc booking deposits
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa lead đã có lịch hẹn hoặc đặt cọc. Vui lòng xử lý các dữ liệu liên quan trước.'
                ], 422); // Trả về JSON error → Không cho xóa lead có related data
            }

            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            $lead->delete(); // Soft delete lead → Trait tự động set deleted_by và deleted_at
            Log::info('Lead deleted successfully:', ['lead_id' => $id]); // Ghi log delete success → Track successful delete

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            return response()->json([
                'success' => true,
                'message' => 'Lead đã được xóa thành công!'
            ]); // Trả về JSON success → Frontend sẽ hiển thị thông báo thành công

        } catch (\Exception $e) { // Nếu có lỗi
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            Log::error('Error deleting lead: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'xóa lead');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
        }
    }

    /**
     * Cập nhật trạng thái lead
     * 
     * MỤC ĐÍCH:
     * Cập nhật trạng thái lead (new, contacted, qualified, proposal, negotiation, converted, lost) với ownership filter (Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties)
     * 
     * INPUT:
     * - Request: status (new, contacted, qualified, proposal, negotiation, converted, lost)
     * - Route parameter: id (lead ID)
     * - Session: organization_id, user_id
     * - Database: leads, properties
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", status: "..."} hoặc {success: false, message: "..."}
     * - Database: Cập nhật status trong bảng leads
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization ID từ session
     * 2. Kiểm tra quyền: crm.lead.update
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter (nếu agent: filter theo viewings của assigned properties)
     * 5. Load lead
     * 6. Validate input: status (phải là một trong các giá trị cho phép)
     * 7. Cập nhật status
     * 8. Trả về JSON success với status mới
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Lấy chi tiết lead
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng leads: Cập nhật status
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.lead.update
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties
     * - Status: new, contacted, qualified, proposal, negotiation, converted, lost
     * 
     * @param \Illuminate\Http\Request $request Request chứa status
     * @param int $id Lead ID
     * @return \Illuminate\Http\JsonResponse JSON response với success/error
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
            if (!$organizationId) { // Nếu không có organization ID
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thuộc tổ chức nào.'
                ], 403); // Trả về JSON error → User phải thuộc organization
            }
            
            // Check capability - chuyển trạng thái là thao tác update
            $this->requireCapability('crm.lead.update', 'Bạn không có quyền cập nhật trạng thái lead.'); // Kiểm tra quyền cập nhật lead → Dừng nếu không có quyền
            
            // Check if user can view all leads or only own leads
            // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
            $canViewAll = $this->canViewAll('crm.lead'); // Kiểm tra user có thể xem tất cả leads không → Manager có view_all, Agent có view_own
            
            // Get lead
            $query = Lead::where('organization_id', $organizationId); // Tạo base query filter theo organization → Sử dụng index idx_leads_organization_id nếu có
            
            // For agent, filter by viewings of assigned properties
            if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
                /** @var \App\Models\User $user */
                $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lấy assigned properties
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
                
                if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                    $query->whereHas('viewings', function($q) use ($assignedPropertyIds) { // Filter: lead phải có viewings → Chỉ lấy leads có viewings của assigned properties
                        $q->whereIn('property_id', $assignedPropertyIds); // Filter viewings theo assigned properties → Agent chỉ xem leads có viewings của properties được assign
                    });
                } else { // Nếu không có assigned properties
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền cập nhật lead này.'
                    ], 403); // Trả về JSON error → Agent không có assigned properties thì không cập nhật được
                }
            }
            
            $lead = $query->findOrFail($id); // Tìm lead theo ID → Throw 404 nếu không tìm thấy

            $request->validate([
                'status' => 'required|in:new,contacted,qualified,proposal,negotiation,converted,lost', // status: bắt buộc, phải là một trong các giá trị cho phép
            ], [
                'status.required' => 'Vui lòng chọn trạng thái.',
                'status.in' => 'Trạng thái không hợp lệ.',
            ]);

            $lead->update(['status' => $request->status]); // Cập nhật status → Lưu trạng thái mới

            return response()->json([
                'success' => true,
                'message' => 'Trạng thái lead đã được cập nhật thành công!',
                'status' => $request->status // Status mới → Frontend sẽ update UI
            ]); // Trả về JSON success → Frontend sẽ hiển thị thông báo thành công

        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error updating lead status: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'cập nhật trạng thái');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
        }
    }

    /**
     * Hiển thị trang thống kê leads
     * 
     * MỤC ĐÍCH:
     * Hiển thị trang thống kê leads với các metrics: total_leads, new_leads, contacted_leads, qualified_leads, converted_leads, lost_leads, conversion_rate, this_month_leads, last_month_leads, growth_rate, leads_by_source, leads_by_month, leads_by_status
     * 
     * INPUT:
     * - Session: organization_id, user_id
     * - Database: leads
     * 
     * OUTPUT:
     * - View: staff.crm.leads.statistics (với statistics, leadsBySource, leadsByMonth, leadsByStatus)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization ID từ session
     * 2. Gọi getLeadStatistics() để tính statistics với ownership filter
     * 3. Tính leads_by_source (group by source)
     * 4. Tính leads_by_month (12 tháng gần nhất)
     * 5. Tính leads_by_status (group by status)
     * 6. Trả về view
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Lấy danh sách leads để tính statistics
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Statistics được tính với ownership filter (Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties)
     * - Leads by source, month, status không có ownership filter (hiển thị tổng số của organization)
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View hoặc redirect nếu có lỗi
     */
    public function statistics()
    {
        try {
            $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
            if (!$organizationId) { // Nếu không có organization ID
                return redirect()->route('login')->with('error', 'Bạn chưa được gán vào tổ chức nào.'); // Redirect đến login → User phải thuộc organization
            }

            $statistics = $this->getLeadStatistics($organizationId); // Tính statistics với ownership filter → Hiển thị trong view

            // Get leads by source
            $leadsBySource = Lead::where('organization_id', $organizationId) // Query từ bảng leads → Sử dụng index idx_leads_organization_id nếu có
                ->selectRaw('source, COUNT(*) as count') // Select source và đếm → Nhóm leads theo source
                ->groupBy('source') // Nhóm theo source → Tính số lượng leads mỗi source
                ->orderBy('count', 'desc') // Sắp xếp theo count giảm dần → Hiển thị source có nhiều leads nhất trước
                ->get(); // Lấy tất cả kết quả → Dùng để vẽ chart

            // Get leads by month (last 12 months)
            $leadsByMonth = Lead::where('organization_id', $organizationId) // Query từ bảng leads → Sử dụng index idx_leads_organization_id nếu có
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count') // Format created_at thành YYYY-MM và đếm → Nhóm leads theo tháng
                ->where('created_at', '>=', now()->subMonths(12)) // Filter: created_at >= 12 tháng trước → Chỉ lấy leads tạo trong 12 tháng gần nhất
                ->groupBy('month') // Nhóm theo tháng → Tính số lượng leads mỗi tháng
                ->orderBy('month') // Sắp xếp theo tháng tăng dần → Hiển thị từ tháng cũ đến mới
                ->get(); // Lấy tất cả kết quả → Dùng để vẽ chart

            // Get leads by status
            $leadsByStatus = Lead::where('organization_id', $organizationId) // Query từ bảng leads → Sử dụng index idx_leads_organization_id nếu có
                ->selectRaw('status, COUNT(*) as count') // Select status và đếm → Nhóm leads theo status
                ->groupBy('status') // Nhóm theo status → Tính số lượng leads mỗi status
                ->orderBy('count', 'desc') // Sắp xếp theo count giảm dần → Hiển thị status có nhiều leads nhất trước
                ->get(); // Lấy tất cả kết quả → Dùng để vẽ chart

            return view('staff.crm.leads.statistics', compact(
                'statistics', // Statistics với ownership filter → Hiển thị trong view
                'leadsBySource', // Leads by source → Dùng để vẽ chart
                'leadsByMonth', // Leads by month → Dùng để vẽ chart
                'leadsByStatus' // Leads by status → Dùng để vẽ chart
            )); // Trả về view → Hiển thị trang thống kê

        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error in LeadController@statistics: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'tải thống kê');
            return redirect()->back()->with('error', $safeMessage); // Redirect về trang trước với error message → Hiển thị thông báo lỗi
        }
    }


    /**
     * Tính toán statistics cho organization (private method)
     * 
     * MỤC ĐÍCH:
     * Tính toán các metrics statistics cho leads của organization với ownership filter (Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties)
     * 
     * INPUT:
     * - Parameter: organizationId (organization ID)
     * - Database: leads, properties
     * 
     * OUTPUT:
     * - Array: [
     *     'total_leads' => int,
     *     'new_leads' => int,
     *     'contacted_leads' => int,
     *     'qualified_leads' => int,
     *     'converted_leads' => int,
     *     'lost_leads' => int,
     *     'conversion_rate' => float,
     *     'this_month_leads' => int,
     *     'last_month_leads' => int,
     *     'growth_rate' => float
     *   ]
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo base query với organization filter và ownership filter
     * 2. Tính total_leads (tổng số leads)
     * 3. Tính leads theo status: new, contacted, qualified, converted, lost
     * 4. Tính conversion_rate (tỷ lệ chuyển đổi = converted_leads / total_leads * 100)
     * 5. Tính this_month_leads (leads tạo trong tháng này)
     * 6. Tính last_month_leads (leads tạo trong tháng trước)
     * 7. Tính growth_rate (tỷ lệ tăng trưởng = (this_month - last_month) / last_month * 100)
     * 8. Trả về array statistics
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Lấy danh sách leads để tính statistics
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties
     * - Sử dụng clone baseQuery để tối ưu performance (tránh rebuild query)
     * - Conversion rate và growth rate được làm tròn 2 chữ số thập phân
     * 
     * @param int $organizationId Organization ID
     * @return array Array chứa các metrics statistics
     */
    private function getLeadStatistics($organizationId)
    {
        // Optimized stats queries using base query
        $baseQuery = Lead::where('organization_id', $organizationId) // Tạo base query filter theo organization → Sử dụng index idx_leads_organization_id nếu có
            ->whereNull('deleted_at'); // Chỉ lấy leads chưa bị xóa → Exclude soft-deleted leads
        
        // Apply ownership filter to statistics query
        $this->applyOwnershipFilter($baseQuery, 'crm.lead'); // Áp dụng ownership filter → Manager xem tất cả, Agent chỉ xem leads có viewings của assigned properties

        $totalLeads = (clone $baseQuery)->count(); // Đếm tổng số leads → Hiển thị trong statistics
        $newLeads = (clone $baseQuery)->where('status', 'new')->count(); // Đếm leads mới → Hiển thị trong statistics
        $contactedLeads = (clone $baseQuery)->where('status', 'contacted')->count(); // Đếm leads đã liên hệ → Hiển thị trong statistics
        $qualifiedLeads = (clone $baseQuery)->where('status', 'qualified')->count(); // Đếm leads đủ điều kiện → Hiển thị trong statistics
        $convertedLeads = (clone $baseQuery)->where('status', 'converted')->count(); // Đếm leads đã chuyển đổi → Hiển thị trong statistics
        $lostLeads = (clone $baseQuery)->where('status', 'lost')->count(); // Đếm leads đã mất → Hiển thị trong statistics

        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0; // Tính tỷ lệ chuyển đổi → (converted_leads / total_leads) * 100, làm tròn 2 chữ số

        // This month leads - uses idx_leads_created_at if available
        $thisMonthLeads = (clone $baseQuery) // Clone base query → Dùng để tính statistics riêng
            ->whereMonth('created_at', now()->month) // Filter theo tháng hiện tại → Chỉ lấy leads tạo trong tháng này
            ->whereYear('created_at', now()->year) // Filter theo năm hiện tại → Chỉ lấy leads tạo trong năm này
            ->count(); // Đếm số lượng → Hiển thị trong statistics

        // Last month leads - optimized
        $lastMonthLeads = (clone $baseQuery) // Clone base query → Dùng để tính statistics riêng
            ->whereMonth('created_at', now()->subMonth()->month) // Filter theo tháng trước → Chỉ lấy leads tạo trong tháng trước
            ->whereYear('created_at', now()->subMonth()->year) // Filter theo năm của tháng trước → Chỉ lấy leads tạo trong năm của tháng trước
            ->count(); // Đếm số lượng → Hiển thị trong statistics

        $growthRate = $lastMonthLeads > 0  // Nếu có leads tháng trước
            ? round((($thisMonthLeads - $lastMonthLeads) / $lastMonthLeads) * 100, 2)  // Tính tỷ lệ tăng trưởng → ((this_month - last_month) / last_month) * 100, làm tròn 2 chữ số
            : 0; // Nếu không có leads tháng trước → Growth rate = 0

        return [
            'total_leads' => $totalLeads, // Tổng số leads → Hiển thị trong statistics
            'new_leads' => $newLeads, // Số leads mới → Hiển thị trong statistics
            'contacted_leads' => $contactedLeads, // Số leads đã liên hệ → Hiển thị trong statistics
            'qualified_leads' => $qualifiedLeads, // Số leads đủ điều kiện → Hiển thị trong statistics
            'converted_leads' => $convertedLeads, // Số leads đã chuyển đổi → Hiển thị trong statistics
            'lost_leads' => $lostLeads, // Số leads đã mất → Hiển thị trong statistics
            'conversion_rate' => $conversionRate, // Tỷ lệ chuyển đổi → Hiển thị trong statistics
            'this_month_leads' => $thisMonthLeads, // Số leads tháng này → Hiển thị trong statistics
            'last_month_leads' => $lastMonthLeads, // Số leads tháng trước → Hiển thị trong statistics
            'growth_rate' => $growthRate, // Tỷ lệ tăng trưởng → Hiển thị trong statistics
        ];
    }
}