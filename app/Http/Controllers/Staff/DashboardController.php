<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Lease;
use App\Models\Viewing;
use App\Models\Lead;
use App\Models\BookingDeposit;
use App\Models\CommissionEvent;
use App\Services\ErpModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controller quản lý Dashboard cho Staff (Manager và Agent)
 * 
 * MỤC ĐÍCH:
 * - Hiển thị dashboard thống nhất cho cả Manager và Agent với dữ liệu phù hợp theo vai trò
 * - Manager: Xem thống kê tổng quan của toàn bộ tổ chức (properties, units, revenue, commission, top performers)
 * - Agent: Xem thống kê cá nhân (assigned properties, own leases, viewings, bookings, commission)
 * - Cung cấp API endpoints để lấy dữ liệu biểu đồ và xóa cache
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Xác định role (manager/agent) từ session, lấy dữ liệu dashboard phù hợp, trả về view
 * 2. getManagerDashboardData(): Lấy dữ liệu tổng quan cho manager (có cache 5 phút)
 *    - Key stats: properties count, occupancy rate, units, viewings, leads conversion
 *    - Revenue stats: monthly revenue, growth, commission, pending invoices, open tickets
 *    - Occupancy stats: available, occupied, reserved, maintenance units
 *    - Top performers: Top 5 agents theo commission trong tháng
 *    - Urgent tasks: Overdue invoices, expiring leases, pending viewings
 *    - Recent activities: 5 audit logs gần nhất
 *    - Analytics: New leads, viewings, leases, deposits trong 30 ngày
 * 3. getAgentDashboardData(): Lấy dữ liệu cá nhân cho agent (không cache)
 *    - Stats: Assigned properties, units, leases, viewings, leads, bookings, commission
 *    - Recent activities: Recent leases, viewings, leads, bookings
 *    - Commission summary: Total paid/pending, this month paid/pending
 *    - Today's tasks: Viewings today, bookings to process
 *    - Upcoming viewings: Viewings trong 7 ngày tới
 *    - Performance metrics: Leases/viewings/bookings this month, conversion rate
 *    - Properties: Danh sách assigned properties với stats chi tiết
 * 4. getRevenueChartData(): API endpoint trả về dữ liệu biểu đồ revenue/commission 6 tháng gần nhất
 * 5. clearCache(): API endpoint xóa cache dashboard của manager
 * 
 * ENDPOINTS:
 * - GET /staff/dashboard: Hiển thị dashboard
 * - GET /staff/dashboard/revenue-chart: Lấy dữ liệu biểu đồ revenue (JSON)
 * - POST /staff/dashboard/clear-cache: Xóa cache dashboard (JSON)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Models: Property, Unit, Lease, Viewing, Lead, BookingDeposit, CommissionEvent
 * - Database tables: properties, units, leases, viewings, leads, booking_deposits, commission_events, invoices, tickets, audit_logs
 * - Session: auth_role_key (để xác định role)
 * - Services: ErpModuleService (để lấy accessible modules)
 * - Cache: dashboard_data_manager_org_{organizationId} (cache 5 phút cho manager dashboard)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Cache: Lưu manager dashboard data vào cache (5 phút TTL)
 * - Không có thay đổi database, chỉ đọc dữ liệu
 * 
 * SERVICES SỬ DỤNG:
 * - ErpModuleService: Lấy danh sách modules mà user có quyền truy cập
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng DB::table() với JOINs thay vì Eloquent whereHas() cho performance tốt hơn
 * - Sử dụng DISTINCT để tránh duplicate khi JOIN nhiều bảng
 * - Cache manager dashboard data trong 5 phút để giảm database queries
 * - Agent dashboard không cache vì dữ liệu cá nhân cần real-time
 * - Sử dụng eager loading (with()) cho relationships trong agent dashboard
 * 
 * LƯU Ý:
 * - Manager dashboard được cache 5 phút để tối ưu performance
 * - Agent dashboard không cache để đảm bảo dữ liệu real-time
 * - Tất cả queries đều filter theo organization_id để đảm bảo data isolation
 * - Sử dụng try-catch trong các private methods để handle errors gracefully
 * - Conversion rate được tính dựa trên tháng trước (viewings -> bookings)
 * - Occupancy rate = (occupied_units / total_units) * 100
 * - Revenue growth = ((current_month - previous_month) / previous_month) * 100
 */
