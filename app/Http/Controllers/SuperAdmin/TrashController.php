<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use App\Models\Organization;

class TrashController extends Controller
{
    /**
     * Danh sách các bảng có soft delete
     */
    private $softDeleteTables = [
        'leads' => ['model' => 'App\Models\Lead', 'name' => 'Leads', 'organization_field' => 'organization_id'],
        'viewings' => ['model' => 'App\Models\Viewing', 'name' => 'Lịch hẹn', 'organization_field' => 'organization_id'],
        'properties' => ['model' => 'App\Models\Property', 'name' => 'Bất động sản', 'organization_field' => 'organization_id'],
        'units' => ['model' => 'App\Models\Unit', 'name' => 'Phòng', 'organization_field' => null],
        'users' => ['model' => 'App\Models\User', 'name' => 'Người dùng', 'organization_field' => null],
        'organizations' => ['model' => 'App\Models\Organization', 'name' => 'Tổ chức', 'organization_field' => null],
        'leases' => ['model' => 'App\Models\Lease', 'name' => 'Hợp đồng', 'organization_field' => 'organization_id'],
        'booking_deposits' => ['model' => 'App\Models\BookingDeposit', 'name' => 'Đặt cọc', 'organization_field' => 'organization_id'],
        'invoices' => ['model' => 'App\Models\Invoice', 'name' => 'Hóa đơn', 'organization_field' => 'organization_id'],
        'documents' => ['model' => 'App\Models\Document', 'name' => 'Tài liệu', 'organization_field' => null],
        'reviews' => ['model' => 'App\Models\Review', 'name' => 'Đánh giá', 'organization_field' => 'organization_id'],
        'tickets' => ['model' => 'App\Models\Ticket', 'name' => 'Ticket', 'organization_field' => 'organization_id'],
        'payments' => ['model' => 'App\Models\Payment', 'name' => 'Thanh toán', 'organization_field' => null],
        // 'commissions' => ['model' => 'App\Models\Commission', 'name' => 'Hoa hồng', 'organization_field' => null], // Bảng không tồn tại
        'salary_contracts' => ['model' => 'App\Models\SalaryContract', 'name' => 'Hợp đồng lương', 'organization_field' => null],
        'salary_advances' => ['model' => 'App\Models\SalaryAdvance', 'name' => 'Tạm ứng lương', 'organization_field' => 'organization_id'],
        'vendors' => ['model' => 'App\Models\Vendor', 'name' => 'Nhà cung cấp', 'organization_field' => 'organization_id'],
        'services' => ['model' => 'App\Models\Service', 'name' => 'Dịch vụ', 'organization_field' => null],
        'property_types' => ['model' => 'App\Models\PropertyType', 'name' => 'Loại BĐS', 'organization_field' => null],
        'locations' => ['model' => 'App\Models\Location', 'name' => 'Địa chỉ', 'organization_field' => null],
        'locations_2025' => ['model' => 'App\Models\Location2025', 'name' => 'Địa chỉ 2025', 'organization_field' => null],
        'amenities' => ['model' => 'App\Models\Amenity', 'name' => 'Tiện ích', 'organization_field' => null],
        'deposit_refunds' => ['model' => 'App\Models\DepositRefund', 'name' => 'Hoàn cọc', 'organization_field' => 'organization_id'],
        'company_invoices' => ['model' => 'App\Models\CompanyInvoice', 'name' => 'Hóa đơn công ty', 'organization_field' => 'organization_id'],
        'commission_events' => ['model' => 'App\Models\CommissionEvent', 'name' => 'Sự kiện hoa hồng', 'organization_field' => null],
        'commission_policies' => ['model' => 'App\Models\CommissionPolicy', 'name' => 'Chính sách hoa hồng', 'organization_field' => null],
        'payroll_payslips' => ['model' => 'App\Models\PayrollPayslip', 'name' => 'Bảng lương', 'organization_field' => null],
        'ticket_logs' => ['model' => 'App\Models\TicketLog', 'name' => 'Log Ticket', 'organization_field' => null],
        'review_replies' => ['model' => 'App\Models\ReviewReply', 'name' => 'Phản hồi đánh giá', 'organization_field' => null],
    ];

