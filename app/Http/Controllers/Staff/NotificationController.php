<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ChecksCapabilities;

    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user->id;
        
        $search = $request->query('search');
        $type = $request->query('type');
        $status = $request->query('status', 'all'); // Default to 'all'

        $notificationsQuery = Notification::with(['channel', 'auditLog'])
            ->where('to_user_id', $userId)
            ->where('channel_id', 1) // Only in_app notifications for dashboard
            ->orderBy('created_at', 'desc');

        if ($search) {
            $notificationsQuery->where(function ($query) use ($search) {
                $query->where('subject', 'like', '%' . $search . '%')
                      ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        if ($type) {
            // Filter by notification type (based on subject/content keywords)
            $notificationsQuery->where(function ($query) use ($type) {
                $keywords = $this->getKeywordsForType($type);
                foreach ($keywords as $keyword) {
                    $query->orWhere('subject', 'like', '%' . $keyword . '%')
                          ->orWhere('content', 'like', '%' . $keyword . '%');
                }
            });
        }

        if ($status === 'unread') {
            $notificationsQuery->where('status', 'queued');
        } elseif ($status === 'read') {
            $notificationsQuery->where('status', 'sent');
        } elseif ($status === 'important') {
            $notificationsQuery->where(function ($query) {
                $query->where('subject', 'like', '%quá hạn%')
                      ->orWhere('subject', 'like', '%khẩn cấp%')
                      ->orWhere('subject', 'like', '%hết hạn%');
            });
        }

        $notifications = $notificationsQuery->paginate(20);

        $stats = $this->getNotificationStats($userId);
        $unreadCount = Notification::where('to_user_id', $userId)
            ->where('channel_id', 1)
            ->where('status', 'queued')
            ->count();
        
        // Process notifications to add type, icon, and entity link data
        $processedNotifications = $notifications->getCollection()->map(function ($notification) {
            $notification->type = $this->getNotificationType($notification);
            $notification->icon = $this->getNotificationIcon($notification);
            
            // Load audit log relationship
            if ($notification->audit_log_id) {
                $notification->load('auditLog');
                if ($notification->auditLog) {
                    $notification->entity_link = $this->getEntityLink($notification->auditLog);
                    $notification->entity_type = $notification->auditLog->entity_type;
                    $notification->entity_id = $notification->auditLog->entity_id;
                }
            }
            
            return $notification;
        });
        
        // Replace the collection with processed data
        $notifications->setCollection($processedNotifications);
        
        // If HTMX request, check what needs to be updated
        if ($request->header('HX-Request')) {
            $target = $request->header('HX-Target');
            
            // If target is stats-container, return statistics cards
            if ($target === '#stats-container') {
                $statsFormatted = [
                    'total' => [
                        'value' => $stats['total'] ?? 0,
                        'label' => 'Tổng thông báo',
                        'icon' => 'fa-bell',
                        'color' => 'primary',
                        'filter' => '',
                    ],
                    'unread' => [
                        'value' => $stats['unread'] ?? 0,
                        'label' => 'Chưa đọc',
                        'icon' => 'fa-envelope',
                        'color' => 'warning',
                        'filter' => 'unread',
                    ],
                    'read' => [
                        'value' => $stats['read'] ?? 0,
                        'label' => 'Đã đọc',
                        'icon' => 'fa-check-circle',
                        'color' => 'success',
                        'filter' => 'read',
                    ],
                    'important' => [
                        'value' => $stats['important'] ?? 0,
                        'label' => 'Quan trọng',
                        'icon' => 'fa-exclamation-triangle',
                        'color' => 'danger',
                        'filter' => 'important',
                    ],
                ];
                
                return view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => request('status', ''),
                    'filterKey' => 'status',
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'tableContainerId' => 'notifications-table-container',
                    'action' => route('staff.notifications.index'),
                    'columns' => 4
                ]);
            }
            
            // Otherwise, return list partial with out-of-band stats update
            $listHtml = view('staff.notifications.partials.list', compact('notifications', 'unreadCount'))->render();
            
            // Also include stats cards update out-of-band
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng thông báo',
                    'icon' => 'fa-bell',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'unread' => [
                    'value' => $stats['unread'] ?? 0,
                    'label' => 'Chưa đọc',
                    'icon' => 'fa-envelope',
                    'color' => 'warning',
                    'filter' => 'unread',
                ],
                'read' => [
                    'value' => $stats['read'] ?? 0,
                    'label' => 'Đã đọc',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'filter' => 'read',
                ],
                'important' => [
                    'value' => $stats['important'] ?? 0,
                    'label' => 'Quan trọng',
                    'icon' => 'fa-exclamation-triangle',
                    'color' => 'danger',
                    'filter' => 'important',
                ],
            ];
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'notifications-table-container',
                'action' => route('staff.notifications.index'),
                'columns' => 4
            ])->render();
            
            // Return with out-of-band swap for stats
            // HTMX will swap listHtml into tableContainerId, and stats-container with hx-swap-oob separately
            // Remove the outer wrapper div from statsHtml since it's already wrapped in stats-container
            $statsHtmlInner = preg_replace('/^<div[^>]*>|<\/div>$/s', '', $statsHtml);
            return response($listHtml . "\n<div id=\"stats-container\" hx-swap-oob=\"true\">\n" . $statsHtmlInner . "\n</div>");
        }
        
        return view('staff.notifications.index', compact('notifications', 'stats', 'unreadCount', 'type', 'status', 'search'));
    }

    /**
     * Get unread notification count for AJAX requests
     */
    public function getUnreadCount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user->id;
        
        $count = Notification::where('to_user_id', $userId)
            ->where('channel_id', 1)
            ->where('status', 'queued')
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
            'unread_count' => $count
        ]);
    }

    /**
     * Get recent notifications for header dropdown
     */
    public function getRecent()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user->id;
        
        $notifications = Notification::where('to_user_id', $userId)
            ->where('channel_id', 1)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $unreadCount = Notification::where('to_user_id', $userId)
            ->where('channel_id', 1)
            ->where('status', 'queued')
            ->count();

        $processedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'subject' => $notification->subject,
                'content' => $notification->content,
                'status' => $notification->status,
                'created_at' => $notification->created_at,
                'type' => $this->getNotificationType($notification),
                'icon' => $this->getNotificationIcon($notification),
                'is_unread' => $notification->status === 'queued'
            ];
        });

        return response()->json([
            'success' => true,
            'notifications' => $processedNotifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, $id = null)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user->id;
        
        // Support both route parameter and request parameter
        $notificationId = $id ?? $request->input('id');
        
        try {
            $notification = Notification::where('to_user_id', $userId)
                ->where('id', $notificationId)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thông báo không tồn tại'
                ], 404);
            }

            $notification->update([
                'status' => 'sent',
                'sent_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã đánh dấu đã xem'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking notification as read', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating notification'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user->id;
        
        try {
            Notification::where('to_user_id', $userId)
                ->where('channel_id', 1)
                ->where('status', 'queued')
                ->update([
                    'status' => 'sent',
                    'sent_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã đánh dấu tất cả đã xem'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating notifications'
            ], 500);
        }
    }

    /**
     * Show notification detail
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user->id;
        
        $notification = Notification::where('to_user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        // Mark as read when viewing
        if ($notification->status === 'queued') {
            $notification->update([
                'status' => 'sent',
                'sent_at' => now()
            ]);
        }

        // Get entity link from audit_log if available
        $entityLink = null;
        if ($notification->audit_log_id && $notification->auditLog) {
            $entityLink = $this->getEntityLink($notification->auditLog);
        }

        return response()->json([
            'success' => true,
            'notification' => [
                'id' => $notification->id,
                'subject' => $notification->subject,
                'content' => $notification->content,
                'status' => $notification->status,
                'created_at' => $notification->created_at,
                'type' => $this->getNotificationType($notification),
                'icon' => $this->getNotificationIcon($notification),
                'entity_link' => $entityLink,
                'entity_type' => $notification->auditLog->entity_type ?? null,
                'entity_id' => $notification->auditLog->entity_id ?? null,
            ]
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user->id;
        
        try {
            $notification = Notification::where('to_user_id', $userId)
                ->where('id', $id)
                ->first();

            if ($notification) {
                $notification->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xóa thông báo'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting notification', [
                'notification_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting notification'
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    private function getNotificationStats($userId)
    {
        $total = Notification::where('to_user_id', $userId)
            ->where('channel_id', 1)
            ->count();

        $unread = Notification::where('to_user_id', $userId)
            ->where('channel_id', 1)
            ->where('status', 'queued')
            ->count();

        $read = Notification::where('to_user_id', $userId)
            ->where('channel_id', 1)
            ->where('status', 'sent')
            ->count();

        $important = Notification::where('to_user_id', $userId)
            ->where('channel_id', 1)
            ->where(function ($query) {
                $query->where('subject', 'like', '%quá hạn%')
                      ->orWhere('subject', 'like', '%khẩn cấp%')
                      ->orWhere('subject', 'like', '%hết hạn%');
            })
            ->count();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $read,
            'important' => $important
        ];
    }

    /**
     * Get notification type based on content
     */
    private function getNotificationType($notification)
    {
        $subject = strtolower($notification->subject);
        $content = strtolower($notification->content ?? '');

        if (strpos($subject, 'hợp đồng') !== false || strpos($subject, 'contract') !== false) {
            return 'contract';
        } elseif (strpos($subject, 'thanh toán') !== false || strpos($subject, 'payment') !== false) {
            return 'payment';
        } elseif (strpos($subject, 'hết hạn') !== false || strpos($subject, 'expir') !== false) {
            return 'expiry';
        } elseif (strpos($subject, 'báo cáo') !== false || strpos($subject, 'report') !== false) {
            return 'report';
        } elseif (strpos($subject, 'nhân viên') !== false || strpos($subject, 'staff') !== false) {
            return 'staff';
        } else {
            return 'general';
        }
    }

    /**
     * Get notification icon based on type
     */
    private function getNotificationIcon($notification)
    {
        $type = $this->getNotificationType($notification);

        switch ($type) {
            case 'contract':
                return 'fas fa-file-contract';
            case 'payment':
                return 'fas fa-credit-card';
            case 'expiry':
                return 'fas fa-exclamation-triangle';
            case 'report':
                return 'fas fa-chart-line';
            case 'staff':
                return 'fas fa-users';
            default:
                return 'fas fa-bell';
        }
    }

    /**
     * Get keywords for notification type filtering
     */
    private function getKeywordsForType($type)
    {
        switch ($type) {
            case 'contract':
                return ['hợp đồng', 'contract', 'lease'];
            case 'payment':
                return ['thanh toán', 'payment', 'invoice'];
            case 'expiry':
                return ['hết hạn', 'expir', 'sắp hết'];
            case 'report':
                return ['báo cáo', 'report', 'thống kê'];
            case 'staff':
                return ['nhân viên', 'staff', 'employee'];
            default:
                return [];
        }
    }

    /**
     * Get entity link from audit log
     */
    private function getEntityLink($auditLog): ?string
    {
        if (!$auditLog || !$auditLog->entity_type || !$auditLog->entity_id) {
            return null;
        }

        try {
            $entityType = strtolower($auditLog->entity_type);
            $entityId = $auditLog->entity_id;

            // Kiểm tra entity có tồn tại không trước khi tạo link
            if (!$this->entityExists($entityType, $entityId)) {
                return null;
            }

            return match($entityType) {
                'lease' => route('staff.leases.show', $entityId),
                'leaseservice' => $this->getLeaseServiceLink($entityId),
                'invoice' => route('staff.invoices.show', $entityId),
                'invoiceitem' => $this->getInvoiceItemLink($entityId),
                'payment' => route('staff.payments.show', $entityId),
                'ticket' => route('staff.tickets.show', $entityId),
                'ticketlog' => $this->getTicketLogLink($entityId),
                'viewing' => route('staff.viewings.show', $entityId),
                'lead' => route('staff.leads.show', $entityId),
                'property' => route('staff.properties.show', $entityId),
                'unit' => route('staff.units.show', $entityId),
                'review' => route('staff.reviews.show', $entityId),
                'reviewreply' => $this->getReviewReplyLink($entityId),
                'bookingdeposit' => route('staff.booking-deposits.show', $entityId),
                'depositrefund' => route('staff.deposit-refunds.show', $entityId),
                'leaseserviceset' => $this->getLeaseServiceSetLink($entityId),
                'leaseservicesetitem' => $this->getLeaseServiceSetItemLink($entityId),
                'payrollpayslip' => route('staff.payroll-payslips.show', $entityId),
                'payrollcycle' => route('staff.payroll-cycles.show', $entityId),
                'salaryadvance' => route('staff.salary-advances.show', $entityId),
                'salarycontract' => route('staff.salary-contracts.show', $entityId),
                'commissionevent' => route('staff.commission-events.show', $entityId),
                'commissionpolicy' => route('staff.commission-policies.show', $entityId),
                'masterlease' => route('staff.master-leases.show', $entityId),
                'companyinvoice' => route('staff.company-invoices.show', $entityId),
                default => null,
            };
        } catch (\Exception $e) {
            Log::warning('Failed to generate entity link', [
                'audit_log_id' => $auditLog->id ?? null,
                'entity_type' => $auditLog->entity_type ?? null,
                'entity_id' => $auditLog->entity_id ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if entity exists
     */
    private function entityExists(string $entityType, $entityId): bool
    {
        try {
            $modelClass = $this->getModelClass($entityType);
            if (!$modelClass || !class_exists($modelClass)) {
                return false;
            }

            return $modelClass::where('id', $entityId)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get model class from entity type
     */
    private function getModelClass(string $entityType): ?string
    {
        $modelMap = [
            'lease' => \App\Models\Lease::class,
            'leaseservice' => \App\Models\LeaseServiceSet::class, // Updated: LeaseService is deprecated
            'invoice' => \App\Models\Invoice::class,
            'invoiceitem' => \App\Models\InvoiceItem::class,
            'payment' => \App\Models\Payment::class,
            'ticket' => \App\Models\Ticket::class,
            'ticketlog' => \App\Models\TicketLog::class,
            'viewing' => \App\Models\Viewing::class,
            'lead' => \App\Models\Lead::class,
            'property' => \App\Models\Property::class,
            'unit' => \App\Models\Unit::class,
            'review' => \App\Models\Review::class,
            'reviewreply' => \App\Models\ReviewReply::class,
            'bookingdeposit' => \App\Models\BookingDeposit::class,
            'depositrefund' => \App\Models\DepositRefund::class,
            'leaseserviceset' => \App\Models\LeaseServiceSet::class,
            'leaseservicesetitem' => \App\Models\LeaseServiceSetItem::class,
            'payrollpayslip' => \App\Models\PayrollPayslip::class,
            'payrollcycle' => \App\Models\PayrollCycle::class,
            'salaryadvance' => \App\Models\SalaryAdvance::class,
            'salarycontract' => \App\Models\SalaryContract::class,
            'commissionevent' => \App\Models\CommissionEvent::class,
            'commissionpolicy' => \App\Models\CommissionPolicy::class,
            'masterlease' => \App\Models\MasterLease::class,
            'companyinvoice' => \App\Models\CompanyInvoice::class,
        ];

        return $modelMap[strtolower($entityType)] ?? null;
    }

    /**
     * Get lease service set link (redirect to lease service settings page)
     * DEPRECATED: Use getLeaseServiceSetLink instead
     */
    private function getLeaseServiceLink($leaseServiceSetId): ?string
    {
        try {
            // LeaseService is deprecated, redirect to lease service settings page
            return route('staff.lease-service-settings.index');
        } catch (\Exception $e) {
            // Ignore
        }
        return null;
    }

    /**
     * Get link to lease service set detail page
     */
    private function getLeaseServiceSetLink($leaseServiceSetId): ?string
    {
        try {
            // Check if lease service set exists
            $leaseServiceSet = \App\Models\LeaseServiceSet::find($leaseServiceSetId);
            if ($leaseServiceSet) {
                return route('staff.lease-service-settings.sets.show', $leaseServiceSetId);
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return null;
    }

    /**
     * Get link to lease service set from lease service set item
     */
    private function getLeaseServiceSetItemLink($leaseServiceSetItemId): ?string
    {
        try {
            $leaseServiceSetItem = \App\Models\LeaseServiceSetItem::find($leaseServiceSetItemId);
            if ($leaseServiceSetItem && $leaseServiceSetItem->lease_service_set_id) {
                // Link về lease service set chủ
                return route('staff.lease-service-settings.sets.show', $leaseServiceSetItem->lease_service_set_id);
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return null;
    }

    /**
     * Get link to invoice from invoice item
     */
    private function getInvoiceItemLink($invoiceItemId): ?string
    {
        try {
            $invoiceItem = \App\Models\InvoiceItem::find($invoiceItemId);
            if ($invoiceItem && $invoiceItem->invoice_id) {
                return route('staff.invoices.show', $invoiceItem->invoice_id);
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return null;
    }

    /**
     * Get link to ticket from ticket log
     */
    private function getTicketLogLink($ticketLogId): ?string
    {
        try {
            $ticketLog = \App\Models\TicketLog::find($ticketLogId);
            if ($ticketLog && $ticketLog->ticket_id) {
                return route('staff.tickets.show', $ticketLog->ticket_id);
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return null;
    }

    /**
     * Get link to review from review reply
     */
    private function getReviewReplyLink($reviewReplyId): ?string
    {
        try {
            $reviewReply = \App\Models\ReviewReply::find($reviewReplyId);
            if ($reviewReply && $reviewReply->review_id) {
                return route('staff.reviews.show', $reviewReply->review_id);
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return null;
    }
}