class DashboardController extends Controller
{
    /**
     * Hiển thị dashboard cho Staff (Manager hoặc Agent)
     * 
     * MỤC ĐÍCH:
     * Hiển thị dashboard thống nhất cho Staff với dữ liệu phù hợp theo vai trò (Manager xem tổng quan tổ chức, Agent xem dữ liệu cá nhân)
     * 
     * INPUT:
     * - Session: auth_role_key (để xác định role là manager hay agent)
     * - Auth::user(): User hiện tại đang đăng nhập
     * - Database: properties, units, leases, viewings, leads, booking_deposits, commission_events, invoices, tickets, audit_logs (thông qua các private methods)
     * 
     * OUTPUT:
     * - View: staff.dashboard (với dữ liệu dashboard, modules, isManager, isAgent)
     * - Redirect: Nếu không có organization_id thì redirect về login với error message
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy user hiện tại từ Auth
     * 2. Lấy organization_id từ relationship organizations() của user
     * 3. Kiểm tra nếu không có organization_id thì redirect về login với error message
     * 4. Lấy role key từ session (auth_role_key) để xác định là manager hay agent
     * 5. Lấy danh sách accessible ERP modules từ ErpModuleService
     * 6. Gọi method phù hợp để lấy dashboard data:
     *    - Manager: getManagerDashboardData() (có cache)
     *    - Agent: getAgentDashboardData() (không cache)
     * 7. Trả về view 'staff.dashboard' với dữ liệu đã chuẩn bị
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - user->organizations(): Relationship để lấy organization_id
     * - session('auth_role_key'): Role key từ session
     * - ErpModuleService::getUserAccessibleModules(): Danh sách modules accessible
     * - Các private methods: getManagerDashboardData(), getAgentDashboardData() (đọc từ database)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lấy organization_id và check quyền
        
        // Lấy organization ID từ relationship organizations() của user → Dùng để filter data theo organization
        $organizationId = $user->organizations()->first()?->id;
        
        if (!$organizationId) { // Nếu không có organization ID
            return redirect()->route('login') // Chuyển về trang login
                ->with('error', 'Bạn cần tham gia một tổ chức trước khi sử dụng dashboard.'); // Hiển thị thông báo lỗi
        }

        // Lấy role key từ session → Dùng để xác định là manager hay agent
        $roleKey = session('auth_role_key', '');
        $isManager = $roleKey === 'manager'; // Kiểm tra nếu là manager → Dùng để hiển thị dữ liệu tổng quan
        $isAgent = $roleKey === 'agent'; // Kiểm tra nếu là agent → Dùng để hiển thị dữ liệu cá nhân

        // Lấy danh sách accessible ERP modules → Dùng để hiển thị modules mà user có quyền truy cập
        $modules = ErpModuleService::getUserAccessibleModules($user->id, $organizationId);

        // Lấy dashboard data dựa trên role → Dùng để hiển thị thống kê phù hợp
        if ($isManager) {
            $dashboardData = $this->getManagerDashboardData($organizationId); // Lấy dữ liệu tổng quan cho manager (có cache)
        } else {
            $dashboardData = $this->getAgentDashboardData($user, $organizationId); // Lấy dữ liệu cá nhân cho agent (không cache)
        }

        return view('staff.dashboard', compact('dashboardData', 'modules', 'isManager', 'isAgent')); // Trả về view với dữ liệu đã chuẩn bị
    }

    /**
     * Lấy dữ liệu dashboard cho Manager (thống kê tổng quan của toàn bộ tổ chức)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo cache key dựa trên organization_id
     * 2. Sử dụng Cache::remember() để cache dữ liệu trong 5 phút (300 giây)
     * 3. Nếu cache không tồn tại, thực hiện các queries để lấy:
     *    - Key stats: Số lượng properties, occupancy rate, units, viewings, leads conversion
     *    - Revenue stats: Doanh thu tháng hiện tại, growth, commission, pending invoices, open tickets
     *    - Occupancy stats: Số lượng units theo trạng thái (available, occupied, reserved, maintenance)
     *    - Top performers: Top 5 agents theo commission trong tháng hiện tại
     *    - Urgent tasks: Overdue invoices, expiring leases (trong 30 ngày), pending viewings
     *    - Recent activities: 5 audit logs gần nhất
     *    - Analytics: Số lượng leads, viewings, leases, deposits mới trong 30 ngày qua
     * 4. Trả về array chứa tất cả dữ liệu đã lấy
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - getKeyStats(): Thống kê cơ bản (properties, units, occupancy, viewings, leads)
     * - getRevenueStats(): Thống kê doanh thu và commission
     * - getOccupancyStats(): Thống kê tình trạng occupancy của units
     * - getTopPerformers(): Top 5 agents theo commission
     * - getUrgentTasks(): Các tasks cần xử lý gấp
     * - getRecentActivities(): Các hoạt động gần đây từ audit_logs
     * - getAnalyticsData(): Dữ liệu phân tích trong 30 ngày
     * 
     * DỮ LIỆU GHI VÀO:
     * - Cache: Lưu dữ liệu vào cache với key "dashboard_data_manager_org_{organizationId}" (TTL: 5 phút)
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng cache để tránh query database mỗi lần load dashboard
     * - Cache TTL 5 phút để cân bằng giữa performance và data freshness
     * 
     * @param int $organizationId ID của tổ chức
     * @return array Dữ liệu dashboard cho manager
     */
    private function getManagerDashboardData($organizationId)
    {
        // Tạo cache key dựa trên organization_id → Mỗi organization có cache riêng để tránh conflict
        $cacheKey = "dashboard_data_manager_org_{$organizationId}";
        
        // Cache dữ liệu trong 5 phút (300 giây) → Nếu cache tồn tại trả về cache, nếu không thực hiện queries và lưu vào cache
        return Cache::remember($cacheKey, 300, function () use ($organizationId) {
            return [
                'stats' => $this->getKeyStats($organizationId), // Lấy thống kê cơ bản → Dùng để hiển thị key metrics
                'revenue' => $this->getRevenueStats($organizationId), // Lấy thống kê doanh thu → Dùng để hiển thị revenue stats
                'occupancy' => $this->getOccupancyStats($organizationId), // Lấy thống kê occupancy → Dùng để hiển thị tình trạng phòng
                'topPerformers' => $this->getTopPerformers($organizationId), // Lấy top 5 agents → Dùng để hiển thị top performers
                'urgentTasks' => $this->getUrgentTasks($organizationId), // Lấy urgent tasks → Dùng để hiển thị tasks cần xử lý
                'recentActivities' => $this->getRecentActivities($organizationId), // Lấy hoạt động gần đây → Dùng để hiển thị recent activities
                'analytics' => $this->getAnalyticsData($organizationId), // Lấy analytics data → Dùng để hiển thị phân tích chi tiết
            ];
        });
    }