    /**
     * Hiển thị danh sách dữ liệu đã xóa
     */
    public function index(Request $request)
    {
        $table = $request->get('table', 'leads');
        $organizationId = $request->get('organization_id');
        $search = $request->get('search');
        $deletedFrom = $request->get('deleted_from');
        $deletedTo = $request->get('deleted_to');
        
        // Validate table
        if (!isset($this->softDeleteTables[$table])) {
            $table = 'leads';
        }
        
        $tableConfig = $this->softDeleteTables[$table];
        $modelClass = $tableConfig['model'];
        
        // Kiểm tra bảng và model có tồn tại không
        if (!Schema::hasTable($table)) {
            return redirect()->route('superadmin.trash.index')
                ->with('error', 'Bảng không tồn tại trong database.');
        }
        
        if (!class_exists($modelClass)) {
            return redirect()->route('superadmin.trash.index')
                ->with('error', 'Model không tồn tại.');
        }
        
        // Get organizations for filter
        $organizations = Organization::whereNull('deleted_at')
            ->orderBy('name')
            ->get();
        
        // Build query
        try {
            $query = $modelClass::onlyTrashed();
        } catch (\Exception $e) {
            return redirect()->route('superadmin.trash.index')
                ->with('error', 'Không thể truy vấn bảng này: ' . $e->getMessage());
        }
        
        // Filter by organization if table has organization field
        if ($tableConfig['organization_field'] && $organizationId) {
            $query->where($tableConfig['organization_field'], $organizationId);
        }
        
        // Filter by deleted date
        if ($deletedFrom) {
            $query->whereDate('deleted_at', '>=', $deletedFrom);
        }
        if ($deletedTo) {
            $query->whereDate('deleted_at', '<=', $deletedTo);
        }
        
        // Search
        if ($search) {
            $query->where(function($q) use ($search, $table) {
                // Common search fields
                if (Schema::hasColumn($table, 'name')) {
                    $q->orWhere('name', 'like', "%{$search}%");
                }
                if (Schema::hasColumn($table, 'email')) {
                    $q->orWhere('email', 'like', "%{$search}%");
                }
                if (Schema::hasColumn($table, 'phone')) {
                    $q->orWhere('phone', 'like', "%{$search}%");
                }
                if (Schema::hasColumn($table, 'code')) {
                    $q->orWhere('code', 'like', "%{$search}%");
                }
                // For viewings
                if ($table === 'viewings' && Schema::hasColumn($table, 'lead_name')) {
                    $q->orWhere('lead_name', 'like', "%{$search}%");
                    $q->orWhere('lead_phone', 'like', "%{$search}%");
                }
            });
        }
        
        // Get deleted records
        $records = $query->orderBy('deleted_at', 'desc')->paginate(20);
        
        // Lọc danh sách bảng chỉ hiển thị các bảng tồn tại
        $availableTables = [];
        foreach ($this->softDeleteTables as $tableKey => $config) {
            try {
                if (Schema::hasTable($tableKey) && class_exists($config['model'])) {
                    $availableTables[$tableKey] = $config;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return view('superadmin.trash.index', compact(
            'records',
            'table',
            'tableConfig',
            'organizations',
            'organizationId',
            'search',
            'deletedFrom',
            'deletedTo',
            'availableTables'
        ));
    }
    
    /**
     * Khôi phục một bản ghi
     */
    public function restore(Request $request, $table, $id)
    {
        if (!isset($this->softDeleteTables[$table])) {
            return response()->json([
                'success' => false,
                'message' => 'Bảng không hợp lệ'
            ], 400);
        }
        
        $tableConfig = $this->softDeleteTables[$table];
        $modelClass = $tableConfig['model'];
        
        try {
            $record = $modelClass::onlyTrashed()->findOrFail($id);
            $record->restore();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã khôi phục thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Xóa vĩnh viễn một bản ghi
     */
    public function forceDelete(Request $request, $table, $id)
    {
        if (!isset($this->softDeleteTables[$table])) {
            return response()->json([
                'success' => false,
                'message' => 'Bảng không hợp lệ'
            ], 400);
        }
        
        $tableConfig = $this->softDeleteTables[$table];
        $modelClass = $tableConfig['model'];
        
        try {
            $record = $modelClass::onlyTrashed()->findOrFail($id);
            $record->forceDelete();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa vĩnh viễn thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Khôi phục nhiều bản ghi
     */
    public function restoreMultiple(Request $request, $table)
    {
        if (!isset($this->softDeleteTables[$table])) {
            return response()->json([
                'success' => false,
                'message' => 'Bảng không hợp lệ'
            ], 400);
        }
        
        $tableConfig = $this->softDeleteTables[$table];
        $modelClass = $tableConfig['model'];
        $ids = $request->get('ids', []);
        
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn ít nhất một bản ghi'
            ], 400);
        }
        
        try {
            $count = $modelClass::onlyTrashed()->whereIn('id', $ids)->restore();
            
            return response()->json([
                'success' => true,
                'message' => "Đã khôi phục {$count} bản ghi thành công!"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Xóa vĩnh viễn nhiều bản ghi
     */
    public function forceDeleteMultiple(Request $request, $table)
    {
        if (!isset($this->softDeleteTables[$table])) {
            return response()->json([
                'success' => false,
                'message' => 'Bảng không hợp lệ'
            ], 400);
        }
        
        $tableConfig = $this->softDeleteTables[$table];
        $modelClass = $tableConfig['model'];
        $ids = $request->get('ids', []);
        
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn ít nhất một bản ghi'
            ], 400);
        }
        
        try {
            $count = 0;
            $records = $modelClass::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($records as $record) {
                $record->forceDelete();
                $count++;
            }
            
            return response()->json([
                'success' => true,
                'message' => "Đã xóa vĩnh viễn {$count} bản ghi thành công!"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}

