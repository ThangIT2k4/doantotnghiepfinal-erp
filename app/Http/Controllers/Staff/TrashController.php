<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\ErrorHelper;
use App\Http\Controllers\Controller;
use App\Traits\FiltersByOwnership;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\Organization;

class TrashController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;
    
    /**
     * Danh sách các bảng có soft delete (chỉ các bảng thuộc organization)
     */
    private $softDeleteTables = [
        'leads' => ['model' => 'App\Models\Lead', 'name' => 'Leads', 'organization_field' => 'organization_id'],
        'viewings' => ['model' => 'App\Models\Viewing', 'name' => 'Lịch hẹn', 'organization_field' => 'organization_id'],
        'properties' => ['model' => 'App\Models\Property', 'name' => 'Bất động sản', 'organization_field' => 'organization_id'],
        'units' => ['model' => 'App\Models\Unit', 'name' => 'Phòng', 'organization_field' => null],
        'leases' => ['model' => 'App\Models\Lease', 'name' => 'Hợp đồng', 'organization_field' => 'organization_id'],
        'booking_deposits' => ['model' => 'App\Models\BookingDeposit', 'name' => 'Đặt cọc', 'organization_field' => 'organization_id'],
        'invoices' => ['model' => 'App\Models\Invoice', 'name' => 'Hóa đơn', 'organization_field' => 'organization_id'],
        'documents' => ['model' => 'App\Models\Document', 'name' => 'Tài liệu', 'organization_field' => null],
        'reviews' => ['model' => 'App\Models\Review', 'name' => 'Đánh giá', 'organization_field' => 'organization_id'],
        'tickets' => ['model' => 'App\Models\Ticket', 'name' => 'Ticket', 'organization_field' => 'organization_id'],
        'payments' => ['model' => 'App\Models\Payment', 'name' => 'Thanh toán', 'organization_field' => null],
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
     * Hiển thị danh sách dữ liệu đã xóa trong tổ chức
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        $table = $request->get('table', 'leads');
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
            return redirect()->route('staff.trash.index')
                ->with('error', 'Bảng không tồn tại trong database.');
        }
        
        if (!class_exists($modelClass)) {
            return redirect()->route('staff.trash.index')
                ->with('error', 'Model không tồn tại.');
        }
        
        // Build query - chỉ lấy dữ liệu của tổ chức
        try {
            $query = $modelClass::onlyTrashed();
            
            // Filter theo organization nếu bảng có organization_field
            if ($tableConfig['organization_field']) {
                $query->where($tableConfig['organization_field'], $organizationId);
            } else {
                // Với các bảng không có organization_field, filter qua quan hệ
                if ($table === 'units') {
                    $query->whereHas('property', function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    });
                } elseif ($table === 'documents') {
                    // Documents có thể liên kết với nhiều model khác nhau
                    $query->where(function($q) use ($organizationId) {
                        // Documents của properties
                        $q->where(function($subQ) use ($organizationId) {
                            $subQ->where('owner_type', 'App\Models\Property')
                                 ->whereHasMorph('owner', 'App\Models\Property', function($propQ) use ($organizationId) {
                                     $propQ->where('organization_id', $organizationId);
                                 });
                        })
                        // Documents của leases
                        ->orWhere(function($subQ) use ($organizationId) {
                            $subQ->where('owner_type', 'App\Models\Lease')
                                 ->whereHasMorph('owner', 'App\Models\Lease', function($leaseQ) use ($organizationId) {
                                     $leaseQ->where('organization_id', $organizationId);
                                 });
                        })
                        // Documents của viewings
                        ->orWhere(function($subQ) use ($organizationId) {
                            $subQ->where('owner_type', 'App\Models\Viewing')
                                 ->whereHasMorph('owner', 'App\Models\Viewing', function($viewingQ) use ($organizationId) {
                                     $viewingQ->whereHas('property', function($propQ) use ($organizationId) {
                                         $propQ->where('organization_id', $organizationId);
                                     });
                                 });
                        });
                    });
                } elseif ($table === 'payments') {
                    $query->whereHas('invoice.lease', function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    });
                } elseif ($table === 'salary_contracts') {
                    $query->whereHas('user.organizationUsers', function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    });
                } elseif ($table === 'commission_events') {
                    $query->where('organization_id', $organizationId);
                } elseif ($table === 'commission_policies') {
                    $query->where('organization_id', $organizationId);
                } elseif ($table === 'payroll_payslips') {
                    $query->whereHas('user.organizationUsers', function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    });
                } elseif ($table === 'ticket_logs') {
                    $query->whereHas('ticket', function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    });
                } elseif ($table === 'review_replies') {
                    $query->whereHas('review', function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    });
                }
                // Các bảng services, property_types, locations, locations_2025, amenities không filter theo organization
            }
        } catch (\Exception $e) {
            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Không thể truy vấn bảng này. Vui lòng thử lại sau.');
            return redirect()->route('staff.trash.index')
                ->with('error', $safeMessage);
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
        
        // Get deleted records with sorting
        $sortBy = $request->get('sort_by', 'deleted_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['id', 'deleted_at', 'created_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'deleted_at';
        }
        
        $allowedSortOrders = ['asc', 'desc'];
        if (!in_array($sortOrder, $allowedSortOrders)) {
            $sortOrder = 'desc';
        }
        
        $records = $query->orderBy($sortBy, $sortOrder)->paginate(20);
        
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
        
        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        $isAjax = $request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest');
        
        // Prepare table HTML for both HTMX and AJAX requests
        if ($isHtmx || $isAjax) {
            try {
                // Render only table content for AJAX/HTMX
                $tableHtml = view('staff.trash.partials.table', [
                    'records' => $records,
                    'table' => $table,
                    'tableConfig' => $tableConfig,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder,
                ])->render();
                
                // Handle HTMX request - return HTML directly
                if ($isHtmx) {
                    return response($tableHtml)
                        ->header('HX-Push-Url', $request->fullUrl());
                }
                
                // Handle AJAX request - return JSON (backward compatibility)
                return response()->json([
                    'success' => true,
                    'table_html' => $tableHtml,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('TrashController AJAX/HTMX Error: ' . $e->getMessage());
                $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.');
                if ($isHtmx) {
                    return response('<div class="alert alert-danger">' . $safeMessage . '</div>', 500);
                }
                return response()->json([
                    'success' => false,
                    'message' => $safeMessage,
                ], 500);
            }
        }
        
        return view('staff.trash.index', compact(
            'records',
            'table',
            'tableConfig',
            'organizationId',
            'search',
            'deletedFrom',
            'deletedTo',
            'availableTables',
            'sortBy',
            'sortOrder'
        ));
    }
    
    /**
     * Khôi phục một bản ghi
     */
    public function restore(Request $request, $table, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }
        
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
            
            // Kiểm tra quyền - chỉ khôi phục dữ liệu của tổ chức
            if ($tableConfig['organization_field']) {
                if ($record->{$tableConfig['organization_field']} != $organizationId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền khôi phục bản ghi này.'
                    ], 403);
                }
            } else {
                // Với các bảng không có organization_field, kiểm tra qua quan hệ
                if (!$this->belongsToOrganization($record, $table, $organizationId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền khôi phục bản ghi này.'
                    ], 403);
                }
            }
            
            $record->restore();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã khôi phục thành công!'
            ]);
        } catch (\Exception $e) {
            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra. Vui lòng thử lại sau.');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500);
        }
    }
    
    /**
     * Kiểm tra xem bản ghi có thuộc tổ chức không
     */
    private function belongsToOrganization($record, $table, $organizationId)
    {
        try {
            // Các bảng có organization_id trực tiếp
            if (Schema::hasColumn($table, 'organization_id')) {
                return isset($record->organization_id) && $record->organization_id == $organizationId;
            }
            
            // Các bảng filter qua quan hệ - cần load relationships
            switch ($table) {
                case 'units':
                    $record->load('property');
                    return $record->property && isset($record->property->organization_id) && $record->property->organization_id == $organizationId;
                    
                case 'documents':
                    $record->load('owner');
                    if ($record->owner_type === 'App\Models\Property') {
                        return $record->owner && isset($record->owner->organization_id) && $record->owner->organization_id == $organizationId;
                    } elseif ($record->owner_type === 'App\Models\Lease') {
                        return $record->owner && isset($record->owner->organization_id) && $record->owner->organization_id == $organizationId;
                    } elseif ($record->owner_type === 'App\Models\Viewing') {
                        $record->owner->load('property');
                        return $record->owner && $record->owner->property && isset($record->owner->property->organization_id) && $record->owner->property->organization_id == $organizationId;
                    }
                    return false;
                    
                case 'payments':
                    $record->load(['invoice.lease']);
                    return $record->invoice && $record->invoice->lease && isset($record->invoice->lease->organization_id) && $record->invoice->lease->organization_id == $organizationId;
                    
                case 'salary_contracts':
                case 'payroll_payslips':
                    $record->load('user.organizationUsers');
                    return $record->user && $record->user->organizationUsers()->where('organization_id', $organizationId)->exists();
                    
                case 'commission_events':
                case 'commission_policies':
                    return isset($record->organization_id) && $record->organization_id == $organizationId;
                    
                case 'ticket_logs':
                    $record->load('ticket');
                    return $record->ticket && isset($record->ticket->organization_id) && $record->ticket->organization_id == $organizationId;
                    
                case 'review_replies':
                    $record->load('review');
                    return $record->review && isset($record->review->organization_id) && $record->review->organization_id == $organizationId;
                    
                default:
                    // Các bảng khác không có organization field thì cho phép
                    return true;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error checking organization for {$table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Xóa vĩnh viễn một bản ghi
     */
    public function forceDelete(Request $request, $table, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }
        
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
            
            // Kiểm tra quyền - chỉ xóa dữ liệu của tổ chức
            if ($tableConfig['organization_field']) {
                if ($record->{$tableConfig['organization_field']} != $organizationId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền xóa bản ghi này.'
                    ], 403);
                }
            } else {
                // Với các bảng không có organization_field, kiểm tra qua quan hệ
                if (!$this->belongsToOrganization($record, $table, $organizationId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền xóa bản ghi này.'
                    ], 403);
                }
            }
            
            $record->forceDelete();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa vĩnh viễn thành công!'
            ]);
        } catch (\Exception $e) {
            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra. Vui lòng thử lại sau.');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500);
        }
    }
    
    /**
     * Khôi phục nhiều bản ghi
     */
    public function restoreMultiple(Request $request, $table)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }
        
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
            $records = $modelClass::onlyTrashed()->whereIn('id', $ids)->get();
            $count = 0;
            
            foreach ($records as $record) {
                // Kiểm tra quyền - chỉ khôi phục dữ liệu của tổ chức
                $canRestore = false;
                if ($tableConfig['organization_field']) {
                    $canRestore = $record->{$tableConfig['organization_field']} == $organizationId;
                } else {
                    $canRestore = $this->belongsToOrganization($record, $table, $organizationId);
                }
                
                if ($canRestore) {
                    $record->restore();
                    $count++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Đã khôi phục {$count} bản ghi thành công!"
            ]);
        } catch (\Exception $e) {
            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra. Vui lòng thử lại sau.');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500);
        }
    }
    
    /**
     * Xóa vĩnh viễn nhiều bản ghi
     */
    public function forceDeleteMultiple(Request $request, $table)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }
        
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
            $records = $modelClass::onlyTrashed()->whereIn('id', $ids)->get();
            $count = 0;
            
            foreach ($records as $record) {
                // Kiểm tra quyền - chỉ xóa dữ liệu của tổ chức
                $canDelete = false;
                if ($tableConfig['organization_field']) {
                    $canDelete = $record->{$tableConfig['organization_field']} == $organizationId;
                } else {
                    $canDelete = $this->belongsToOrganization($record, $table, $organizationId);
                }
                
                if ($canDelete) {
                    $record->forceDelete();
                    $count++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Đã xóa vĩnh viễn {$count} bản ghi thành công!"
            ]);
        } catch (\Exception $e) {
            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra. Vui lòng thử lại sau.');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500);
        }
    }
}

