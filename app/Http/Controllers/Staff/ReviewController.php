<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\ErrorHelper;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Unit;
use App\Models\Property;
use App\Models\Lease;
use App\Models\User;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller: ReviewController
 * 
 * MỤC ĐÍCH:
 * Quản lý đánh giá từ khách thuê về các phòng/bất động sản. Staff có thể xem, phản hồi đánh giá nhưng không được chỉnh sửa hoặc xóa đánh giá.
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. index(): Hiển thị danh sách reviews với filters, search, sort, pagination, statistics. Hỗ trợ HTMX cho dynamic updates.
 * 2. show(): Hiển thị chi tiết review với thông tin đầy đủ và form phản hồi.
 * 3. addReply(): Thêm phản hồi cho review (Manager/Agent có thể phản hồi).
 * 4. statistics(): Hiển thị thống kê reviews theo tháng, theo property, rating distribution.
 * 5. edit(), update(), destroy(): Không được phép (Staff chỉ xem và phản hồi).
 * 
 * ENDPOINTS:
 * - GET /staff/reviews: Danh sách reviews
 * - GET /staff/reviews/{id}: Chi tiết review
 * - POST /staff/reviews/{id}/reply: Thêm phản hồi
 * - GET /staff/reviews/statistics: Thống kê reviews
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: Review (bảng reviews) - Lấy danh sách và chi tiết reviews
 * - Model: ReviewReply (bảng review_replies) - Lấy phản hồi của reviews
 * - Model: Property (bảng properties) - Lấy danh sách properties cho filter
 * - Model: Unit (bảng units) - Lấy thông tin phòng được review
 * - Model: User (bảng users) - Lấy thông tin tenant và staff
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng review_replies: Tạo phản hồi mới cho review
 * 
 * LƯU Ý:
 * - Staff không được phép chỉnh sửa hoặc xóa reviews (chỉ tenant mới có quyền này)
 * - Agent chỉ xem reviews của properties được gán cho mình (ownership filtering)
 * - Manager xem tất cả reviews của organization
 * - Statistics chỉ tính cho approved reviews
 * - Hỗ trợ HTMX cho dynamic updates (filters, sorting, pagination)
 */
