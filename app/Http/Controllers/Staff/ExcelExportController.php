<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Traits\ChecksCapabilities;
use App\Services\ExcelExportPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
// Removed Excel imports - using CSV export instead

class ExcelExportController extends Controller
{
    use ChecksCapabilities;
    
    protected $exportPermissionService;
    
    public function __construct(ExcelExportPermissionService $exportPermissionService)
    {
        $this->exportPermissionService = $exportPermissionService;
    }
    
    /**
     * Check Excel export permission for current organization.
     * This method should be called at the beginning of each export method.
     */
    protected function checkExportPermission(): void
    {
        $organizationId = $this->getCurrentOrganizationId();
        if ($organizationId) {
            $this->exportPermissionService->requireExportPermission($organizationId);
        }
    }
    
    /**
     * Hiển thị trang xuất Excel
     */
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        // Check capability - only manager can access Excel export
        $this->requireCapability('finance.report.export', 'Bạn không có quyền truy cập Excel Export.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check subscription permission for Excel export
        $this->exportPermissionService->requireExportPermission($organizationId);
        
        // Lấy danh sách payment methods
        $paymentMethods = DB::table('payment_methods')->select('id', 'name', 'key_code')->get();
        
        // Danh sách các loại export
        $exportTypes = [
            'properties' => ['name' => 'Bất động sản', 'icon' => 'fas fa-building', 'color' => 'primary'],
            'units' => ['name' => 'Phòng/Căn', 'icon' => 'fas fa-door-open', 'color' => 'success'],
            'invoices' => ['name' => 'Hóa đơn', 'icon' => 'fas fa-file-invoice', 'color' => 'info'],
            'payments' => ['name' => 'Thanh toán', 'icon' => 'fas fa-money-bill-wave', 'color' => 'warning'],
            'leases' => ['name' => 'Hợp đồng', 'icon' => 'fas fa-file-contract', 'color' => 'danger'],
            'payroll' => ['name' => 'Phiếu lương', 'icon' => 'fas fa-money-check-alt', 'color' => 'secondary'],
            'company-invoices' => ['name' => 'Hóa đơn công ty', 'icon' => 'fas fa-file-invoice-dollar', 'color' => 'dark'],
            'cash-outflows' => ['name' => 'Dòng tiền chi', 'icon' => 'fas fa-arrow-down', 'color' => 'secondary'],
        ];
        
        return view('staff.finance.excel-export.index', compact('paymentMethods', 'exportTypes'));
    }
    
