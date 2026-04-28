<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\TicketPriority;
use App\Models\Unit;
use App\Models\Lease;
use App\Models\User;
use App\Models\Invoice;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;
    
    protected $imageService;
    
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has work.access capability
        $hasWorkAccess = $this->checkCapability('work.access');
        if (!$hasWorkAccess) {
            abort(403, 'Bạn không có quyền truy cập module Công việc.');
        }

        // Optimized query with proper index order
        $organizationId = $this->getCurrentOrganizationId();
        
        // Check if user can view all tickets or only own tickets
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('work.ticket');
        
        $query = Ticket::select([
            'tickets.*',
            'units.code as unit_code',
            'ticket_properties.name as property_name',
            'leases.contract_no as lease_contract_no'
        ])
        ->leftJoin('units', 'tickets.unit_id', '=', 'units.id')
        ->leftJoin('properties as ticket_properties', 'tickets.property_id', '=', 'ticket_properties.id')
        ->leftJoin('properties as unit_properties', 'units.property_id', '=', 'unit_properties.id')
        ->leftJoin('leases', 'tickets.lease_id', '=', 'leases.id');

        // Apply filters in optimal order for indexes: organization_id -> deleted_at -> status
        if ($organizationId) {
            $query->where('tickets.organization_id', $organizationId); // Uses idx_tickets_org_deleted_status
        }
        
        // For agent, only show tickets of assigned properties
        if (!$canViewAll) {
            // Lấy các properties được gán cho agent này
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            
            if ($assignedPropertyIds->isEmpty()) {
                return view('staff.work.tickets.index', [
                    'tickets' => collect(),
                    'units' => collect(),
                    'leases' => collect(),
                    'users' => collect(),
                    'properties' => collect(),
                    'priorities' => \App\Models\TicketPriority::orderBy('id')->get()
                ]);
            }
            
            $query->where(function($q) use ($assignedPropertyIds) {
                // Tickets có property thuộc assigned properties
                $q->whereIn('ticket_properties.id', $assignedPropertyIds)
                  // Hoặc tickets có unit thuộc assigned properties
                  ->orWhereIn('unit_properties.id', $assignedPropertyIds)
                  // Hoặc tickets không gắn property/unit cụ thể (general tickets)
                  ->orWhereNull('tickets.property_id');
            });
            
            // Apply soft delete filters for joined tables
            $query->where(function($q) {
                $q->whereNull('units.deleted_at')
                  ->orWhereNull('tickets.unit_id'); // Allow tickets without units
            });
            $query->where(function($q) {
                $q->whereNull('ticket_properties.deleted_at')
                  ->orWhereNull('tickets.property_id'); // Allow tickets without properties
            });
        } else {
            $query->whereNull('units.deleted_at'); // Uses idx_units_deleted_at_property
            $query->whereNull('ticket_properties.deleted_at'); // Uses idx_properties_deleted_at_org
        }
        
        $query->whereNull('tickets.deleted_at'); // Uses idx_tickets_deleted_at_status

        // Apply priority filter EARLY (before statistics calculation) to ensure it's not affected by other conditions
        // Only filter if priority_id is provided and is a valid positive integer
        $priorityIdValue = $request->input('priority_id');
        if ($priorityIdValue !== null && $priorityIdValue !== '' && $priorityIdValue !== '0') {
            $priorityId = (int) $priorityIdValue;
            // Only apply filter if priority_id is a valid positive integer
            if ($priorityId > 0) {
                $priorityExists = TicketPriority::where('id', $priorityId)->exists();
                if ($priorityExists) {
                    // Use whereRaw to ensure exact match and avoid any join issues
                    $query->whereRaw('tickets.priority_id = ?', [$priorityId]);
                    
                    // Debug logging
                    Log::info('Ticket priority filter applied', [
                        'priority_id' => $priorityId,
                        'request_priority_id' => $priorityIdValue,
                        'request_all' => $request->only(['priority_id', 'status'])
                    ]);
                } else {
                    // Log if priority doesn't exist
                    Log::warning('Invalid priority_id in filter', [
                        'priority_id' => $priorityId,
                        'request_priority_id' => $priorityIdValue,
                        'request_params' => $request->only(['priority_id', 'status'])
                    ]);
                }
            } else {
                // Log when priority_id is 0 or invalid
                Log::info('Ticket priority filter skipped - invalid value', [
                    'request_priority_id' => $priorityIdValue,
                    'casted_priority_id' => $priorityId
                ]);
            }
        }

        // Calculate statistics FIRST from base query (before any filters)
        // Query directly from Ticket model to ensure accurate statistics
        $statsQuery = Ticket::where('organization_id', $organizationId)
            ->whereNull('deleted_at');
        
        // For agent, only count tickets of assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            if ($assignedPropertyIds->isEmpty()) {
                $statsQuery->whereRaw('1 = 0'); // No results
            } else {
                $statsQuery->where(function($q) use ($assignedPropertyIds) {
                    $q->whereHas('property', function($q) use ($assignedPropertyIds) {
                        $q->whereIn('properties.id', $assignedPropertyIds);
                    })
                    ->orWhereHas('unit.property', function($q) use ($assignedPropertyIds) {
                        $q->whereIn('properties.id', $assignedPropertyIds);
                    })
                    ->orWhereNull('property_id');
                });
            }
        }
        
        // Count by status using database aggregation for accuracy
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'open' => (int) (clone $statsQuery)->where('status', 'open')->count(),
            'in_progress' => (int) (clone $statsQuery)->where('status', 'in_progress')->count(),
            'resolved' => (int) (clone $statsQuery)->where('status', 'resolved')->count(),
            'closed' => (int) (clone $statsQuery)->where('status', 'closed')->count(),
            'cancelled' => (int) (clone $statsQuery)->where('status', 'cancelled')->count(),
        ];

        // Status filter - uses idx_tickets_deleted_at_status or idx_tickets_org_deleted_status
        if ($request->filled('status')) {
            $query->where('tickets.status', $request->status);
        }

        if ($request->filled('assigned_to')) {
            $query->where('tickets.assigned_to', $request->assigned_to);
        }

        if ($request->filled('created_by')) {
            $query->where('tickets.created_by', $request->created_by);
        }

        if ($request->filled('property_id')) {
            $query->where('tickets.property_id', $request->property_id);
        }

        if ($request->filled('unit_id')) {
            $query->where('tickets.unit_id', $request->unit_id); // Uses idx_tickets_unit_lease_deleted
        }

        if ($request->filled('lease_id')) {
            $query->where('tickets.lease_id', $request->lease_id); // Uses idx_tickets_unit_lease_deleted
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tickets.title', 'like', "%{$search}%")
                  ->orWhere('tickets.description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['id', 'title', 'status', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $query->orderBy('tickets.' . $sortBy, $sortOrder);
        
        $tickets = $query->paginate(20)->withQueryString();
        
        // Eager load relationships for display
        $tickets->load([
            'property',
            'unit.property',
            'lease',
            'createdBy',
            'assignedTo',
            'priorityRelation',
            'logs.actor'
        ]);

        // Get filter options - optimized with indexes
        $properties = \App\Models\Property::whereNull('deleted_at')
            ->where(function($q) use ($organizationId, $canViewAll, $user) {
                if ($organizationId) {
                    $q->where('organization_id', $organizationId);
                }
                if (!$canViewAll) {
                    $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                    $q->whereIn('id', $assignedPropertyIds);
                }
            })
            ->orderBy('name')
            ->get();

        $units = Unit::with('property')
            ->whereNull('deleted_at') // Uses idx_units_deleted_at_status
            ->whereHas('property', function($q) use ($organizationId, $canViewAll, $user) {
                $q->whereNull('deleted_at');
                if ($organizationId) {
                    $q->where('organization_id', $organizationId); // Uses idx_properties_deleted_at_org
                }
                if (!$canViewAll) {
                    $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                    $q->whereIn('properties.id', $assignedPropertyIds);
                }
            })
            ->get();
            
        $leases = Lease::with(['unit.property', 'tenant'])
            ->whereNull('deleted_at') // Uses idx_leases_deleted_at_status
            ->whereHas('unit.property', function($q) use ($organizationId, $canViewAll, $user) {
                $q->whereNull('deleted_at');
                if ($organizationId) {
                    $q->where('organization_id', $organizationId);
                }
                if (!$canViewAll) {
                    $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                    $q->whereIn('properties.id', $assignedPropertyIds);
                }
            })
            ->whereHas('tenant')
            ->get();
        $users = User::where('status', 1)->get();
        $priorities = TicketPriority::orderBy('id')->get();

        // Format stats for statistics-cards component
        $statsFormatted = [
            'total' => [
                'value' => $stats['total'] ?? 0,
                'label' => 'Tổng cộng',
                'icon' => 'fa-list',
                'color' => 'primary',
                'filter' => '',
            ],
            'open' => [
                'value' => $stats['open'] ?? 0,
                'label' => 'Mở',
                'icon' => 'fa-folder-open',
                'color' => 'success',
                'filter' => 'open',
            ],
            'in_progress' => [
                'value' => $stats['in_progress'] ?? 0,
                'label' => 'Đang xử lý',
                'icon' => 'fa-spinner',
                'color' => 'warning',
                'filter' => 'in_progress',
            ],
            'resolved' => [
                'value' => $stats['resolved'] ?? 0,
                'label' => 'Đã giải quyết',
                'icon' => 'fa-check-circle',
                'color' => 'info',
                'filter' => 'resolved',
            ],
            'closed' => [
                'value' => $stats['closed'] ?? 0,
                'label' => 'Đã đóng',
                'icon' => 'fa-archive',
                'color' => 'secondary',
                'filter' => 'closed',
            ],
            'cancelled' => [
                'value' => $stats['cancelled'] ?? 0,
                'label' => 'Đã hủy',
                'icon' => 'fa-times',
                'color' => 'danger',
                'filter' => 'cancelled',
            ],
        ];
        $currentStatus = $request->get('status', '');

        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // If HTMX request, return table partial with statistics cards update via hx-swap-oob
        if ($isHtmx) {
            $tableHtml = view('staff.work.tickets.partials.table', compact('tickets', 'sortBy', 'sortOrder'))->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => $currentStatus,
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'tickets-table-container',
                'action' => route('staff.tickets.index'),
                'columns' => 6
            ])->render();
            
            // Return table HTML with statistics cards update via hx-swap-oob
            $html = $tableHtml . "\n<div id='statistics-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
            
            return response($html)
                ->header('HX-Push-Url', $request->fullUrl());
        }
        
        // Legacy AJAX support (for backward compatibility)
        if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
            return response()->json([
                'success' => true,
                'table_html' => view('staff.work.tickets.partials.table', compact('tickets', 'sortBy', 'sortOrder'))->render(),
                'stats_html' => view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => $currentStatus,
                    'filterKey' => 'status',
                    'onFilterClick' => 'filterByStatus',
                    'onClearClick' => 'clearAllFilters',
                    'columns' => 6
                ])->render(),
            ]);
        }

        return view('staff.work.tickets.index', [
            'tickets' => $tickets,
            'properties' => $properties,
            'units' => $units,
            'leases' => $leases,
            'users' => $users,
            'priorities' => $priorities,
            'stats' => $stats ?? [
                'total' => 0,
                'open' => 0,
                'in_progress' => 0,
                'resolved' => 0,
                'closed' => 0,
                'cancelled' => 0,
            ],
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'statsFormatted' => $statsFormatted,
            'currentStatus' => $currentStatus
        ]);
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('work.ticket.create', 'Bạn không có quyền tạo ticket.');

        // Check if user can view all tickets or only own tickets
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('work.ticket');
        $organizationId = $this->getCurrentOrganizationId();

        // Get properties, units and leases based on capability
        if ($canViewAll) {
            // Manager sees all properties, units and leases in organization
            $properties = \App\Models\Property::whereNull('deleted_at')
                ->where(function($q) use ($organizationId) {
                    if ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    }
                })
                ->orderBy('name')
                ->get();

            $units = Unit::with('property')
                ->whereNull('deleted_at')
                ->whereHas('property', function($q) use ($organizationId) {
                    $q->whereNull('deleted_at');
                    if ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    }
                })
                ->get();
            
            $leases = Lease::with(['unit.property', 'tenant'])
                ->whereNull('deleted_at')
                ->whereHas('unit.property', function($q) use ($organizationId) {
                    $q->whereNull('deleted_at');
                    if ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    }
                })
                ->whereHas('tenant')
                ->get();
        } else {
            // Agent only sees assigned properties, units and leases
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            
            $properties = \App\Models\Property::whereNull('deleted_at')
                ->whereIn('id', $assignedPropertyIds)
                ->orderBy('name')
                ->get();
            
            $units = Unit::with('property')
                ->whereNull('deleted_at')
                ->whereIn('property_id', $assignedPropertyIds)
                ->whereHas('property', function($q) {
                    $q->whereNull('deleted_at');
                })
                ->get();
            
            $leases = Lease::with(['unit.property', 'tenant'])
                ->whereNull('deleted_at')
                ->whereHas('unit', function($q) use ($assignedPropertyIds) {
                    $q->whereIn('property_id', $assignedPropertyIds);
                })
                ->whereHas('tenant')
                ->get();
        }
        
        $users = User::where('status', 1)->get();
        $priorities = TicketPriority::orderBy('id')->get();

        // Get pre-filled values from query parameters
        $prefilledPropertyId = $request->get('property_id');
        $prefilledUnitId = $request->get('unit_id');
        $prefilledLeaseId = $request->get('lease_id');

        return view('staff.work.tickets.create', compact(
            'properties',
            'units',
            'leases',
            'users',
            'priorities',
            'prefilledPropertyId',
            'prefilledUnitId',
            'prefilledLeaseId'
        ));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('work.ticket.create', 'Bạn không có quyền tạo ticket.');

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority_id' => 'required|exists:ticket_priorities,id',
            'property_id' => 'nullable|exists:properties,id',
            'unit_id' => 'nullable|exists:units,id',
            'lease_id' => 'nullable|exists:leases,id',
            'assigned_to' => 'nullable|exists:users,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // For agent, check permissions
        $canViewAll = $this->canViewAll('work.ticket');
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            
            if ($request->filled('property_id') && !$assignedPropertyIds->contains($request->property_id)) {
                return back()->withInput()->with('error', 'Bạn không có quyền tạo ticket cho bất động sản này.');
            }
            
            if ($request->filled('unit_id')) {
                $unit = Unit::where('id', $request->unit_id)
                    ->whereIn('property_id', $assignedPropertyIds)
                    ->first();
                
                if (!$unit) {
                    return back()->withInput()->with('error', 'Bạn không có quyền tạo ticket cho phòng này.');
                }
            }
        }

        try {
            DB::beginTransaction();

            $organizationId = $this->getCurrentOrganizationId();
            
            // Determine property_id: from request, or from unit, or null
            $propertyId = $request->property_id;
            $propertySelectedDirectly = $request->filled('property_id'); // Check if property was selected directly
            if (!$propertyId && $request->filled('unit_id')) {
                $unit = Unit::find($request->unit_id);
                $propertyId = $unit?->property_id;
            }
            
            // Determine assigned_to: logic tự động fill người tạo/quản lý
            // CHỈ chạy khi property được chọn trực tiếp (không phải lấy từ unit)
            $assignedTo = $request->assigned_to;
            $createdBy = Auth::id();
            
            // Mặc định fill người tạo nếu chưa có assigned_to
            if (!$assignedTo) {
                $assignedTo = $createdBy;
            }
            
            // Chỉ tự động cập nhật khi: property được chọn trực tiếp
            if ($propertySelectedDirectly && $propertyId) {
                $property = \App\Models\Property::find($propertyId);
                if ($property) {
                    // Lấy người quản lý mới nhất từ properties_user (role_key = 'manager', sắp xếp theo updated_at DESC)
                    $latestManager = DB::table('properties_user')
                        ->where('property_id', $propertyId)
                        ->where('role_key', 'manager')
                        ->whereNull('deleted_at')
                        ->orderBy('updated_at', 'desc')
                        ->first();
                    
                    if ($latestManager) {
                        $managerUserId = $latestManager->user_id;
                        // Kiểm tra người tạo có phải quản lý property không
                        if ($managerUserId == $createdBy) {
                            // Người tạo là quản lý property → giữ nguyên
                            $assignedTo = $createdBy;
                        } else {
                            // Người tạo không phải quản lý → chuyển sang quản lý mới nhất
                            $assignedTo = $managerUserId;
                        }
                    } else {
                        // Nếu property chưa phân người quản lý -> giữ nguyên người tạo
                        $assignedTo = $createdBy;
                    }
                }
            }
            
            $ticket = Ticket::create([
                'organization_id' => $organizationId,
                'property_id' => $propertyId,
                'unit_id' => $request->unit_id,
                'lease_id' => $request->lease_id,
                'created_by' => $createdBy,
                'assigned_to' => $assignedTo,
                'title' => $request->title,
                'description' => $request->description,
                'priority_id' => $request->priority_id,
                'status' => 'open',
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
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    // Attach document to ticket using the new many-to-many relationship
                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error uploading ticket image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }

            // Create initial log
            $log = new TicketLog([
                'ticket_id' => $ticket->id,
                'actor_id' => Auth::id(),
                'action' => 'created',
                'detail' => 'Ticket được tạo mới',
            ]);
            $log->created_at = now();
            $log->save();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket đã được tạo thành công!',
                    'redirect' => route('staff.tickets.show', $ticket->id)
                ]);
            }

            return redirect()->route('staff.tickets.show', $ticket->id)
                ->with('success', 'Ticket đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo ticket: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi tạo ticket: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has work.access capability
        $hasWorkAccess = $this->checkCapability('work.access');
        if (!$hasWorkAccess) {
            abort(403, 'Bạn không có quyền truy cập module Công việc.');
        }
        
        $ticket = Ticket::with([
            'unit.property',
            'lease.tenant',
            'createdBy',
            'assignedTo',
            'priorityRelation',
            'logs.actor',
            'logs.linkedInvoice',
            'logs.companyInvoice',
            'logs.vendor',
            'documents.uploader'
        ])->findOrFail($id);

        // For agent, check if ticket belongs to assigned properties
        $canViewAll = $this->canViewAll('work.ticket');
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            $hasAccess = false;
            
            // Ticket không có unit (general ticket) hoặc unit thuộc assigned properties
            if (!$ticket->unit_id || ($ticket->unit && $assignedPropertyIds->contains($ticket->unit->property_id))) {
                $hasAccess = true;
            }
            
            if (!$hasAccess) {
                abort(403, 'Bạn không có quyền xem ticket này.');
            }
        }

        $vendors = \App\Models\Vendor::select('id','name')->get();

        return view('staff.work.tickets.show', compact('ticket', 'vendors'));
    }

    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('work.ticket.update', 'Bạn không có quyền cập nhật ticket.');
        
        $ticket = Ticket::findOrFail($id);
        
        // For agent, check if ticket belongs to assigned properties
        $canViewAll = $this->canViewAll('work.ticket');
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            $hasAccess = false;
            
            // Ticket có property thuộc assigned properties
            if ($ticket->property_id && $assignedPropertyIds->contains($ticket->property_id)) {
                $hasAccess = true;
            }
            // Hoặc ticket không có property nhưng có unit thuộc assigned properties
            elseif (!$ticket->property_id && $ticket->unit_id && $ticket->unit && $assignedPropertyIds->contains($ticket->unit->property_id)) {
                $hasAccess = true;
            }
            // Hoặc ticket không có property và unit (general ticket)
            elseif (!$ticket->property_id && !$ticket->unit_id) {
                $hasAccess = true;
            }
            
            if (!$hasAccess) {
                abort(403, 'Bạn không có quyền chỉnh sửa ticket này.');
            }
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        // Get properties, units and leases based on capability
        if ($canViewAll) {
            // Manager sees all properties, units and leases in organization
            $properties = \App\Models\Property::whereNull('deleted_at')
                ->where(function($q) use ($organizationId) {
                    if ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    }
                })
                ->orderBy('name')
                ->get();

            $units = Unit::with('property')
                ->whereNull('deleted_at')
                ->whereHas('property', function($q) use ($organizationId) {
                    $q->whereNull('deleted_at');
                    if ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    }
                })
                ->get();
            
            $leases = Lease::with(['unit.property', 'tenant'])
                ->whereNull('deleted_at')
                ->whereHas('unit.property', function($q) use ($organizationId) {
                    $q->whereNull('deleted_at');
                    if ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    }
                })
                ->whereHas('tenant')
                ->get();
        } else {
            // Agent only sees assigned properties, units and leases
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            
            $properties = \App\Models\Property::whereNull('deleted_at')
                ->whereIn('id', $assignedPropertyIds)
                ->orderBy('name')
                ->get();
            
            $units = Unit::with('property')
                ->whereNull('deleted_at')
                ->whereIn('property_id', $assignedPropertyIds)
                ->whereHas('property', function($q) {
                    $q->whereNull('deleted_at');
                })
                ->get();
            
            $leases = Lease::with(['unit.property', 'tenant'])
                ->whereNull('deleted_at')
                ->whereHas('unit', function($q) use ($assignedPropertyIds) {
                    $q->whereIn('property_id', $assignedPropertyIds);
                })
                ->whereHas('tenant')
                ->get();
        }
        
        $users = User::where('status', 1)->get();
        $priorities = TicketPriority::orderBy('id')->get();

        return view('staff.work.tickets.edit', compact(
            'ticket',
            'properties',
            'units',
            'leases',
            'users',
            'priorities'
        ));
    }

    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('work.ticket.update', 'Bạn không có quyền cập nhật ticket.');

        $ticket = Ticket::findOrFail($id);
        
        // For agent, check if ticket belongs to assigned properties
        $canViewAll = $this->canViewAll('work.ticket');
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            $hasAccess = false;
            
            // Ticket có property thuộc assigned properties
            if ($ticket->property_id && $assignedPropertyIds->contains($ticket->property_id)) {
                $hasAccess = true;
            }
            // Hoặc ticket không có property nhưng có unit thuộc assigned properties
            elseif (!$ticket->property_id && $ticket->unit_id && $ticket->unit && $assignedPropertyIds->contains($ticket->unit->property_id)) {
                $hasAccess = true;
            }
            // Hoặc ticket không có property và unit (general ticket)
            elseif (!$ticket->property_id && !$ticket->unit_id) {
                $hasAccess = true;
            }
            
            if (!$hasAccess) {
                abort(403, 'Bạn không có quyền cập nhật ticket này.');
            }
            
            // For agent, check if new property/unit belongs to assigned properties
            if ($request->filled('property_id') && !$assignedPropertyIds->contains($request->property_id)) {
                return back()->withInput()->with('error', 'Bạn không có quyền cập nhật ticket cho bất động sản này.');
            }
            
            if ($request->filled('unit_id')) {
                $unit = Unit::where('id', $request->unit_id)
                    ->whereIn('property_id', $assignedPropertyIds)
                    ->first();
                
                if (!$unit) {
                    return back()->withInput()->with('error', 'Bạn không có quyền cập nhật ticket cho phòng này.');
                }
            }
        }

        // Prepare data - convert empty strings to null for nullable fields and convert IDs to integers
        $data = $request->all();
        
        // Convert IDs to integers
        if (isset($data['priority_id'])) {
            $data['priority_id'] = (int) $data['priority_id'];
        }
        if (isset($data['property_id']) && $data['property_id'] !== '') {
            $data['property_id'] = (int) $data['property_id'];
        } elseif (isset($data['property_id']) && $data['property_id'] === '') {
            $data['property_id'] = null;
        }
        if (isset($data['unit_id']) && $data['unit_id'] !== '') {
            $data['unit_id'] = (int) $data['unit_id'];
        } elseif (isset($data['unit_id']) && $data['unit_id'] === '') {
            $data['unit_id'] = null;
        }
        if (isset($data['lease_id']) && $data['lease_id'] !== '') {
            $data['lease_id'] = (int) $data['lease_id'];
        } elseif (isset($data['lease_id']) && $data['lease_id'] === '') {
            $data['lease_id'] = null;
        }
        if (isset($data['assigned_to']) && $data['assigned_to'] !== '') {
            $data['assigned_to'] = (int) $data['assigned_to'];
        } elseif (isset($data['assigned_to']) && $data['assigned_to'] === '') {
            $data['assigned_to'] = null;
        }
        if (isset($data['description']) && $data['description'] === '') {
            $data['description'] = null;
        }
        
        // Determine property_id: from request, or from unit, or keep existing
        if (!isset($data['property_id']) || $data['property_id'] === null) {
            if (isset($data['unit_id']) && $data['unit_id']) {
                $unit = Unit::find($data['unit_id']);
                $data['property_id'] = $unit?->property_id;
            } else {
                $data['property_id'] = $ticket->property_id; // Keep existing
            }
        }
        
        // Merge processed data back to request
        $request->merge($data);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority_id' => 'required|exists:ticket_priorities,id',
            'status' => 'required|in:open,in_progress,resolved,closed,cancelled',
            'property_id' => 'nullable|exists:properties,id',
            'unit_id' => 'nullable|exists:units,id',
            'lease_id' => 'nullable|exists:leases,id',
            'assigned_to' => 'nullable|exists:users,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $ticket->status;
            $oldAssignedTo = $ticket->assigned_to;

            // Handle image upload
            $updateData = [
                'title' => $request->title,
                'description' => $request->description ?: null,
                'priority_id' => $request->priority_id,
                'status' => $request->status,
                'property_id' => $data['property_id'] ?? null,
                'unit_id' => $request->unit_id ?: null,
                'lease_id' => $request->lease_id ?: null,
                'assigned_to' => $request->assigned_to ?: null,
            ];
            
            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                try {
                    // Delete old primary image if exists
                    $oldPrimaryImage = $ticket->documents()
                        ->where('document_type', 'image')
                        ->where('is_primary', true)
                        ->first();
                    
                    if ($oldPrimaryImage) {
                        // Delete file from storage (lưu trực tiếp vào public/storage)
                        $filePath = $oldPrimaryImage->getRawOriginal('file_url');
                        $fullPath = public_path('storage/' . $filePath);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                        // Delete document record
                        $oldPrimaryImage->delete();
                    }

                    // Upload new image using ImageService
                    $file = $request->file('image');
                    $uploadedFile = $this->imageService->uploadFile($file, 'tickets', 'ticket-documents');

                    $document = \App\Models\Document::create([
                        'owner_type' => Ticket::class,
                        'owner_id' => $ticket->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'is_primary' => true,
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    // Attach document to ticket using the new many-to-many relationship
                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error uploading ticket image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }
            
            $ticket->update($updateData);

            // Log changes
            $changes = [];
            if ($oldStatus !== $request->status) {
                $changes[] = "Trạng thái: {$oldStatus} → {$request->status}";
            }
            if ($oldAssignedTo != $request->assigned_to) {
                $oldUser = $oldAssignedTo ? User::find($oldAssignedTo)->full_name : 'Chưa giao';
                $newUser = $request->assigned_to ? User::find($request->assigned_to)->full_name : 'Chưa giao';
                $changes[] = "Người phụ trách: {$oldUser} → {$newUser}";
            }

            if (!empty($changes)) {
                $log = new TicketLog([
                    'ticket_id' => $ticket->id,
                    'actor_id' => Auth::id(),
                    'action' => 'updated',
                    'detail' => 'Cập nhật: ' . implode(', ', $changes),
                ]);
                $log->created_at = now();
                $log->save();
            }

            DB::commit();

            if ($request->expectsJson() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket đã được cập nhật thành công!',
                    'redirect' => route('staff.tickets.show', $ticket->id)
                ]);
            }

            return redirect()->route('staff.tickets.show', $ticket->id)
                ->with('success', 'Ticket đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật ticket: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi cập nhật ticket: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('work.ticket.delete', 'Bạn không có quyền xóa ticket.');

        try {
            $ticket = Ticket::findOrFail($id);
            
            // For agent, check if ticket belongs to assigned properties
            $canViewAll = $this->canViewAll('work.ticket');
            if (!$canViewAll) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                $hasAccess = false;
                
                // Ticket không có unit (general ticket) hoặc unit thuộc assigned properties
                if (!$ticket->unit_id || ($ticket->unit && $assignedPropertyIds->contains($ticket->unit->property_id))) {
                    $hasAccess = true;
                }
                
                if (!$hasAccess) {
                    abort(403, 'Bạn không có quyền xóa ticket này.');
                }
            }
            
            // Soft delete the ticket (trait sẽ tự động set deleted_by)
            $ticket->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.tickets.index')
                ->with('success', 'Ticket đã được xóa thành công!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting ticket: ' . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa ticket: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa ticket: ' . $e->getMessage());
        }
    }

    // Add log to ticket
    public function addLog(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'action' => 'required|string|max:100',
            'detail' => 'nullable|string',
            'cost_amount' => 'nullable|numeric|min:0',
            'cost_note' => 'nullable|string|max:255',
            'charge_to' => 'required|in:none,tenant_deposit,tenant_invoice,landlord,self_pay_vendor',
            'vendor_id' => 'nullable|required_if:charge_to,self_pay_vendor|exists:vendors,id',
            'linked_invoice_id' => 'nullable|exists:invoices,id',
            'warranty_period_days' => 'nullable|integer|min:0|max:3650',
            'warranty_expires_at' => 'nullable|date|after:today',
            'invoice_document' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        try {
            DB::beginTransaction();

            $warrantyExpiresAt = null;
            if ($request->warranty_period_days) {
                $warrantyExpiresAt = now()->addDays((int) $request->warranty_period_days);
            } elseif ($request->warranty_expires_at) {
                $warrantyExpiresAt = \Carbon\Carbon::parse($request->warranty_expires_at);
            }

            $log = new TicketLog([
                'ticket_id' => $ticket->id,
                'actor_id' => Auth::id(),
                'action' => $request->action,
                'detail' => $request->detail,
                'cost_amount' => $request->cost_amount ?? 0,
                'cost_note' => $request->cost_note,
                'charge_to' => $request->charge_to,
                'linked_invoice_id' => $request->linked_invoice_id,
                'vendor_id' => $request->vendor_id,
                'warranty_period_days' => $request->warranty_period_days ? (int) $request->warranty_period_days : null,
                'warranty_expires_at' => $warrantyExpiresAt,
            ]);
            $log->created_at = now();
            
            // Upload invoice document BEFORE saving (so we can attach later)
            $uploadedDocData = null;
            if ($request->hasFile('invoice_document') && $request->charge_to === 'self_pay_vendor') {
                try {
                    $file = $request->file('invoice_document');
                    
                    // Upload using ImageService
                    $uploadedFile = $this->imageService->uploadFile($file, 'tickets', 'invoice-documents');

                    $uploadedDocData = [
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                    ];
                    
                    \Illuminate\Support\Facades\Log::info('>>> Uploaded invoice document', [
                        'file_name' => $uploadedFile['original_name'],
                        'file_size' => $uploadedFile['size'],
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error uploading invoice document: ' . $e->getMessage());
                    // Don't fail the entire request if upload fails
                }
            }
            
            $log->save(); // Observer chạy → tạo CompanyInvoice
            
            \Illuminate\Support\Facades\Log::info('>>> Log saved', [
                'ticket_log_id' => $log->id,
                'has_upload_data' => !is_null($uploadedDocData),
            ]);
            
            // Tự động chuyển status sang "in_progress" nếu ticket đang ở "open" và không phải log đầu tiên "created"
            if ($ticket->status === 'open' && $request->action !== 'created') {
                $ticket->status = 'in_progress';
                $ticket->save();
            }

            DB::commit(); // Commit transaction TRƯỚC khi attach document
            
            \Illuminate\Support\Facades\Log::info('>>> Transaction committed');

            // Attach document to CompanyInvoice AFTER transaction committed
            if ($uploadedDocData && $request->charge_to === 'self_pay_vendor') {
                try {
                    \Illuminate\Support\Facades\Log::info('>>> Searching for CompanyInvoice', [
                        'ticket_log_id' => $log->id,
                    ]);
                    
                    // Find the CompanyInvoice created by Observer (AFTER commit)
                    $companyInvoice = \App\Models\CompanyInvoice::where('ticket_log_id', $log->id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if ($companyInvoice) {
                        $document = \App\Models\Document::create([
                            'owner_type' => \App\Models\CompanyInvoice::class,
                            'owner_id' => $companyInvoice->id,
                            'file_url' => $uploadedDocData['file_url'],
                            'file_name' => $uploadedDocData['file_name'],
                            'mime_type' => $uploadedDocData['mime_type'],
                            'file_size' => $uploadedDocData['file_size'],
                            'document_type' => 'document', // ENUM: image, document, avatar, photo, attachment
                            'description' => 'Hóa đơn từ ticket log',
                            'uploaded_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        
                        \Illuminate\Support\Facades\Log::info('>>> SUCCESS: Attached document to CompanyInvoice', [
                            'company_invoice_id' => $companyInvoice->id,
                            'document_id' => $document->id,
                            'file_name' => $uploadedDocData['file_name'],
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::error('>>> FAILED: CompanyInvoice not found for attaching document', [
                            'ticket_log_id' => $log->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('>>> EXCEPTION: Error attaching document to CompanyInvoice: ' . $e->getMessage(), [
                        'ticket_log_id' => $log->id,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nhật ký đã được thêm thành công!',
                    'log' => $log->load('actor')
                ]);
            }

            return back()->with('success', 'Nhật ký đã được thêm thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi thêm nhật ký: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi thêm nhật ký: ' . $e->getMessage());
        }
    }

    // API method to get units for a property
    public function getUnits($propertyId)
    {
        $units = Unit::where('property_id', $propertyId)->get();
        return response()->json($units);
    }

    // API method to get leases for a unit
    public function getLeases($unitId)
    {
        try {
            $organizationId = $this->getCurrentOrganizationId();
            
            $leases = Lease::where('unit_id', $unitId)
                ->where('organization_id', $organizationId)
                ->with(['tenant'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'leases' => $leases
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Có lỗi xảy ra khi lấy danh sách hợp đồng: ' . $e->getMessage()
            ], 500);
        }
    }

    // API method to get property manager
    public function getPropertyManager($propertyId)
    {
        try {
            $organizationId = $this->getCurrentOrganizationId();
            
            // Verify property belongs to organization
            $property = \App\Models\Property::where('id', $propertyId)
                ->where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->first();
            
            if (!$property) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bất động sản không tồn tại hoặc bạn không có quyền truy cập'
                ], 404);
            }
            
            // Lấy người quản lý mới nhất từ properties_user (role_key = 'manager', sắp xếp theo updated_at DESC)
            $latestManager = DB::table('properties_user')
                ->where('property_id', $propertyId)
                ->where('role_key', 'manager')
                ->whereNull('deleted_at')
                ->orderBy('updated_at', 'desc')
                ->first();
            
            if ($latestManager) {
                $manager = User::find($latestManager->user_id);
                if ($manager) {
                    return response()->json([
                        'success' => true,
                        'manager' => [
                            'id' => $manager->id,
                            'full_name' => $manager->full_name ?? $manager->userProfile->full_name ?? 'N/A'
                        ]
                    ]);
                }
            }
            
            // Không có quản lý
            return response()->json([
                'success' => true,
                'manager' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Có lỗi xảy ra khi lấy người quản lý: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload document to ticket
     */
    public function uploadDocument(Request $request, $id)
    {
        $this->requireCapability('work.ticket.update', 'Bạn không có quyền tải lên tài liệu.');
        
        try {
            $ticket = Ticket::findOrFail($id);

            $validated = $request->validate([
                'document' => 'required|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
                'description' => 'nullable|string|max:500',
            ]);

            $file = $request->file('document');
            
            // Sử dụng ImageService để upload
            $uploadedFile = $this->imageService->uploadFile($file, 'tickets', 'ticket-documents');

            $document = \App\Models\Document::create([
                'owner_type' => Ticket::class,
                'owner_id' => $ticket->id,
                'file_url' => $uploadedFile['original'],
                'file_name' => $uploadedFile['original_name'],
                'mime_type' => $uploadedFile['mime_type'],
                'file_size' => $uploadedFile['size'],
                'uploaded_by' => Auth::id(),
                'created_at' => now(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tải lên tài liệu thành công!',
                    'document' => $document->load('uploader')
                ]);
            }

            return back()->with('success', 'Tải lên tài liệu thành công!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error uploading ticket document: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tải lên tài liệu: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi tải lên tài liệu: ' . $e->getMessage());
        }
    }

    /**
     * Delete document from ticket
     */
    public function deleteDocument(Request $request, $ticketId, $documentId)
    {
        $this->requireCapability('work.ticket.update', 'Bạn không có quyền xóa tài liệu.');
        
        try {
            $ticket = Ticket::findOrFail($ticketId);
            
            $document = \App\Models\Document::where('owner_type', Ticket::class)
                ->where('owner_id', $ticket->id)
                ->findOrFail($documentId);

            // Delete file from storage
            // Delete file from storage (lưu trực tiếp vào public/storage)
            $fullPath = public_path('storage/' . $document->file_url);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }

            // Delete document record
            $document->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Xóa tài liệu thành công!'
                ]);
            }

            return back()->with('success', 'Xóa tài liệu thành công!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting ticket document: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa tài liệu: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa tài liệu: ' . $e->getMessage());
        }
    }

    /**
     * Get maintenance history for a unit
     */
    public function getUnitMaintenanceHistory(Request $request, $unitId)
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();

        $unit = Unit::with('property')->findOrFail($unitId);

        // Verify unit belongs to organization
        if ($organizationId && $unit->property && $unit->property->organization_id != $organizationId) {
            abort(403, 'Bạn không có quyền xem lịch sử bảo trì của phòng này.');
        }

        // Get all tickets for this unit
        $tickets = Ticket::with([
            'createdBy',
            'assignedTo',
            'priorityRelation',
            'logs.actor',
            'logs.vendor'
        ])
        ->where('unit_id', $unitId)
        ->whereNull('deleted_at')
        ->orderBy('created_at', 'desc')
        ->get();

        // Calculate statistics
        $statistics = [
            'total_tickets' => $tickets->count(),
            'open_tickets' => $tickets->where('status', 'open')->count(),
            'in_progress_tickets' => $tickets->where('status', 'in_progress')->count(),
            'resolved_tickets' => $tickets->where('status', 'resolved')->count(),
            'closed_tickets' => $tickets->where('status', 'closed')->count(),
            'total_cost' => $tickets->sum(function($ticket) {
                return $ticket->logs->sum('cost_amount');
            }),
            'cost_by_charge_to' => $tickets->flatMap(function($ticket) {
                return $ticket->logs->where('cost_amount', '>', 0);
            })->groupBy('charge_to')->map(function($logs) {
                return $logs->sum('cost_amount');
            }),
            'warranties' => [
                'active' => $tickets->flatMap(function($ticket) {
                    return $ticket->logs->filter(function($log) {
                        return $log->hasActiveWarranty();
                    });
                })->count(),
                'expiring_soon' => $tickets->flatMap(function($ticket) {
                    return $ticket->logs->filter(function($log) {
                        return $log->warranty_status === 'expiring_soon';
                    });
                })->count(),
                'expired' => $tickets->flatMap(function($ticket) {
                    return $ticket->logs->filter(function($log) {
                        return $log->warranty_status === 'expired';
                    });
                })->count(),
            ],
        ];

        return view('staff.work.tickets.unit-history', compact('unit', 'tickets', 'statistics'));
    }
}