class ReviewController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;
    
    /**
     * Hiển thị danh sách reviews - Danh sách đánh giá với filters, search, sort, pagination
     * 
     * MỤC ĐÍCH:
     * Hiển thị danh sách đánh giá từ khách thuê với đầy đủ filters (status, property, tenant, rating, recommend, search, date range), 
     * sorting, pagination và statistics. Hỗ trợ HTMX cho dynamic updates không reload trang.
     * 
     * INPUT:
     * - Request: status, property_id, tenant_id, rating_min, rating_max, recommend, search, date_from, date_to, sort_by, sort_order (query parameters)
     * - Session: organization_id, user_id
     * - Database: reviews, units, properties, users, review_replies
     * 
     * OUTPUT:
     * - View: staff.crm.reviews.index (full page) hoặc partial HTML (HTMX request)
     * - Data: reviews (paginated), properties, tenants, stats (total, pending, approved, rejected, average ratings, rating distribution)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền crm.access và organization ID
     * 2. Kiểm tra ownership (Manager xem tất cả, Agent chỉ xem reviews của properties được gán)
     * 3. Build query với JOINs (reviews, units, properties) và filters
     * 4. Tính statistics từ base query (trước khi apply filters) để đảm bảo chính xác
     * 5. Áp dụng filters (status, property, tenant, rating, recommend, search, date range)
     * 6. Sort và paginate results
     * 7. Eager load relationships cho display
     * 8. Nếu HTMX request → trả về partial HTML với hx-swap-oob để update stats
     * 9. Nếu không → trả về full page view
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng reviews: Lấy danh sách reviews với filters
     * - Bảng units: JOIN để lấy thông tin phòng
     * - Bảng properties: JOIN để lấy thông tin bất động sản và filter
     * - Bảng users: Lấy danh sách tenants cho filter
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Statistics được tính từ base query (trước filters) để đảm bảo chính xác
     * - Agent chỉ xem reviews của properties được gán (assignedProperties)
     * - HTMX request trả về partial HTML để update table và stats container
     * - Query được tối ưu với indexes: idx_reviews_org_deleted_status, idx_reviews_deleted_at_status, idx_reviews_unit_deleted_rating
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data theo ownership
        
        $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
        if (!$hasCrmAccess) {
            abort(403, 'Bạn không có quyền truy cập module CRM.'); // Trả về lỗi 403 nếu không có quyền
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter reviews theo organization
        
        if (!$organizationId) { // Nếu không có organization ID
            $stats = [ // Khởi tạo stats mặc định với giá trị 0
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
            ];
            return view('staff.crm.reviews.index', [ // Trả về view với data rỗng và thông báo lỗi
                'reviews' => collect([]),
                'properties' => collect([]),
                'tenants' => collect([]),
                'stats' => $stats
            ])->with('error', 'Bạn không thuộc tổ chức nào!');
        }
        
        $canViewAll = $this->canViewAll('crm.review'); // Kiểm tra ownership: Manager xem tất cả, Agent chỉ xem của mình → Dùng để filter data

        $query = Review::select([ // Tạo query với SELECT cụ thể → Tối ưu performance và lấy thông tin cần thiết
            'reviews.*',
            'units.code as unit_code', // Lấy mã phòng từ units
            'properties.name as property_name' // Lấy tên bất động sản từ properties
        ])
        ->leftJoin('units', 'reviews.unit_id', '=', 'units.id') // JOIN với bảng units → Lấy thông tin phòng
        ->leftJoin('properties', 'units.property_id', '=', 'properties.id') // JOIN với bảng properties → Lấy thông tin bất động sản
        ->where('reviews.organization_id', $organizationId); // Filter theo organization → Sử dụng index idx_reviews_org_deleted_status
        
        if (!$canViewAll) { // Nếu Agent (không xem tất cả)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách properties được gán cho agent → Dùng để filter reviews
            
            if ($assignedPropertyIds->isEmpty()) { // Nếu agent không có property nào được gán
                $stats = [ // Khởi tạo stats mặc định
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                ];
                return view('staff.crm.reviews.index', [ // Trả về view với data rỗng
                    'reviews' => collect([]),
                    'properties' => collect([]),
                    'tenants' => collect([]),
                    'stats' => $stats
                ]);
            }
            
            $query->whereIn('properties.id', $assignedPropertyIds); // Filter chỉ lấy reviews của properties được gán → Agent chỉ xem reviews của mình
        }
        
        $query->whereNull('reviews.deleted_at') // Chỉ lấy reviews chưa bị xóa → Sử dụng index idx_reviews_deleted_at_status
              ->whereNull('units.deleted_at') // Chỉ lấy units chưa bị xóa → Sử dụng index idx_units_deleted_at_property
              ->whereNull('properties.deleted_at'); // Chỉ lấy properties chưa bị xóa → Sử dụng index idx_properties_deleted_at_org

        $statsQuery = Review::where('organization_id', $organizationId) // Tạo query riêng để tính statistics → Đảm bảo tính chính xác trước khi apply filters
            ->whereNull('deleted_at'); // Chỉ lấy reviews chưa bị xóa
        
        if (!$canViewAll) { // Nếu Agent (không xem tất cả)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách properties được gán
            if ($assignedPropertyIds->isEmpty()) { // Nếu không có property nào
                $statsQuery->whereRaw('1 = 0'); // Điều kiện false → Không có kết quả
            } else {
                $statsQuery->whereHas('unit.property', function($q) use ($assignedPropertyIds) { // Filter chỉ lấy reviews của properties được gán
                    $q->whereIn('properties.id', $assignedPropertyIds);
                });
            }
        }
        
        $totalCount = (clone $statsQuery)->count(); // Đếm tổng số reviews → Dùng để hiển thị statistics
        $approvedQuery = (clone $statsQuery)->where('status', 'approved'); // Query riêng cho approved reviews → Dùng để tính average ratings
        
        $stats = [ // Tính statistics cơ bản: tổng số, pending, approved, rejected
            'total' => (int) $totalCount,
            'pending' => (int) (clone $statsQuery)->where('status', 'pending')->count(), // Đếm reviews đang chờ duyệt
            'approved' => (int) $approvedQuery->count(), // Đếm reviews đã duyệt
            'rejected' => (int) (clone $statsQuery)->where('status', 'rejected')->count(), // Đếm reviews đã từ chối
        ];
        
        if ($stats['approved'] > 0) { // Nếu có reviews đã duyệt
            $stats['avg_overall_rating'] = round((float) (clone $approvedQuery)->avg('overall_rating'), 1); // Tính điểm trung bình tổng thể → Làm tròn 1 chữ số thập phân
            $stats['avg_location_rating'] = round((float) (clone $approvedQuery)->avg('location_rating'), 1); // Tính điểm trung bình vị trí
            $stats['avg_quality_rating'] = round((float) (clone $approvedQuery)->avg('quality_rating'), 1); // Tính điểm trung bình chất lượng
            $stats['avg_service_rating'] = round((float) (clone $approvedQuery)->avg('service_rating'), 1); // Tính điểm trung bình dịch vụ
            $stats['avg_price_rating'] = round((float) (clone $approvedQuery)->avg('price_rating'), 1); // Tính điểm trung bình giá cả
            
            $stats['rating_distribution'] = [ // Phân bố đánh giá theo sao (1-5) → Dùng để hiển thị biểu đồ
                5 => (int) (clone $approvedQuery)->where('overall_rating', 5)->count(), // Đếm reviews 5 sao
                4 => (int) (clone $approvedQuery)->where('overall_rating', 4)->count(), // Đếm reviews 4 sao
                3 => (int) (clone $approvedQuery)->where('overall_rating', 3)->count(), // Đếm reviews 3 sao
                2 => (int) (clone $approvedQuery)->where('overall_rating', 2)->count(), // Đếm reviews 2 sao
                1 => (int) (clone $approvedQuery)->where('overall_rating', 1)->count(), // Đếm reviews 1 sao
            ];
            
            $stats['recommend_yes'] = (int) (clone $approvedQuery)->where('recommend', 'yes')->count(); // Đếm reviews khuyến nghị "Có"
            $stats['recommend_maybe'] = (int) (clone $approvedQuery)->where('recommend', 'maybe')->count(); // Đếm reviews khuyến nghị "Có thể"
            $stats['recommend_no'] = (int) (clone $approvedQuery)->where('recommend', 'no')->count(); // Đếm reviews khuyến nghị "Không"
            
            $stats['with_replies'] = (int) (clone $approvedQuery)->whereHas('allReplies')->count(); // Đếm reviews có phản hồi
        } else { // Nếu không có reviews đã duyệt
            $stats['avg_overall_rating'] = 0; // Gán giá trị mặc định 0 cho tất cả statistics
            $stats['avg_location_rating'] = 0;
            $stats['avg_quality_rating'] = 0;
            $stats['avg_service_rating'] = 0;
            $stats['avg_price_rating'] = 0;
            $stats['rating_distribution'] = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]; // Phân bố mặc định: tất cả = 0
            $stats['recommend_yes'] = 0;
            $stats['recommend_maybe'] = 0;
            $stats['recommend_no'] = 0;
            $stats['with_replies'] = 0;
        }

        if ($request->filled('status')) { // Nếu có filter theo status
            $query->where('reviews.status', $request->status); // Filter theo trạng thái (pending, approved, rejected) → Sử dụng index idx_reviews_deleted_at_status
        }
        
        if ($request->filled('property_id')) { // Nếu có filter theo property
            $query->where('properties.id', $request->property_id); // Filter theo bất động sản → Chỉ lấy reviews của property này
        }

        if ($request->filled('tenant_id')) { // Nếu có filter theo tenant
            $query->where('reviews.tenant_id', $request->tenant_id); // Filter theo khách thuê → Chỉ lấy reviews của tenant này
        }

        if ($request->filled('rating_min')) { // Nếu có filter điểm tối thiểu
            $query->where('reviews.overall_rating', '>=', $request->rating_min); // Filter điểm >= rating_min → Sử dụng index idx_reviews_unit_deleted_rating
        }

        if ($request->filled('rating_max')) { // Nếu có filter điểm tối đa
            $query->where('reviews.overall_rating', '<=', $request->rating_max); // Filter điểm <= rating_max → Sử dụng index idx_reviews_unit_deleted_rating
        }

        if ($request->filled('recommend')) { // Nếu có filter theo khuyến nghị
            $query->where('reviews.recommend', $request->recommend); // Filter theo khuyến nghị (yes, maybe, no)
        }

        if ($request->filled('search')) { // Nếu có tìm kiếm
            $search = $request->search; // Lấy từ khóa tìm kiếm
            $query->where(function($q) use ($search) { // Tìm kiếm trong nhiều trường → OR logic
                $q->where('reviews.title', 'like', "%{$search}%") // Tìm trong tiêu đề review
                  ->orWhere('reviews.content', 'like', "%{$search}%") // Tìm trong nội dung review
                  ->orWhere('units.code', 'like', "%{$search}%") // Tìm trong mã phòng
                  ->orWhere('properties.name', 'like', "%{$search}%"); // Tìm trong tên bất động sản
            });
        }

        if ($request->filled('date_from')) { // Nếu có filter từ ngày
            $query->whereDate('reviews.created_at', '>=', $request->date_from); // Filter từ ngày tạo >= date_from → Sử dụng index idx_reviews_deleted_at_created
        }
        if ($request->filled('date_to')) { // Nếu có filter đến ngày
            $query->whereDate('reviews.created_at', '<=', $request->date_to); // Filter từ ngày tạo <= date_to
        }

        $sortBy = $request->get('sort_by', 'created_at'); // Lấy field sort (mặc định: created_at) → Dùng để sort results
        $sortOrder = $request->get('sort_order', 'desc'); // Lấy thứ tự sort (mặc định: desc) → Dùng để sort results
        
        $allowedSortFields = ['id', 'created_at', 'overall_rating', 'status', 'title']; // Danh sách fields được phép sort → Bảo mật: tránh SQL injection
        if (!in_array($sortBy, $allowedSortFields)) { // Nếu field không hợp lệ
            $sortBy = 'created_at'; // Gán mặc định về created_at
        }
        
        $allowedSortOrders = ['asc', 'desc']; // Danh sách thứ tự sort hợp lệ
        if (!in_array($sortOrder, $allowedSortOrders)) { // Nếu thứ tự không hợp lệ
            $sortOrder = 'desc'; // Gán mặc định về desc
        }
        
        $reviews = $query->orderBy("reviews.{$sortBy}", $sortOrder)->paginate(20); // Sort và phân trang 20 bản ghi/trang → Dùng để hiển thị danh sách
        
        $reviews->load([ // Eager load relationships → Tối ưu performance, tránh N+1 query
            'unit.property', // Load unit và property → Hiển thị thông tin phòng và bất động sản
            'lease.tenant', // Load lease và tenant → Hiển thị thông tin hợp đồng và khách thuê
            'tenant', // Load tenant → Hiển thị thông tin người đánh giá
            'organization', // Load organization → Hiển thị thông tin tổ chức
            'documents' // Load documents → Hiển thị hình ảnh review
        ]);

        if ($canViewAll) { // Nếu Manager (xem tất cả)
            $properties = Property::where('organization_id', $organizationId) // Lấy tất cả properties của organization → Dùng cho filter dropdown
                ->where('status', 1) // Chỉ lấy properties đang active
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa → Sử dụng index idx_properties_deleted_at_org
                ->orderBy('name') // Sắp xếp theo tên
                ->get(); // Lấy tất cả kết quả
        } else { // Nếu Agent (chỉ xem của mình)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách properties được gán
            $properties = Property::whereIn('id', $assignedPropertyIds) // Chỉ lấy properties được gán → Agent chỉ filter properties của mình
                ->where('status', 1) // Chỉ lấy properties đang active
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->orderBy('name') // Sắp xếp theo tên
                ->get(); // Lấy tất cả kết quả
        }
        
        $tenants = User::whereHas('organizationRoles', function($q) use ($organizationId) { // Lấy danh sách tenants của organization → Dùng cho filter dropdown
            $q->where('organization_id', $organizationId) // Filter theo organization
              ->whereIn('key_code', ['tenant']); // Chỉ lấy users có role tenant
        })->get(); // Lấy tất cả kết quả

        $isHtmx = $request->header('HX-Request') === 'true'; // Kiểm tra nếu là HTMX request → Dùng để trả về partial HTML thay vì full page
        
        if ($isHtmx) { // Nếu là HTMX request (dynamic update không reload trang)
            try {
                $sortBy = $request->get('sort_by', 'created_at'); // Lấy field sort từ request
                $sortOrder = $request->get('sort_order', 'desc'); // Lấy thứ tự sort từ request
                
                $allowedSortFields = ['id', 'created_at', 'overall_rating', 'status', 'title']; // Danh sách fields hợp lệ
                if (!in_array($sortBy, $allowedSortFields)) { // Validate field sort → Bảo mật: tránh SQL injection
                    $sortBy = 'created_at'; // Gán mặc định nếu không hợp lệ
                }
                
                $allowedSortOrders = ['asc', 'desc']; // Danh sách thứ tự hợp lệ
                if (!in_array($sortOrder, $allowedSortOrders)) { // Validate thứ tự sort
                    $sortOrder = 'desc'; // Gán mặc định nếu không hợp lệ
                }
                
                $tableHtml = view('staff.crm.reviews.partials.table', compact('reviews', 'sortBy', 'sortOrder'))->render(); // Render partial table → Dùng để update table container
                
                $statsFormatted = [ // Format statistics cho component statistics-cards → Dùng để hiển thị statistics cards
                    'total' => [
                        'value' => $stats['total'] ?? 0,
                        'label' => 'Tổng cộng',
                        'icon' => 'fa-list',
                        'color' => 'primary',
                        'filter' => '',
                    ],
                    'pending' => [
                        'value' => $stats['pending'] ?? 0,
                        'label' => 'Chờ duyệt',
                        'icon' => 'fa-clock',
                        'color' => 'warning',
                        'filter' => 'pending',
                    ],
                    'approved' => [
                        'value' => $stats['approved'] ?? 0,
                        'label' => 'Đã duyệt',
                        'icon' => 'fa-check-circle',
                        'color' => 'success',
                        'filter' => 'approved',
                    ],
                    'rejected' => [
                        'value' => $stats['rejected'] ?? 0,
                        'label' => 'Đã từ chối',
                        'icon' => 'fa-times-circle',
                        'color' => 'danger',
                        'filter' => 'rejected',
                    ],
                ];
                
                $statsHtml = view('staff.components.statistics-cards', [ // Render statistics cards component → Dùng để update stats container
                    'stats' => $statsFormatted,
                    'currentFilter' => request('status', ''),
                    'filterKey' => 'status',
                    'onFilterClick' => 'htmx-filter', // Sử dụng HTMX filter
                    'onClearClick' => 'htmx-clear', // Sử dụng HTMX clear
                    'tableContainerId' => 'reviews-table-container',
                    'action' => route('staff.reviews.index'),
                    'columns' => 4
                ])->render();
                
                $innerTableHtml = $tableHtml; // Khởi tạo inner HTML → Dùng để extract nội dung bên trong container
                
                if (class_exists('DOMDocument')) { // Nếu có DOMDocument (PHP extension) → Parse HTML chính xác hơn
                    libxml_use_internal_errors(true); // Bật internal errors → Tránh warning khi parse HTML không hoàn chỉnh
                    $dom = new \DOMDocument(); // Tạo DOMDocument → Parse HTML
                    $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // Load HTML → Parse thành DOM
                    $xpath = new \DOMXPath($dom); // Tạo XPath → Query DOM elements
                    $container = $xpath->query('//div[@id="reviews-table-container"]')->item(0); // Tìm container div → Lấy element chứa table
                    if ($container) { // Nếu tìm thấy container
                        $innerHtml = ''; // Khởi tạo inner HTML
                        foreach ($container->childNodes as $child) { // Duyệt qua các child nodes
                            $innerHtml .= $dom->saveHTML($child); // Lưu HTML của từng child → Extract nội dung bên trong
                        }
                        $innerTableHtml = trim($innerHtml); // Gán inner HTML đã extract
                    }
                    libxml_clear_errors(); // Clear errors → Dọn dẹp sau khi parse
                }
                
                if ($innerTableHtml === $tableHtml) { // Nếu DOMDocument không extract được → Dùng regex fallback
                    if (preg_match('/<div[^>]*id=["\']reviews-table-container["\'][^>]*>(.*)<\/div>\s*$/s', $tableHtml, $matches)) { // Regex match container div → Extract nội dung bên trong
                        $innerTableHtml = trim($matches[1]); // Gán inner HTML từ regex match
                    }
                }
                
                $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>"; // Tạo HTML response với hx-swap-oob → Update stats container out-of-band (không ảnh hưởng table)
                
                return response($html) // Trả về HTML response
                    ->header('HX-Push-Url', $request->fullUrl()); // Push URL vào browser history → Update URL khi filter/sort
            } catch (\Exception $e) { // Nếu có lỗi
                Log::error('ReviewController HTMX Error: ' . $e->getMessage()); // Ghi log lỗi → Để debug
                $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.');
                return response('<div class="alert alert-danger">' . $safeMessage . '</div>', 500); // Trả về lỗi 500 với thông báo an toàn
            }
        }

        return view('staff.crm.reviews.index', compact( // Trả về full page view (không phải HTMX) → Hiển thị trang đầy đủ
            'reviews',
            'properties',
            'tenants',
            'stats',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * Hiển thị chi tiết review - Chi tiết đánh giá với đầy đủ thông tin và form phản hồi
     * 
     * MỤC ĐÍCH:
     * Hiển thị chi tiết đánh giá từ khách thuê với đầy đủ thông tin (ratings, content, images, replies) và form để staff phản hồi.
     * 
     * INPUT:
     * - Request: id (route parameter) - ID của review
     * - Session: organization_id, user_id
     * - Database: reviews, units, properties, users, review_replies, documents
     * 
     * OUTPUT:
     * - View: staff.crm.reviews.show
     * - Data: review (với đầy đủ relationships: unit, property, lease, tenant, replies, documents)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền crm.access và organization ID
     * 2. Kiểm tra ownership (Manager xem tất cả, Agent chỉ xem reviews của properties được gán)
     * 3. Load review với đầy đủ relationships (unit, property, lease, tenant, replies, documents)
     * 4. Nếu Agent → Filter chỉ lấy reviews của properties được gán
     * 5. Trả về view với review data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng reviews: Lấy review theo ID
     * - Bảng units: Eager load thông tin phòng
     * - Bảng properties: Eager load thông tin bất động sản, location, master leases
     * - Bảng users: Eager load thông tin tenant và staff (replies)
     * - Bảng review_replies: Eager load phản hồi (parent và child replies)
     * - Bảng documents: Eager load hình ảnh review
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Agent chỉ xem reviews của properties được gán (assignedProperties)
     * - Eager load đầy đủ relationships để tránh N+1 query
     * - Load cả location và location2025 (backward compatibility)
     */
    public function show($id)
    {
        /** @var User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
        if (!$hasCrmAccess) {
            abort(403, 'Bạn không có quyền truy cập module CRM.'); // Trả về lỗi 403 nếu không có quyền
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter review
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Trả về lỗi 403
        }
        
        $canViewAll = $this->canViewAll('crm.review'); // Kiểm tra ownership: Manager xem tất cả, Agent chỉ xem của mình → Dùng để filter data
        
        $query = Review::with([ // Eager load relationships → Tối ưu performance, tránh N+1 query
            'unit.property.location', // Load unit, property và location → Hiển thị thông tin vị trí
            'unit.property.location2025', // Load location2025 → Backward compatibility
            'unit.property.masterLeases.landlord', // Load master leases và landlord → Hiển thị thông tin chủ nhà
            'lease', // Load lease → Hiển thị thông tin hợp đồng
            'tenant', // Load tenant → Hiển thị thông tin người đánh giá
            'replies.user', // Load replies và user → Hiển thị phản hồi của staff
            'replies.childReplies.user', // Load nested replies → Hiển thị phản hồi lồng nhau
            'organization', // Load organization → Hiển thị thông tin tổ chức
            'documents' // Load documents → Hiển thị hình ảnh review
        ])->where('organization_id', $organizationId); // Filter theo organization
        
        if (!$canViewAll) { // Nếu Agent (không xem tất cả)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách properties được gán
            
            if ($assignedPropertyIds->isEmpty()) { // Nếu agent không có property nào được gán
                abort(403, 'Bạn không có quyền xem review này.'); // Trả về lỗi 403
            }
            
            $query->join('units', 'reviews.unit_id', '=', 'units.id') // JOIN với units → Filter theo property
                ->join('properties', 'units.property_id', '=', 'properties.id') // JOIN với properties → Filter theo property
                ->whereIn('properties.id', $assignedPropertyIds) // Chỉ lấy reviews của properties được gán → Agent chỉ xem reviews của mình
                ->whereNull('units.deleted_at') // Chỉ lấy units chưa bị xóa
                ->whereNull('properties.deleted_at'); // Chỉ lấy properties chưa bị xóa
        }
        
        $review = $query->findOrFail($id); // Tìm review theo ID → Trả về 404 nếu không tìm thấy

        return view('staff.crm.reviews.show', compact('review')); // Trả về view với review data
    }

    /**
     * Hiển thị form chỉnh sửa review - Không được phép
     * 
     * MỤC ĐÍCH:
     * Staff không được phép chỉnh sửa đánh giá. Chỉ tenant mới có quyền chỉnh sửa đánh giá của mình.
     * 
     * INPUT:
     * - Request: id (route parameter) - ID của review
     * 
     * OUTPUT:
     * - HTTP 403: Trả về lỗi không có quyền
     * 
     * LƯU Ý:
     * - Staff chỉ có thể xem và phản hồi đánh giá, không được chỉnh sửa hoặc xóa
     */
    public function edit($id)
    {
        abort(403, 'Bạn không có quyền chỉnh sửa đánh giá. Staff chỉ có thể xem và phản hồi đánh giá.'); // Trả về lỗi 403 → Staff không được phép chỉnh sửa
    }

    /**
     * Cập nhật review - Không được phép
     * 
     * MỤC ĐÍCH:
     * Staff không được phép cập nhật đánh giá. Chỉ tenant mới có quyền cập nhật đánh giá của mình.
     * 
     * INPUT:
     * - Request: id (route parameter), data (request body)
     * 
     * OUTPUT:
     * - JSON: {success: false, message: "..."} (nếu AJAX request)
     * - HTTP 403: Trả về lỗi không có quyền (nếu normal request)
     * 
     * LƯU Ý:
     * - Staff chỉ có thể xem và phản hồi đánh giá, không được chỉnh sửa hoặc xóa
     */
    public function update(Request $request, $id)
    {
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) { // Nếu là AJAX/JSON request
            return response()->json([ // Trả về JSON response
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật đánh giá. Staff chỉ có thể xem và phản hồi đánh giá.'
            ], 403); // HTTP 403 Forbidden
        }
        
        abort(403, 'Bạn không có quyền cập nhật đánh giá. Staff chỉ có thể xem và phản hồi đánh giá.'); // Trả về lỗi 403 cho normal request
    }

    /**
     * Xóa review - Không được phép
     * 
     * MỤC ĐÍCH:
     * Staff không được phép xóa đánh giá. Chỉ tenant mới có quyền xóa đánh giá của mình.
     * 
     * INPUT:
     * - Request: id (route parameter)
     * 
     * OUTPUT:
     * - JSON: {success: false, message: "..."} với HTTP 403
     * 
     * LƯU Ý:
     * - Staff chỉ có thể xem và phản hồi đánh giá, không được chỉnh sửa hoặc xóa
     */
    public function destroy($id)
    {
        return response()->json([ // Trả về JSON response
            'success' => false,
            'message' => 'Bạn không có quyền xóa đánh giá. Staff chỉ có thể xem và phản hồi đánh giá.'
        ], 403); // HTTP 403 Forbidden
    }

    /**
     * Thêm phản hồi cho review - Staff phản hồi đánh giá từ khách thuê
     * 
     * MỤC ĐÍCH:
     * Cho phép Manager/Agent thêm phản hồi cho review. Hỗ trợ nested replies (phản hồi lồng nhau) thông qua parent_reply_id.
     * 
     * INPUT:
     * - Request: id (route parameter), content (required, 10-1000 ký tự), parent_reply_id (optional, cho nested replies)
     * - Session: organization_id, user_id
     * - Database: reviews, review_replies, units, properties
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", reply: {...}} (nếu AJAX request)
     * - Redirect: back() với success message (nếu normal request)
     * - Database: Tạo bản ghi mới trong review_replies
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền crm.review.reply và organization ID
     * 2. Kiểm tra ownership (Manager xem tất cả, Agent chỉ phản hồi reviews của properties được gán)
     * 3. Validate input (content: required, 10-1000 ký tự; parent_reply_id: optional, phải tồn tại)
     * 4. Xác định user_type (manager hoặc agent) dựa trên capability
     * 5. Tạo ReviewReply với content, user_id, parent_reply_id, user_type
     * 6. Trả về JSON hoặc redirect back với success message
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng reviews: Kiểm tra review có tồn tại và thuộc organization
     * - Bảng review_replies: Kiểm tra parent_reply_id có tồn tại (nếu có)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng review_replies: Tạo bản ghi mới với content, user_id, parent_reply_id, user_type
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Agent chỉ phản hồi reviews của properties được gán (assignedProperties)
     * - Hỗ trợ nested replies thông qua parent_reply_id
     * - user_type được xác định tự động: Manager → 'manager', Agent → 'agent'
     */
    public function addReply(Request $request, $id)
    {
        /** @var User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và lưu user_id
        
        $this->requireCapability('crm.review.reply', 'Bạn không có quyền phản hồi review.'); // Kiểm tra quyền phản hồi → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter review
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON response
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào!'
            ], 403); // HTTP 403 Forbidden
        }
        
        $canViewAll = $this->canViewAll('crm.review'); // Kiểm tra ownership: Manager xem tất cả, Agent chỉ xem của mình → Dùng để filter review
        
        $query = Review::where('organization_id', $organizationId); // Tạo query filter theo organization
        
        if (!$canViewAll) { // Nếu Agent (không xem tất cả)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách properties được gán
            
            if ($assignedPropertyIds->isEmpty()) { // Nếu agent không có property nào được gán
                return response()->json([ // Trả về JSON response
                    'success' => false,
                    'message' => 'Bạn không có quyền phản hồi review này.'
                ], 403); // HTTP 403 Forbidden
            }
            
            $query->join('units', 'reviews.unit_id', '=', 'units.id') // JOIN với units → Filter theo property
                ->join('properties', 'units.property_id', '=', 'properties.id') // JOIN với properties → Filter theo property
                ->whereIn('properties.id', $assignedPropertyIds) // Chỉ lấy reviews của properties được gán → Agent chỉ phản hồi reviews của mình
                ->whereNull('units.deleted_at') // Chỉ lấy units chưa bị xóa
                ->whereNull('properties.deleted_at'); // Chỉ lấy properties chưa bị xóa
        }
        
        $review = $query->findOrFail($id); // Tìm review theo ID → Trả về 404 nếu không tìm thấy
        
        $request->validate([ // Validate input → Đảm bảo data hợp lệ
            'content' => 'required|string|min:10|max:1000', // content: bắt buộc, string, 10-1000 ký tự
            'parent_reply_id' => 'nullable|exists:review_replies,id' // parent_reply_id: optional, phải tồn tại trong review_replies
        ], [
            'content.required' => 'Vui lòng nhập nội dung phản hồi',
            'content.min' => 'Nội dung phản hồi phải có ít nhất 10 ký tự',
            'content.max' => 'Nội dung phản hồi không được vượt quá 1000 ký tự',
        ]);

        try {
            $canViewAll = $this->canViewAll('crm.review'); // Kiểm tra lại ownership → Xác định user_type
            $userType = $canViewAll ? 'manager' : 'agent'; // Xác định user_type: Manager → 'manager', Agent → 'agent' → Dùng để phân biệt loại user
            
            $reply = $review->replies()->create([ // Tạo ReviewReply mới → Lưu phản hồi vào database
                'content' => $request->content, // Nội dung phản hồi
                'user_id' => Auth::id(), // ID của user đang phản hồi
                'parent_reply_id' => $request->parent_reply_id ?? null, // ID của reply cha (nếu là nested reply)
                'user_type' => $userType, // Loại user (manager hoặc agent)
            ]);

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) { // Nếu là AJAX/JSON request
                return response()->json([ // Trả về JSON response
                    'success' => true,
                    'message' => 'Phản hồi đã được thêm thành công!',
                    'reply' => $reply->load('user') // Load user relationship → Hiển thị thông tin người phản hồi
                ]);
            }

            return back()->with('success', 'Phản hồi đã được thêm thành công!'); // Redirect back với success message

        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Review reply error: ' . $e->getMessage(), [ // Ghi log lỗi → Để debug
                'review_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'thêm phản hồi');
            
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) { // Nếu là AJAX/JSON request
                return response()->json([ // Trả về JSON response với lỗi
                    'success' => false,
                    'message' => $safeMessage
                ], 500); // HTTP 500 Internal Server Error
            }

            return back()->with('error', $safeMessage); // Redirect back với error message an toàn
        }
    }

    /**
     * Lưu phản hồi cho review - Alias của addReply
     * 
     * MỤC ĐÍCH:
     * Alias method của addReply() để tương thích với Agent controller. Sử dụng cùng logic với addReply().
     * 
     * INPUT:
     * - Request: id (route parameter), content, parent_reply_id (optional)
     * 
     * OUTPUT:
     * - Giống như addReply()
     * 
     * LƯU Ý:
     * - Method này chỉ là alias, gọi trực tiếp addReply()
     */
    public function storeReply(Request $request, $id)
    {
        return $this->addReply($request, $id); // Gọi method addReply() → Sử dụng cùng logic
    }

    /**
     * Hiển thị trang thống kê reviews - Thống kê chi tiết về đánh giá
     * 
     * MỤC ĐÍCH:
     * Hiển thị thống kê chi tiết về reviews bao gồm: tổng số, trạng thái, average ratings, rating distribution, 
     * recommendation statistics, thống kê theo tháng (12 tháng gần nhất), và thống kê theo property.
     * 
     * INPUT:
     * - Request: (không có parameters, chỉ hiển thị)
     * - Session: organization_id, user_id
     * - Database: reviews, units, properties
     * 
     * OUTPUT:
     * - View: staff.crm.reviews.statistics
     * - Data: totalReviews, approvedReviews, pendingReviews, rejectedReviews, average ratings, rating distribution, 
     *   recommendation statistics, monthlyStats (12 tháng), propertyStats
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền crm.access và organization ID
     * 2. Kiểm tra ownership (Manager xem tất cả, Agent chỉ xem reviews của properties được gán)
     * 3. Tạo base query với filters theo ownership
     * 4. Tính overall statistics (total, approved, pending, rejected)
     * 5. Tính average ratings (chỉ cho approved reviews)
     * 6. Tính rating distribution (1-5 stars)
     * 7. Tính recommendation statistics
     * 8. Tính monthly statistics (12 tháng gần nhất)
     * 9. Tính property statistics (theo từng property)
     * 10. Trả về view với tất cả statistics
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng reviews: Lấy tất cả reviews để tính statistics
     * - Bảng properties: Lấy danh sách properties để tính statistics theo property
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Statistics chỉ tính cho approved reviews (trừ overall statistics)
     * - Agent chỉ xem statistics của properties được gán (assignedProperties)
     * - Monthly statistics tính cho 12 tháng gần nhất
     * - Property statistics được sort theo total reviews descending
     */
    public function statistics(Request $request)
    {
        /** @var User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
        if (!$hasCrmAccess) {
            abort(403, 'Bạn không có quyền truy cập module CRM.'); // Trả về lỗi 403 nếu không có quyền
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter reviews
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Trả về lỗi 403
        }
        
        $canViewAll = $this->canViewAll('crm.review'); // Kiểm tra ownership: Manager xem tất cả, Agent chỉ xem của mình → Dùng để filter data
        
        $baseQuery = Review::where('organization_id', $organizationId) // Tạo base query → Dùng để tính tất cả statistics
            ->whereNull('deleted_at'); // Chỉ lấy reviews chưa bị xóa
        
        if (!$canViewAll) { // Nếu Agent (không xem tất cả)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách properties được gán
            if ($assignedPropertyIds->isEmpty()) { // Nếu không có property nào
                $baseQuery->whereRaw('1 = 0'); // Điều kiện false → Không có kết quả
            } else {
                $baseQuery->whereHas('unit.property', function($q) use ($assignedPropertyIds) { // Filter chỉ lấy reviews của properties được gán
                    $q->whereIn('properties.id', $assignedPropertyIds)
                      ->whereNull('properties.deleted_at');
                });
            }
        }
        
        $totalReviews = (clone $baseQuery)->count(); // Đếm tổng số reviews → Dùng để hiển thị
        $approvedReviews = (clone $baseQuery)->where('status', 'approved')->count(); // Đếm reviews đã duyệt
        $pendingReviews = (clone $baseQuery)->where('status', 'pending')->count(); // Đếm reviews đang chờ duyệt
        $rejectedReviews = (clone $baseQuery)->where('status', 'rejected')->count(); // Đếm reviews đã từ chối
        
        $approvedQuery = (clone $baseQuery)->where('status', 'approved'); // Query riêng cho approved reviews → Dùng để tính average ratings
        $avgOverallRating = $approvedReviews > 0 ? round((float) (clone $approvedQuery)->avg('overall_rating'), 2) : 0; // Tính điểm trung bình tổng thể → Làm tròn 2 chữ số thập phân
        $avgLocationRating = $approvedReviews > 0 ? round((float) (clone $approvedQuery)->avg('location_rating'), 2) : 0; // Tính điểm trung bình vị trí
        $avgQualityRating = $approvedReviews > 0 ? round((float) (clone $approvedQuery)->avg('quality_rating'), 2) : 0; // Tính điểm trung bình chất lượng
        $avgServiceRating = $approvedReviews > 0 ? round((float) (clone $approvedQuery)->avg('service_rating'), 2) : 0; // Tính điểm trung bình dịch vụ
        $avgPriceRating = $approvedReviews > 0 ? round((float) (clone $approvedQuery)->avg('price_rating'), 2) : 0; // Tính điểm trung bình giá cả
        
        $ratingDistribution = [ // Phân bố đánh giá theo sao (1-5) → Dùng để hiển thị biểu đồ
            5 => (int) (clone $approvedQuery)->where('overall_rating', 5)->count(), // Đếm reviews 5 sao
            4 => (int) (clone $approvedQuery)->where('overall_rating', 4)->count(), // Đếm reviews 4 sao
            3 => (int) (clone $approvedQuery)->where('overall_rating', 3)->count(), // Đếm reviews 3 sao
            2 => (int) (clone $approvedQuery)->where('overall_rating', 2)->count(), // Đếm reviews 2 sao
            1 => (int) (clone $approvedQuery)->where('overall_rating', 1)->count(), // Đếm reviews 1 sao
        ];
        
        $recommendYes = (int) (clone $approvedQuery)->where('recommend', 'yes')->count(); // Đếm reviews khuyến nghị "Có"
        $recommendMaybe = (int) (clone $approvedQuery)->where('recommend', 'maybe')->count(); // Đếm reviews khuyến nghị "Có thể"
        $recommendNo = (int) (clone $approvedQuery)->where('recommend', 'no')->count(); // Đếm reviews khuyến nghị "Không"
        
        $reviewsWithReplies = (int) (clone $approvedQuery)->whereHas('allReplies')->count(); // Đếm reviews có phản hồi
        
        $monthlyStats = []; // Khởi tạo mảng monthly statistics → Dùng để lưu thống kê theo tháng
        for ($i = 11; $i >= 0; $i--) { // Duyệt 12 tháng gần nhất (từ 11 tháng trước đến hiện tại)
            $date = now()->subMonths($i); // Lấy ngày của tháng (i tháng trước) → Dùng để tính statistics cho tháng đó
            $monthStart = $date->copy()->startOfMonth(); // Lấy ngày đầu tháng → Dùng để filter reviews trong tháng
            $monthEnd = $date->copy()->endOfMonth(); // Lấy ngày cuối tháng → Dùng để filter reviews trong tháng
            
            $monthQuery = (clone $baseQuery) // Clone base query → Dùng để tính statistics cho tháng này
                ->whereBetween('created_at', [$monthStart, $monthEnd]); // Filter reviews được tạo trong tháng
            
            $monthlyStats[] = [ // Thêm statistics của tháng vào mảng → Dùng để hiển thị bảng thống kê theo tháng
                'month' => $date->format('Y-m'), // Format: YYYY-MM → Dùng để sort và identify
                'month_label' => $date->format('m/Y'), // Format: MM/YYYY → Dùng để hiển thị
                'total' => (int) (clone $monthQuery)->count(), // Đếm tổng số reviews trong tháng
                'approved' => (int) (clone $monthQuery)->where('status', 'approved')->count(), // Đếm reviews đã duyệt trong tháng
                'pending' => (int) (clone $monthQuery)->where('status', 'pending')->count(), // Đếm reviews đang chờ duyệt trong tháng
                'rejected' => (int) (clone $monthQuery)->where('status', 'rejected')->count(), // Đếm reviews đã từ chối trong tháng
                'avg_rating' => (clone $monthQuery)->where('status', 'approved')->count() > 0 // Tính điểm trung bình (chỉ approved reviews)
                    ? round((float) (clone $monthQuery)->where('status', 'approved')->avg('overall_rating'), 2) // Làm tròn 2 chữ số thập phân
                    : 0, // Nếu không có approved reviews → Gán 0
            ];
        }
        
        $propertyStats = []; // Khởi tạo mảng property statistics → Dùng để lưu thống kê theo property
        if ($canViewAll) { // Nếu Manager (xem tất cả)
            $properties = Property::where('organization_id', $organizationId) // Lấy tất cả properties của organization
                ->where('status', 1) // Chỉ lấy properties đang active
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->orderBy('name') // Sắp xếp theo tên
                ->get(); // Lấy tất cả kết quả
        } else { // Nếu Agent (chỉ xem của mình)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách properties được gán
            $properties = Property::whereIn('id', $assignedPropertyIds) // Chỉ lấy properties được gán
                ->where('status', 1) // Chỉ lấy properties đang active
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->orderBy('name') // Sắp xếp theo tên
                ->get(); // Lấy tất cả kết quả
        }
        
        foreach ($properties as $property) { // Duyệt qua từng property → Tính statistics cho từng property
            $propertyReviews = (clone $baseQuery) // Clone base query → Dùng để tính statistics cho property này
                ->whereHas('unit', function($q) use ($property) { // Filter chỉ lấy reviews của units thuộc property này
                    $q->where('property_id', $property->id)
                      ->whereNull('deleted_at');
                });
            
            $propertyApproved = (clone $propertyReviews)->where('status', 'approved'); // Query riêng cho approved reviews của property
            $propertyApprovedCount = $propertyApproved->count(); // Đếm số approved reviews → Dùng để tính average rating
            
            $propertyStats[] = [ // Thêm statistics của property vào mảng → Dùng để hiển thị bảng thống kê theo property
                'property_id' => $property->id, // ID của property → Dùng để link đến filter
                'property_name' => $property->name, // Tên property → Dùng để hiển thị
                'total' => (int) (clone $propertyReviews)->count(), // Đếm tổng số reviews của property
                'approved' => $propertyApprovedCount, // Đếm reviews đã duyệt của property
                'pending' => (int) (clone $propertyReviews)->where('status', 'pending')->count(), // Đếm reviews đang chờ duyệt của property
                'rejected' => (int) (clone $propertyReviews)->where('status', 'rejected')->count(), // Đếm reviews đã từ chối của property
                'avg_rating' => $propertyApprovedCount > 0 // Tính điểm trung bình (chỉ approved reviews)
                    ? round((float) (clone $propertyApproved)->avg('overall_rating'), 2) // Làm tròn 2 chữ số thập phân
                    : 0, // Nếu không có approved reviews → Gán 0
            ];
        }
        
        usort($propertyStats, function($a, $b) { // Sort property statistics theo total reviews descending → Hiển thị properties có nhiều reviews trước
            return $b['total'] <=> $a['total'];
        });
        
        return view('staff.crm.reviews.statistics', compact( // Trả về view với tất cả statistics → Hiển thị trang thống kê
            'totalReviews',
            'approvedReviews',
            'pendingReviews',
            'rejectedReviews',
            'avgOverallRating',
            'avgLocationRating',
            'avgQualityRating',
            'avgServiceRating',
            'avgPriceRating',
            'ratingDistribution',
            'recommendYes',
            'recommendMaybe',
            'recommendNo',
            'reviewsWithReplies',
            'monthlyStats',
            'propertyStats'
        ));
    }

}
