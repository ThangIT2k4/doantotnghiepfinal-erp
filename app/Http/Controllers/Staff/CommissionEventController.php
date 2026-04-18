<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\ErrorHelper;
use App\Http\Controllers\Controller;
use App\Models\CommissionEvent;
use App\Models\CommissionPolicy;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\User;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionEventController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;
    
    /**
     * Get commission event statuses
     */
    private function getStatuses(): array
    {
        return [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'paid' => 'Đã thanh toán',
            'reversed' => 'Đã hoàn',
            'cancelled' => 'Đã hủy'
        ];
    }

    /**
     * Get trigger events
     */
    private function getTriggerEvents(): array
    {
        return [
            'deposit_paid' => 'Thanh toán cọc',
            'lease_signed' => 'Ký hợp đồng',
            'invoice_paid' => 'Thanh toán hóa đơn',
            'viewing_done' => 'Hoàn thành xem phòng'
        ];
    }
    
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has finance.access capability
        $hasFinanceAccess = $this->checkCapability('finance.access');
        if (!$hasFinanceAccess) {
            abort(403, 'Bạn không có quyền truy cập module Finance.');
        }
        
        // Check capability - manager can view all, agent can only view their own
        $this->requireCapability('finance.commission.view', 'Bạn không có quyền xem Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all commission events or only own events
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('finance.commission');
        
        $query = CommissionEvent::with(['policy', 'agent', 'lease.tenant', 'unit.property'])
            ->where('organization_id', $organizationId);

        // Tự động filter theo ownership nếu agent chỉ có view_own
        if ($this->shouldFilterByOwnership('finance.commission')) {
            $query->where('agent_id', $user->id);
        }

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('agent', function($agentQuery) use ($search) {
                    $agentQuery->whereHas('userProfile', function($profileQuery) use ($search) {
                        $profileQuery->where('full_name', 'like', "%{$search}%");
                    });
                })
                ->orWhereHas('policy', function($policyQuery) use ($search) {
                    $policyQuery->where('title', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trigger_event')) {
            $query->where('trigger_event', $request->trigger_event);
        }

        if ($request->filled('agent_id')) {
            // For agent, only allow filtering by their own ID
            if (!$canViewAll && $request->agent_id != $user->id) {
                abort(403, 'Bạn không có quyền xem Commission Events của người khác.');
            }
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->filled('policy_id')) {
            $query->where('policy_id', $request->policy_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('occurred_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('occurred_at', '<=', $request->date_to);
        }

        // Get events with sorting
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['id', 'occurred_at', 'commission_total', 'status', 'trigger_event', 'created_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $events = $query->orderBy($sortBy, $sortOrder)->paginate(20)->withQueryString();

        // Get filter options
        // Get only managers and agents (exclude tenants, admin, landlord)
        $agents = User::with('userProfile')
            ->whereHas('organizationUsers', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                  ->where('status', 'active')
                  ->whereNull('deleted_at')
                  ->whereHas('role', function($roleQuery) {
                      // Chỉ include agent và manager roles
                      $roleQuery->whereIn('key_code', ['agent', 'manager']);
                  });
            })
            ->whereDoesntHave('userRoles', function($q) {
                $q->where('key_code', 'tenant'); // Exclude tenants
            })
            ->whereNull('deleted_at')
            ->get()
            ->sortBy(function($user) {
                return $user->userProfile->full_name ?? $user->full_name ?? $user->email ?? '';
            })
            ->values();

        $policies = CommissionPolicy::where('organization_id', $organizationId)
            ->where('active', true)
            ->get();

        $statuses = $this->getStatuses();
        $triggerEvents = $this->getTriggerEvents();
        
        // Calculate TOTAL statistics (NOT filtered - always show all)
        // Query directly from CommissionEvent model to ensure accurate statistics
        $statsQuery = CommissionEvent::where('organization_id', $organizationId)->whereNull('deleted_at');
        
        // For agent, only show their own events
        if (!$canViewAll) {
            $statsQuery->where('agent_id', $user->id);
        }
        
        // Calculate statistics FIRST from base query (before any filters)
        $totalStats = [
            'total_events' => (clone $statsQuery)->count(),
            'total_commission' => (clone $statsQuery)->sum('commission_total'),
        ];
        
        // Calculate status statistics (NOT filtered - always show all)
        $statusStats = [
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
            'paid' => (clone $statsQuery)->where('status', 'paid')->count(),
            'reversed' => (clone $statsQuery)->where('status', 'reversed')->count(),
            'cancelled' => (clone $statsQuery)->where('status', 'cancelled')->count(),
        ];
        
        // Calculate commission by status (NOT filtered - always show all)
        $filteredStats = [
            'paid_commission' => (clone $statsQuery)->where('status', 'paid')->sum('commission_total'),
            'pending_commission' => (clone $statsQuery)->where('status', 'pending')->sum('commission_total'),
            'approved_commission' => (clone $statsQuery)->where('status', 'approved')->sum('commission_total'),
        ];
        
        // Combine total and filtered stats
        $stats = array_merge($totalStats, $filteredStats);

        // Format stats for statistics-cards component
        // Use $totalStats for "Tổng cộng" stats (not filtered)
        // Use $statusStats and $filteredStats for status-based stats (filtered)
        $statsFormatted = [
            'total' => [
                'value' => $totalStats['total_events'] ?? 0,
                'label' => 'Tổng sự kiện',
                'icon' => 'fa-chart-line',
                'color' => 'primary',
                'filter' => '',
            ],
            'pending' => [
                'value' => $statusStats['pending'] ?? 0,
                'label' => 'Chờ duyệt',
                'icon' => 'fa-clock',
                'color' => 'warning',
                'filter' => 'pending',
            ],
            'approved' => [
                'value' => $statusStats['approved'] ?? 0,
                'label' => 'Đã duyệt',
                'icon' => 'fa-check-circle',
                'color' => 'info',
                'filter' => 'approved',
            ],
            'paid' => [
                'value' => $statusStats['paid'] ?? 0,
                'label' => 'Đã thanh toán',
                'icon' => 'fa-money-bill-wave',
                'color' => 'success',
                'filter' => 'paid',
            ],
            'total_commission' => [
                'value' => $totalStats['total_commission'] ?? 0,
                'label' => 'Tổng hoa hồng (VNĐ)',
                'icon' => 'fa-coins',
                'color' => 'success',
                'filter' => '',
                'format' => 'currency', // Special format for currency
            ],
            'paid_commission' => [
                'value' => $filteredStats['paid_commission'] ?? 0,
                'label' => 'Đã thanh toán (VNĐ)',
                'icon' => 'fa-check-double',
                'color' => 'success',
                'filter' => 'paid',
                'format' => 'currency', // Special format for currency
            ],
        ];
        
        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // If HTMX request, return table partial with statistics cards update via hx-swap-oob
        if ($isHtmx) {
            $tableHtml = view('staff.finance.commission-events.partials.table', [
                'events' => $events,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'commission-events-table-container',
                'action' => route('staff.commission-events.index'),
                'columns' => 6
            ])->render();
            
            // Return table HTML with statistics cards update via hx-swap-oob
            $html = $tableHtml . "\n<div id='statistics-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
            
            return response($html)
                ->header('HX-Push-Url', $request->fullUrl());
        }
        
        // Legacy AJAX support (for backward compatibility)
        if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
            $tableHtml = view('staff.finance.commission-events.partials.table', [
                'events' => $events,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'filterByStatus',
                'onClearClick' => 'clearAllFilters',
                'columns' => 6
            ])->render();
            
            return response()->json([
                'success' => true,
                'html' => $tableHtml,
                'table_html' => $tableHtml, // Also provide table_html for compatibility
                'stats_html' => $statsHtml,
                'pagination' => [
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                ]
            ]);
        }

        return view('staff.finance.commission-events.index', compact('events', 'agents', 'policies', 'statuses', 'triggerEvents', 'stats', 'statusStats', 'totalStats', 'filteredStats', 'sortBy', 'sortOrder', 'statsFormatted'));
    }

    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create commission events
        $this->requireCapability('finance.commission.create', 'Bạn không có quyền tạo Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Get only managers and agents (exclude tenants, admin, landlord)
        $agents = User::with('userProfile')
            ->whereHas('organizationUsers', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                  ->where('status', 'active')
                  ->whereNull('deleted_at')
                  ->whereHas('role', function($roleQuery) {
                      // Chỉ include agent và manager roles
                      $roleQuery->whereIn('key_code', ['agent', 'manager']);
                  });
            })
            ->whereDoesntHave('userRoles', function($q) {
                $q->where('key_code', 'tenant'); // Exclude tenants
            })
            ->whereNull('deleted_at')
            ->get()
            ->sortBy(function($user) {
                return $user->userProfile->full_name ?? $user->full_name ?? $user->email ?? '';
            })
            ->values();

        $policies = CommissionPolicy::where('organization_id', $organizationId)
            ->where('active', true)
            ->get();

        $leases = Lease::where('organization_id', $organizationId)
            ->with(['tenant', 'unit.property'])
            ->get();

        $triggerEvents = $this->getTriggerEvents();
        $statuses = $this->getStatuses();

        return view('staff.finance.commission-events.create', compact('agents', 'policies', 'leases', 'triggerEvents', 'statuses'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create commission events
        $this->requireCapability('finance.commission.create', 'Bạn không có quyền tạo Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
            $validated = $request->validate([
                'agent_id' => 'required|exists:users,id',
                'policy_id' => 'required|exists:commission_policies,id',
                'trigger_event' => 'required|in:deposit_paid,lease_signed,invoice_paid,viewing_done',
                'occurred_at' => 'required|date',
                'amount_base' => 'required|numeric|min:0',
                'commission_total' => 'nullable|numeric|min:0',
                'lease_id' => [
                    'nullable',
                    'exists:leases,id',
                    function ($attribute, $value, $fail) use ($organizationId, $request) {
                        if ($value) {
                            $lease = Lease::where('id', $value)
                                ->where('organization_id', $organizationId)
                                ->whereNull('deleted_at')
                                ->first();
                            
                            if (!$lease) {
                                $fail('Hợp đồng thuê không tồn tại hoặc không thuộc tổ chức của bạn.');
                                return;
                            }
                            
                            // Nếu có agent_id, kiểm tra lease có thuộc agent đó không
                            if ($request->filled('agent_id') && $lease->agent_id != $request->input('agent_id')) {
                                $fail('Hợp đồng thuê không thuộc về nhân viên đã chọn.');
                            }
                        }
                    },
                ],
                'unit_id' => 'nullable|exists:units,id',
                'status' => 'required|in:pending,approved,paid,reversed,cancelled'
            ]);

            try {
                DB::beginTransaction();

                $policy = CommissionPolicy::findOrFail($validated['policy_id']);
                
                // Verify policy belongs to user's organization
                // Convert both to int for comparison to avoid type mismatch issues
                $policyOrgId = (int) $policy->organization_id;
                $userOrgId = (int) $organizationId;
                
                if ($policyOrgId !== $userOrgId) {
                    Log::warning('Unauthorized access attempt - policy does not belong to user organization', [
                        'user_id' => $user->id,
                        'user_organization_id' => $userOrgId,
                        'policy_id' => $policy->id,
                        'policy_organization_id' => $policyOrgId,
                    ]);
                    abort(403, 'Unauthorized access - policy does not belong to your organization.');
                }

                // Tự động gán agent_id cho agent (không cho phép sửa)
                // Manager có thể gán cho agent khác, Agent phải gán cho chính mình
                $this->enforceAgentId($validated, 'agent_id');

                // Calculate commission if not provided
                $commissionTotal = $validated['commission_total'] ?? null;
                // Convert to float if it's a string
                if ($commissionTotal !== null) {
                    $commissionTotal = (float) $commissionTotal;
                }
                
                // If commission_total is 0, null, or empty, calculate it
                if (!$commissionTotal || $commissionTotal == 0) {
                    if ($policy->calc_type === 'percent') {
                        $commissionTotal = ($validated['amount_base'] * $policy->percent_value) / 100;
                    } elseif ($policy->calc_type === 'flat') {
                        $commissionTotal = $policy->flat_amount;
                    } else {
                        // Default to 0 if calc_type is not recognized
                        $commissionTotal = 0;
                    }
                }

                // Prepare data for creation
                $eventData = [
                    'organization_id' => $organizationId,
                    'agent_id' => $validated['agent_id'],
                    'policy_id' => $validated['policy_id'],
                    'trigger_event' => $validated['trigger_event'],
                    'ref_type' => $validated['lease_id'] ? 'lease' : 'manual',
                    'ref_id' => $validated['lease_id'] ?: 0,
                    'occurred_at' => $validated['occurred_at'],
                    'amount_base' => (float) $validated['amount_base'],
                    'commission_total' => (float) $commissionTotal,
                    'status' => $validated['status'],
                ];
                
                // Only add lease_id and unit_id if they are provided
                if (!empty($validated['lease_id'])) {
                    $eventData['lease_id'] = $validated['lease_id'];
                }
                if (!empty($validated['unit_id'])) {
                    $eventData['unit_id'] = $validated['unit_id'];
                }
                
                Log::info('Creating commission event', [
                    'event_data' => $eventData,
                    'user_id' => $user->id ?? null,
                    'organization_id' => $organizationId
                ]);
                
                try {
                    $commissionEvent = CommissionEvent::create($eventData);
                } catch (\Exception $createException) {
                    // Log detailed error for database/create issues
                    Log::error('Failed to create CommissionEvent record', [
                        'exception_type' => get_class($createException),
                        'message' => $createException->getMessage(),
                        'file' => $createException->getFile(),
                        'line' => $createException->getLine(),
                        'event_data' => $eventData,
                        'user_id' => $user->id ?? null,
                        'organization_id' => $organizationId
                    ]);
                    throw $createException; // Re-throw to be caught by outer catch
                }

            DB::commit();

            // Trigger notification for commission event creation (after commit to avoid transaction rollback)
            // Wrap in try-catch to prevent notification errors from affecting the main operation
            try {
                event(new \App\Events\CommissionEventNotification($commissionEvent, 'created'));
            } catch (\Exception $notificationException) {
                // Log notification error but don't fail the main operation
                Log::warning('Failed to send commission event notification', [
                    'commission_event_id' => $commissionEvent->id,
                    'error' => $notificationException->getMessage(),
                    'file' => $notificationException->getFile(),
                    'line' => $notificationException->getLine(),
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sự kiện hoa hồng đã được tạo thành công!',
                    'data' => $commissionEvent
                ]);
            }

            return redirect()->route('staff.commission-events.index')
                ->with('success', 'Sự kiện hoa hồng đã được tạo thành công!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('Error creating commission event: Validation failed', [
                'errors' => $e->errors(),
                'user_id' => $user->id ?? null,
                'organization_id' => $organizationId ?? null
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withInput()->withErrors($e->errors())->with('error', 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Get safe error message for user display
            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra khi tạo sự kiện hoa hồng. Vui lòng thử lại sau hoặc liên hệ Admin.');
            
            // Log detailed error for debugging
            Log::error('Error creating commission event', [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'organization_id' => $organizationId ?? null,
                'request_data' => $request->except(['_token', 'password', 'password_confirmation'])
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $safeMessage
                ], 500);
            }

            return back()->withInput()->with('error', $safeMessage);
        }
    }

    public function show(CommissionEvent $commissionEvent)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has finance.access capability
        $hasFinanceAccess = $this->checkCapability('finance.access');
        if (!$hasFinanceAccess) {
            abort(403, 'Bạn không có quyền truy cập module Finance.');
        }
        
        // Check capability
        $this->requireCapability('finance.commission.view', 'Bạn không có quyền xem Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        // Convert both to int for comparison to avoid type mismatch issues
        $eventOrgId = (int) $commissionEvent->organization_id;
        $userOrgId = (int) $organizationId;
        
        if ($eventOrgId !== $userOrgId) {
            Log::warning('Unauthorized access attempt to commission event', [
                'user_id' => $user->id,
                'user_organization_id' => $userOrgId,
                'event_id' => $commissionEvent->id,
                'event_organization_id' => $eventOrgId,
                'commission_event_organization_id_raw' => $commissionEvent->organization_id,
                'getCurrentOrganizationId_result' => $organizationId,
            ]);
            abort(403, 'Unauthorized access to commission event.');
        }
        
        // Check if user can view all commission events or only own events
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('finance.commission');
        
        // For agent, only allow viewing their own commission events
        if (!$canViewAll && $commissionEvent->agent_id !== $user->id) {
            abort(403, 'Bạn không có quyền xem Commission Events của người khác.');
        }
        
        $commissionEvent->load([
            'policy', 
            'agent', 
            'lease.tenant', 
            'unit.property', 
            'user',
            'organization'
        ]);

        return view('staff.finance.commission-events.show', compact('commissionEvent'));
    }

    public function edit(CommissionEvent $commissionEvent)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can edit commission events
        $this->requireCapability('finance.commission.update', 'Bạn không có quyền chỉnh sửa Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        // Convert both to int for comparison to avoid type mismatch issues
        $eventOrgId = (int) $commissionEvent->organization_id;
        $userOrgId = (int) $organizationId;
        
        if ($eventOrgId !== $userOrgId) {
            Log::warning('Unauthorized access attempt to commission event', [
                'user_id' => $user->id,
                'user_organization_id' => $userOrgId,
                'event_id' => $commissionEvent->id,
                'event_organization_id' => $eventOrgId,
            ]);
            abort(403, 'Unauthorized access to commission event.');
        }

        $statuses = $this->getStatuses();
        $triggerEvents = $this->getTriggerEvents();

        $policies = CommissionPolicy::where('organization_id', $organizationId)
            ->where('active', true)
            ->get();

        // Get only managers and agents (exclude tenants, admin, landlord)
        $agents = User::with('userProfile')
            ->whereHas('organizationUsers', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                  ->where('status', 'active')
                  ->whereNull('deleted_at')
                  ->whereHas('role', function($roleQuery) {
                      // Chỉ include agent và manager roles
                      $roleQuery->whereIn('key_code', ['agent', 'manager']);
                  });
            })
            ->whereDoesntHave('userRoles', function($q) {
                $q->where('key_code', 'tenant'); // Exclude tenants
            })
            ->whereNull('deleted_at')
            ->get()
            ->sortBy(function($user) {
                return $user->userProfile->full_name ?? $user->full_name ?? $user->email ?? '';
            })
            ->values();

        $leases = Lease::where('organization_id', $organizationId)
            ->with(['tenant', 'unit.property'])
            ->get();

        return view('staff.finance.commission-events.edit', compact(
            'commissionEvent', 
            'statuses', 
            'triggerEvents', 
            'policies', 
            'agents', 
            'leases'
        ));
    }

    public function update(Request $request, CommissionEvent $commissionEvent)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update commission events
        $this->requireCapability('finance.commission.update', 'Bạn không có quyền cập nhật Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        // Convert both to int for comparison to avoid type mismatch issues
        $eventOrgId = (int) $commissionEvent->organization_id;
        $userOrgId = (int) $organizationId;
        
        if ($eventOrgId !== $userOrgId) {
            Log::warning('Unauthorized access attempt to commission event', [
                'user_id' => $user->id,
                'user_organization_id' => $userOrgId,
                'event_id' => $commissionEvent->id,
                'event_organization_id' => $eventOrgId,
            ]);
            abort(403, 'Unauthorized access to commission event.');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,paid,reversed,cancelled',
            'amount_base' => 'required|numeric|min:0',
            'commission_total' => 'required|numeric|min:0',
            'occurred_at' => 'required|date',
            'agent_id' => 'nullable|exists:users,id',
            'policy_id' => 'required|exists:commission_policies,id',
            'lease_id' => 'nullable|exists:leases,id',
            'unit_id' => 'nullable|exists:units,id',
            'note' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Verify policy belongs to user's organization
            if ($validated['policy_id']) {
                $policy = CommissionPolicy::findOrFail($validated['policy_id']);
                $this->checkOrganizationAccess(
                    $policy->organization_id,
                    'Unauthorized access - policy does not belong to your organization.',
                    'commission_policy',
                    $policy->id
                );
            }

            // Tự động gán agent_id cho agent (không cho phép sửa)
            // Manager có thể gán cho agent khác, Agent phải gán cho chính mình
            $this->enforceAgentId($validated, 'agent_id');

            $commissionEvent->update([
                'status' => $validated['status'],
                'amount_base' => $validated['amount_base'],
                'commission_total' => $validated['commission_total'],
                'occurred_at' => $validated['occurred_at'],
                'agent_id' => $validated['agent_id'],
                'policy_id' => $validated['policy_id'],
                'lease_id' => $validated['lease_id'],
                'unit_id' => $validated['unit_id'],
            ]);

            // Trigger notification for commission event update
            event(new \App\Events\CommissionEventNotification($commissionEvent, 'updated'));

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sự kiện hoa hồng đã được cập nhật thành công!',
                    'data' => $commissionEvent
                ]);
            }

            return redirect()->route('staff.commission-events.show', $commissionEvent->id)
                ->with('success', 'Sự kiện hoa hồng đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating commission event: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật sự kiện hoa hồng'
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật sự kiện hoa hồng');
        }
    }

    public function destroy(CommissionEvent $commissionEvent)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can delete commission events
        $this->requireCapability('finance.commission.delete', 'Bạn không có quyền xóa Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        // Convert both to int for comparison to avoid type mismatch issues
        $eventOrgId = (int) $commissionEvent->organization_id;
        $userOrgId = (int) $organizationId;
        
        if ($eventOrgId !== $userOrgId) {
            Log::warning('Unauthorized access attempt to commission event', [
                'user_id' => $user->id,
                'user_organization_id' => $userOrgId,
                'event_id' => $commissionEvent->id,
                'event_organization_id' => $eventOrgId,
            ]);
            abort(403, 'Unauthorized access to commission event.');
        }

        try {
            // Trigger notification for commission event deletion
            event(new \App\Events\CommissionEventNotification($commissionEvent, 'cancelled'));
            
            $commissionEvent->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sự kiện hoa hồng đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.commission-events.index')
                ->with('success', 'Sự kiện hoa hồng đã được xóa thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting commission event: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa sự kiện hoa hồng'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa sự kiện hoa hồng');
        }
    }

    public function approve(CommissionEvent $commissionEvent)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can approve commission events
        $this->requireCapability('finance.commission.approve', 'Bạn không có quyền duyệt Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        // Convert both to int for comparison to avoid type mismatch issues
        $eventOrgId = (int) $commissionEvent->organization_id;
        $userOrgId = (int) $organizationId;
        
        if ($eventOrgId !== $userOrgId) {
            Log::warning('Unauthorized access attempt to commission event', [
                'user_id' => $user->id,
                'user_organization_id' => $userOrgId,
                'event_id' => $commissionEvent->id,
                'event_organization_id' => $eventOrgId,
            ]);
            abort(403, 'Unauthorized access to commission event.');
        }

        try {
            $commissionEvent->update(['status' => 'approved']);

            // Trigger notification for commission event approval
            event(new \App\Events\CommissionEventNotification($commissionEvent, 'approved'));

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sự kiện hoa hồng đã được duyệt!'
                ]);
            }

            return back()->with('success', 'Sự kiện hoa hồng đã được duyệt!');

        } catch (\Exception $e) {
            Log::error('Error approving commission event: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi duyệt sự kiện hoa hồng'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi duyệt sự kiện hoa hồng');
        }
    }

    public function markAsPaid(CommissionEvent $commissionEvent)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can mark commission events as paid
        $this->requireCapability('finance.commission.pay', 'Bạn không có quyền thanh toán Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        // Convert both to int for comparison to avoid type mismatch issues
        $eventOrgId = (int) $commissionEvent->organization_id;
        $userOrgId = (int) $organizationId;
        
        if ($eventOrgId !== $userOrgId) {
            Log::warning('Unauthorized access attempt to commission event', [
                'user_id' => $user->id,
                'user_organization_id' => $userOrgId,
                'event_id' => $commissionEvent->id,
                'event_organization_id' => $eventOrgId,
            ]);
            abort(403, 'Unauthorized access to commission event.');
        }

        try {
            $commissionEvent->update(['status' => 'paid']);

            // Trigger notification for commission event payment
            event(new \App\Events\CommissionEventNotification($commissionEvent, 'paid'));

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sự kiện hoa hồng đã được đánh dấu là đã thanh toán!'
                ]);
            }

            return back()->with('success', 'Sự kiện hoa hồng đã được đánh dấu là đã thanh toán!');

        } catch (\Exception $e) {
            Log::error('Error marking commission event as paid: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi đánh dấu sự kiện hoa hồng'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi đánh dấu sự kiện hoa hồng');
        }
    }

    public function updateStatus(Request $request, CommissionEvent $commissionEvent)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update commission event status
        $this->requireCapability('finance.commission.update', 'Bạn không có quyền cập nhật trạng thái Commission Events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        // Convert both to int for comparison to avoid type mismatch issues
        $eventOrgId = (int) $commissionEvent->organization_id;
        $userOrgId = (int) $organizationId;
        
        if ($eventOrgId !== $userOrgId) {
            Log::warning('Unauthorized access attempt to commission event', [
                'user_id' => $user->id,
                'user_organization_id' => $userOrgId,
                'event_id' => $commissionEvent->id,
                'event_organization_id' => $eventOrgId,
            ]);
            abort(403, 'Unauthorized access to commission event.');
        }

        $request->validate([
            'status' => 'required|in:reversed,cancelled'
        ]);

        try {
            $commissionEvent->update(['status' => $request->status]);

            // Trigger notification for commission event status change
            event(new \App\Events\CommissionEventNotification($commissionEvent, $request->status));

            $statusLabels = [
                'reversed' => 'Hoàn hoa hồng',
                'cancelled' => 'Hủy sự kiện'
            ];

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Sự kiện hoa hồng đã được {$statusLabels[$request->status]} thành công!"
                ]);
            }

            return back()->with('success', "Sự kiện hoa hồng đã được {$statusLabels[$request->status]} thành công!");

        } catch (\Exception $e) {
            Log::error('Error updating commission event status: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật trạng thái sự kiện hoa hồng'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi cập nhật trạng thái sự kiện hoa hồng');
        }
    }

    /**
     * API endpoint để lấy danh sách hợp đồng thuê theo agent_id
     * Dùng để filter hợp đồng khi chọn nhân viên trong form tạo sự kiện hoa hồng
     */
    public function getLeasesByAgent(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            if (!$user) {
                Log::warning('getLeasesByAgent: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa đăng nhập.'
                ], 401);
            }
            
            // Check capability
            try {
                $this->requireCapability('finance.commission.create', 'Bạn không có quyền truy cập.');
            } catch (\Exception $e) {
                Log::warning('getLeasesByAgent: Capability check failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập.'
                ], 403);
            }
            
            $organizationId = $this->getCurrentOrganizationId();
            
            if (!$organizationId) {
                Log::warning('getLeasesByAgent: No organization ID', [
                    'user_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thuộc tổ chức nào.'
                ], 403);
            }

            $agentId = $request->input('agent_id');
            
            try {
                if (!$agentId) {
                    // Nếu không có agent_id, trả về tất cả leases
                    $leases = Lease::where('organization_id', $organizationId)
                        ->whereNull('deleted_at')
                        ->with(['tenant', 'unit.property'])
                        ->get();
                } else {
                    // Validate agent_id exists and belongs to organization
                    $agent = User::where('id', $agentId)
                        ->whereHas('organizationUsers', function($q) use ($organizationId) {
                            $q->where('organization_id', $organizationId)
                              ->where('status', 'active');
                        })
                        ->first();
                    
                    if (!$agent) {
                        Log::warning('getLeasesByAgent: Invalid agent_id', [
                            'agent_id' => $agentId,
                            'organization_id' => $organizationId
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Nhân viên không hợp lệ.'
                        ], 400);
                    }
                    
                    // Filter leases theo agent_id
                    $leases = Lease::where('organization_id', $organizationId)
                        ->where('agent_id', $agentId)
                        ->whereNull('deleted_at')
                        ->with(['tenant', 'unit.property'])
                        ->get();
                }

                // Format leases cho dropdown với error handling
                $formattedLeases = $leases->map(function($lease) {
                    try {
                        $contractNo = $lease->contract_no ?? 'HD' . str_pad($lease->id, 6, '0', STR_PAD_LEFT);
                        
                        // Safe access to relationships
                        $propertyName = 'N/A';
                        $unitCode = 'N/A';
                        
                        if ($lease->unit) {
                            $unitCode = $lease->unit->code ?? ($lease->unit->name ?? 'N/A');
                            
                            if ($lease->unit->property) {
                                $propertyName = $lease->unit->property->name ?? 'N/A';
                            }
                        }
                        
                        $displayText = $contractNo . ' - ' . $propertyName . ' - ' . $unitCode;
                        
                        return [
                            'id' => $lease->id,
                            'text' => $displayText,
                            'rent_amount' => $lease->rent_amount ?? 0,
                            'unit_id' => $lease->unit_id ?? ''
                        ];
                    } catch (\Exception $e) {
                        Log::error('getLeasesByAgent: Error formatting lease', [
                            'lease_id' => $lease->id ?? null,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Return a safe fallback
                        return [
                            'id' => $lease->id ?? 0,
                            'text' => 'HD' . str_pad($lease->id ?? 0, 6, '0', STR_PAD_LEFT) . ' - N/A',
                            'rent_amount' => $lease->rent_amount ?? 0,
                            'unit_id' => $lease->unit_id ?? ''
                        ];
                    }
                })->filter(); // Remove any null values

                return response()->json([
                    'success' => true,
                    'leases' => $formattedLeases->values()
                ]);
                
            } catch (\Exception $e) {
                Log::error('getLeasesByAgent: Database error', [
                    'user_id' => $user->id,
                    'organization_id' => $organizationId,
                    'agent_id' => $agentId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tải danh sách hợp đồng. Vui lòng thử lại sau.'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('getLeasesByAgent: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
            ], 500);
        }
    }
}