    /**
     * Preview dữ liệu trước khi export
     */
    public function preview(Request $request)
    {
        try {
            // Check capability - only manager can preview export
            $this->requireCapability('finance.report.export', 'Bạn không có quyền xem preview export.');
            
            // Check subscription permission
            $organizationId = $this->getCurrentOrganizationId();
            if ($organizationId) {
                $this->exportPermissionService->requireExportPermission($organizationId);
            }
            
            $exportType = $request->get('export_type');
            $limit = $request->get('limit', 10);
            
            if (!$exportType) {
                return response()->json(['success' => false, 'message' => 'Vui lòng chọn loại xuất']);
            }
            
            // Gọi method tương ứng để lấy dữ liệu preview
            $methodName = 'preview' . str_replace('-', '', ucwords($exportType, '-'));
            
            if (!method_exists($this, $methodName)) {
                return response()->json(['success' => false, 'message' => 'Loại xuất không hợp lệ']);
            }
            
            $data = $this->$methodName($request, $limit);
            
            return view('staff.finance.excel-export.preview', [
                'data' => $data['data'],
                'columns' => $data['columns'],
                'columnKeys' => $data['columnKeys'] ?? [],
                'total' => $data['total'],
                'exportType' => $exportType
            ]);
        } catch (\Exception $e) {
            Log::error('Export Preview Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Preview methods cho từng loại
     */
    private function previewProperties(Request $request, $limit = 10)
    {
        $organizationId = $this->getCurrentOrganizationId();
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = DB::table('properties as p')
            ->leftJoin('organizations as org', 'p.organization_id', '=', 'org.id')
            ->leftJoin('property_types as pt', 'p.property_type_id', '=', 'pt.id')
            ->leftJoin('locations as loc', 'p.location_id', '=', 'loc.id')
            ->leftJoin('geo_provinces as prov', 'loc.province_code', '=', 'prov.code')
            ->leftJoin('geo_districts as dist', 'loc.district_code', '=', 'dist.code')
            ->leftJoin('geo_wards as ward', 'loc.ward_code', '=', 'ward.code')
            ->leftJoin('locations_2025 as loc2025', 'p.location_id_2025', '=', 'loc2025.id')
            ->leftJoin('geo_provinces_2025 as prov2025', 'loc2025.province_code', '=', 'prov2025.code')
            ->leftJoin('geo_wards_2025 as ward2025', 'loc2025.ward_code', '=', 'ward2025.code')
            ->leftJoin('users as deleted_by_user', 'p.deleted_by', '=', 'deleted_by_user.id')
            ->where('p.organization_id', $organizationId)
            ->whereNull('p.deleted_at')
            ->select([
                'p.id',
                'p.name as ten_bat_dong_san',
                'org.name as ten_to_chuc',
                'pt.name as loai_bat_dong_san',
                DB::raw("CONCAT(COALESCE(loc.street, ''), ', ', COALESCE(ward.name, ''), ', ', COALESCE(dist.name, ''), ', ', COALESCE(prov.name, '')) as dia_chi_cu"),
                DB::raw("CONCAT(COALESCE(loc2025.street, ''), ', ', COALESCE(ward2025.name, ''), ', ', COALESCE(prov2025.name, '')) as dia_chi_moi"),
                'p.total_floors as tong_so_tang',
                'p.status as trang_thai',
                'p.created_at as ngay_tao',
                'p.updated_at as ngay_cap_nhat',
                DB::raw("CASE WHEN p.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa"),
                'deleted_by_user.email as nguoi_xoa'
            ]);

        if ($status !== null && $status !== '') {
            $query->where('p.status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('p.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('p.created_at', '<=', $dateTo);
        }

            $total = $query->count();
            $data = $query->limit($limit)->get();
            $columns = ['ID', 'Tên bất động sản', 'Tổ chức', 'Loại bất động sản', 'Địa chỉ (cũ)', 'Địa chỉ (mới 2025)', 'Tổng số tầng', 'Trạng thái', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa', 'Người xóa'];
            $columnKeys = ['id', 'ten_bat_dong_san', 'ten_to_chuc', 'loai_bat_dong_san', 'dia_chi_cu', 'dia_chi_moi', 'tong_so_tang', 'trang_thai', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa', 'nguoi_xoa'];
            
            return ['data' => $data, 'columns' => $columns, 'columnKeys' => $columnKeys, 'total' => $total];
    }
    
    private function previewUnits(Request $request, $limit = 10)
    {
        $organizationId = $this->getCurrentOrganizationId();
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = DB::table('units as u')
            ->leftJoin('properties as p', 'u.property_id', '=', 'p.id')
            ->leftJoin('organizations as org', 'p.organization_id', '=', 'org.id')
            ->leftJoin('users as deleted_by_user', 'u.deleted_by', '=', 'deleted_by_user.id')
            ->where('p.organization_id', $organizationId)
            ->whereNull('u.deleted_at')
            ->select([
                'u.id',
                'u.code as ma_phong',
                'p.name as ten_bat_dong_san',
                'u.floor as tang',
                'u.area_m2 as dien_tich',
                'u.unit_type as loai_phong',
                'u.base_rent as gia_thue',
                'u.deposit_amount as tien_coc',
                'u.max_occupancy as so_nguoi_toi_da',
                'u.status as trang_thai',
                'u.created_at as ngay_tao',
                'u.updated_at as ngay_cap_nhat',
                DB::raw("CASE WHEN u.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa"),
                'deleted_by_user.email as nguoi_xoa'
            ]);

        if ($status !== null && $status !== '') {
            $query->where('u.status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('u.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('u.created_at', '<=', $dateTo);
        }

        $total = $query->count();
        $data = $query->limit($limit)->get();
        $columns = ['ID', 'Mã phòng', 'Tên bất động sản', 'Tầng', 'Diện tích (m²)', 'Loại phòng', 'Giá thuê', 'Tiền cọc', 'Số người tối đa', 'Trạng thái', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa', 'Người xóa'];
        $columnKeys = ['id', 'ma_phong', 'ten_bat_dong_san', 'tang', 'dien_tich', 'loai_phong', 'gia_thue', 'tien_coc', 'so_nguoi_toi_da', 'trang_thai', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa', 'nguoi_xoa'];
        
        return ['data' => $data, 'columns' => $columns, 'columnKeys' => $columnKeys, 'total' => $total];
    }
    
    private function previewInvoices(Request $request, $limit = 10)
    {
        $organizationId = $this->getCurrentOrganizationId();
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = DB::table('invoices as inv')
            ->leftJoin('organizations as org', 'inv.organization_id', '=', 'org.id')
            ->leftJoin('leases as l', 'inv.lease_id', '=', 'l.id')
            ->leftJoin('units as u_lease', 'l.unit_id', '=', 'u_lease.id')
            ->leftJoin('properties as p_lease', 'u_lease.property_id', '=', 'p_lease.id')
            ->leftJoin('booking_deposits as bd', 'inv.booking_deposit_id', '=', 'bd.id')
            ->leftJoin('units as u_bd', 'bd.unit_id', '=', 'u_bd.id')
            ->leftJoin('properties as p_bd', 'u_bd.property_id', '=', 'p_bd.id')
            ->leftJoin('users as tenant', 'l.tenant_id', '=', 'tenant.id')
            ->leftJoin('user_profiles as tenant_profile', 'tenant.id', '=', 'tenant_profile.user_id')
            ->leftJoin('users as created_by_user', 'inv.created_by', '=', 'created_by_user.id')
            ->leftJoin('users as deleted_by_user', 'inv.deleted_by', '=', 'deleted_by_user.id')
            ->where('inv.organization_id', $organizationId)
            ->whereNull('inv.deleted_at')
            ->select([
                'inv.id',
                'inv.invoice_no as so_hoa_don',
                'inv.invoice_type as loai_hoa_don',
                'bd.reference_number as ma_dat_coc',
                'l.contract_no as so_hop_dong',
                DB::raw("COALESCE(p_lease.name, p_bd.name, 'N/A') as ten_bat_dong_san"),
                DB::raw("COALESCE(u_lease.code, u_bd.code, 'N/A') as ma_phong"),
                DB::raw("COALESCE(tenant_profile.full_name, tenant.email, 'N/A') as ten_khach_thue"),
                'inv.issue_date as ngay_phat_hanh',
                'inv.due_date as ngay_den_han',
                'inv.status as trang_thai',
                'inv.subtotal as tong_phu',
                'inv.tax_amount as tien_thue',
                'inv.discount_amount as tien_giam_gia',
                'inv.total_amount as tong_tien',
                'inv.currency as tien_te',
                'inv.created_at as ngay_tao',
                'created_by_user.email as nguoi_tao',
                DB::raw("CASE WHEN inv.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
            ]);

        if ($status !== null && $status !== '') {
            $query->where('inv.status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('inv.issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('inv.issue_date', '<=', $dateTo);
        }

        $total = $query->count();
        $data = $query->limit($limit)->get();
        $columns = ['ID', 'Số hóa đơn', 'Loại hóa đơn', 'Mã đặt cọc', 'Số hợp đồng', 'Tên bất động sản', 'Mã phòng', 'Tên khách thuê', 'Ngày phát hành', 'Ngày đến hạn', 'Trạng thái', 'Tổng phụ', 'Tiền thuế', 'Tiền giảm giá', 'Tổng tiền', 'Tiền tệ', 'Ngày tạo', 'Người tạo', 'Trạng thái xóa'];
        $columnKeys = ['id', 'so_hoa_don', 'loai_hoa_don', 'ma_dat_coc', 'so_hop_dong', 'ten_bat_dong_san', 'ma_phong', 'ten_khach_thue', 'ngay_phat_hanh', 'ngay_den_han', 'trang_thai', 'tong_phu', 'tien_thue', 'tien_giam_gia', 'tong_tien', 'tien_te', 'ngay_tao', 'nguoi_tao', 'trang_thai_xoa'];
        
        return ['data' => $data, 'columns' => $columns, 'columnKeys' => $columnKeys, 'total' => $total];
    }
    
    private function previewPayments(Request $request, $limit = 10)
    {
        $organizationId = $this->getCurrentOrganizationId();
        $status = $request->get('status');
        $methodId = $request->get('method_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = DB::table('payments as pay')
            ->leftJoin('invoices as inv', 'pay.invoice_id', '=', 'inv.id')
            ->leftJoin('organizations as org', 'inv.organization_id', '=', 'org.id')
            ->leftJoin('payment_methods as pm', 'pay.method_id', '=', 'pm.id')
            ->leftJoin('users as payer', 'pay.payer_user_id', '=', 'payer.id')
            ->leftJoin('user_profiles as payer_profile', 'payer.id', '=', 'payer_profile.user_id')
            ->leftJoin('leads as lead', 'pay.lead_id', '=', 'lead.id')
            ->leftJoin('users as deleted_by_user', 'pay.deleted_by', '=', 'deleted_by_user.id')
            ->where('inv.organization_id', $organizationId)
            ->whereNull('pay.deleted_at')
            ->select([
                'pay.id',
                'inv.invoice_no as so_hoa_don',
                'pm.name as phuong_thuc_thanh_toan',
                'pay.amount as so_tien',
                'pay.paid_at as ngay_thanh_toan',
                'pay.status as trang_thai',
                'pay.txn_ref as ma_giao_dich',
                DB::raw("COALESCE(payer_profile.full_name, payer.email, lead.name, 'N/A') as nguoi_thanh_toan"),
                DB::raw("CASE WHEN payer.id IS NOT NULL THEN 'Người dùng' WHEN lead.id IS NOT NULL THEN 'Lead' ELSE 'N/A' END as loai_nguoi_thanh_toan"),
                'pay.note as ghi_chu',
                'pay.created_at as ngay_tao',
                DB::raw("CASE WHEN pay.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
            ]);

        if ($status !== null && $status !== '') {
            $query->where('pay.status', $status);
        }
        if ($methodId !== null && $methodId !== '') {
            $query->where('pay.method_id', $methodId);
        }
        if ($dateFrom) {
            $query->whereDate('pay.paid_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('pay.paid_at', '<=', $dateTo);
        }

        $total = $query->count();
        $data = $query->limit($limit)->get();
        $columns = ['ID', 'Số hóa đơn', 'Phương thức thanh toán', 'Số tiền', 'Ngày thanh toán', 'Trạng thái', 'Mã giao dịch', 'Người thanh toán', 'Loại người thanh toán', 'Ghi chú', 'Ngày tạo', 'Trạng thái xóa'];
        $columnKeys = ['id', 'so_hoa_don', 'phuong_thuc_thanh_toan', 'so_tien', 'ngay_thanh_toan', 'trang_thai', 'ma_giao_dich', 'nguoi_thanh_toan', 'loai_nguoi_thanh_toan', 'ghi_chu', 'ngay_tao', 'trang_thai_xoa'];
        
        return ['data' => $data, 'columns' => $columns, 'columnKeys' => $columnKeys, 'total' => $total];
    }
    
    private function previewLeases(Request $request, $limit = 10)
    {
        $organizationId = $this->getCurrentOrganizationId();
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = DB::table('leases as l')
            ->leftJoin('organizations as org', 'l.organization_id', '=', 'org.id')
            ->leftJoin('units as u', 'l.unit_id', '=', 'u.id')
            ->leftJoin('properties as p', 'u.property_id', '=', 'p.id')
            ->leftJoin('booking_deposits as bd', 'l.booking_id', '=', 'bd.id')
            ->leftJoin('leads as lead', 'bd.lead_id', '=', 'lead.id')
            ->leftJoin('users as tenant', 'l.tenant_id', '=', 'tenant.id')
            ->leftJoin('user_profiles as tenant_profile', 'tenant.id', '=', 'tenant_profile.user_id')
            ->leftJoin('users as agent', 'l.agent_id', '=', 'agent.id')
            ->leftJoin('user_profiles as agent_profile', 'agent.id', '=', 'agent_profile.user_id')
            ->leftJoin('payment_cycles as pc', 'l.payment_cycle_id', '=', 'pc.id')
            ->leftJoin('users as deleted_by_user', 'l.deleted_by', '=', 'deleted_by_user.id')
            ->where('l.organization_id', $organizationId)
            ->whereNull('l.deleted_at')
            ->select([
                'l.id',
                'l.contract_no as so_hop_dong',
                'p.name as ten_bat_dong_san',
                'u.code as ma_phong',
                'l.tenant_id',
                DB::raw("COALESCE(tenant_profile.full_name, tenant.email, 'N/A') as ten_khach_thue"),
                'bd.lead_id',
                DB::raw("COALESCE(lead.name, lead.email, lead.phone, 'N/A') as ten_lead"),
                DB::raw("COALESCE(agent_profile.full_name, agent.email, 'N/A') as ten_moi_gioi"),
                'l.start_date as ngay_bat_dau',
                'l.end_date as ngay_ket_thuc',
                'l.termination_date as ngay_cham_dut',
                'l.rent_amount as tien_thue',
                'l.deposit_amount as tien_coc',
                'l.status as trang_thai',
                'pc.name as chu_ky_thanh_toan',
                'l.signed_at as ngay_ky',
                'l.created_at as ngay_tao',
                'l.updated_at as ngay_cap_nhat',
                DB::raw("CASE WHEN l.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
            ]);

        if ($status !== null && $status !== '') {
            $query->where('l.status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('l.start_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('l.start_date', '<=', $dateTo);
        }

        $total = $query->count();
        $data = $query->limit($limit)->get();
        $columns = ['ID', 'Số hợp đồng', 'Tên bất động sản', 'Mã phòng', 'ID Khách thuê', 'Tên khách thuê', 'ID Lead', 'Tên Lead', 'Tên môi giới', 'Ngày bắt đầu', 'Ngày kết thúc', 'Ngày chấm dứt', 'Tiền thuê', 'Tiền cọc', 'Trạng thái', 'Chu kỳ thanh toán', 'Ngày ký', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa'];
        $columnKeys = ['id', 'so_hop_dong', 'ten_bat_dong_san', 'ma_phong', 'tenant_id', 'ten_khach_thue', 'lead_id', 'ten_lead', 'ten_moi_gioi', 'ngay_bat_dau', 'ngay_ket_thuc', 'ngay_cham_dut', 'tien_thue', 'tien_coc', 'trang_thai', 'chu_ky_thanh_toan', 'ngay_ky', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa'];
        
        return ['data' => $data, 'columns' => $columns, 'columnKeys' => $columnKeys, 'total' => $total];
    }
    
    private function previewPayroll(Request $request, $limit = 10)
    {
        $organizationId = $this->getCurrentOrganizationId();
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = DB::table('payroll_payslips as pp')
            ->leftJoin('payroll_cycles as pc', 'pp.payroll_cycle_id', '=', 'pc.id')
            ->leftJoin('organizations as org', 'pc.organization_id', '=', 'org.id')
            ->leftJoin('users as u', 'pp.user_id', '=', 'u.id')
            ->leftJoin('user_profiles as up', 'u.id', '=', 'up.user_id')
            ->leftJoin('users as deleted_by_user', 'pp.deleted_by', '=', 'deleted_by_user.id')
            ->where('pc.organization_id', $organizationId)
            ->whereNull('pp.deleted_at')
            ->select([
                'pp.id',
                'pc.period_month as thang_ky',
                DB::raw("COALESCE(up.full_name, u.email, 'N/A') as ten_nhan_vien"),
                'u.email as email',
                'pp.gross_amount as tong_luong',
                'pp.deduction_amount as tien_khau_tru',
                'pp.net_amount as luong_thuc_linh',
                'pp.status as trang_thai',
                'pp.paid_at as ngay_thanh_toan',
                'pp.payment_method as phuong_thuc_thanh_toan',
                'pp.note as ghi_chu',
                'pp.created_at as ngay_tao',
                'pp.updated_at as ngay_cap_nhat',
                DB::raw("CASE WHEN pp.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
            ]);

        if ($status !== null && $status !== '') {
            $query->where('pp.status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('pp.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('pp.created_at', '<=', $dateTo);
        }

        $total = $query->count();
        $data = $query->limit($limit)->get();
        $columns = ['ID', 'Tháng kỳ', 'Tên nhân viên', 'Email', 'Tổng lương', 'Tiền khấu trừ', 'Lương thực lĩnh', 'Trạng thái', 'Ngày thanh toán', 'Phương thức thanh toán', 'Ghi chú', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa'];
        $columnKeys = ['id', 'thang_ky', 'ten_nhan_vien', 'email', 'tong_luong', 'tien_khau_tru', 'luong_thuc_linh', 'trang_thai', 'ngay_thanh_toan', 'phuong_thuc_thanh_toan', 'ghi_chu', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa'];
        
        return ['data' => $data, 'columns' => $columns, 'columnKeys' => $columnKeys, 'total' => $total];
    }
    
    private function previewCompanyInvoices(Request $request, $limit = 10)
    {
        $organizationId = $this->getCurrentOrganizationId();
        $status = $request->get('status');
        $invoiceType = $request->get('invoice_type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = DB::table('company_invoices as ci')
            ->leftJoin('organizations as org', 'ci.organization_id', '=', 'org.id')
            ->leftJoin('vendors as v', 'ci.vendor_id', '=', 'v.id')
            ->leftJoin('users as u', 'ci.user_id', '=', 'u.id')
            ->leftJoin('user_profiles as up', 'u.id', '=', 'up.user_id')
            ->leftJoin('master_leases as ml', 'ci.master_lease_id', '=', 'ml.id')
            ->leftJoin('properties as p', 'ml.property_id', '=', 'p.id')
            ->leftJoin('tickets as t', 'ci.ticket_id', '=', 't.id')
            ->leftJoin('deposit_refunds as dr', 'ci.deposit_refund_id', '=', 'dr.id')
            ->leftJoin('payroll_payslips as pp', 'ci.payroll_payslip_id', '=', 'pp.id')
            ->leftJoin('users as created_by_user', 'ci.created_by', '=', 'created_by_user.id')
            ->leftJoin('users as deleted_by_user', 'ci.deleted_by', '=', 'deleted_by_user.id')
            ->where('ci.organization_id', $organizationId)
            ->whereNull('ci.deleted_at')
            ->select([
                'ci.id',
                'ci.invoice_no as so_hoa_don',
                'ci.invoice_type as loai_hoa_don',
                'v.name as ten_nha_cung_cap',
                DB::raw("COALESCE(up.full_name, u.email, 'N/A') as ten_nguoi_nhan"),
                'ml.contract_no as so_hop_dong_tong',
                'p.name as ten_bat_dong_san',
                't.title as tieu_de_ticket',
                'ci.issue_date as ngay_phat_hanh',
                'ci.due_date as ngay_den_han',
                'ci.status as trang_thai',
                'ci.subtotal as tong_phu',
                'ci.tax_amount as tien_thue',
                'ci.discount_amount as tien_giam_gia',
                'ci.total_amount as tong_tien',
                'ci.currency as tien_te',
                'ci.description as mo_ta',
                'ci.note as ghi_chu',
                'ci.created_at as ngay_tao',
                'created_by_user.email as nguoi_tao',
                DB::raw("CASE WHEN ci.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
            ]);

        if ($status !== null && $status !== '') {
            $query->where('ci.status', $status);
        }
        if ($invoiceType !== null && $invoiceType !== '') {
            $query->where('ci.invoice_type', $invoiceType);
        }
        if ($dateFrom) {
            $query->whereDate('ci.issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('ci.issue_date', '<=', $dateTo);
        }

        $total = $query->count();
        $data = $query->limit($limit)->get();
        $columns = ['ID', 'Số hóa đơn', 'Loại hóa đơn', 'Tên nhà cung cấp', 'Tên người nhận', 'Số hợp đồng tổng', 'Tên bất động sản', 'Tiêu đề ticket', 'Ngày phát hành', 'Ngày đến hạn', 'Trạng thái', 'Tổng phụ', 'Tiền thuế', 'Tiền giảm giá', 'Tổng tiền', 'Tiền tệ', 'Mô tả', 'Ghi chú', 'Ngày tạo', 'Người tạo', 'Trạng thái xóa'];
        $columnKeys = ['id', 'so_hoa_don', 'loai_hoa_don', 'ten_nha_cung_cap', 'ten_nguoi_nhan', 'so_hop_dong_tong', 'ten_bat_dong_san', 'tieu_de_ticket', 'ngay_phat_hanh', 'ngay_den_han', 'trang_thai', 'tong_phu', 'tien_thue', 'tien_giam_gia', 'tong_tien', 'tien_te', 'mo_ta', 'ghi_chu', 'ngay_tao', 'nguoi_tao', 'trang_thai_xoa'];
        
        return ['data' => $data, 'columns' => $columns, 'columnKeys' => $columnKeys, 'total' => $total];
    }
    
    private function previewCashOutflows(Request $request, $limit = 10)
    {
        $organizationId = $this->getCurrentOrganizationId();
        $status = $request->get('status');
        $methodId = $request->get('method_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = DB::table('cash_outflows as co')
            ->leftJoin('company_invoices as ci', 'co.company_invoice_id', '=', 'ci.id')
            ->leftJoin('organizations as org', 'ci.organization_id', '=', 'org.id')
            ->leftJoin('payment_methods as pm', 'co.payment_method_id', '=', 'pm.id')
            ->leftJoin('users as deleted_by_user', 'co.deleted_by', '=', 'deleted_by_user.id')
            ->where('ci.organization_id', $organizationId)
            ->whereNull('co.deleted_at')
            ->select([
                'co.id',
                'ci.invoice_no as so_hoa_don',
                'pm.name as phuong_thuc_thanh_toan',
                'co.amount as so_tien',
                'co.paid_at as ngay_thanh_toan',
                'co.status as trang_thai',
                'co.transaction_ref as ma_giao_dich',
                'co.note as ghi_chu',
                'co.created_at as ngay_tao',
                'co.updated_at as ngay_cap_nhat',
                DB::raw("CASE WHEN co.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
            ]);

        if ($status !== null && $status !== '') {
            $query->where('co.status', $status);
        }
        if ($methodId !== null && $methodId !== '') {
            $query->where('co.payment_method_id', $methodId);
        }
        if ($dateFrom) {
            $query->whereDate('co.paid_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('co.paid_at', '<=', $dateTo);
        }

        $total = $query->count();
        $data = $query->limit($limit)->get();
        $columns = ['ID', 'Số hóa đơn', 'Phương thức thanh toán', 'Số tiền', 'Ngày thanh toán', 'Trạng thái', 'Mã giao dịch', 'Ghi chú', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa'];
        $columnKeys = ['id', 'so_hoa_don', 'phuong_thuc_thanh_toan', 'so_tien', 'ngay_thanh_toan', 'trang_thai', 'ma_giao_dich', 'ghi_chu', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa'];
        
        return ['data' => $data, 'columns' => $columns, 'columnKeys' => $columnKeys, 'total' => $total];
    }
    
    /**
     * Lấy danh sách bảng có thể xuất
     */
    public function getTables()
    {
        try {
            /** @var User|null $user */
            $user = Auth::user();
            $organizationId = $user->organizations()->first()?->id;
            
            $tables = $this->getAvailableTables($organizationId);
            
            return response()->json([
                'success' => true,
                'data' => $tables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách bảng: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy danh sách cột của bảng
     */
    public function getTableColumns(Request $request)
    {
        try {
            $tableName = $request->get('table');
            
            if (!$tableName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tên bảng không được để trống'
                ]);
            }
            
            // Check if table exists
            if (!DB::getSchemaBuilder()->hasTable($tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bảng không tồn tại'
                ]);
            }
            
            $columns = DB::select("DESCRIBE {$tableName}");
            $columnList = [];
            
            foreach ($columns as $column) {
                $columnList[] = [
                    'name' => $column->Field,
                    'type' => $column->Type,
                    'display_name' => $this->getColumnDisplayName($column->Field)
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $columnList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách cột: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Xuất danh sách bất động sản
     */
    public function exportProperties(Request $request)
    {
        try {
            // Check capability - only manager can export data
            $this->requireCapability('finance.report.export', 'Bạn không có quyền xuất dữ liệu.');
            
            // Check subscription permission
            $this->checkExportPermission();
            
            $organizationId = $this->getCurrentOrganizationId();
            if (!$organizationId) {
                abort(403, 'Bạn không thuộc tổ chức nào.');
            }

            $status = $request->get('status');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = DB::table('properties as p')
                ->leftJoin('organizations as org', 'p.organization_id', '=', 'org.id')
                ->leftJoin('property_types as pt', 'p.property_type_id', '=', 'pt.id')
                ->leftJoin('locations as loc', 'p.location_id', '=', 'loc.id')
                ->leftJoin('geo_provinces as prov', 'loc.province_code', '=', 'prov.code')
                ->leftJoin('geo_districts as dist', 'loc.district_code', '=', 'dist.code')
                ->leftJoin('geo_wards as ward', 'loc.ward_code', '=', 'ward.code')
                ->leftJoin('locations_2025 as loc2025', 'p.location_id_2025', '=', 'loc2025.id')
                ->leftJoin('geo_provinces_2025 as prov2025', 'loc2025.province_code', '=', 'prov2025.code')
                ->leftJoin('geo_wards_2025 as ward2025', 'loc2025.ward_code', '=', 'ward2025.code')
                ->leftJoin('users as deleted_by_user', 'p.deleted_by', '=', 'deleted_by_user.id')
                ->where('p.organization_id', $organizationId)
                ->whereNull('p.deleted_at')
                ->select([
                    'p.id',
                    'p.name as ten_bat_dong_san',
                    'org.name as ten_to_chuc',
                    'pt.name as loai_bat_dong_san',
                    DB::raw("CONCAT(COALESCE(loc.street, ''), ', ', COALESCE(ward.name, ''), ', ', COALESCE(dist.name, ''), ', ', COALESCE(prov.name, '')) as dia_chi_cu"),
                    DB::raw("CONCAT(COALESCE(loc2025.street, ''), ', ', COALESCE(ward2025.name, ''), ', ', COALESCE(prov2025.name, '')) as dia_chi_moi"),
                    'p.total_floors as tong_so_tang',
                    'p.status as trang_thai',
                    'p.created_at as ngay_tao',
                    'p.updated_at as ngay_cap_nhat',
                    DB::raw("CASE WHEN p.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa"),
                    'deleted_by_user.email as nguoi_xoa'
                ]);

            if ($status !== null && $status !== '') {
                $query->where('p.status', $status);
            }

            if ($dateFrom) {
                $query->whereDate('p.created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('p.created_at', '<=', $dateTo);
            }

            $data = $query->get();
            $columns = ['ID', 'Tên bất động sản', 'Tổ chức', 'Loại bất động sản', 'Địa chỉ (cũ)', 'Địa chỉ (mới 2025)', 'Tổng số tầng', 'Trạng thái', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa', 'Người xóa'];
            $columnKeys = ['id', 'ten_bat_dong_san', 'ten_to_chuc', 'loai_bat_dong_san', 'dia_chi_cu', 'dia_chi_moi', 'tong_so_tang', 'trang_thai', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa', 'nguoi_xoa'];

            return $this->exportToCSV($data, $columns, $columnKeys, 'danh-sach-bat-dong-san');
        } catch (\Exception $e) {
            Log::error('Export Properties Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Xuất danh sách phòng
     */
    public function exportUnits(Request $request)
    {
        try {
            // Check capability - only manager can export data
            $this->requireCapability('finance.report.export', 'Bạn không có quyền xuất dữ liệu.');
            
            // Check subscription permission
            $this->checkExportPermission();
            
            $organizationId = $this->getCurrentOrganizationId();
            if (!$organizationId) {
                abort(403, 'Bạn không thuộc tổ chức nào.');
            }

            $status = $request->get('status');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = DB::table('units as u')
                ->leftJoin('properties as p', 'u.property_id', '=', 'p.id')
                ->leftJoin('organizations as org', 'p.organization_id', '=', 'org.id')
                ->leftJoin('users as deleted_by_user', 'u.deleted_by', '=', 'deleted_by_user.id')
                ->where('p.organization_id', $organizationId)
                ->whereNull('u.deleted_at')
                ->select([
                    'u.id',
                    'u.code as ma_phong',
                    'p.name as ten_bat_dong_san',
                    'u.floor as tang',
                    'u.area_m2 as dien_tich',
                    'u.unit_type as loai_phong',
                    'u.base_rent as gia_thue',
                    'u.deposit_amount as tien_coc',
                    'u.max_occupancy as so_nguoi_toi_da',
                    'u.status as trang_thai',
                    'u.created_at as ngay_tao',
                    'u.updated_at as ngay_cap_nhat',
                    DB::raw("CASE WHEN u.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa"),
                    'deleted_by_user.email as nguoi_xoa'
                ]);

            if ($status !== null && $status !== '') {
                $query->where('u.status', $status);
            }

            if ($dateFrom) {
                $query->whereDate('u.created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('u.created_at', '<=', $dateTo);
            }

            $data = $query->get();
            $columns = ['ID', 'Mã phòng', 'Tên bất động sản', 'Tầng', 'Diện tích (m²)', 'Loại phòng', 'Giá thuê', 'Tiền cọc', 'Số người tối đa', 'Trạng thái', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa', 'Người xóa'];
            $columnKeys = ['id', 'ma_phong', 'ten_bat_dong_san', 'tang', 'dien_tich', 'loai_phong', 'gia_thue', 'tien_coc', 'so_nguoi_toi_da', 'trang_thai', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa', 'nguoi_xoa'];

            return $this->exportToCSV($data, $columns, $columnKeys, 'danh-sach-phong');
                } catch (\Exception $e) {
            Log::error('Export Units Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Xuất danh sách hóa đơn
     */
    public function exportInvoices(Request $request)
    {
        try {
            // Check capability - only manager can export data
            $this->requireCapability('finance.report.export', 'Bạn không có quyền xuất dữ liệu.');
            
            // Check subscription permission
            $this->checkExportPermission();
            
            $organizationId = $this->getCurrentOrganizationId();
            if (!$organizationId) {
                abort(403, 'Bạn không thuộc tổ chức nào.');
            }

            $status = $request->get('status');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = DB::table('invoices as inv')
                ->leftJoin('organizations as org', 'inv.organization_id', '=', 'org.id')
                ->leftJoin('leases as l', 'inv.lease_id', '=', 'l.id')
                ->leftJoin('units as u_lease', 'l.unit_id', '=', 'u_lease.id')
                ->leftJoin('properties as p_lease', 'u_lease.property_id', '=', 'p_lease.id')
                ->leftJoin('booking_deposits as bd', 'inv.booking_deposit_id', '=', 'bd.id')
                ->leftJoin('units as u_bd', 'bd.unit_id', '=', 'u_bd.id')
                ->leftJoin('properties as p_bd', 'u_bd.property_id', '=', 'p_bd.id')
                ->leftJoin('users as tenant', 'l.tenant_id', '=', 'tenant.id')
                ->leftJoin('user_profiles as tenant_profile', 'tenant.id', '=', 'tenant_profile.user_id')
                ->leftJoin('users as created_by_user', 'inv.created_by', '=', 'created_by_user.id')
                ->leftJoin('users as deleted_by_user', 'inv.deleted_by', '=', 'deleted_by_user.id')
                ->where('inv.organization_id', $organizationId)
                ->whereNull('inv.deleted_at')
                ->select([
                    'inv.id',
                    'inv.invoice_no as so_hoa_don',
                    'inv.invoice_type as loai_hoa_don',
                    'bd.reference_number as ma_dat_coc',
                    'l.contract_no as so_hop_dong',
                    DB::raw("COALESCE(p_lease.name, p_bd.name, 'N/A') as ten_bat_dong_san"),
                    DB::raw("COALESCE(u_lease.code, u_bd.code, 'N/A') as ma_phong"),
                    DB::raw("COALESCE(tenant_profile.full_name, tenant.email, 'N/A') as ten_khach_thue"),
                    'inv.issue_date as ngay_phat_hanh',
                    'inv.due_date as ngay_den_han',
                    'inv.status as trang_thai',
                    'inv.subtotal as tong_phu',
                    'inv.tax_amount as tien_thue',
                    'inv.discount_amount as tien_giam_gia',
                    'inv.total_amount as tong_tien',
                    'inv.currency as tien_te',
                    'inv.created_at as ngay_tao',
                    'created_by_user.email as nguoi_tao',
                    DB::raw("CASE WHEN inv.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
                ]);

            if ($status !== null && $status !== '') {
                $query->where('inv.status', $status);
            }

            if ($dateFrom) {
                $query->whereDate('inv.issue_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('inv.issue_date', '<=', $dateTo);
            }

            $data = $query->get();
            $columns = ['ID', 'Số hóa đơn', 'Loại hóa đơn', 'Mã đặt cọc', 'Số hợp đồng', 'Tên bất động sản', 'Mã phòng', 'Tên khách thuê', 'Ngày phát hành', 'Ngày đến hạn', 'Trạng thái', 'Tổng phụ', 'Tiền thuế', 'Tiền giảm giá', 'Tổng tiền', 'Tiền tệ', 'Ngày tạo', 'Người tạo', 'Trạng thái xóa'];
            $columnKeys = ['id', 'so_hoa_don', 'loai_hoa_don', 'ma_dat_coc', 'so_hop_dong', 'ten_bat_dong_san', 'ma_phong', 'ten_khach_thue', 'ngay_phat_hanh', 'ngay_den_han', 'trang_thai', 'tong_phu', 'tien_thue', 'tien_giam_gia', 'tong_tien', 'tien_te', 'ngay_tao', 'nguoi_tao', 'trang_thai_xoa'];

            return $this->exportToCSV($data, $columns, $columnKeys, 'danh-sach-hoa-don');
        } catch (\Exception $e) {
            Log::error('Export Invoices Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Xuất danh sách thanh toán
     */
    public function exportPayments(Request $request)
    {
        try {
            // Check capability - only manager can export data
            $this->requireCapability('finance.report.export', 'Bạn không có quyền xuất dữ liệu.');
            
            // Check subscription permission
            $this->checkExportPermission();
            
            $organizationId = $this->getCurrentOrganizationId();
            if (!$organizationId) {
                abort(403, 'Bạn không thuộc tổ chức nào.');
            }

            $status = $request->get('status');
            $methodId = $request->get('method_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = DB::table('payments as pay')
                ->leftJoin('invoices as inv', 'pay.invoice_id', '=', 'inv.id')
                ->leftJoin('organizations as org', 'inv.organization_id', '=', 'org.id')
                ->leftJoin('payment_methods as pm', 'pay.method_id', '=', 'pm.id')
                ->leftJoin('users as payer', 'pay.payer_user_id', '=', 'payer.id')
                ->leftJoin('user_profiles as payer_profile', 'payer.id', '=', 'payer_profile.user_id')
                ->leftJoin('leads as lead', 'pay.lead_id', '=', 'lead.id')
                ->leftJoin('users as deleted_by_user', 'pay.deleted_by', '=', 'deleted_by_user.id')
                ->where('inv.organization_id', $organizationId)
                ->whereNull('pay.deleted_at')
                ->select([
                    'pay.id',
                    'inv.invoice_no as so_hoa_don',
                    'pm.name as phuong_thuc_thanh_toan',
                    'pay.amount as so_tien',
                    'pay.paid_at as ngay_thanh_toan',
                    'pay.status as trang_thai',
                    'pay.txn_ref as ma_giao_dich',
                    DB::raw("COALESCE(payer_profile.full_name, payer.email, lead.name, 'N/A') as nguoi_thanh_toan"),
                    DB::raw("CASE WHEN payer.id IS NOT NULL THEN 'Người dùng' WHEN lead.id IS NOT NULL THEN 'Lead' ELSE 'N/A' END as loai_nguoi_thanh_toan"),
                    'pay.note as ghi_chu',
                    'pay.created_at as ngay_tao',
                    DB::raw("CASE WHEN pay.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
                ]);

            if ($status !== null && $status !== '') {
                $query->where('pay.status', $status);
            }

            if ($methodId !== null && $methodId !== '') {
                $query->where('pay.method_id', $methodId);
            }

            if ($dateFrom) {
                $query->whereDate('pay.paid_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('pay.paid_at', '<=', $dateTo);
            }

            $data = $query->get();
            $columns = ['ID', 'Số hóa đơn', 'Phương thức thanh toán', 'Số tiền', 'Ngày thanh toán', 'Trạng thái', 'Mã giao dịch', 'Người thanh toán', 'Loại người thanh toán', 'Ghi chú', 'Ngày tạo', 'Trạng thái xóa'];
            $columnKeys = ['id', 'so_hoa_don', 'phuong_thuc_thanh_toan', 'so_tien', 'ngay_thanh_toan', 'trang_thai', 'ma_giao_dich', 'nguoi_thanh_toan', 'loai_nguoi_thanh_toan', 'ghi_chu', 'ngay_tao', 'trang_thai_xoa'];

            return $this->exportToCSV($data, $columns, $columnKeys, 'danh-sach-thanh-toan');
        } catch (\Exception $e) {
            Log::error('Export Payments Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Xuất danh sách hợp đồng
     */
    public function exportLeases(Request $request)
    {
        try {
            // Check capability - only manager can export data
            $this->requireCapability('finance.report.export', 'Bạn không có quyền xuất dữ liệu.');
            
            // Check subscription permission
            $this->checkExportPermission();
            
            $organizationId = $this->getCurrentOrganizationId();
            if (!$organizationId) {
                abort(403, 'Bạn không thuộc tổ chức nào.');
            }

            $status = $request->get('status');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = DB::table('leases as l')
                ->leftJoin('organizations as org', 'l.organization_id', '=', 'org.id')
                ->leftJoin('units as u', 'l.unit_id', '=', 'u.id')
                ->leftJoin('properties as p', 'u.property_id', '=', 'p.id')
                ->leftJoin('booking_deposits as bd', 'l.booking_id', '=', 'bd.id')
                ->leftJoin('leads as lead', 'bd.lead_id', '=', 'lead.id')
                ->leftJoin('users as tenant', 'l.tenant_id', '=', 'tenant.id')
                ->leftJoin('user_profiles as tenant_profile', 'tenant.id', '=', 'tenant_profile.user_id')
                ->leftJoin('users as agent', 'l.agent_id', '=', 'agent.id')
                ->leftJoin('user_profiles as agent_profile', 'agent.id', '=', 'agent_profile.user_id')
                ->leftJoin('payment_cycles as pc', 'l.payment_cycle_id', '=', 'pc.id')
                ->leftJoin('users as deleted_by_user', 'l.deleted_by', '=', 'deleted_by_user.id')
                ->where('l.organization_id', $organizationId)
                ->whereNull('l.deleted_at')
                ->select([
                    'l.id',
                    'l.contract_no as so_hop_dong',
                    'p.name as ten_bat_dong_san',
                    'u.code as ma_phong',
                    'l.tenant_id',
                    DB::raw("COALESCE(tenant_profile.full_name, tenant.email, 'N/A') as ten_khach_thue"),
                    'bd.lead_id',
                    DB::raw("COALESCE(lead.name, lead.email, lead.phone, 'N/A') as ten_lead"),
                    DB::raw("COALESCE(agent_profile.full_name, agent.email, 'N/A') as ten_moi_gioi"),
                    'l.start_date as ngay_bat_dau',
                    'l.end_date as ngay_ket_thuc',
                    'l.termination_date as ngay_cham_dut',
                    'l.rent_amount as tien_thue',
                    'l.deposit_amount as tien_coc',
                    'l.status as trang_thai',
                    'pc.name as chu_ky_thanh_toan',
                    'l.signed_at as ngay_ky',
                    'l.created_at as ngay_tao',
                    'l.updated_at as ngay_cap_nhat',
                    DB::raw("CASE WHEN l.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
                ]);

            if ($status !== null && $status !== '') {
                $query->where('l.status', $status);
            }

            if ($dateFrom) {
                $query->whereDate('l.start_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('l.start_date', '<=', $dateTo);
            }

            $data = $query->get();
            $columns = ['ID', 'Số hợp đồng', 'Tên bất động sản', 'Mã phòng', 'ID Khách thuê', 'Tên khách thuê', 'ID Lead', 'Tên Lead', 'Tên môi giới', 'Ngày bắt đầu', 'Ngày kết thúc', 'Ngày chấm dứt', 'Tiền thuê', 'Tiền cọc', 'Trạng thái', 'Chu kỳ thanh toán', 'Ngày ký', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa'];
            $columnKeys = ['id', 'so_hop_dong', 'ten_bat_dong_san', 'ma_phong', 'tenant_id', 'ten_khach_thue', 'lead_id', 'ten_lead', 'ten_moi_gioi', 'ngay_bat_dau', 'ngay_ket_thuc', 'ngay_cham_dut', 'tien_thue', 'tien_coc', 'trang_thai', 'chu_ky_thanh_toan', 'ngay_ky', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa'];

            return $this->exportToCSV($data, $columns, $columnKeys, 'danh-sach-hop-dong');
        } catch (\Exception $e) {
            Log::error('Export Leases Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Xuất danh sách phiếu lương
     */
    public function exportPayroll(Request $request)
    {
        try {
            // Check capability - only manager can export data
            $this->requireCapability('finance.report.export', 'Bạn không có quyền xuất dữ liệu.');
            
            // Check subscription permission
            $this->checkExportPermission();
            
            $organizationId = $this->getCurrentOrganizationId();
            if (!$organizationId) {
                abort(403, 'Bạn không thuộc tổ chức nào.');
            }

            $status = $request->get('status');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = DB::table('payroll_payslips as pp')
                ->leftJoin('payroll_cycles as pc', 'pp.payroll_cycle_id', '=', 'pc.id')
                ->leftJoin('organizations as org', 'pc.organization_id', '=', 'org.id')
                ->leftJoin('users as u', 'pp.user_id', '=', 'u.id')
                ->leftJoin('user_profiles as up', 'u.id', '=', 'up.user_id')
                ->leftJoin('users as deleted_by_user', 'pp.deleted_by', '=', 'deleted_by_user.id')
                ->where('pc.organization_id', $organizationId)
                ->whereNull('pp.deleted_at')
                ->select([
                    'pp.id',
                    'pc.period_month as thang_ky',
                    DB::raw("COALESCE(up.full_name, u.email, 'N/A') as ten_nhan_vien"),
                    'u.email as email',
                    'pp.gross_amount as tong_luong',
                    'pp.deduction_amount as tien_khau_tru',
                    'pp.net_amount as luong_thuc_linh',
                    'pp.status as trang_thai',
                    'pp.paid_at as ngay_thanh_toan',
                    'pp.payment_method as phuong_thuc_thanh_toan',
                    'pp.note as ghi_chu',
                    'pp.created_at as ngay_tao',
                    'pp.updated_at as ngay_cap_nhat',
                    DB::raw("CASE WHEN pp.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
                ]);

            if ($status !== null && $status !== '') {
                $query->where('pp.status', $status);
            }

            if ($dateFrom) {
                $query->whereDate('pp.created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('pp.created_at', '<=', $dateTo);
            }

            $data = $query->get();
            $columns = ['ID', 'Tháng kỳ', 'Tên nhân viên', 'Email', 'Tổng lương', 'Tiền khấu trừ', 'Lương thực lĩnh', 'Trạng thái', 'Ngày thanh toán', 'Phương thức thanh toán', 'Ghi chú', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa'];
            $columnKeys = ['id', 'thang_ky', 'ten_nhan_vien', 'email', 'tong_luong', 'tien_khau_tru', 'luong_thuc_linh', 'trang_thai', 'ngay_thanh_toan', 'phuong_thuc_thanh_toan', 'ghi_chu', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa'];

            return $this->exportToCSV($data, $columns, $columnKeys, 'danh-sach-phieu-luong');
        } catch (\Exception $e) {
            Log::error('Export Payroll Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Xuất danh sách hóa đơn công ty
     */
    public function exportCompanyInvoices(Request $request)
    {
        try {
            // Check capability - only manager can export data
            $this->requireCapability('finance.report.export', 'Bạn không có quyền xuất dữ liệu.');
            
            // Check subscription permission
            $this->checkExportPermission();
            
            $organizationId = $this->getCurrentOrganizationId();
            if (!$organizationId) {
                abort(403, 'Bạn không thuộc tổ chức nào.');
            }

            $status = $request->get('status');
            $invoiceType = $request->get('invoice_type');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = DB::table('company_invoices as ci')
                ->leftJoin('organizations as org', 'ci.organization_id', '=', 'org.id')
                ->leftJoin('vendors as v', 'ci.vendor_id', '=', 'v.id')
                ->leftJoin('users as u', 'ci.user_id', '=', 'u.id')
                ->leftJoin('user_profiles as up', 'u.id', '=', 'up.user_id')
                ->leftJoin('master_leases as ml', 'ci.master_lease_id', '=', 'ml.id')
                ->leftJoin('properties as p', 'ml.property_id', '=', 'p.id')
                ->leftJoin('tickets as t', 'ci.ticket_id', '=', 't.id')
                ->leftJoin('deposit_refunds as dr', 'ci.deposit_refund_id', '=', 'dr.id')
                ->leftJoin('payroll_payslips as pp', 'ci.payroll_payslip_id', '=', 'pp.id')
                ->leftJoin('users as created_by_user', 'ci.created_by', '=', 'created_by_user.id')
                ->leftJoin('users as deleted_by_user', 'ci.deleted_by', '=', 'deleted_by_user.id')
                ->where('ci.organization_id', $organizationId)
                ->whereNull('ci.deleted_at')
                ->select([
                    'ci.id',
                    'ci.invoice_no as so_hoa_don',
                    'ci.invoice_type as loai_hoa_don',
                    'v.name as ten_nha_cung_cap',
                    DB::raw("COALESCE(up.full_name, u.email, 'N/A') as ten_nguoi_nhan"),
                    'ml.contract_no as so_hop_dong_tong',
                    'p.name as ten_bat_dong_san',
                    't.title as tieu_de_ticket',
                    'ci.issue_date as ngay_phat_hanh',
                    'ci.due_date as ngay_den_han',
                    'ci.status as trang_thai',
                    'ci.subtotal as tong_phu',
                    'ci.tax_amount as tien_thue',
                    'ci.discount_amount as tien_giam_gia',
                    'ci.total_amount as tong_tien',
                    'ci.currency as tien_te',
                    'ci.description as mo_ta',
                    'ci.note as ghi_chu',
                    'ci.created_at as ngay_tao',
                    'created_by_user.email as nguoi_tao',
                    DB::raw("CASE WHEN ci.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
                ]);

            if ($status !== null && $status !== '') {
                $query->where('ci.status', $status);
            }

            if ($invoiceType !== null && $invoiceType !== '') {
                $query->where('ci.invoice_type', $invoiceType);
            }

            if ($dateFrom) {
                $query->whereDate('ci.issue_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('ci.issue_date', '<=', $dateTo);
            }

            $data = $query->get();
            $columns = ['ID', 'Số hóa đơn', 'Loại hóa đơn', 'Tên nhà cung cấp', 'Tên người nhận', 'Số hợp đồng tổng', 'Tên bất động sản', 'Tiêu đề ticket', 'Ngày phát hành', 'Ngày đến hạn', 'Trạng thái', 'Tổng phụ', 'Tiền thuế', 'Tiền giảm giá', 'Tổng tiền', 'Tiền tệ', 'Mô tả', 'Ghi chú', 'Ngày tạo', 'Người tạo', 'Trạng thái xóa'];
            $columnKeys = ['id', 'so_hoa_don', 'loai_hoa_don', 'ten_nha_cung_cap', 'ten_nguoi_nhan', 'so_hop_dong_tong', 'ten_bat_dong_san', 'tieu_de_ticket', 'ngay_phat_hanh', 'ngay_den_han', 'trang_thai', 'tong_phu', 'tien_thue', 'tien_giam_gia', 'tong_tien', 'tien_te', 'mo_ta', 'ghi_chu', 'ngay_tao', 'nguoi_tao', 'trang_thai_xoa'];

            return $this->exportToCSV($data, $columns, $columnKeys, 'danh-sach-hoa-don-cong-ty');
        } catch (\Exception $e) {
            Log::error('Export Company Invoices Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Xuất danh sách dòng tiền chi
     */
    public function exportCashOutflows(Request $request)
    {
        try {
            // Check capability - only manager can export data
            $this->requireCapability('finance.report.export', 'Bạn không có quyền xuất dữ liệu.');
            
            // Check subscription permission
            $this->checkExportPermission();
            
            $organizationId = $this->getCurrentOrganizationId();
            if (!$organizationId) {
                abort(403, 'Bạn không thuộc tổ chức nào.');
            }

            $status = $request->get('status');
            $methodId = $request->get('method_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = DB::table('cash_outflows as co')
                ->leftJoin('company_invoices as ci', 'co.company_invoice_id', '=', 'ci.id')
                ->leftJoin('organizations as org', 'ci.organization_id', '=', 'org.id')
                ->leftJoin('payment_methods as pm', 'co.payment_method_id', '=', 'pm.id')
                ->leftJoin('users as deleted_by_user', 'co.deleted_by', '=', 'deleted_by_user.id')
                ->where('ci.organization_id', $organizationId)
                ->whereNull('co.deleted_at')
                ->select([
                    'co.id',
                    'ci.invoice_no as so_hoa_don',
                    'pm.name as phuong_thuc_thanh_toan',
                    'co.amount as so_tien',
                    'co.paid_at as ngay_thanh_toan',
                    'co.status as trang_thai',
                    'co.transaction_ref as ma_giao_dich',
                    'co.note as ghi_chu',
                    'co.created_at as ngay_tao',
                    'co.updated_at as ngay_cap_nhat',
                    DB::raw("CASE WHEN co.deleted_at IS NULL THEN 'Hoạt động' ELSE 'Đã xóa' END as trang_thai_xoa")
                ]);

            if ($status !== null && $status !== '') {
                $query->where('co.status', $status);
            }

            if ($methodId !== null && $methodId !== '') {
                $query->where('co.payment_method_id', $methodId);
            }

            if ($dateFrom) {
                $query->whereDate('co.paid_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('co.paid_at', '<=', $dateTo);
            }

            $data = $query->get();
            $columns = ['ID', 'Số hóa đơn', 'Phương thức thanh toán', 'Số tiền', 'Ngày thanh toán', 'Trạng thái', 'Mã giao dịch', 'Ghi chú', 'Ngày tạo', 'Ngày cập nhật', 'Trạng thái xóa'];
            $columnKeys = ['id', 'so_hoa_don', 'phuong_thuc_thanh_toan', 'so_tien', 'ngay_thanh_toan', 'trang_thai', 'ma_giao_dich', 'ghi_chu', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai_xoa'];

            return $this->exportToCSV($data, $columns, $columnKeys, 'danh-sach-dong-tien-chi');
        } catch (\Exception $e) {
            Log::error('Export Cash Outflows Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper method để xuất CSV
     */
    private function exportToCSV($data, $columns, $columnKeys, $filename)
    {
            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có dữ liệu để xuất'
                ]);
            }
            
        // Kiểm tra và loại bỏ các cột có tất cả giá trị null
        $validColumns = [];
        $validColumnKeys = [];
        $columnHasData = [];
        
        // Khởi tạo mảng kiểm tra cho mỗi cột
        foreach ($columnKeys as $index => $key) {
            $columnHasData[$index] = false;
        }
        
        // Kiểm tra từng dòng dữ liệu
        foreach ($data as $row) {
            $rowArray = is_array($row) ? $row : (array) $row;
            
            foreach ($columnKeys as $index => $key) {
                $value = $rowArray[$key] ?? null;
                
                // Kiểm tra nếu giá trị không null và không rỗng
                if ($value !== null && $value !== '' && trim((string)$value) !== '') {
                    $columnHasData[$index] = true;
                }
            }
        }
        
        // Chỉ giữ lại các cột có dữ liệu
        foreach ($columnKeys as $index => $key) {
            if ($columnHasData[$index]) {
                $validColumns[] = $columns[$index];
                $validColumnKeys[] = $key;
            }
        }
        
        // Nếu không còn cột nào, trả về lỗi
        if (empty($validColumns)) {
            return response()->json([
                'success' => false,
                'message' => 'Không có dữ liệu hợp lệ để xuất'
            ]);
        }

        $output = '';
        
        // Tạo header với các cột hợp lệ
        $output .= '"' . implode('","', $validColumns) . '"' . "\r\n";
        
        // Tạo dữ liệu - chỉ lấy các cột hợp lệ
        foreach ($data as $row) {
            $rowArray = is_array($row) ? $row : (array) $row;
            $csvRow = [];
            
            // Lấy giá trị theo thứ tự validColumnKeys
            foreach ($validColumnKeys as $key) {
                $value = $rowArray[$key] ?? '';
                if ($value === null) {
                    $value = '';
                }
                $value = (string) $value;
                $escapedValue = str_replace('"', '""', $value);
                $csvRow[] = '"' . $escapedValue . '"';
            }
            $output .= implode(',', $csvRow) . "\r\n";
        }
            
            // Add BOM for Excel UTF-8 compatibility
            $bom = "\xEF\xBB\xBF";
        $csvDataWithBom = $bom . $output;
            
        $filename = $filename . '-' . date('Y-m-d-H-i-s');
        
            return response($csvDataWithBom, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Content-Encoding' => 'UTF-8'
            ]);
    }
    
    /**
     * Lấy danh sách bảng có thể xuất
     */
    private function getAvailableTables($organizationId)
    {
        $tableNames = [
            'properties' => 'Bất động sản',
            'units' => 'Căn hộ/Phòng',
            'leases' => 'Hợp đồng thuê',
            'invoices' => 'Hóa đơn',
            'payments' => 'Thanh toán',
            'users' => 'Người dùng',
            'commission_events' => 'Sự kiện hoa hồng',
            'tickets' => 'Yêu cầu hỗ trợ',
            'reviews' => 'Đánh giá',
            'viewings' => 'Xem nhà',
            'leads' => 'Khách hàng tiềm năng',
            'booking_deposits' => 'Đặt cọc',
            'deposit_refunds' => 'Hoàn cọc',
            'notifications' => 'Thông báo'
        ];
        
        $tables = [];
        
        // Lấy danh sách bảng từ database
        try {
            $dbTables = DB::select('SHOW TABLES');
            $existingTables = [];
            
            foreach ($dbTables as $table) {
                $tableKey = 'Tables_in_' . DB::getDatabaseName();
                $existingTables[] = $table->$tableKey;
            }
            
            foreach ($tableNames as $tableName => $displayName) {
                if (in_array($tableName, $existingTables)) {
                    $tables[] = [
                        'name' => $tableName,
                        'display_name' => $displayName
                    ];
                }
            }
        } catch (\Exception $e) {
            // Nếu lỗi, trả về danh sách mặc định
            $tables = [
                ['name' => 'properties', 'display_name' => 'Bất động sản'],
                ['name' => 'units', 'display_name' => 'Căn hộ/Phòng'],
                ['name' => 'leases', 'display_name' => 'Hợp đồng thuê'],
                ['name' => 'invoices', 'display_name' => 'Hóa đơn'],
                ['name' => 'payments', 'display_name' => 'Thanh toán']
            ];
        }
        
        return $tables;
    }
    
    /**
     * Lấy dữ liệu bảng với phân quyền
     */
    private function getTableData($tableName, $columns, $limit = null)
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organizationId = null;
        
        if ($user) {
            $organizationId = $user->organizations()->first()?->id;
        }
        
        $query = DB::table($tableName);
        
        // Áp dụng phân quyền theo bảng (chỉ khi có user và organization_id)
        if ($user && $organizationId && in_array($tableName, ['properties', 'units', 'leases', 'invoices', 'payments', 'commission_events', 'tickets', 'reviews', 'viewings', 'leads', 'booking_deposits', 'deposit_refunds', 'notifications'])) {
            if (DB::getSchemaBuilder()->hasColumn($tableName, 'organization_id')) {
                $query->where('organization_id', $organizationId);
            }
        }
        
        // Chọn cột
        $query->select($columns);
        
        // Thêm limit nếu có
        if ($limit) {
            $query->limit($limit);
        }
        
        $data = $query->get();
        
        // Chuyển đổi dữ liệu thành array đúng định dạng
        $result = [];
        foreach ($data as $row) {
            $rowArray = [];
            foreach ($columns as $column) {
                $rowArray[] = $row->$column ?? '';
            }
            $result[] = $rowArray;
        }
        
        return $result;
    }
    
    /**
     * Lấy dữ liệu từ nhiều bảng với JOIN
     */
    private function getJoinedTableData($mainTable, $relatedTable, $columns, $joins, $limit = null)
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organizationId = null;
        
        if ($user) {
            $organizationId = $user->organizations()->first()?->id;
        }
        
        // Tạo query với main table
        $query = DB::table($mainTable);
        $joinedTables = [$mainTable]; // Track joined tables to avoid duplicates
        $tableAliases = [$mainTable => $mainTable]; // Map table names to aliases
        
        // Thêm JOIN cho mỗi join được định nghĩa
        foreach ($joins as $index => $join) {
            $joinType = strtoupper($join['type']);
            $tableA = $join['tableA'];
            $columnA = $join['columnA'];
            $tableB = $join['tableB'];
            $columnB = $join['columnB'];
            
            // Debug log để kiểm tra
            Log::info('JOIN Configuration:', [
                'index' => $index,
                'join_type' => $joinType,
                'table_a' => $tableA,
                'column_a' => $columnA,
                'table_b' => $tableB,
                'column_b' => $columnB
            ]);
            
            // Determine which table is already joined and which is new
            $existingTable = null;
            $newTable = null;
            $existingColumn = null;
            $newColumn = null;
            
            if (in_array($tableA, $joinedTables)) {
                $existingTable = $tableA;
                $newTable = $tableB;
                $existingColumn = $columnA;
                $newColumn = $columnB;
            } elseif (in_array($tableB, $joinedTables)) {
                $existingTable = $tableB;
                $newTable = $tableA;
                $existingColumn = $columnB;
                $newColumn = $columnA;
            } else {
                // Neither table is joined yet, skip this join for now
                Log::warning('Skipping JOIN - neither table is joined yet', [
                    'table_a' => $tableA,
                    'table_b' => $tableB
                ]);
                continue;
            }
            
            // Create unique alias for the new table
            $alias = $newTable . '_' . $index;
            $tableAliases[$newTable] = $alias;
            
            // Add the JOIN
            switch ($joinType) {
                case 'INNER':
                    $query->join($newTable . ' as ' . $alias, $existingTable . '.' . $existingColumn, '=', $alias . '.' . $newColumn);
                    break;
                case 'LEFT':
                    $query->leftJoin($newTable . ' as ' . $alias, $existingTable . '.' . $existingColumn, '=', $alias . '.' . $newColumn);
                    break;
                case 'RIGHT':
                    $query->rightJoin($newTable . ' as ' . $alias, $existingTable . '.' . $existingColumn, '=', $alias . '.' . $newColumn);
                    break;
            }
            
            $joinedTables[] = $newTable;
        }
        
        // Áp dụng phân quyền cho tất cả các bảng đã join
        foreach ($joinedTables as $tableName) {
            if ($user && $organizationId && in_array($tableName, ['properties', 'units', 'leases', 'invoices', 'payments', 'commission_events', 'tickets', 'reviews', 'viewings', 'leads', 'booking_deposits', 'deposit_refunds', 'notifications'])) {
                if (DB::getSchemaBuilder()->hasColumn($tableName, 'organization_id')) {
                    $alias = $tableAliases[$tableName];
                    $query->where($alias . '.organization_id', $organizationId);
                }
            }
        }
        
        // Chọn cột với prefix table name để tránh conflict
        $selectColumns = [];
        foreach ($columns as $column) {
            if (strpos($column, '.') !== false) {
                // Column đã có prefix table, cần cập nhật alias nếu cần
                $parts = explode('.', $column);
                $tableName = $parts[0];
                $columnName = $parts[1];
                
                if (isset($tableAliases[$tableName])) {
                    $selectColumns[] = $tableAliases[$tableName] . '.' . $columnName;
                } else {
                    $selectColumns[] = $column;
                }
            } else {
                // Thêm prefix cho bảng chính
                $selectColumns[] = $mainTable . '.' . $column;
            }
        }
        
        $query->select($selectColumns);
        
        // Thêm limit nếu có
        if ($limit) {
            $query->limit($limit);
        }
        
        $data = $query->get();
        
        // Debug log
        Log::info('JOIN Query Result:', [
            'data_count' => count($data),
            'table_aliases' => $tableAliases,
            'columns' => $columns,
            'first_row' => $data->first() ? (array) $data->first() : null
        ]);
        
        // Chuyển đổi dữ liệu thành array đúng định dạng
        $result = [];
        foreach ($data as $row) {
            $rowArray = [];
            foreach ($columns as $column) {
                // Xử lý column name với hoặc không có prefix
                $columnKey = $column;
                if (strpos($column, '.') !== false) {
                    // Column có prefix, cần cập nhật alias nếu cần
                    $parts = explode('.', $column);
                    $tableName = $parts[0];
                    $columnName = $parts[1];
                    
                    if (isset($tableAliases[$tableName])) {
                        $columnKey = $tableAliases[$tableName] . '.' . $columnName;
                    }
                } else {
                    // Column không có prefix, thêm prefix bảng chính
                    $columnKey = $mainTable . '.' . $column;
                }
                
                $rowArray[] = $row->$columnKey ?? '';
            }
            $result[] = $rowArray;
        }
        
        return $result;
    }
    
    /**
     * Tạo CSV từ dữ liệu
     */
    private function createCSV($data, $columns)
    {
        $output = '';
        
        // Tạo header
        $headers = [];
        foreach ($columns as $column) {
            // Xử lý column name với prefix table
            $displayName = $this->getColumnDisplayName($column);
            if (strpos($column, '.') !== false) {
                // Column có prefix, tách ra để lấy tên cột
                $parts = explode('.', $column);
                $tableName = $parts[0];
                $columnName = $parts[1];
                $displayName = $this->getColumnDisplayName($columnName) . ' (' . $tableName . ')';
            }
            $headers[] = $displayName;
        }
        $output .= '"' . implode('","', $headers) . '"' . "\r\n";
        
        // Tạo dữ liệu
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($row as $value) {
                // Xử lý giá trị null và empty
                if ($value === null) {
                    $value = '';
                }
                
                // Chuyển đổi sang string và xử lý encoding
                $value = (string) $value;
                
                // Escape quotes và wrap in quotes
                $escapedValue = str_replace('"', '""', $value);
                $csvRow[] = '"' . $escapedValue . '"';
            }
            $output .= implode(',', $csvRow) . "\r\n";
        }
        
        return $output;
    }
    
    /**
     * Lấy tên hiển thị của cột
     */
    private function getColumnDisplayName($columnName)
    {
        $columnNames = [
            // ID và tham chiếu
            'id' => 'ID',
            'organization_id' => 'ID Tổ chức',
            'user_id' => 'ID Người dùng',
            'property_id' => 'ID Bất động sản',
            'unit_id' => 'ID Căn hộ',
            'lease_id' => 'ID Hợp đồng',
            'invoice_id' => 'ID Hóa đơn',
            'agent_id' => 'ID Môi giới',
            'tenant_id' => 'ID Khách thuê',
            'lead_id' => 'ID Khách hàng tiềm năng',
            // 'owner_id' => 'ID Chủ sở hữu', // Removed - now managed through master_leases
            'property_type_id' => 'ID Loại bất động sản',
            'location_id' => 'ID Vị trí',
            'location_id_2025' => 'ID Vị trí 2025',
            'booking_deposit_id' => 'ID Đặt cọc',
            'method_id' => 'ID Phương thức',
            'policy_id' => 'ID Chính sách',
            'payer_user_id' => 'ID Người thanh toán',
            'created_by' => 'ID Người tạo',
            'assigned_to' => 'ID Người được giao',
            'cancelled_by' => 'ID Người hủy',
            'deleted_by' => 'ID Người xóa',
            
            // Thông tin cơ bản
            'name' => 'Tên',
            'title' => 'Tiêu đề',
            'description' => 'Mô tả',
            'content' => 'Nội dung',
            'note' => 'Ghi chú',
            'result_note' => 'Ghi chú kết quả',
            'status' => 'Trạng thái',
            'priority' => 'Độ ưu tiên',
            'type' => 'Loại',
            'unit_type' => 'Loại căn hộ',
            'invoice_type' => 'Loại hóa đơn',
            'trigger_event' => 'Sự kiện kích hoạt',
            'ref_type' => 'Loại tham chiếu',
            'currency' => 'Tiền tệ',
            'source' => 'Nguồn',
            
            // Thông tin liên hệ
            'phone' => 'Số điện thoại',
            'email' => 'Email',
            'full_name' => 'Họ và tên',
            'lead_name' => 'Tên khách hàng tiềm năng',
            'lead_phone' => 'Số điện thoại khách hàng tiềm năng',
            'lead_email' => 'Email khách hàng tiềm năng',
            'avatar' => 'Ảnh đại diện',
            'avatar_url' => 'URL ảnh đại diện',
            'image' => 'Hình ảnh',
            'images' => 'Hình ảnh',
            
            // Thông tin địa lý
            'desired_city' => 'Thành phố mong muốn',
            'location_rating' => 'Đánh giá vị trí',
            
            // Thông tin tài chính
            'amount' => 'Số tiền',
            'amount_base' => 'Số tiền cơ sở',
            'rent_amount' => 'Tiền thuê',
            'deposit_amount' => 'Tiền cọc',
            'base_rent' => 'Tiền thuê cơ bản',
            'budget_min' => 'Ngân sách tối thiểu',
            'budget_max' => 'Ngân sách tối đa',
            'subtotal' => 'Tổng phụ',
            'tax_amount' => 'Số tiền thuế',
            'discount_amount' => 'Số tiền giảm giá',
            'total_amount' => 'Tổng số tiền',
            'commission_total' => 'Tổng hoa hồng',
            'ref_id' => 'ID Tham chiếu',
            
            // Thông tin thanh toán
            'payment_cycle' => 'Chu kỳ thanh toán',
            'billing_day' => 'Ngày lập hóa đơn',
            'payment_notes' => 'Ghi chú thanh toán',
            // Old payment cycle fields removed - now using payment_cycles relationship
            // 'lease_payment_cycle' => 'Chu kỳ thanh toán hợp đồng',
            // 'lease_payment_day' => 'Ngày thanh toán hợp đồng',
            // 'lease_payment_notes' => 'Ghi chú thanh toán hợp đồng',
            // 'lease_custom_months' => 'Số tháng tùy chỉnh hợp đồng',
            // 'prop_payment_cycle' => 'Chu kỳ thanh toán bất động sản',
            // 'prop_payment_day' => 'Ngày thanh toán bất động sản',
            // 'prop_payment_notes' => 'Ghi chú thanh toán bất động sản',
            // 'prop_custom_months' => 'Số tháng tùy chỉnh bất động sản',
            'custom_months' => 'Số tháng tùy chỉnh',
            'paid_at' => 'Ngày thanh toán',
            'txn_ref' => 'Mã giao dịch',
            
            // Thông tin hợp đồng
            'contract_no' => 'Số hợp đồng',
            'signed_at' => 'Ngày ký',
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'issue_date' => 'Ngày phát hành',
            'due_date' => 'Ngày đến hạn',
            'schedule_at' => 'Ngày lên lịch',
            'occurred_at' => 'Ngày xảy ra',
            'cancelled_at' => 'Ngày hủy',
            
            // Thông tin bất động sản
            'code' => 'Mã',
            'floor' => 'Tầng',
            'area_m2' => 'Diện tích (m²)',
            'total_floors' => 'Tổng số tầng',
            'max_occupancy' => 'Số người tối đa',
            
            // Đánh giá và phản hồi
            'overall_rating' => 'Đánh giá tổng thể',
            'quality_rating' => 'Đánh giá chất lượng',
            'service_rating' => 'Đánh giá dịch vụ',
            'price_rating' => 'Đánh giá giá cả',
            'rating' => 'Đánh giá',
            'comment' => 'Bình luận',
            'recommend' => 'Khuyến nghị',
            'helpful_count' => 'Số lượt hữu ích',
            'view_count' => 'Số lượt xem',
            'highlights' => 'Điểm nổi bật',
            
            // Thông tin xác thực
            'email_verified_at' => 'Ngày xác thực email',
            'phone_verified_at' => 'Ngày xác thực số điện thoại',
            'last_login_at' => 'Lần đăng nhập cuối',
            'google_id' => 'ID Google',
            'password_hash' => 'Mã hash mật khẩu',
            'remember_token' => 'Token ghi nhớ',
            
            // Thông tin hệ thống
            'created_at' => 'Ngày tạo',
            'updated_at' => 'Ngày cập nhật',
            'deleted_at' => 'Ngày xóa',
            'is_auto_created' => 'Tự động tạo',
            'invoice_no' => 'Số hóa đơn',
            
            // Thông tin bổ sung
            'subject' => 'Chủ đề',
            'event_type' => 'Loại sự kiện',
            'event_date' => 'Ngày sự kiện',
            'viewing_date' => 'Ngày xem',
            'viewing_time' => 'Thời gian xem',
            'refund_amount' => 'Số tiền hoàn',
            'refund_date' => 'Ngày hoàn',
            'read_at' => 'Đã đọc lúc'
        ];
        
        return $columnNames[$columnName] ?? ucfirst(str_replace('_', ' ', $columnName));
    }
}
