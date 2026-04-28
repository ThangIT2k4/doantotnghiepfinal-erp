<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\Lease;
use App\Models\Unit;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TicketController extends Controller
{
    protected $imageService;
    
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    /**
     * Display a listing of the tenant's tickets
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query using JOINs and proper index order
        $query = Ticket::select([
            'tickets.*',
            'user_profiles_assigned.full_name as assigned_to_name',
            'units.code as unit_name',
            'properties.name as property_name',
            DB::raw("CONCAT_WS(', ', locations.street, locations.ward, locations.district, locations.city) as location_address"),
            DB::raw("CONCAT_WS(', ', locations2025.street, locations2025.ward, locations2025.city) as location2025_address")
        ])
        ->join('leases', 'tickets.lease_id', '=', 'leases.id')
        ->leftJoin('users as users_assigned', 'tickets.assigned_to', '=', 'users_assigned.id')
        ->leftJoin('user_profiles as user_profiles_assigned', 'users_assigned.id', '=', 'user_profiles_assigned.user_id')
        ->leftJoin('units', 'tickets.unit_id', '=', 'units.id')
        ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
        ->leftJoin('locations', 'properties.location_id', '=', 'locations.id')
        ->leftJoin('locations as locations2025', 'properties.location_id_2025', '=', 'locations2025.id')
        ->whereIn('leases.id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
        ->whereNull('tickets.deleted_at') // Uses idx_tickets_deleted_at_status
        ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
        ->whereNull('units.deleted_at'); // Uses idx_units_deleted_at_property

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Apply status filter - uses idx_tickets_deleted_at_status
        if ($request->filled('status') && $request->status !== 'all') {
            $status = $request->status;
            $query->where('tickets.status', $status);
        }

        // Apply priority filter - support both priority_id and priority (key_code)
        if ($request->filled('priority') && $request->priority !== 'all') {
            $priority = $request->priority;
            // If priority is a key_code (low, medium, high, urgent), find the ID
            if (!is_numeric($priority)) {
                $priorityModel = \App\Models\TicketPriority::where('key_code', $priority)->first();
                if ($priorityModel) {
                    $query->where('tickets.priority_id', $priorityModel->id);
                }
            } else {
                $query->where('tickets.priority_id', $priority);
            }
        } elseif ($request->filled('priority_id') && $request->priority_id !== 'all') {
            $priorityId = $request->priority_id;
            $query->where('tickets.priority_id', $priorityId);
        }

        $tickets = $query->latest('tickets.created_at')->paginate(10);

        // Eager load attached documents to avoid N+1 queries
        $tickets->load('documents');

        // Calculate statistics
        $stats = $this->calculateTicketStats($user->id);

        // Get filter parameters
        $status = $request->get('status', 'all');
        $priority = $request->get('priority', 'all');
        $search = $request->get('search', '');

        // Check if HTMX request
        if ($request->header('HX-Request')) {
            // Prepare stats cards with HTMX attributes
            $ticketStats = [
                [
                    'icon' => 'fas fa-folder-open',
                    'value' => $stats['open'] ?? 0,
                    'label' => 'Đang mở',
                    'active' => $status === 'open',
                    'data-filter' => 'open',
                    'statusClass' => 'open',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'open', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc ticket đang mở'
                ],
                [
                    'icon' => 'fas fa-cog',
                    'value' => $stats['in_progress'] ?? 0,
                    'label' => 'Đang xử lý',
                    'active' => $status === 'in_progress',
                    'data-filter' => 'in_progress',
                    'statusClass' => 'in_progress',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'in_progress', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc ticket đang xử lý'
                ],
                [
                    'icon' => 'fas fa-check-circle',
                    'value' => $stats['resolved'] ?? 0,
                    'label' => 'Đã giải quyết',
                    'active' => $status === 'resolved',
                    'data-filter' => 'resolved',
                    'statusClass' => 'resolved',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'resolved', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc ticket đã giải quyết'
                ],
                [
                    'icon' => 'fas fa-archive',
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'active' => $status === 'all',
                    'data-filter' => 'all',
                    'statusClass' => 'total',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'all', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để xem tất cả ticket'
                ]
            ];

            // Prepare filter tabs
            $filterTabs = [
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'active' => $status === 'all',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'all', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-folder'
                ],
                [
                    'label' => 'Đang mở',
                    'value' => 'open',
                    'active' => $status === 'open',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'open', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-folder-open'
                ],
                [
                    'label' => 'Đang xử lý',
                    'value' => 'in_progress',
                    'active' => $status === 'in_progress',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'in_progress', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-cog'
                ],
                [
                    'label' => 'Đã giải quyết',
                    'value' => 'resolved',
                    'active' => $status === 'resolved',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'resolved', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-check-circle'
                ],
                [
                    'label' => 'Đã đóng',
                    'value' => 'closed',
                    'active' => $status === 'closed',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'closed', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-archive'
                ]
            ];

            // Get priorities for dropdown
            $priorities = \App\Models\TicketPriority::orderBy('id')->get();
            $additionalFields = '<div class="priority-filter-blue">
                <select class="form-select priority-select-blue" name="priority" id="priorityFilter" 
                        hx-get="' . route('tenant.tickets.index') . '"
                        hx-target="#tickets-list-container"
                        hx-swap="innerHTML"
                        hx-push-url="true"
                        hx-indicator="#htmx-loading"
                        hx-trigger="change"
                        hx-include="[name=\'search\'], [name=\'status\']">
                    <option value="all">Tất cả độ ưu tiên</option>';
            foreach ($priorities as $priorityOption) {
                $additionalFields .= '<option value="' . $priorityOption->key_code . '" ' . ($priority === $priorityOption->key_code ? 'selected' : '') . '>' . $priorityOption->name . '</option>';
            }
            $additionalFields .= '</select>
            </div>';

            $ticketsListHtml = view('tenant.ticket.partials.tickets-list', compact('tickets'))->render();
            $statsCardsHtml = view('tenant.components.stats-cards', [
                'stats' => $ticketStats,
                'columns' => 4,
                'class' => 'mb-4'
            ])->render();
            
            $filterSectionHtml = view('tenant.components.filter-section', [
                'searchPlaceholder' => 'Tìm kiếm ticket...',
                'searchValue' => $search,
                'filters' => $filterTabs,
                'formId' => 'filterForm',
                'searchInputId' => 'searchInput',
                'hxGet' => route('tenant.tickets.index'),
                'hxTarget' => '#tickets-list-container',
                'hxSwap' => 'innerHTML',
                'hxPushUrl' => 'true',
                'hxIndicator' => '#htmx-loading',
                'hxTrigger' => 'input delay:500ms from:#searchInput, change from:#priorityFilter',
                'additionalFields' => $additionalFields
            ])->render();
            
            // Return tickets list with stats cards and filter section update via hx-swap-oob
            $html = $ticketsListHtml 
                . "\n<div id='stats-cards-container' hx-swap-oob='true'>" . $statsCardsHtml . "</div>"
                . "\n<div id='filter-section-container' hx-swap-oob='true'>" . $filterSectionHtml . "</div>";
            
            return response($html)
                ->header('HX-Push-Url', $request->fullUrl());
        }

        return view('tenant.ticket.index', compact('tickets', 'stats', 'status', 'priority', 'search'));
    }

    /**
     * Show the form for creating a new ticket
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query with indexes
        $leases = Lease::with([
            'unit.property'
        ])
        ->whereIn('id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
        ->where('status', 'active')
        ->whereNull('deleted_at') // Uses idx_leases_deleted_at_status
        ->get();

        $priorities = TicketPriority::orderBy('id')->get();
        return view('tenant.ticket.create', compact('leases', 'priorities'));
    }

    /**
     * Store a newly created ticket
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Validate input
            $validated = $request->validate([
                'title' => 'required|string|min:5|max:255',
                'description' => 'required|string|min:10',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'priority_id' => 'required|exists:ticket_priorities,id',
                'unit_id' => 'required|exists:units,id',
                'lease_id' => 'required|exists:leases,id'
            ], [
                'title.required' => 'Vui lòng nhập tiêu đề ticket',
                'title.min' => 'Tiêu đề phải có ít nhất 5 ký tự',
                'description.required' => 'Vui lòng nhập mô tả',
                'description.min' => 'Mô tả phải có ít nhất 10 ký tự',
                'image.image' => 'File phải là hình ảnh',
                'image.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif',
                'image.max' => 'Kích thước hình ảnh không được vượt quá 2MB',
                'priority.required' => 'Vui lòng chọn độ ưu tiên',
                'unit_id.required' => 'Vui lòng chọn hợp đồng (unit_id)',
                'lease_id.required' => 'Vui lòng chọn hợp đồng',
            ]);

            // Get accessible lease IDs (user as tenant or resident)
            $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
            
            // Verify that the lease belongs to the tenant - optimized with indexes
            $lease = Lease::with(['unit.property'])
                ->where('id', $validated['lease_id'])
                ->whereIn('id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
                ->where('unit_id', $validated['unit_id'])
                ->where('status', 'active')
                ->whereNull('deleted_at') // Uses idx_leases_deleted_at_status
                ->firstOrFail();

            // Get property and auto-assign agent from lease
            $property = $lease->unit->property;
            $manager = $property ? $property->getPrimaryManager() : null;
            $organizationId = $property && $property->organization_id 
                ? $property->organization_id 
                : $user->organization_id;

            // Auto-assign to lease agent, fallback to property manager if no agent
            $assignedTo = $lease->agent_id ?? ($manager ? $manager->id : null);

            // Create ticket
            $ticket = Ticket::create([
                'organization_id' => $organizationId,
                'unit_id' => $validated['unit_id'],
                'lease_id' => $validated['lease_id'],
                'created_by' => $user->id,
                'assigned_to' => $assignedTo,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'priority_id' => $validated['priority_id'],
                'status' => 'open'
            ]);

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                try {
                    $file = $request->file('image');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'tickets', 'ticket-documents');

                    $document = \App\Models\Document::create([
                        'owner_type' => Ticket::class,
                        'owner_id' => $ticket->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                    ]);

                    // Attach document to ticket using the new many-to-many relationship
                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading ticket image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }

            Log::info('Ticket created:', [
                'ticket_id' => $ticket->id,
                'assigned_to' => $assignedTo,
                'lease_agent_id' => $lease->agent_id,
                'property' => $property ? $property->name : null
            ]);

            return redirect()->route('tenant.tickets.show', $ticket->id)
                ->with('success', 'Ticket đã được tạo thành công!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Ticket creation error:', [
                'message' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified ticket
     */
    public function show($id)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query using JOIN instead of whereHas
        $ticket = Ticket::select([
            'tickets.*',
            'user_profiles_created.full_name as created_by_name',
            'user_profiles_assigned.full_name as assigned_to_name',
            'units.code as unit_name',
            'properties.name as property_name',
            DB::raw("CONCAT_WS(', ', locations.street, locations.ward, locations.district, locations.city) as location_address"),
            DB::raw("CONCAT_WS(', ', locations2025.street, locations2025.ward, locations2025.city) as location2025_address"),
            'leases.contract_no as lease_contract_number'
        ])
        ->join('leases', 'tickets.lease_id', '=', 'leases.id')
        ->leftJoin('users as users_created', 'tickets.created_by', '=', 'users_created.id')
        ->leftJoin('user_profiles as user_profiles_created', 'users_created.id', '=', 'user_profiles_created.user_id')
        ->leftJoin('users as users_assigned', 'tickets.assigned_to', '=', 'users_assigned.id')
        ->leftJoin('user_profiles as user_profiles_assigned', 'users_assigned.id', '=', 'user_profiles_assigned.user_id')
        ->leftJoin('units', 'tickets.unit_id', '=', 'units.id')
        ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
        ->leftJoin('locations', 'properties.location_id', '=', 'locations.id')
        ->leftJoin('locations as locations2025', 'properties.location_id_2025', '=', 'locations2025.id')
        ->where('tickets.id', $id)
        ->whereIn('leases.id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
        ->whereNull('tickets.deleted_at') // Uses idx_tickets_deleted_at_status
        ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
        ->firstOrFail();

        // Load logs separately to avoid N+1
        $ticket->logs = \App\Models\TicketLog::select([
            'ticket_logs.*',
            'user_profiles.full_name as actor_name'
        ])
        ->leftJoin('users', 'ticket_logs.actor_id', '=', 'users.id')
        ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
        ->where('ticket_logs.ticket_id', $ticket->id)
        ->whereNull('ticket_logs.deleted_at')
        ->orderBy('ticket_logs.created_at', 'desc')
        ->get();

        // Load attached documents (images)
        $ticket->load('documents');

        return view('tenant.ticket.show', compact('ticket'));
    }

    /**
     * Show the form for editing the specified ticket
     */
    public function edit($id)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        $ticket = Ticket::with([
            'priorityRelation',
            'unit.property',
            'lease.unit.property',
            'createdBy',
            'assignedTo',
            'documents'
        ])
        ->join('leases', 'tickets.lease_id', '=', 'leases.id')
        ->where('tickets.id', $id)
        ->whereIn('leases.id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
        ->whereNull('tickets.deleted_at') // Uses idx_tickets_deleted_at_status
        ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
        ->firstOrFail();

        // Only allow editing open tickets (but still show form with warning)
        $priorities = TicketPriority::orderBy('id')->get();
        return view('tenant.ticket.edit', compact('ticket', 'priorities'));
    }

    /**
     * Update the specified ticket
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            // Get accessible lease IDs (user as tenant or resident)
            $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
            
            // Optimized query with JOIN
            $ticket = Ticket::join('leases', 'tickets.lease_id', '=', 'leases.id')
                ->where('tickets.id', $id)
                ->whereIn('leases.id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
                ->whereNull('tickets.deleted_at') // Uses idx_tickets_deleted_at_status
                ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
                ->select('tickets.*')
                ->firstOrFail();
            
            // Load attached documents for image handling
            $ticket->load('documents');

            // Only allow updating open tickets
            if ($ticket->status !== 'open') {
                return redirect()->back()
                    ->with('error', 'Chỉ có thể chỉnh sửa ticket đang ở trạng thái "Đang mở".')
                    ->withInput();
            }

            // Validate input
            $validated = $request->validate([
                'title' => 'required|string|min:5|max:255',
                'description' => 'required|string|min:10',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'priority_id' => 'required|exists:ticket_priorities,id'
            ], [
                'title.required' => 'Vui lòng nhập tiêu đề ticket',
                'title.min' => 'Tiêu đề phải có ít nhất 5 ký tự',
                'description.required' => 'Vui lòng nhập mô tả',
                'description.min' => 'Mô tả phải có ít nhất 10 ký tự',
                'image.image' => 'File phải là hình ảnh',
                'image.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif',
                'image.max' => 'Kích thước hình ảnh không được vượt quá 2MB',
                'priority_id.required' => 'Vui lòng chọn độ ưu tiên',
                'priority_id.exists' => 'Độ ưu tiên không hợp lệ',
            ]);

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                try {
                    // Delete old primary image if exists
                    $oldPrimaryImage = $ticket->documents()
                        ->where('document_type', 'image')
                        ->where('is_primary', true)
                        ->first();
                    
                    if ($oldPrimaryImage) {
                        // Delete file from storage
                        $filePath = $oldPrimaryImage->getRawOriginal('file_url');
                        // Delete file from storage (lưu trực tiếp vào public/storage)
                        $fullPath = public_path('storage/' . $filePath);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                        // Delete document record
                        $oldPrimaryImage->delete();
                    }

                    // Upload new image
                    $file = $request->file('image');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'tickets', 'ticket-documents');

                    $document = \App\Models\Document::create([
                        'owner_type' => Ticket::class,
                        'owner_id' => $ticket->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                    ]);

                    // Attach document to ticket using the new many-to-many relationship
                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading ticket image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }

            // Update ticket (lease and unit cannot be changed)
            $ticket->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'priority_id' => $validated['priority_id']
            ]);

            Log::info('Ticket updated:', [
                'ticket_id' => $ticket->id,
                'updated_by' => $user->id
            ]);

            return redirect()->route('tenant.tickets.show', $ticket->id)
                ->with('success', 'Ticket đã được cập nhật thành công!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Ticket update error:', [
                'message' => $e->getMessage(),
                'ticket_id' => $id
            ]);
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Cancel the specified ticket (change status to cancelled instead of delete)
     */
    public function destroy($id)
    {
        Log::info('Destroy/Cancel method called', ['ticket_id' => $id]);
        
        $user = Auth::user();
        Log::info('User authenticated', ['user_id' => $user->id]);
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        $ticket = Ticket::join('leases', 'tickets.lease_id', '=', 'leases.id')
            ->where('tickets.id', $id)
            ->whereIn('leases.id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
            ->whereIn('tickets.status', ['open', 'in_progress']) // Only allow cancelling open or in_progress tickets
            ->whereNull('tickets.deleted_at') // Uses idx_tickets_deleted_at_status
            ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
            ->first();

        if (!$ticket) {
            Log::error('Ticket not found or not allowed to cancel', [
                'ticket_id' => $id,
                'user_id' => $user->id
            ]);
            
            return redirect()->route('tenant.tickets.index')
                ->with('error', 'Không tìm thấy ticket hoặc bạn không có quyền hủy ticket này! Chỉ có thể hủy ticket đang mở hoặc đang xử lý.');
        }

        Log::info('Ticket found, attempting to cancel', [
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status
        ]);

        // Update status to cancelled
        $ticket->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'cancelled_by' => $user->id,
            'updated_at' => Carbon::now()
        ]);

        Log::info('Ticket cancelled successfully', [
            'ticket_id' => $ticket->id,
            'new_status' => 'cancelled'
        ]);

        return redirect()->route('tenant.tickets.index')
            ->with('success', 'Ticket đã được hủy thành công!');
    }

    /**
     * Get units for a specific lease (AJAX)
     */
    public function getUnitsByLease($leaseId)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        $lease = Lease::with('unit.property')
            ->where('id', $leaseId)
            ->whereIn('id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
            ->where('status', 'active')
            ->whereNull('deleted_at') // Uses idx_leases_deleted_at_status
            ->firstOrFail();

        return response()->json([
            'unit' => [
                'id' => $lease->unit->id,
                'name' => $lease->unit->name,
                'property_name' => $lease->unit->property->name,
                'address' => $lease->unit->property->address
            ]
        ]);
    }

    /**
     * Calculate ticket statistics for the tenant
     */
    private function calculateTicketStats($tenantId)
    {
        $now = Carbon::now();

        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($tenantId);
        
        // Optimized stats queries using JOIN instead of whereHas
        $baseQuery = Ticket::join('leases', 'tickets.lease_id', '=', 'leases.id')
            ->whereIn('leases.id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
            ->whereNull('tickets.deleted_at') // Uses idx_tickets_deleted_at_status
            ->whereNull('leases.deleted_at'); // Uses idx_leases_deleted_at_status

        $open = (clone $baseQuery)->where('tickets.status', 'open')->count();
        $inProgress = (clone $baseQuery)->where('tickets.status', 'in_progress')->count();
        $resolved = (clone $baseQuery)->where('tickets.status', 'resolved')->count();
        $closed = (clone $baseQuery)->where('tickets.status', 'closed')->count();
        $total = (clone $baseQuery)->count();

        return [
            'open' => $open,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'closed' => $closed,
            'total' => $total
        ];
    }
}