    /**
     * Lấy dữ liệu dashboard cho Agent (thống kê cá nhân của agent)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy danh sách assigned property IDs từ relationship assignedProperties() của user
     * 2. Tính toán các statistics:
     *    - Total properties: Số lượng properties được assign
     *    - Total/available/occupied units: Thống kê units trong assigned properties
     *    - Active leases: Số lượng leases active mà agent quản lý
     *    - Total/today viewings: Tổng số và số viewings hôm nay
     *    - My leads: Số lượng leads liên quan đến viewings hoặc bookings của agent
     *    - Active bookings: Số lượng bookings đang pending hoặc paid
     *    - Total/pending commission: Tổng commission đã trả và đang chờ
     * 3. Lấy recent activities:
     *    - Recent leases: 5 leases gần nhất với relationships (unit.property, tenant)
     *    - Recent viewings: 5 viewings gần nhất với relationships (unit.property, tenant, lead)
     *    - My leads: 5 leads gần nhất với viewings và bookings liên quan
     *    - My bookings: 5 bookings gần nhất với status pending/paid
     * 4. Tính commission summary:
     *    - Total paid/pending: Tổng commission đã trả và đang chờ
     *    - This month paid/pending: Commission trong tháng hiện tại
     * 5. Lấy today's tasks:
     *    - Viewings today: Viewings hôm nay với status requested/confirmed
     *    - Bookings to process: Bookings pending với hold_until >= today
     * 6. Lấy upcoming viewings: Viewings trong 7 ngày tới
     * 7. Tính performance metrics:
     *    - Leases/viewings/bookings this month: Số lượng trong tháng hiện tại
     *    - Conversion rate: Tỷ lệ chuyển đổi từ viewings sang bookings (tháng trước)
     * 8. Lấy danh sách properties với stats chi tiết (total units, available, occupied, active leases, occupancy rate)
     * 9. Trả về array chứa tất cả dữ liệu
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - user->assignedProperties(): Danh sách properties được assign cho agent
     * - Models: Unit, Lease, Viewing, Lead, BookingDeposit, CommissionEvent, Property
     * - Database tables: units, leases, viewings, leads, booking_deposits, commission_events, properties
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu (không cache vì cần real-time)
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng eager loading (with()) để tránh N+1 queries
     * - Sử dụng whereIn() với assignedPropertyIds để filter hiệu quả
     * - Sử dụng whereHas() với closure để filter leads liên quan đến agent
     * 
     * @param \App\Models\User $user User hiện tại (agent)
     * @param int $organizationId ID của tổ chức
     * @return array Dữ liệu dashboard cho agent
     */
    private function getAgentDashboardData($user, $organizationId)
    {
        // Lấy danh sách property IDs được assign cho agent này → Dùng để filter units, leases, viewings
        // Sử dụng pluck() để chỉ lấy ID, không cần load toàn bộ model → Tối ưu performance
        $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
        
        // Tính toán các statistics cơ bản cho agent → Dùng để hiển thị key metrics trên dashboard
        $stats = [
            // Đếm số lượng properties được assign → Dùng để hiển thị tổng số BĐS quản lý
            'total_properties' => $assignedPropertyIds->count(),
            // Đếm tổng số units trong assigned properties → Dùng để tính occupancy rate
            'total_units' => Unit::whereIn('property_id', $assignedPropertyIds)->count(),
            // Đếm units có status 'available' → Dùng để hiển thị số phòng trống
            'available_units' => Unit::whereIn('property_id', $assignedPropertyIds)
                ->where('status', 'available') // Chỉ lấy units trống
                ->count(),
            // Đếm units có status 'occupied' → Dùng để tính occupancy rate
            'occupied_units' => Unit::whereIn('property_id', $assignedPropertyIds)
                ->where('status', 'occupied') // Chỉ lấy units đã thuê
                ->count(),
            // Đếm leases active mà agent quản lý → Dùng để hiển thị số hợp đồng đang hoạt động
            'active_leases' => Lease::where('agent_id', $user->id) // Filter theo agent
                ->where('status', 'active') // Chỉ lấy leases đang active
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count(),
            // Đếm tổng số viewings → Dùng để hiển thị tổng số lượt xem phòng
            'total_viewings' => Viewing::where('agent_id', $user->id)->count(),
            // Đếm viewings hôm nay → Dùng để hiển thị số lịch hẹn hôm nay
            'today_viewings' => Viewing::where('agent_id', $user->id)
                ->whereDate('schedule_at', today()) // Chỉ lấy viewings hôm nay
                ->count(),
            // Đếm leads liên quan đến agent → Dùng để hiển thị số leads của agent
            // Sử dụng whereHas() để filter leads có viewings hoặc bookingDeposits với agent_id
            // distinct() để tránh đếm trùng nếu lead có nhiều viewings/bookings
            'my_leads' => Lead::whereHas('viewings', function($q) use ($user) {
                $q->where('agent_id', $user->id); // Leads có viewings của agent này
            })->orWhereHas('bookingDeposits', function($q) use ($user) {
                $q->where('agent_id', $user->id); // Hoặc có bookings của agent này
            })->distinct()->count(), // Distinct để tránh đếm trùng
            // Đếm bookings đang pending hoặc paid → Dùng để hiển thị số đặt cọc đang xử lý
            'active_bookings' => BookingDeposit::where('agent_id', $user->id)
                ->whereIn('payment_status', ['pending', 'paid']) // Chỉ lấy pending hoặc paid
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count(),
            // Tính tổng commission đã trả → Dùng để hiển thị tổng hoa hồng đã nhận
            'total_commission' => CommissionEvent::where('agent_id', $user->id)
                ->where('status', 'paid') // Chỉ lấy commission đã thanh toán
                ->sum('commission_total'), // Tính tổng commission
            // Tính tổng commission đang chờ → Dùng để hiển thị hoa hồng chờ thanh toán
            'pending_commission' => CommissionEvent::where('agent_id', $user->id)
                ->where('status', 'pending') // Chỉ lấy commission đang chờ
                ->sum('commission_total'), // Tính tổng commission
        ];

        // Lấy 5 leases gần nhất → Dùng để hiển thị recent leases trên dashboard
        $recentLeases = Lease::where('agent_id', $user->id) // Filter theo agent
            ->with(['unit.property', 'tenant']) // Eager load relationships → Tránh N+1 queries
            ->orderBy('created_at', 'desc') // Sắp xếp mới nhất trước
            ->limit(5) // Chỉ lấy 5 bản ghi
            ->get();

        // Lấy 5 viewings gần nhất → Dùng để hiển thị recent viewings trên dashboard
        $recentViewings = Viewing::where('agent_id', $user->id) // Filter theo agent
            ->with(['unit.property', 'tenant', 'lead']) // Eager load relationships → Tránh N+1 queries
            ->orderBy('schedule_at', 'desc') // Sắp xếp theo lịch hẹn mới nhất
            ->limit(5) // Chỉ lấy 5 bản ghi
            ->get();

        // Lấy 5 leads gần nhất liên quan đến agent → Dùng để hiển thị my leads trên dashboard
        $myLeads = Lead::whereHas('viewings', function($q) use ($user) {
            $q->where('agent_id', $user->id); // Leads có viewings của agent
        })->orWhereHas('bookingDeposits', function($q) use ($user) {
            $q->where('agent_id', $user->id); // Hoặc có bookings của agent
        })->with(['viewings' => function($q) use ($user) {
            $q->where('agent_id', $user->id)->latest('schedule_at')->limit(1); // Lấy viewing mới nhất
        }, 'bookingDeposits' => function($q) use ($user) {
            $q->where('agent_id', $user->id)->latest('created_at')->limit(1); // Lấy booking mới nhất
        }])->orderBy('created_at', 'desc')->limit(5)->get(); // Sắp xếp mới nhất, chỉ lấy 5

        // Lấy 5 bookings gần nhất → Dùng để hiển thị my bookings trên dashboard
        $myBookings = BookingDeposit::where('agent_id', $user->id) // Filter theo agent
            ->whereIn('payment_status', ['pending', 'paid']) // Chỉ lấy pending hoặc paid
            ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
            ->with(['unit.property', 'lead', 'tenantUser']) // Eager load relationships → Tránh N+1 queries
            ->orderBy('created_at', 'desc') // Sắp xếp mới nhất trước
            ->limit(5) // Chỉ lấy 5 bản ghi
            ->get();

        // Tính commission summary → Dùng để hiển thị tổng quan hoa hồng
        $commissionSummary = [
            // Tổng commission đã trả (tất cả thời gian) → Dùng để hiển thị tổng hoa hồng đã nhận
            'total_paid' => CommissionEvent::where('agent_id', $user->id)
                ->where('status', 'paid') // Chỉ lấy đã thanh toán
                ->sum('commission_total'), // Tính tổng
            // Tổng commission đang chờ (tất cả thời gian) → Dùng để hiển thị hoa hồng chờ thanh toán
            'total_pending' => CommissionEvent::where('agent_id', $user->id)
                ->where('status', 'pending') // Chỉ lấy đang chờ
                ->sum('commission_total'), // Tính tổng
            // Commission đã trả trong tháng này → Dùng để hiển thị hoa hồng tháng này
            'this_month' => CommissionEvent::where('agent_id', $user->id)
                ->where('status', 'paid') // Chỉ lấy đã thanh toán
                ->whereMonth('occurred_at', Carbon::now()->month) // Filter tháng hiện tại
                ->whereYear('occurred_at', Carbon::now()->year) // Filter năm hiện tại
                ->sum('commission_total'), // Tính tổng
            // Commission đang chờ trong tháng này → Dùng để hiển thị hoa hồng chờ tháng này
            'this_month_pending' => CommissionEvent::where('agent_id', $user->id)
                ->where('status', 'pending') // Chỉ lấy đang chờ
                ->whereMonth('occurred_at', Carbon::now()->month) // Filter tháng hiện tại
                ->whereYear('occurred_at', Carbon::now()->year) // Filter năm hiện tại
                ->sum('commission_total'), // Tính tổng
        ];

        // Lấy tasks hôm nay → Dùng để hiển thị công việc cần làm hôm nay
        $todayTasks = [
            // Viewings hôm nay → Dùng để hiển thị lịch hẹn hôm nay
            'viewings_today' => Viewing::where('agent_id', $user->id)
                ->whereDate('schedule_at', today()) // Chỉ lấy viewings hôm nay
                ->whereIn('status', ['requested', 'confirmed']) // Chỉ lấy requested hoặc confirmed
                ->with(['unit.property', 'lead', 'tenant']) // Eager load relationships → Tránh N+1 queries
                ->orderBy('schedule_at', 'asc') // Sắp xếp theo giờ sớm nhất
                ->get(),
            // Bookings cần xử lý → Dùng để hiển thị đặt cọc cần xử lý
            'bookings_to_process' => BookingDeposit::where('agent_id', $user->id)
                ->where('payment_status', 'pending') // Chỉ lấy đang pending
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->where('hold_until', '>=', today()) // Chỉ lấy hold_until >= hôm nay
                ->with(['unit.property', 'lead']) // Eager load relationships → Tránh N+1 queries
                ->orderBy('hold_until', 'asc') // Sắp xếp theo hold_until sớm nhất
                ->limit(5) // Chỉ lấy 5 bản ghi
                ->get(),
        ];

        // Lấy viewings trong 7 ngày tới → Dùng để hiển thị lịch hẹn sắp tới
        $upcomingViewings = Viewing::where('agent_id', $user->id)
            ->where('schedule_at', '>=', now()) // Chỉ lấy từ bây giờ trở đi
            ->where('schedule_at', '<=', now()->addDays(7)) // Chỉ lấy trong 7 ngày tới
            ->whereIn('status', ['requested', 'confirmed']) // Chỉ lấy requested hoặc confirmed
            ->with(['unit.property', 'lead', 'tenant']) // Eager load relationships → Tránh N+1 queries
            ->orderBy('schedule_at', 'asc') // Sắp xếp theo lịch hẹn sớm nhất
            ->get();

        // Tính performance metrics → Dùng để hiển thị hiệu suất làm việc của agent
        $performanceMetrics = [
            // Đếm leases trong tháng này → Dùng để hiển thị số hợp đồng ký trong tháng
            'leases_this_month' => Lease::where('agent_id', $user->id)
                ->whereMonth('created_at', Carbon::now()->month) // Filter tháng hiện tại
                ->whereYear('created_at', Carbon::now()->year) // Filter năm hiện tại
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count(),
            // Đếm viewings trong tháng này → Dùng để tính conversion rate
            'viewings_this_month' => Viewing::where('agent_id', $user->id)
                ->whereMonth('schedule_at', Carbon::now()->month) // Filter tháng hiện tại
                ->whereYear('schedule_at', Carbon::now()->year) // Filter năm hiện tại
                ->count(),
            // Đếm bookings trong tháng này → Dùng để tính conversion rate
            'bookings_this_month' => BookingDeposit::where('agent_id', $user->id)
                ->whereMonth('created_at', Carbon::now()->month) // Filter tháng hiện tại
                ->whereYear('created_at', Carbon::now()->year) // Filter năm hiện tại
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count(),
            'conversion_rate' => 0, // Khởi tạo conversion rate = 0
        ];

        // Tính conversion rate dựa trên tháng trước → Dùng để hiển thị tỷ lệ chuyển đổi
        // Đếm tổng viewings tháng trước → Dùng để tính mẫu số
        $totalViewingsLastMonth = Viewing::where('agent_id', $user->id)
            ->whereMonth('schedule_at', Carbon::now()->subMonth()->month) // Filter tháng trước
            ->whereYear('schedule_at', Carbon::now()->subMonth()->year) // Filter năm tháng trước
            ->count();
        // Đếm tổng bookings tháng trước → Dùng để tính tử số
        $totalBookingsLastMonth = BookingDeposit::where('agent_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month) // Filter tháng trước
            ->whereYear('created_at', Carbon::now()->subMonth()->year) // Filter năm tháng trước
            ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
            ->count();
        
        if ($totalViewingsLastMonth > 0) { // Nếu có viewings tháng trước
            // Tính conversion rate: (bookings / viewings) * 100 → Dùng để hiển thị tỷ lệ chuyển đổi
            $performanceMetrics['conversion_rate'] = round(($totalBookingsLastMonth / $totalViewingsLastMonth) * 100, 1);
        }

        // Lấy properties với stats chi tiết → Dùng để hiển thị danh sách BĐS được gán
        $properties = Property::whereIn('id', $assignedPropertyIds) // Chỉ lấy assigned properties
            ->with(['units' => function($query) {
                // Eager load units với leases active → Tránh N+1 queries
                $query->with(['leases' => function($leaseQuery) {
                    $leaseQuery->where('status', 'active')->whereNull('deleted_at'); // Chỉ lấy leases active
                }]);
            }])
            ->where('status', 1) // Chỉ lấy properties active
            ->get();

        // Tính stats cho từng property → Dùng để hiển thị thống kê chi tiết từng BĐS
        $properties->each(function ($property) {
            $property->total_units = $property->units->count(); // Tổng số units → Dùng để tính occupancy rate
            $property->available_units = $property->units->where('status', 'available')->count(); // Units trống → Dùng để hiển thị số phòng trống
            $property->occupied_units = $property->units->where('status', 'occupied')->count(); // Units đã thuê → Dùng để tính occupancy rate
            $property->active_leases = $property->units->sum(function($unit) {
                return $unit->leases->count(); // Tổng số leases active → Dùng để hiển thị số hợp đồng
            });
            // Tính occupancy rate: (occupied / total) * 100 → Dùng để hiển thị tỷ lệ lấp đầy
            $property->occupancy_rate = $property->total_units > 0 ? 
                round(($property->occupied_units / $property->total_units) * 100, 1) : 0;
        });

        return [
            'stats' => $stats,
            'recentLeases' => $recentLeases,
            'recentViewings' => $recentViewings,
            'properties' => $properties,
            'myLeads' => $myLeads,
            'myBookings' => $myBookings,
            'commissionSummary' => $commissionSummary,
            'todayTasks' => $todayTasks,
            'upcomingViewings' => $upcomingViewings,
            'performanceMetrics' => $performanceMetrics,
        ];
    }

    /**
     * Lấy thống kê cơ bản cho Manager (key performance statistics)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Đếm số lượng properties trong organization (chưa bị xóa)
     * 2. Đếm tổng số units trong organization (JOIN với properties, filter theo organization_id)
     * 3. Đếm số units đang occupied (JOIN với leases, filter status = 'active', sử dụng DISTINCT)
     * 4. Tính occupancy rate = (occupied_units / total_units) * 100
     * 5. Đếm số upcoming viewings (schedule_at >= now, status = 'confirmed')
     * 6. Đếm tổng số leads và converted leads trong organization
     * 7. Tính conversion rate = (converted_leads / total_leads) * 100
     * 8. Trả về array chứa tất cả statistics
     * 9. Nếu có lỗi, log error và trả về giá trị mặc định (0)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database tables: properties, units, leases, viewings, leads
     * - Filter theo organization_id để đảm bảo data isolation
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng DB::table() với JOINs thay vì Eloquent để tối ưu performance
     * - Sử dụng DISTINCT khi đếm occupied units để tránh duplicate (một unit có thể có nhiều leases)
     * - Filter deleted_at để loại bỏ soft-deleted records
     * 
     * @param int $organizationId ID của tổ chức
     * @return array Thống kê cơ bản (properties_count, occupancy_rate, total_units, occupied_units, upcoming_viewings, conversion_rate, total_leads, converted_leads)
     */
    private function getKeyStats($organizationId)
    {
        try {
            // Đếm số lượng properties trong organization (chưa bị xóa)
            $propertiesCount = DB::table('properties')
                ->where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->count();

            // Đếm tổng số units trong organization
            // JOIN với properties để filter theo organization_id
            $totalUnits = DB::table('units')
                ->join('properties', 'properties.id', '=', 'units.property_id')
                ->where('properties.organization_id', $organizationId)
                ->whereNull('units.deleted_at')
                ->whereNull('properties.deleted_at')
                ->count();

            // Đếm số units đang occupied (có lease active)
            // JOIN với leases và filter status = 'active'
            // Sử dụng DISTINCT để tránh đếm trùng nếu một unit có nhiều leases (không nên xảy ra nhưng để an toàn)
            $occupiedUnits = DB::table('units')
                ->join('properties', 'properties.id', '=', 'units.property_id')
                ->join('leases', 'leases.unit_id', '=', 'units.id')
                ->where('properties.organization_id', $organizationId)
                ->where('leases.status', 'active')
                ->whereNull('units.deleted_at')
                ->whereNull('properties.deleted_at')
                ->whereNull('leases.deleted_at')
                ->distinct()
                ->count('units.id');

            // Tính occupancy rate: (số units occupied / tổng số units) * 100
            // Nếu totalUnits = 0 thì trả về 0 để tránh chia cho 0
            $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

            // Đếm số upcoming viewings (viewings sắp tới với status = 'confirmed')
            // JOIN với properties để filter theo organization_id
            $upcomingViewings = DB::table('viewings')
                ->join('properties', 'properties.id', '=', 'viewings.property_id')
                ->where('properties.organization_id', $organizationId)
                ->where('viewings.schedule_at', '>=', now())
                ->where('viewings.status', 'confirmed')
                ->whereNull('viewings.deleted_at')
                ->whereNull('properties.deleted_at')
                ->count();

            // Đếm tổng số leads trong organization
            $totalLeads = DB::table('leads')
                ->where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->count();

            // Đếm số leads đã converted (status = 'converted')
            $convertedLeads = DB::table('leads')
                ->where('organization_id', $organizationId)
                ->where('status', 'converted')
                ->whereNull('deleted_at')
                ->count();

            // Tính conversion rate: (số leads converted / tổng số leads) * 100
            // Nếu totalLeads = 0 thì trả về 0 để tránh chia cho 0
            $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

            return [
                'properties_count' => $propertiesCount,
                'occupancy_rate' => $occupancyRate,
                'total_units' => $totalUnits,
                'occupied_units' => $occupiedUnits,
                'upcoming_viewings' => $upcomingViewings,
                'conversion_rate' => $conversionRate,
                'total_leads' => $totalLeads,
                'converted_leads' => $convertedLeads,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting key stats: ' . $e->getMessage());
            return [
                'properties_count' => 0,
                'occupancy_rate' => 0,
                'total_units' => 0,
                'occupied_units' => 0,
                'upcoming_viewings' => 0,
                'conversion_rate' => 0,
                'total_leads' => 0,
                'converted_leads' => 0,
            ];
        }
    }

    /**
     * Lấy thống kê doanh thu và commission cho Manager
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tính monthly revenue: Tổng total_amount của invoices có status = 'paid' trong tháng hiện tại
     * 2. Tính previous month revenue: Tổng total_amount của invoices có status = 'paid' trong tháng trước
     * 3. Tính revenue growth: ((monthlyRevenue - previousMonthRevenue) / previousMonthRevenue) * 100
     * 4. Tính monthly commission: Tổng commission_total của commission_events trong tháng hiện tại
     * 5. Tính previous month commission: Tổng commission_total của commission_events trong tháng trước
     * 6. Tính commission growth: ((monthlyCommission - previousMonthCommission) / previousMonthCommission) * 100
     * 7. Đếm pending invoices: Số lượng invoices có status 'issued' hoặc 'overdue'
     * 8. Đếm open tickets: Số lượng tickets có status 'open' hoặc 'in_progress'
     * 9. Trả về array chứa tất cả statistics
     * 10. Nếu có lỗi, log error và trả về giá trị mặc định (0)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database tables: invoices, commission_events, tickets
     * - Filter theo organization_id và tháng (year, month)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng DB::table() với sum() để tính tổng trực tiếp từ database
     * - Filter theo year và month để giảm số lượng records cần xử lý
     * 
     * @param int $organizationId ID của tổ chức
     * @return array Thống kê doanh thu (monthly_revenue, revenue_growth, monthly_commission, commission_growth, pending_invoices, open_tickets)
     */
    private function getRevenueStats($organizationId)
    {
        try {
            // Tính tổng doanh thu tháng hiện tại từ invoices đã thanh toán (status = 'paid')
            // Filter theo organization_id, year, month của issue_date
            $monthlyRevenue = DB::table('invoices')
                ->where('organization_id', $organizationId)
                ->where('status', 'paid')
                ->whereYear('issue_date', now()->year)
                ->whereMonth('issue_date', now()->month)
                ->whereNull('deleted_at')
                ->sum('total_amount');

            // Tính tổng doanh thu tháng trước từ invoices đã thanh toán
            // Sử dụng subMonth() để lấy tháng trước
            $previousMonthRevenue = DB::table('invoices')
                ->where('organization_id', $organizationId)
                ->where('status', 'paid')
                ->whereYear('issue_date', now()->subMonth()->year)
                ->whereMonth('issue_date', now()->subMonth()->month)
                ->whereNull('deleted_at')
                ->sum('total_amount');

            // Tính revenue growth: ((tháng hiện tại - tháng trước) / tháng trước) * 100
            // Nếu previousMonthRevenue = 0 thì trả về 0 để tránh chia cho 0
            $revenueGrowth = $previousMonthRevenue > 0 
                ? round((($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 1)
                : 0;

            // Tính tổng commission tháng hiện tại → Dùng để hiển thị hoa hồng tháng này
            $monthlyCommission = DB::table('commission_events')
                ->where('organization_id', $organizationId) // Filter theo organization
                ->whereYear('occurred_at', now()->year) // Filter năm hiện tại
                ->whereMonth('occurred_at', now()->month) // Filter tháng hiện tại
                ->sum('commission_total'); // Tính tổng commission

            // Tính tổng commission tháng trước → Dùng để tính commission growth
            $previousMonthCommission = DB::table('commission_events')
                ->where('organization_id', $organizationId) // Filter theo organization
                ->whereYear('occurred_at', now()->subMonth()->year) // Filter năm tháng trước
                ->whereMonth('occurred_at', now()->subMonth()->month) // Filter tháng trước
                ->sum('commission_total'); // Tính tổng commission

            // Tính commission growth: ((tháng hiện tại - tháng trước) / tháng trước) * 100 → Dùng để hiển thị tăng trưởng
            // Nếu previousMonthCommission = 0 thì trả về 0 để tránh chia cho 0
            $commissionGrowth = $previousMonthCommission > 0 
                ? round((($monthlyCommission - $previousMonthCommission) / $previousMonthCommission) * 100, 1)
                : 0;

            // Đếm pending invoices → Dùng để hiển thị số hóa đơn cần xử lý
            $pendingInvoices = DB::table('invoices')
                ->where('organization_id', $organizationId) // Filter theo organization
                ->whereIn('status', ['issued', 'overdue']) // Chỉ lấy issued hoặc overdue
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count();

            // Đếm open tickets → Dùng để hiển thị số tickets đang mở
            $openTickets = DB::table('tickets')
                ->where('organization_id', $organizationId) // Filter theo organization
                ->whereIn('status', ['open', 'in_progress']) // Chỉ lấy open hoặc in_progress
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count();

            return [
                'monthly_revenue' => $monthlyRevenue,
                'revenue_growth' => $revenueGrowth,
                'monthly_commission' => $monthlyCommission,
                'commission_growth' => $commissionGrowth,
                'pending_invoices' => $pendingInvoices,
                'open_tickets' => $openTickets,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting revenue stats: ' . $e->getMessage());
            return [
                'monthly_revenue' => 0,
                'revenue_growth' => 0,
                'monthly_commission' => 0,
                'commission_growth' => 0,
                'pending_invoices' => 0,
                'open_tickets' => 0,
            ];
        }
    }

    /**
     * Lấy thống kê tình trạng occupancy của units cho Manager
     * 
     * LUỒNG XỬ LÝ:
     * 1. Đếm tổng số units trong organization (JOIN với properties)
     * 2. Đếm số units đang occupied (JOIN với leases, status = 'active', DISTINCT)
     * 3. Đếm số units đang reserved (JOIN với leases, status = 'pending', DISTINCT)
     * 4. Đếm số units đang maintenance (status = 'maintenance')
     * 5. Tính available units = totalUnits - occupiedUnits - reservedUnits - maintenanceUnits
     * 6. Trả về array chứa số lượng units theo từng trạng thái
     * 7. Nếu có lỗi, log error và trả về giá trị mặc định (0)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database tables: units, properties, leases
     * - Filter theo organization_id và status
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng DB::table() với JOINs để tối ưu performance
     * - Sử dụng DISTINCT khi đếm occupied/reserved units để tránh duplicate
     * 
     * @param int $organizationId ID của tổ chức
     * @return array Thống kê occupancy (available, occupied, reserved, maintenance)
     */
    private function getOccupancyStats($organizationId)
    {
        try {
            // Đếm tổng số units trong organization → Dùng để tính available units
            $totalUnits = DB::table('units')
                ->join('properties', 'properties.id', '=', 'units.property_id') // JOIN với properties để filter theo organization
                ->where('properties.organization_id', $organizationId) // Filter theo organization
                ->whereNull('units.deleted_at') // Chỉ lấy units chưa bị xóa
                ->whereNull('properties.deleted_at') // Chỉ lấy properties chưa bị xóa
                ->count();

            // Đếm units đang occupied (có lease active) → Dùng để hiển thị số phòng đã thuê
            $occupiedUnits = DB::table('units')
                ->join('properties', 'properties.id', '=', 'units.property_id') // JOIN với properties
                ->join('leases', 'leases.unit_id', '=', 'units.id') // JOIN với leases
                ->where('properties.organization_id', $organizationId) // Filter theo organization
                ->where('leases.status', 'active') // Chỉ lấy leases active
                ->whereNull('units.deleted_at') // Chỉ lấy units chưa bị xóa
                ->whereNull('properties.deleted_at') // Chỉ lấy properties chưa bị xóa
                ->whereNull('leases.deleted_at') // Chỉ lấy leases chưa bị xóa
                ->distinct() // DISTINCT để tránh đếm trùng
                ->count('units.id');

            // Đếm units đang reserved (có lease pending) → Dùng để hiển thị số phòng đặt cọc
            $reservedUnits = DB::table('units')
                ->join('properties', 'properties.id', '=', 'units.property_id') // JOIN với properties
                ->join('leases', 'leases.unit_id', '=', 'units.id') // JOIN với leases
                ->where('properties.organization_id', $organizationId) // Filter theo organization
                ->where('leases.status', 'pending') // Chỉ lấy leases pending
                ->whereNull('units.deleted_at') // Chỉ lấy units chưa bị xóa
                ->whereNull('properties.deleted_at') // Chỉ lấy properties chưa bị xóa
                ->whereNull('leases.deleted_at') // Chỉ lấy leases chưa bị xóa
                ->distinct() // DISTINCT để tránh đếm trùng
                ->count('units.id');

            // Đếm units đang maintenance → Dùng để hiển thị số phòng bảo trì
            $maintenanceUnits = DB::table('units')
                ->join('properties', 'properties.id', '=', 'units.property_id') // JOIN với properties
                ->where('properties.organization_id', $organizationId) // Filter theo organization
                ->where('units.status', 'maintenance') // Chỉ lấy units có status maintenance
                ->whereNull('units.deleted_at') // Chỉ lấy units chưa bị xóa
                ->whereNull('properties.deleted_at') // Chỉ lấy properties chưa bị xóa
                ->count();

            // Tính available units = total - occupied - reserved - maintenance → Dùng để hiển thị số phòng trống
            $availableUnits = $totalUnits - $occupiedUnits - $reservedUnits - $maintenanceUnits;

            return [
                'available' => max(0, $availableUnits),
                'occupied' => $occupiedUnits,
                'reserved' => $reservedUnits,
                'maintenance' => $maintenanceUnits,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting occupancy stats: ' . $e->getMessage());
            return [
                'available' => 0,
                'occupied' => 0,
                'reserved' => 0,
                'maintenance' => 0,
            ];
        }
    }

    /**
     * Lấy top 5 agents có performance tốt nhất (theo commission) trong tháng hiện tại
     * 
     * LUỒNG XỬ LÝ:
     * 1. JOIN commission_events với users để lấy thông tin agent
     * 2. Filter theo organization_id và tháng hiện tại (year, month của created_at)
     * 3. Group by user id và full_name
     * 4. Tính tổng commission_total và đếm số deals (COUNT(*))
     * 5. Order by total_commission DESC để lấy top performers
     * 6. Limit 5 để chỉ lấy top 5
     * 7. Trả về collection chứa thông tin agents
     * 8. Nếu có lỗi, log error và trả về empty collection
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database tables: commission_events, users
     * - Filter theo organization_id, year, month của created_at
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng DB::table() với JOINs và aggregation (SUM, COUNT)
     * - Group by và order by để sắp xếp và limit kết quả
     * 
     * @param int $organizationId ID của tổ chức
     * @return \Illuminate\Support\Collection Top 5 agents với total_commission và deals count
     */
    private function getTopPerformers($organizationId)
    {
        try {
            // Lấy top 5 agents theo commission trong tháng hiện tại → Dùng để hiển thị top performers
            return DB::table('commission_events')
                ->join('users', 'users.id', '=', 'commission_events.agent_id') // JOIN với users để lấy thông tin agent
                ->where('commission_events.organization_id', $organizationId) // Filter theo organization
                ->whereNull('commission_events.deleted_at') // Chỉ lấy commission chưa bị xóa
                ->whereNull('users.deleted_at') // Chỉ lấy users chưa bị xóa
                ->select(
                    'users.id', // ID của agent
                    'users.full_name', // Tên đầy đủ của agent
                    DB::raw('SUM(commission_events.commission_total) as total_commission'), // Tổng commission → Dùng để sắp xếp
                    DB::raw('COUNT(*) as deals') // Số lượng deals → Dùng để hiển thị số giao dịch
                )
                ->whereYear('commission_events.created_at', now()->year) // Filter năm hiện tại
                ->whereMonth('commission_events.created_at', now()->month) // Filter tháng hiện tại
                ->groupBy('users.id', 'users.full_name') // Group by agent → Tính tổng commission cho mỗi agent
                ->orderByDesc('total_commission') // Sắp xếp theo commission giảm dần → Lấy top performers
                ->limit(5) // Chỉ lấy top 5
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting top performers: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Lấy danh sách các tasks cần xử lý gấp cho Manager
     * 
     * LUỒNG XỬ LÝ:
     * 1. Đếm số overdue invoices: Invoices có status = 'overdue'
     * 2. Đếm số expiring leases: Leases có end_date <= 30 ngày tới và status = 'active'
     * 3. Đếm số pending viewings: Viewings có status = 'requested' (JOIN với properties để filter theo organization)
     * 4. Trả về array chứa số lượng từng loại urgent task
     * 5. Nếu có lỗi, log error và trả về giá trị mặc định (0)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database tables: invoices, leases, viewings, properties
     * - Filter theo organization_id và các điều kiện cụ thể
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng DB::table() với JOINs khi cần filter theo organization
     * - Sử dụng whereDate() và addDays() để filter theo thời gian
     * 
     * @param int $organizationId ID của tổ chức
     * @return array Số lượng urgent tasks (overdue_invoices, expiring_leases, pending_viewings)
     */
    private function getUrgentTasks($organizationId)
    {
        try {
            // Đếm overdue invoices → Dùng để hiển thị số hóa đơn quá hạn cần xử lý
            $overdueInvoices = DB::table('invoices')
                ->where('organization_id', $organizationId) // Filter theo organization
                ->where('status', 'overdue') // Chỉ lấy invoices quá hạn
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count();

            // Đếm expiring leases (hết hạn trong 30 ngày) → Dùng để hiển thị số hợp đồng sắp hết hạn
            $expiringLeases = DB::table('leases')
                ->where('organization_id', $organizationId) // Filter theo organization
                ->where('end_date', '<=', now()->addDays(30)) // Chỉ lấy leases hết hạn trong 30 ngày tới
                ->where('status', 'active') // Chỉ lấy leases đang active
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count();

            // Đếm pending viewings → Dùng để hiển thị số lịch hẹn chờ duyệt
            $pendingViewings = DB::table('viewings')
                ->join('properties', 'properties.id', '=', 'viewings.property_id') // JOIN với properties để filter theo organization
                ->where('properties.organization_id', $organizationId) // Filter theo organization
                ->where('viewings.status', 'requested') // Chỉ lấy viewings đang requested (chờ duyệt)
                ->whereNull('viewings.deleted_at') // Chỉ lấy viewings chưa bị xóa
                ->whereNull('properties.deleted_at') // Chỉ lấy properties chưa bị xóa
                ->count();

            return [
                'overdue_invoices' => $overdueInvoices,
                'expiring_leases' => $expiringLeases,
                'pending_viewings' => $pendingViewings,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting urgent tasks: ' . $e->getMessage());
            return [
                'overdue_invoices' => 0,
                'expiring_leases' => 0,
                'pending_viewings' => 0,
            ];
        }
    }

    /**
     * Lấy 5 hoạt động gần nhất từ audit_logs cho Manager
     * 
     * LUỒNG XỬ LÝ:
     * 1. JOIN audit_logs với users để lấy thông tin actor (người thực hiện)
     * 2. Filter theo organization_id
     * 3. Order by created_at DESC để lấy các hoạt động mới nhất
     * 4. Limit 5 để chỉ lấy 5 hoạt động gần nhất
     * 5. Select audit_logs.* và users.full_name
     * 6. Trả về collection chứa audit logs với thông tin actor
     * 7. Nếu có lỗi, log error và trả về empty collection
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database tables: audit_logs, users
     * - Filter theo organization_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng DB::table() với JOINs
     * - Order by và limit để giảm số lượng records trả về
     * 
     * @param int $organizationId ID của tổ chức
     * @return \Illuminate\Support\Collection 5 audit logs gần nhất với thông tin actor
     */
    private function getRecentActivities($organizationId)
    {
        try {
            // Lấy 5 audit logs gần nhất → Dùng để hiển thị hoạt động gần đây
            return DB::table('audit_logs')
                ->join('users', 'users.id', '=', 'audit_logs.actor_id') // JOIN với users để lấy thông tin người thực hiện
                ->where('audit_logs.organization_id', $organizationId) // Filter theo organization
                ->whereNull('audit_logs.deleted_at') // Chỉ lấy audit logs chưa bị xóa
                ->whereNull('users.deleted_at') // Chỉ lấy users chưa bị xóa
                ->orderBy('audit_logs.created_at', 'desc') // Sắp xếp mới nhất trước
                ->limit(5) // Chỉ lấy 5 bản ghi
                ->select('audit_logs.*', 'users.full_name') // Select tất cả audit_logs fields và full_name của user
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting recent activities: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Lấy dữ liệu phân tích trong 30 ngày qua cho Manager
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tính 30 ngày trước từ hiện tại
     * 2. Đếm số new leads: Leads được tạo trong 30 ngày qua
     * 3. Đếm số total viewings: Viewings được tạo trong 30 ngày qua (JOIN với properties)
     * 4. Đếm số new leases: Leases được tạo trong 30 ngày qua
     * 5. Đếm số new deposits: Booking deposits được thanh toán (payment_status = 'paid') trong 30 ngày qua (JOIN với units và properties)
     * 6. Trả về array chứa tất cả analytics data
     * 7. Nếu có lỗi, log error và trả về giá trị mặc định (0)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database tables: leads, viewings, properties, leases, booking_deposits, units
     * - Filter theo organization_id và created_at >= 30 ngày trước
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng DB::table() với JOINs khi cần filter theo organization
     * - Filter theo created_at >= thirtyDaysAgo để giảm số lượng records
     * 
     * @param int $organizationId ID của tổ chức
     * @return array Analytics data (new_leads, total_viewings, new_leases, new_deposits)
     */
    private function getAnalyticsData($organizationId)
    {
        try {
            // Tính 30 ngày trước từ hiện tại → Dùng để filter data trong 30 ngày qua
            $thirtyDaysAgo = now()->subDays(30);

            // Đếm new leads trong 30 ngày qua → Dùng để hiển thị số leads mới
            $newLeads = DB::table('leads')
                ->where('organization_id', $organizationId) // Filter theo organization
                ->where('created_at', '>=', $thirtyDaysAgo) // Chỉ lấy leads tạo trong 30 ngày qua
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count();

            // Đếm total viewings trong 30 ngày qua → Dùng để hiển thị số lượt xem phòng
            $totalViewings = DB::table('viewings')
                ->join('properties', 'properties.id', '=', 'viewings.property_id') // JOIN với properties để filter theo organization
                ->where('properties.organization_id', $organizationId) // Filter theo organization
                ->where('viewings.created_at', '>=', $thirtyDaysAgo) // Chỉ lấy viewings tạo trong 30 ngày qua
                ->whereNull('viewings.deleted_at') // Chỉ lấy viewings chưa bị xóa
                ->whereNull('properties.deleted_at') // Chỉ lấy properties chưa bị xóa
                ->count();

            // Đếm new leases trong 30 ngày qua → Dùng để hiển thị số hợp đồng ký mới
            $newLeases = DB::table('leases')
                ->where('organization_id', $organizationId) // Filter theo organization
                ->where('created_at', '>=', $thirtyDaysAgo) // Chỉ lấy leases tạo trong 30 ngày qua
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->count();

            // Đếm new deposits (đã thanh toán) trong 30 ngày qua → Dùng để hiển thị số đặt cọc mới
            $newDeposits = DB::table('booking_deposits')
                ->join('units', 'booking_deposits.unit_id', '=', 'units.id') // JOIN với units
                ->join('properties', 'properties.id', '=', 'units.property_id') // JOIN với properties để filter theo organization
                ->where('booking_deposits.organization_id', $organizationId) // Filter theo organization
                ->where('properties.organization_id', $organizationId) // Filter theo organization (double check)
                ->where('booking_deposits.created_at', '>=', $thirtyDaysAgo) // Chỉ lấy deposits tạo trong 30 ngày qua
                ->where('booking_deposits.payment_status', 'paid') // Chỉ lấy deposits đã thanh toán
                ->whereNull('booking_deposits.deleted_at') // Chỉ lấy deposits chưa bị xóa
                ->whereNull('units.deleted_at') // Chỉ lấy units chưa bị xóa
                ->whereNull('properties.deleted_at') // Chỉ lấy properties chưa bị xóa
                ->count();

            return [
                'new_leads' => $newLeads,
                'total_viewings' => $totalViewings,
                'new_leases' => $newLeases,
                'new_deposits' => $newDeposits,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting analytics data: ' . $e->getMessage());
            return [
                'new_leads' => 0,
                'total_viewings' => 0,
                'new_leases' => 0,
                'new_deposits' => 0,
            ];
        }
    }

    /**
     * API endpoint: Lấy dữ liệu biểu đồ revenue và commission cho 6 tháng gần nhất
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy user hiện tại từ Auth
     * 2. Lấy organization_id từ relationship organizations()
     * 3. Kiểm tra nếu không có organization_id thì trả về JSON error 404
     * 4. Loop qua 6 tháng gần nhất (từ 5 tháng trước đến tháng hiện tại):
     *    - Tính startOfMonth và endOfMonth cho mỗi tháng
     *    - Format month label (mm/yyyy)
     *    - Tính revenue: Tổng total_amount của invoices paid trong tháng đó
     *    - Tính commission: Tổng commission_total của commission_events trong tháng đó
     *    - Convert sang đơn vị triệu (chia cho 1,000,000) và làm tròn 2 chữ số
     * 5. Trả về JSON response với labels (months), revenue data, và commission data
     * 6. Nếu có lỗi, log error và trả về JSON error 500 với data rỗng
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - user->organizations(): Relationship để lấy organization_id
     * - Database tables: invoices, commission_events
     * - Filter theo organization_id và date range (startOfMonth, endOfMonth)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng whereBetween() với startOfMonth và endOfMonth để filter hiệu quả
     * - Sử dụng sum() để tính tổng trực tiếp từ database
     * 
     * @return \Illuminate\Http\JsonResponse JSON response chứa labels, revenue, và commission data
     */
    public function getRevenueChartData()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lấy organization_id
        
        // Lấy organization ID từ relationship organizations() của user → Dùng để filter data theo organization
        $organizationId = $user->organizations()->first()?->id;
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json(['error' => 'Organization not found'], 404); // Trả về lỗi 404 → Frontend hiển thị thông báo lỗi
        }

        try {
            $months = []; // Array chứa labels cho biểu đồ (format: mm/yyyy) → Dùng để hiển thị trục X
            $revenueData = []; // Array chứa revenue data cho từng tháng (đơn vị: triệu) → Dùng để vẽ biểu đồ revenue
            $commissionData = []; // Array chứa commission data cho từng tháng (đơn vị: triệu) → Dùng để vẽ biểu đồ commission
            
            // Loop qua 6 tháng gần nhất (từ 5 tháng trước đến tháng hiện tại) → Dùng để tính revenue và commission cho từng tháng
            // $i = 5: 5 tháng trước, $i = 0: tháng hiện tại
            for ($i = 5; $i >= 0; $i--) {
                // Tính ngày của tháng tương ứng → Dùng để lấy startOfMonth và endOfMonth
                $date = Carbon::now()->subMonths($i);
                // Lấy ngày đầu tháng (00:00:00) → Dùng để filter invoices/commission từ đầu tháng
                $startOfMonth = $date->copy()->startOfMonth();
                // Lấy ngày cuối tháng (23:59:59) → Dùng để filter invoices/commission đến cuối tháng
                $endOfMonth = $date->copy()->endOfMonth();
                
                // Format month label: mm/yyyy (ví dụ: 01/2024) → Dùng để hiển thị trên trục X của biểu đồ
                $months[] = $date->format('m/Y');
                
                // Tính revenue cho tháng này: Tổng total_amount của invoices đã thanh toán → Dùng để hiển thị doanh thu
                // Filter theo organization_id, status = 'paid', và issue_date trong khoảng tháng
                $revenue = DB::table('invoices')
                    ->where('organization_id', $organizationId) // Chỉ lấy invoices của organization này
                    ->where('status', 'paid') // Chỉ lấy invoices đã thanh toán
                    ->whereBetween('issue_date', [$startOfMonth, $endOfMonth]) // Chỉ lấy invoices trong tháng này
                    ->whereNull('deleted_at') // Chỉ lấy invoices chưa bị xóa
                    ->sum('total_amount'); // Tính tổng total_amount → Dùng để hiển thị doanh thu
                
                // Convert sang đơn vị triệu (chia cho 1,000,000) và làm tròn 2 chữ số thập phân → Dùng để hiển thị trên biểu đồ
                $revenueData[] = round($revenue / 1000000, 2);
                
                // Tính commission cho tháng này: Tổng commission_total của commission_events → Dùng để hiển thị hoa hồng
                // Filter theo organization_id và occurred_at trong khoảng tháng
                $commission = DB::table('commission_events')
                    ->where('organization_id', $organizationId) // Chỉ lấy commission của organization này
                    ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth]) // Chỉ lấy commission trong tháng này
                    ->whereNull('deleted_at') // Chỉ lấy commission chưa bị xóa
                    ->sum('commission_total'); // Tính tổng commission_total → Dùng để hiển thị hoa hồng
                
                // Convert sang đơn vị triệu và làm tròn 2 chữ số thập phân → Dùng để hiển thị trên biểu đồ
                $commissionData[] = round($commission / 1000000, 2);
            }
            
            return response()->json([ // Trả về JSON response → Frontend dùng để vẽ biểu đồ
                'success' => true,
                'data' => [
                    'labels' => $months, // Labels cho trục X (tháng)
                    'revenue' => $revenueData, // Data cho biểu đồ revenue
                    'commission' => $commissionData // Data cho biểu đồ commission
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting revenue chart data: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            return response()->json([ // Trả về JSON error → Frontend hiển thị thông báo lỗi
                'success' => false,
                'error' => 'Failed to fetch chart data',
                'data' => [
                    'labels' => [],
                    'revenue' => [],
                    'commission' => []
                ]
            ], 500);
        }
    }

    /**
     * API endpoint: Xóa cache dashboard của manager
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy user hiện tại từ Auth
     * 2. Lấy organization_id từ relationship organizations()
     * 3. Nếu có organization_id, tạo cache key và xóa cache bằng Cache::forget()
     * 4. Trả về JSON response với success message
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - user->organizations(): Relationship để lấy organization_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Cache: Xóa cache key "dashboard_data_manager_org_{organizationId}"
     * 
     * @return \Illuminate\Http\JsonResponse JSON response với success message
     */
    public function clearCache()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lấy organization_id
        
        // Lấy organization ID từ relationship organizations() của user → Dùng để tạo cache key
        $organizationId = $user->organizations()->first()?->id;
        
        if ($organizationId) { // Nếu có organization ID
            // Tạo cache key giống với key được sử dụng trong getManagerDashboardData() → Dùng để xóa đúng cache
            $cacheKey = "dashboard_data_manager_org_{$organizationId}";
            // Xóa cache → Dashboard sẽ load dữ liệu mới nhất từ database ở lần request tiếp theo
            Cache::forget($cacheKey);
        }

        return response()->json(['success' => true, 'message' => 'Dashboard cache cleared successfully']); // Trả về JSON success → Frontend hiển thị thông báo thành công
    }
}

