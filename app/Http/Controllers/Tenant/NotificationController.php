<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\Lease;
use App\Models\Invoice;
use App\Models\Viewing;
use App\Models\Ticket;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controller: NotificationController
 * 
 * MỤC ĐÍCH:
 * Quản lý và hiển thị notifications cho tenant (người thuê), bao gồm xem danh sách, đánh dấu đã đọc, 
 * xóa notifications và cài đặt preferences
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. index(): Hiển thị danh sách notifications với filters (type, status, search)
 * 2. markAsRead(): Đánh dấu notification đã đọc
 * 3. markAllAsRead(): Đánh dấu tất cả notifications đã đọc
 * 4. getRecent(): Lấy notifications gần đây cho header dropdown
 * 5. getSettings(): Lấy cài đặt notification preferences
 * 6. updateSettings(): Cập nhật notification preferences
 * 
 * ENDPOINTS:
 * - GET /tenant/notifications: Danh sách notifications
 * - GET /tenant/notifications/unread-count: Số lượng chưa đọc
 * - GET /tenant/notifications/recent: Notifications gần đây
 * - POST /tenant/notifications/{id}/mark-read: Đánh dấu đã đọc
 * - POST /tenant/notifications/mark-all-read: Đánh dấu tất cả đã đọc
 * - GET /tenant/notifications/{id}: Chi tiết notification
 * - DELETE /tenant/notifications/{id}: Xóa notification
 * - GET /tenant/notifications/settings/preferences: Lấy cài đặt
 * - POST /tenant/notifications/settings/preferences: Cập nhật cài đặt
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: Notification (bảng notifications) - Lấy notifications của tenant
 * - Model: Lease (bảng leases) - Lấy danh sách leases của tenant để filter notifications
 * - Model: Invoice, Ticket, Payment, Review, ReviewReply - Lấy IDs để filter notifications
 * - Model: UserNotificationPreference (bảng user_notification_preferences) - Lấy preferences
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng notifications: Cập nhật status (queued → sent) khi đánh dấu đã đọc
 * - Bảng user_notification_preferences: Cập nhật preferences khi user thay đổi cài đặt
 * 
 * LƯU Ý:
 * - Chỉ hiển thị notifications về entities thuộc về tenant (lease, invoice, ticket, payment, review)
 * - Tenant chỉ nhận notifications về dữ liệu của mình (qua lease relationship)
 * - Notifications được filter theo audit_log để đảm bảo chỉ hiển thị đúng dữ liệu của tenant
 */
class NotificationController extends Controller
{
    /**
     * Hiển thị danh sách notifications cho tenant
     * 
     * MỤC ĐÍCH:
     * Hiển thị danh sách notifications của tenant với filters (type, status, search) và pagination
     * 
     * INPUT:
     * - Request: type (payment, contract, appointment, review, maintenance, system), status (all, unread, read, important), search (từ khóa tìm kiếm)
     * - Session: user_id (từ Auth::id())
     * - Database: notifications, leases, invoices, tickets, payments, reviews, review_replies, ticket_logs
     * 
     * OUTPUT:
     * - View: tenant.notifications.index (nếu không phải HTMX request)
     * - HTML: notifications list HTML (nếu là HTMX request) với stats cards và filter section
     * - Data: notifications (paginated), stats (total, unread, read, important), unreadCount
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tenant ID từ session → Dùng để filter notifications
     * 2. Lấy filter parameters (type, status, search) từ request → Dùng để filter notifications
     * 3. Lấy danh sách IDs của entities thuộc tenant (lease, invoice, ticket, payment, review) → Dùng để filter notifications qua audit_log
     * 4. Build query với filters (type, status, search) và filter theo entities của tenant → Lấy notifications phù hợp
     * 5. Tính statistics (total, unread, read, important) → Dùng để hiển thị stats cards
     * 6. Process notifications (thêm type, icon, entity_link) → Dùng để hiển thị
     * 7. Trả về view hoặc HTML (nếu HTMX request)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng notifications: Lấy notifications của tenant với filters
     * - Bảng leases: Lấy danh sách lease IDs của tenant → Dùng để filter notifications về leases
     * - Bảng invoices: Lấy invoice IDs qua lease → Dùng để filter notifications về invoices
     * - Bảng tickets: Lấy ticket IDs qua lease → Dùng để filter notifications về tickets
     * - Bảng payments: Lấy payment IDs qua invoice → Dùng để filter notifications về payments
     * - Bảng reviews: Lấy review IDs qua lease → Dùng để filter notifications về reviews
     * - Bảng review_replies: Lấy review reply IDs qua review → Dùng để filter notifications về review replies
     * - Bảng ticket_logs: Lấy ticket log IDs qua ticket → Dùng để filter notifications về ticket logs
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Chỉ hiển thị notifications về entities thuộc về tenant (qua lease relationship)
     * - Notifications được filter qua audit_log để đảm bảo chỉ hiển thị đúng dữ liệu của tenant
     * - Hỗ trợ HTMX để load động không cần reload trang
     */
    public function index(Request $request)
    {
        $tenantId = Auth::id(); // Lấy ID của tenant đang đăng nhập → Dùng để filter notifications
        
        // Lấy filter parameters từ request → Dùng để filter notifications
        $type = $request->get('type', ''); // Loại notification (payment, contract, appointment, review, maintenance, system)
        $status = $request->get('status', 'all'); // Trạng thái (all, unread, read, important)
        $search = $request->get('search', ''); // Từ khóa tìm kiếm trong subject/content
        
        // Lấy danh sách lease_ids của tenant (bao gồm cả resident) từ TẤT CẢ organizations → Dùng để filter notifications về leases
        $tenantLeaseIds = Lease::getAccessibleLeaseIds($tenantId)->toArray();
        
        // Lấy danh sách invoice_ids của tenant (qua lease) → Dùng để filter notifications về invoices
        $tenantInvoiceIds = Invoice::whereIn('lease_id', $tenantLeaseIds)
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách invoiceitem_ids của tenant (qua invoice) → Dùng để filter notifications về invoice items
        $tenantInvoiceItemIds = DB::table('invoice_items')
            ->whereIn('invoice_id', $tenantInvoiceIds)
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách ticket_ids của tenant (qua lease) → Dùng để filter notifications về tickets
        $tenantTicketIds = Ticket::whereIn('lease_id', $tenantLeaseIds)
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách review_ids của tenant (qua lease) → Dùng để filter notifications về reviews
        $tenantReviewIds = Review::whereIn('lease_id', $tenantLeaseIds)
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách reviewreply_ids của tenant (qua review -> lease) → Dùng để filter notifications về review replies
        $tenantReviewReplyIds = ReviewReply::whereIn('review_id', $tenantReviewIds)
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách payment_ids của tenant (qua invoice) → Dùng để filter notifications về payments
        $tenantPaymentIds = Payment::whereIn('invoice_id', $tenantInvoiceIds)
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách ticketlog_ids của tenant (qua ticket) → Dùng để filter notifications về ticket logs
        $tenantTicketLogIds = DB::table('ticket_logs')
            ->whereIn('ticket_id', $tenantTicketIds)
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách depositrefund_ids của tenant (qua lease) → Dùng để filter notifications về deposit refunds
        $tenantDepositRefundIds = DB::table('deposit_refunds')
            ->whereIn('lease_id', $tenantLeaseIds)
            ->pluck('id')
            ->toArray();
        
        // Build query cho notifications - chỉ lấy notifications về entities của tenant → Đảm bảo tenant chỉ thấy notifications của mình
        $query = Notification::with(['channel', 'auditLog']) // Eager load relationships → Tránh N+1 query
            ->where('to_user_id', $tenantId) // Chỉ lấy notifications gửi cho tenant này → Filter theo user
            ->where('channel_id', 1) // Chỉ lấy in-app notifications (channel_id = 1) → Bỏ qua email notifications
            ->where(function($q) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds, $tenantDepositRefundIds) {
                // Chỉ lấy notifications về entities của tenant thông qua audit_log → Đảm bảo chỉ hiển thị đúng dữ liệu
                $q->whereHas('auditLog', function($subQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds, $tenantDepositRefundIds) {
                    $subQ->where(function($entityQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds, $tenantDepositRefundIds) {
                        // Filter notifications về leases của tenant → Chỉ hiển thị notifications về leases thuộc tenant
                        $entityQ->where(function($leaseQ) use ($tenantLeaseIds) {
                            $leaseQ->where('entity_type', 'lease')
                                ->whereIn('entity_id', $tenantLeaseIds);
                        })
                        // Filter notifications về invoices của tenant → Chỉ hiển thị notifications về invoices thuộc tenant
                        ->orWhere(function($invoiceQ) use ($tenantInvoiceIds) {
                            $invoiceQ->where('entity_type', 'invoice')
                                ->whereIn('entity_id', $tenantInvoiceIds);
                        })
                        // Filter notifications về invoice items của tenant → Chỉ hiển thị notifications về invoice items thuộc tenant
                        ->orWhere(function($invoiceItemQ) use ($tenantInvoiceItemIds) {
                            $invoiceItemQ->where('entity_type', 'invoiceitem')
                                ->whereIn('entity_id', $tenantInvoiceItemIds);
                        })
                        // Filter notifications về payments của tenant → Chỉ hiển thị notifications về payments thuộc tenant
                        ->orWhere(function($paymentQ) use ($tenantPaymentIds) {
                            $paymentQ->where('entity_type', 'payment')
                                ->whereIn('entity_id', $tenantPaymentIds);
                        })
                        // Filter notifications về tickets của tenant → Chỉ hiển thị notifications về tickets thuộc tenant
                        ->orWhere(function($ticketQ) use ($tenantTicketIds) {
                            $ticketQ->where('entity_type', 'ticket')
                                ->whereIn('entity_id', $tenantTicketIds);
                        })
                        // Filter notifications về ticket logs của tenant → Chỉ hiển thị notifications về ticket logs thuộc tenant
                        ->orWhere(function($ticketLogQ) use ($tenantTicketLogIds) {
                            $ticketLogQ->where('entity_type', 'ticketlog')
                                ->whereIn('entity_id', $tenantTicketLogIds);
                        })
                        // Filter notifications về deposit refunds của tenant → Chỉ hiển thị notifications về deposit refunds thuộc tenant
                        ->orWhere(function($depositRefundQ) use ($tenantDepositRefundIds) {
                            $depositRefundQ->where('entity_type', 'depositrefund')
                                ->whereIn('entity_id', $tenantDepositRefundIds);
                        })
                        // Filter notifications về reviews của tenant → Chỉ hiển thị notifications về reviews thuộc tenant
                        ->orWhere(function($reviewQ) use ($tenantReviewIds) {
                            $reviewQ->where('entity_type', 'review')
                                ->whereIn('entity_id', $tenantReviewIds);
                        })
                        // Filter notifications về review replies của tenant → Chỉ hiển thị notifications về review replies thuộc tenant
                        ->orWhere(function($reviewReplyQ) use ($tenantReviewReplyIds) {
                            $reviewReplyQ->where('entity_type', 'reviewreply')
                                ->whereIn('entity_id', $tenantReviewReplyIds);
                        });
                    });
                })
                // Fallback: Nếu không có audit_log (backward compatibility) → Vẫn hiển thị notification cũ không có audit_log
                ->orWhereNull('audit_log_id');
            });
        
        // Áp dụng filter theo type → Chỉ lấy notifications của loại được chọn
        if ($type && in_array($type, ['payment', 'contract', 'appointment', 'review', 'maintenance', 'system'])) {
            $query->where(function($q) use ($type) {
                switch ($type) {
                    case 'payment': // Filter notifications về thanh toán → Chỉ lấy notifications có từ khóa "thanh toán" hoặc "hóa đơn"
                        $q->where(function($subQ) {
                            $subQ->where('subject', 'like', '%thanh toán%')
                                 ->orWhere('content', 'like', '%hóa đơn%');
                        });
                        break;
                    case 'contract': // Filter notifications về hợp đồng → Chỉ lấy notifications có từ khóa "hợp đồng"
                        $q->where(function($subQ) {
                            $subQ->where('subject', 'like', '%hợp đồng%')
                                 ->orWhere('content', 'like', '%hợp đồng%');
                        });
                        break;
                    case 'appointment': // Filter notifications về lịch hẹn → Chỉ lấy notifications có từ khóa "lịch hẹn"
                        $q->where(function($subQ) {
                            $subQ->where('subject', 'like', '%lịch hẹn%')
                                 ->orWhere('content', 'like', '%lịch hẹn%');
                        });
                        break;
                    case 'review': // Filter notifications về đánh giá → Chỉ lấy notifications có từ khóa "đánh giá"
                        $q->where(function($subQ) {
                            $subQ->where('subject', 'like', '%đánh giá%')
                                 ->orWhere('content', 'like', '%đánh giá%');
                        });
                        break;
                    case 'maintenance': // Filter notifications về sửa chữa → Chỉ lấy notifications có từ khóa "sửa chữa"
                        $q->where(function($subQ) {
                            $subQ->where('subject', 'like', '%sửa chữa%')
                                 ->orWhere('content', 'like', '%sửa chữa%');
                        });
                        break;
                    case 'system': // Filter notifications hệ thống → Chỉ lấy notifications không thuộc các loại khác
                        $q->where(function($subQ) {
                            $subQ->where(function($notPayment) {
                                $notPayment->where('subject', 'not like', '%thanh toán%')
                                          ->where('content', 'not like', '%hóa đơn%');
                            })
                            ->where(function($notContract) {
                                $notContract->where('subject', 'not like', '%hợp đồng%')
                                           ->where('content', 'not like', '%hợp đồng%');
                            })
                            ->where(function($notAppointment) {
                                $notAppointment->where('subject', 'not like', '%lịch hẹn%')
                                              ->where('content', 'not like', '%lịch hẹn%');
                            })
                            ->where(function($notReview) {
                                $notReview->where('subject', 'not like', '%đánh giá%')
                                         ->where('content', 'not like', '%đánh giá%');
                            })
                            ->where(function($notMaintenance) {
                                $notMaintenance->where('subject', 'not like', '%sửa chữa%')
                                              ->where('content', 'not like', '%sửa chữa%');
                            });
                        });
                        break;
                }
            });
        }
        
        // Áp dụng filter theo status → Chỉ lấy notifications theo trạng thái được chọn
        if ($status === 'unread') { // Chỉ lấy notifications chưa đọc
            $query->where('status', 'queued'); // Status = 'queued' nghĩa là chưa đọc
        } elseif ($status === 'read') { // Chỉ lấy notifications đã đọc
            $query->where('status', 'sent'); // Status = 'sent' nghĩa là đã đọc
        } elseif ($status === 'important') { // Chỉ lấy notifications quan trọng
            $query->where(function($q) {
                $q->where('subject', 'like', '%quá hạn%') // Tìm notifications có từ khóa "quá hạn"
                  ->orWhere('subject', 'like', '%khẩn cấp%') // Hoặc "khẩn cấp"
                  ->orWhere('subject', 'like', '%hết hạn%'); // Hoặc "hết hạn"
            });
        }
        
        // Áp dụng filter theo search → Tìm kiếm trong subject và content
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%") // Tìm trong subject
                  ->orWhere('content', 'like', "%{$search}%"); // Hoặc trong content
            });
        }
        
        $notifications = $query->orderBy('created_at', 'desc') // Sắp xếp theo thời gian tạo (mới nhất trước)
            ->paginate(20); // Phân trang 20 bản ghi/trang → Dùng để hiển thị
        
        // Lấy thống kê notifications → Dùng để hiển thị stats cards
        $stats = $this->getNotificationStats($tenantId);
        
        // Lấy số lượng notifications chưa đọc → Dùng để hiển thị badge
        $unreadCount = Notification::where('to_user_id', $tenantId) // Tìm notifications của tenant
            ->where('channel_id', 1) // Chỉ lấy in-app notifications
            ->where('status', 'queued') // Chỉ lấy chưa đọc
            ->where(function($q) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                // Filter theo entities của tenant qua audit_log → Đảm bảo chỉ đếm notifications của tenant
                $q->whereHas('auditLog', function($subQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                    $subQ->where(function($entityQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                        $entityQ->where(function($leaseQ) use ($tenantLeaseIds) {
                            $leaseQ->where('entity_type', 'lease')->whereIn('entity_id', $tenantLeaseIds);
                        })
                        ->orWhere(function($invoiceQ) use ($tenantInvoiceIds) {
                            $invoiceQ->where('entity_type', 'invoice')->whereIn('entity_id', $tenantInvoiceIds);
                        })
                        ->orWhere(function($invoiceItemQ) use ($tenantInvoiceItemIds) {
                            $invoiceItemQ->where('entity_type', 'invoiceitem')->whereIn('entity_id', $tenantInvoiceItemIds);
                        })
                        ->orWhere(function($paymentQ) use ($tenantPaymentIds) {
                            $paymentQ->where('entity_type', 'payment')->whereIn('entity_id', $tenantPaymentIds);
                        })
                        ->orWhere(function($ticketQ) use ($tenantTicketIds) {
                            $ticketQ->where('entity_type', 'ticket')->whereIn('entity_id', $tenantTicketIds);
                        })
                        ->orWhere(function($ticketLogQ) use ($tenantTicketLogIds) {
                            $ticketLogQ->where('entity_type', 'ticketlog')->whereIn('entity_id', $tenantTicketLogIds);
                        })
                        ->orWhere(function($reviewQ) use ($tenantReviewIds) {
                            $reviewQ->where('entity_type', 'review')->whereIn('entity_id', $tenantReviewIds);
                        })
                        ->orWhere(function($reviewReplyQ) use ($tenantReviewReplyIds) {
                            $reviewReplyQ->where('entity_type', 'reviewreply')->whereIn('entity_id', $tenantReviewReplyIds);
                        });
                    });
                })
                ->orWhereNull('audit_log_id'); // Fallback cho notifications cũ không có audit_log
            })
            ->count(); // Đếm số lượng → Dùng để hiển thị badge
        
        // Xử lý notifications để thêm type, icon, và entity_link → Dùng để hiển thị trong UI
        $processedNotifications = $notifications->getCollection()->map(function ($notification) {
            $notification->type = $this->getNotificationType($notification); // Xác định loại notification → Dùng để hiển thị icon và style
            $notification->icon = $this->getNotificationIcon($notification); // Lấy icon theo type → Dùng để hiển thị
            
            // Lấy entity link từ audit_log → Dùng để tạo link đến entity liên quan
            $notification->entity_link = null;
            if ($notification->audit_log_id && $notification->auditLog) {
                $notification->entity_link = $this->getEntityLink($notification->auditLog); // Tạo link đến entity → Dùng để điều hướng
            }
            
            return $notification;
        });
        
        // Thay thế collection với dữ liệu đã xử lý → Dùng để hiển thị
        $notifications->setCollection($processedNotifications);
        
        // Kiểm tra có phải HTMX request không → Dùng để quyết định trả về HTML hay view
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) { // Nếu là HTMX request → Trả về HTML để update động
            // Format stats để hiển thị trong stats cards → Dùng để update stats cards qua hx-swap-oob
            $notificationStats = $this->formatNotificationStats($stats, $type, $status, $search);
            
            // Chuẩn bị filter tabs HTML → Dùng để update filter section qua hx-swap-oob
            $filterTabs = [
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'active' => $status === 'all',
                    'hx-get' => route('tenant.notifications', ['type' => $type, 'status' => 'all', 'search' => $search]),
                    'hx-target' => '#notifications-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-folder'
                ],
                [
                    'label' => 'Chưa đọc',
                    'value' => 'unread',
                    'active' => $status === 'unread',
                    'hx-get' => route('tenant.notifications', ['type' => $type, 'status' => 'unread', 'search' => $search]),
                    'hx-target' => '#notifications-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-circle'
                ],
                [
                    'label' => 'Đã đọc',
                    'value' => 'read',
                    'active' => $status === 'read',
                    'hx-get' => route('tenant.notifications', ['type' => $type, 'status' => 'read', 'search' => $search]),
                    'hx-target' => '#notifications-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-check-circle'
                ],
                [
                    'label' => 'Quan trọng',
                    'value' => 'important',
                    'active' => $status === 'important',
                    'hx-get' => route('tenant.notifications', ['type' => $type, 'status' => 'important', 'search' => $search]),
                    'hx-target' => '#notifications-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-exclamation-triangle'
                ]
            ];
            
            $notificationsListHtml = view('tenant.notifications.partials.notifications-list', compact('notifications'))->render(); // Render notifications list HTML → Dùng để update danh sách
            $statsCardsHtml = view('tenant.components.stats-cards', [
                'stats' => $notificationStats,
                'columns' => 4,
                'class' => 'mb-4'
            ])->render(); // Render stats cards HTML → Dùng để update stats qua hx-swap-oob
            
            // Chuẩn bị additionalFields HTML cho type filter → Dùng để update filter section
            $additionalFields = '<div class="type-filter-blue">
                <select class="form-select type-select-blue" name="type" id="typeFilter" 
                        hx-get="' . route('tenant.notifications') . '"
                        hx-target="#notifications-list-container"
                        hx-swap="innerHTML"
                        hx-push-url="true"
                        hx-indicator="#htmx-loading"
                        hx-trigger="change"
                        hx-include="[name=\'search\'], [name=\'status\']">
                    <option value="">Tất cả loại</option>
                    <option value="payment" ' . ($type === 'payment' ? 'selected' : '') . '>💳 Thanh toán</option>
                    <option value="contract" ' . ($type === 'contract' ? 'selected' : '') . '>📄 Hợp đồng</option>
                    <option value="appointment" ' . ($type === 'appointment' ? 'selected' : '') . '>📅 Lịch hẹn</option>
                    <option value="review" ' . ($type === 'review' ? 'selected' : '') . '>⭐ Đánh giá</option>
                    <option value="maintenance" ' . ($type === 'maintenance' ? 'selected' : '') . '>🔧 Sửa chữa</option>
                    <option value="system" ' . ($type === 'system' ? 'selected' : '') . '>⚙️ Hệ thống</option>
                </select>
            </div>';
            
            $filterSectionHtml = view('tenant.components.filter-section', [
                'searchPlaceholder' => 'Tìm kiếm thông báo...',
                'searchValue' => $search,
                'filters' => $filterTabs,
                'formId' => 'filterForm',
                'searchInputId' => 'searchInput',
                'hxGet' => route('tenant.notifications'),
                'hxTarget' => '#notifications-list-container',
                'hxSwap' => 'innerHTML',
                'hxPushUrl' => 'true',
                'hxIndicator' => '#htmx-loading',
                'hxTrigger' => 'input delay:500ms from:#searchInput, change from:#typeFilter',
                'additionalFields' => $additionalFields
            ])->render(); // Render filter section HTML → Dùng để update filter section qua hx-swap-oob
            
            // Trả về notifications list với stats cards và filter section update qua hx-swap-oob → Update nhiều phần tử cùng lúc
            $html = $notificationsListHtml 
                . "\n<div id='stats-cards-container' hx-swap-oob='true'>" . $statsCardsHtml . "</div>" // Update stats cards
                . "\n<div id='filter-section-container' hx-swap-oob='true'>" . $filterSectionHtml . "</div>"; // Update filter section
            
            return response($html)
                ->header('HX-Push-Url', $request->fullUrl()); // Push URL vào browser history → Dùng để back/forward
        }
        
        return view('tenant.notifications.index', compact('notifications', 'stats', 'unreadCount', 'type', 'status', 'search')); // Trả về view đầy đủ → Dùng cho request thường
    }
    
    /**
     * Lấy thống kê notifications của tenant
     * 
     * MỤC ĐÍCH:
     * Tính toán thống kê notifications (total, unread, read, important) để hiển thị trong stats cards
     * 
     * INPUT:
     * - int $tenantId: ID của tenant
     * 
     * OUTPUT:
     * - array: Mảng thống kê với keys: total, unread, read, important
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy danh sách IDs của entities thuộc tenant → Dùng để filter notifications
     * 2. Build base query với filter theo entities của tenant → Đảm bảo chỉ đếm notifications của tenant
     * 3. Đếm total, unread, read, important từ base query → Tính toán thống kê
     * 4. Trả về mảng thống kê → Dùng để hiển thị stats cards
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng notifications: Đếm notifications của tenant
     * - Bảng leases, invoices, tickets, payments, reviews: Lấy IDs để filter
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    private function getNotificationStats($tenantId)
    {
        // Lấy danh sách IDs của entities thuộc tenant từ TẤT CẢ organizations → Dùng để filter notifications
        $tenantLeaseIds = Lease::getAccessibleLeaseIds($tenantId)->toArray();
        $tenantInvoiceIds = Invoice::whereIn('lease_id', $tenantLeaseIds)->pluck('id')->toArray();
        $tenantTicketIds = Ticket::whereIn('lease_id', $tenantLeaseIds)->pluck('id')->toArray();
        $tenantReviewIds = Review::whereIn('lease_id', $tenantLeaseIds)->pluck('id')->toArray();
        $tenantReviewReplyIds = ReviewReply::whereIn('review_id', $tenantReviewIds)->pluck('id')->toArray();
        $tenantPaymentIds = Payment::whereIn('invoice_id', $tenantInvoiceIds)
            ->pluck('id')
            ->toArray();
        $tenantTicketLogIds = DB::table('ticket_logs')
            ->whereIn('ticket_id', $tenantTicketIds)
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách invoiceitem_ids của tenant (qua invoice) → Dùng để filter notifications về invoice items
        $tenantInvoiceItemIds = DB::table('invoice_items')
            ->whereIn('invoice_id', $tenantInvoiceIds)
            ->pluck('id')
            ->toArray();
        
        // Build base query với filter dựa trên audit_log → Đảm bảo chỉ đếm notifications của tenant
        $baseQuery = Notification::where('to_user_id', $tenantId) // Chỉ lấy notifications của tenant
            ->where('channel_id', 1) // Chỉ lấy in-app notifications
            ->where(function($q) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                // Filter theo entities của tenant qua audit_log → Đảm bảo chỉ đếm đúng notifications
                $q->whereHas('auditLog', function($subQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                    $subQ->where(function($entityQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                        $entityQ->where(function($leaseQ) use ($tenantLeaseIds) {
                            $leaseQ->where('entity_type', 'lease')->whereIn('entity_id', $tenantLeaseIds);
                        })
                        ->orWhere(function($invoiceQ) use ($tenantInvoiceIds) {
                            $invoiceQ->where('entity_type', 'invoice')->whereIn('entity_id', $tenantInvoiceIds);
                        })
                        ->orWhere(function($invoiceItemQ) use ($tenantInvoiceItemIds) {
                            $invoiceItemQ->where('entity_type', 'invoiceitem')->whereIn('entity_id', $tenantInvoiceItemIds);
                        })
                        ->orWhere(function($paymentQ) use ($tenantPaymentIds) {
                            $paymentQ->where('entity_type', 'payment')->whereIn('entity_id', $tenantPaymentIds);
                        })
                        ->orWhere(function($ticketQ) use ($tenantTicketIds) {
                            $ticketQ->where('entity_type', 'ticket')->whereIn('entity_id', $tenantTicketIds);
                        })
                        ->orWhere(function($ticketLogQ) use ($tenantTicketLogIds) {
                            $ticketLogQ->where('entity_type', 'ticketlog')->whereIn('entity_id', $tenantTicketLogIds);
                        })
                        ->orWhere(function($reviewQ) use ($tenantReviewIds) {
                            $reviewQ->where('entity_type', 'review')->whereIn('entity_id', $tenantReviewIds);
                        })
                        ->orWhere(function($reviewReplyQ) use ($tenantReviewReplyIds) {
                            $reviewReplyQ->where('entity_type', 'reviewreply')->whereIn('entity_id', $tenantReviewReplyIds);
                        });
                    });
                })
                ->orWhereNull('audit_log_id'); // Fallback cho notifications cũ không có audit_log
            });
        
        return [
            'total' => $baseQuery->count(), // Tổng số notifications → Dùng để hiển thị
            'unread' => (clone $baseQuery)->where('status', 'queued')->count(), // Số notifications chưa đọc → Dùng để hiển thị
            'read' => (clone $baseQuery)->where('status', 'sent')->count(), // Số notifications đã đọc → Dùng để hiển thị
            'important' => (clone $baseQuery)->where(function($q) {
                $q->where('subject', 'like', '%quá hạn%') // Tìm notifications có từ khóa "quá hạn"
                  ->orWhere('subject', 'like', '%khẩn cấp%') // Hoặc "khẩn cấp"
                  ->orWhere('subject', 'like', '%hết hạn%'); // Hoặc "hết hạn"
            })->count(), // Số notifications quan trọng → Dùng để hiển thị
        ];
    }
    
    /**
     * Lấy số lượng notifications chưa đọc (AJAX)
     * 
     * MỤC ĐÍCH:
     * Trả về số lượng notifications chưa đọc của tenant để hiển thị badge trên header
     * 
     * INPUT:
     * - Session: user_id (từ Auth::id())
     * - Database: notifications
     * 
     * OUTPUT:
     * - JSON: {success: true, unread_count: number}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tenant ID từ session → Dùng để filter notifications
     * 2. Đếm số notifications chưa đọc (status = 'queued') → Dùng để hiển thị badge
     * 3. Trả về JSON với số lượng
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng notifications: Đếm notifications chưa đọc của tenant
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function getUnreadCount()
    {
        $tenantId = Auth::id(); // Lấy ID của tenant đang đăng nhập → Dùng để filter notifications
        
        $unreadCount = Notification::where('to_user_id', $tenantId) // Tìm notifications của tenant
            ->where('channel_id', 1) // Chỉ lấy in-app notifications
            ->where('status', 'queued') // Chỉ lấy notifications chưa đọc (status = 'queued')
            ->count(); // Đếm số lượng → Dùng để hiển thị badge
        
        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount
        ]);
    }
    
    /**
     * Đánh dấu notification đã đọc
     * 
     * MỤC ĐÍCH:
     * Cập nhật trạng thái notification từ 'queued' (chưa đọc) sang 'sent' (đã đọc) khi tenant xem notification
     * 
     * INPUT:
     * - Request: id (notification ID), type, status, search (query parameters cho HTMX)
     * - Session: user_id (từ Auth::id())
     * - Database: notifications
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} (nếu không phải HTMX request)
     * - HTML: notifications list HTML với stats cards (nếu là HTMX request)
     * - Database: Cập nhật status và sent_at trong bảng notifications
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tenant ID từ session → Dùng để verify ownership
     * 2. Tìm notification theo ID và verify thuộc về tenant → Đảm bảo security
     * 3. Cập nhật status từ 'queued' sang 'sent' và set sent_at → Đánh dấu đã đọc
     * 4. Nếu là HTMX request: Reload notifications list với filters → Cập nhật UI động
     * 5. Trả về JSON hoặc HTML tùy request type
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng notifications: Tìm notification theo ID và tenant ID
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Cập nhật status = 'sent' và sent_at = now() → Đánh dấu đã đọc
     * 
     * LƯU Ý:
     * - Chỉ tenant sở hữu notification mới có thể đánh dấu đã đọc
     * - Hỗ trợ HTMX để reload notifications list động không cần reload trang
     */
    public function markAsRead(Request $request, $id)
    {
        $tenantId = Auth::id(); // Lấy ID của tenant đang đăng nhập → Dùng để verify ownership
        
        $notification = Notification::where('id', $id) // Tìm notification theo ID
            ->where('to_user_id', $tenantId) // Chỉ lấy notification của tenant này → Đảm bảo security
            ->where('channel_id', 1) // Chỉ lấy in-app notifications
            ->first(); // Lấy notification đầu tiên → Dùng để cập nhật
        
        if (!$notification) { // Nếu không tìm thấy notification
            if ($request->header('HX-Request') === 'true') { // Nếu là HTMX request
                return response('<div class="alert alert-danger">Thông báo không tồn tại</div>', 404); // Trả về HTML error
            }
            return response()->json([
                'success' => false,
                'message' => 'Thông báo không tồn tại'
            ], 404); // Trả về JSON error
        }
        
        $notification->update([
            'status' => 'sent', // Cập nhật trạng thái thành 'sent' (đã đọc) → Đánh dấu đã xem
            'sent_at' => now() // Lưu thời gian đánh dấu đã đọc → Dùng để tracking
        ]);
        
        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) {
            // Get current filter params
            $type = $request->get('type', '');
            $status = $request->get('status', 'all');
            $search = $request->get('search', '');
            
            // Reload notifications list with updated data
            $request->merge([
                'type' => $type,
                'status' => $status,
                'search' => $search
            ]);
            
            // Reload the notifications list to get updated HTML
            // Get current filter params and reload
            $request->merge([
                'type' => $type,
                'status' => $status,
                'search' => $search
            ]);
            
            // Re-query notifications with same filters
            $tenantLeaseIds = Lease::getAccessibleLeaseIds($tenantId)->toArray();
            $tenantInvoiceIds = Invoice::whereIn('lease_id', $tenantLeaseIds)->pluck('id')->toArray();
            $tenantInvoiceItemIds = DB::table('invoice_items')->whereIn('invoice_id', $tenantInvoiceIds)->pluck('id')->toArray();
            $tenantTicketIds = Ticket::whereIn('lease_id', $tenantLeaseIds)->pluck('id')->toArray();
            $tenantReviewIds = Review::whereIn('lease_id', $tenantLeaseIds)->pluck('id')->toArray();
            $tenantReviewReplyIds = ReviewReply::whereIn('review_id', $tenantReviewIds)->pluck('id')->toArray();
            $tenantPaymentIds = Payment::whereIn('invoice_id', $tenantInvoiceIds)->pluck('id')->toArray();
            $tenantTicketLogIds = DB::table('ticket_logs')->whereIn('ticket_id', $tenantTicketIds)->pluck('id')->toArray();
            
            $query = Notification::with(['channel', 'auditLog'])
                ->where('to_user_id', $tenantId)
                ->where('channel_id', 1)
                ->where(function($q) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                    $q->whereHas('auditLog', function($subQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                        $subQ->where(function($entityQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                            $entityQ->where(function($leaseQ) use ($tenantLeaseIds) {
                                $leaseQ->where('entity_type', 'lease')->whereIn('entity_id', $tenantLeaseIds);
                            })
                            ->orWhere(function($invoiceQ) use ($tenantInvoiceIds) {
                                $invoiceQ->where('entity_type', 'invoice')->whereIn('entity_id', $tenantInvoiceIds);
                            })
                            ->orWhere(function($invoiceItemQ) use ($tenantInvoiceItemIds) {
                                $invoiceItemQ->where('entity_type', 'invoiceitem')->whereIn('entity_id', $tenantInvoiceItemIds);
                            })
                            ->orWhere(function($paymentQ) use ($tenantPaymentIds) {
                                $paymentQ->where('entity_type', 'payment')->whereIn('entity_id', $tenantPaymentIds);
                            })
                            ->orWhere(function($ticketQ) use ($tenantTicketIds) {
                                $ticketQ->where('entity_type', 'ticket')->whereIn('entity_id', $tenantTicketIds);
                            })
                            ->orWhere(function($ticketLogQ) use ($tenantTicketLogIds) {
                                $ticketLogQ->where('entity_type', 'ticketlog')->whereIn('entity_id', $tenantTicketLogIds);
                            })
                            ->orWhere(function($reviewQ) use ($tenantReviewIds) {
                                $reviewQ->where('entity_type', 'review')->whereIn('entity_id', $tenantReviewIds);
                            })
                            ->orWhere(function($reviewReplyQ) use ($tenantReviewReplyIds) {
                                $reviewReplyQ->where('entity_type', 'reviewreply')->whereIn('entity_id', $tenantReviewReplyIds);
                            });
                        });
                    })
                    ->orWhereNull('audit_log_id');
                });
            
            // Apply same filters
            if ($type && in_array($type, ['payment', 'contract', 'appointment', 'review', 'maintenance', 'system'])) {
                $query->where(function($q) use ($type) {
                    switch ($type) {
                        case 'payment':
                            $q->where(function($subQ) {
                                $subQ->where('subject', 'like', '%thanh toán%')
                                     ->orWhere('content', 'like', '%hóa đơn%');
                            });
                            break;
                        case 'contract':
                            $q->where(function($subQ) {
                                $subQ->where('subject', 'like', '%hợp đồng%')
                                     ->orWhere('content', 'like', '%hợp đồng%');
                            });
                            break;
                        case 'appointment':
                            $q->where(function($subQ) {
                                $subQ->where('subject', 'like', '%lịch hẹn%')
                                     ->orWhere('content', 'like', '%lịch hẹn%');
                            });
                            break;
                        case 'review':
                            $q->where(function($subQ) {
                                $subQ->where('subject', 'like', '%đánh giá%')
                                     ->orWhere('content', 'like', '%đánh giá%');
                            });
                            break;
                    case 'maintenance':
                        $q->where(function($subQ) {
                            $subQ->where('subject', 'like', '%sửa chữa%')
                                 ->orWhere('content', 'like', '%sửa chữa%');
                        });
                        break;
                    case 'system':
                        // System notifications are those that don't match any other type
                        $q->where(function($subQ) {
                            $subQ->where(function($notPayment) {
                                $notPayment->where('subject', 'not like', '%thanh toán%')
                                          ->where('content', 'not like', '%hóa đơn%');
                            })
                            ->where(function($notContract) {
                                $notContract->where('subject', 'not like', '%hợp đồng%')
                                           ->where('content', 'not like', '%hợp đồng%');
                            })
                            ->where(function($notAppointment) {
                                $notAppointment->where('subject', 'not like', '%lịch hẹn%')
                                              ->where('content', 'not like', '%lịch hẹn%');
                            })
                            ->where(function($notReview) {
                                $notReview->where('subject', 'not like', '%đánh giá%')
                                         ->where('content', 'not like', '%đánh giá%');
                            })
                            ->where(function($notMaintenance) {
                                $notMaintenance->where('subject', 'not like', '%sửa chữa%')
                                              ->where('content', 'not like', '%sửa chữa%');
                            });
                        });
                        break;
                    }
                });
            }
            
            if ($status === 'unread') {
                $query->where('status', 'queued');
            } elseif ($status === 'read') {
                $query->where('status', 'sent');
            } elseif ($status === 'important') {
                $query->where(function($q) {
                    $q->where('subject', 'like', '%quá hạn%')
                      ->orWhere('subject', 'like', '%khẩn cấp%')
                      ->orWhere('subject', 'like', '%hết hạn%');
                });
            }
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            }
            
            $notifications = $query->orderBy('created_at', 'desc')->paginate(20);
            
            // Process notifications
            $processedNotifications = $notifications->getCollection()->map(function ($notif) {
                $notif->type = $this->getNotificationType($notif);
                $notif->icon = $this->getNotificationIcon($notif);
                $notif->entity_link = null;
                if ($notif->audit_log_id && $notif->auditLog) {
                    $notif->entity_link = $this->getEntityLink($notif->auditLog);
                }
                return $notif;
            });
            $notifications->setCollection($processedNotifications);
            
            // Get updated stats
            $stats = $this->getNotificationStats($tenantId);
            $notificationStats = $this->formatNotificationStats($stats, $type, $status, $search);
            
            $notificationsListHtml = view('tenant.notifications.partials.notifications-list', compact('notifications'))->render();
            $statsCardsHtml = view('tenant.components.stats-cards', [
                'stats' => $notificationStats,
                'columns' => 4,
                'class' => 'mb-4'
            ])->render();
            
            return response($notificationsListHtml . "\n<div id='stats-cards-container' hx-swap-oob='true'>" . $statsCardsHtml . "</div>")
                ->header('HX-Trigger', 'notification-updated');
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu đã xem'
        ]);
    }
    
    /**
     * Đánh dấu tất cả notifications đã đọc
     * 
     * MỤC ĐÍCH:
     * Cập nhật trạng thái tất cả notifications chưa đọc của tenant thành đã đọc
     * 
     * INPUT:
     * - Session: user_id (từ Auth::id())
     * - Database: notifications
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."}
     * - Database: Cập nhật status và sent_at cho tất cả notifications chưa đọc
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tenant ID từ session → Dùng để filter notifications
     * 2. Cập nhật tất cả notifications chưa đọc (status = 'queued') thành đã đọc (status = 'sent') → Đánh dấu tất cả đã xem
     * 3. Trả về JSON success → Dùng để thông báo cho user
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng notifications: Tìm notifications chưa đọc của tenant
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Cập nhật status = 'sent' và sent_at = now() cho tất cả notifications chưa đọc
     */
    public function markAllAsRead()
    {
        $tenantId = Auth::id(); // Lấy ID của tenant đang đăng nhập → Dùng để filter notifications
        
        Notification::where('to_user_id', $tenantId) // Tìm notifications của tenant
            ->where('channel_id', 1) // Chỉ lấy in-app notifications
            ->where('status', 'queued') // Chỉ lấy notifications chưa đọc
            ->update([
                'status' => 'sent', // Cập nhật trạng thái thành 'sent' (đã đọc) → Đánh dấu tất cả đã xem
                'sent_at' => now() // Lưu thời gian đánh dấu đã đọc → Dùng để tracking
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu tất cả đã xem'
        ]);
    }
    
    /**
     * Lấy notifications gần đây (cho header dropdown)
     * 
     * MỤC ĐÍCH:
     * Lấy 5 notifications gần đây nhất của tenant để hiển thị trong header dropdown
     * 
     * INPUT:
     * - Session: user_id (từ Auth::id())
     * - Database: notifications, leases, invoices, tickets, payments, reviews
     * 
     * OUTPUT:
     * - JSON: {success: true, notifications: [...], unread_count: number}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tenant ID từ session → Dùng để filter notifications
     * 2. Lấy danh sách IDs của entities thuộc tenant → Dùng để filter notifications qua audit_log
     * 3. Query 5 notifications gần đây nhất với filter theo entities của tenant → Lấy notifications mới nhất
     * 4. Process notifications (thêm type, icon, entity_link) → Dùng để hiển thị
     * 5. Đếm số notifications chưa đọc → Dùng để hiển thị badge
     * 6. Trả về JSON với notifications và unread_count → Dùng để update header dropdown
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng notifications: Lấy 5 notifications gần đây nhất của tenant
     * - Bảng leases, invoices, tickets, payments, reviews: Lấy IDs để filter
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function getRecent()
    {
        $tenantId = Auth::id(); // Lấy ID của tenant đang đăng nhập → Dùng để filter notifications
        
        // Lấy danh sách IDs của entities thuộc tenant từ TẤT CẢ organizations → Dùng để filter notifications
        $tenantLeaseIds = Lease::getAccessibleLeaseIds($tenantId)->toArray();
        $tenantInvoiceIds = Invoice::whereIn('lease_id', $tenantLeaseIds)->pluck('id')->toArray();
        $tenantInvoiceItemIds = DB::table('invoice_items')->whereIn('invoice_id', $tenantInvoiceIds)->pluck('id')->toArray();
        $tenantTicketIds = Ticket::whereIn('lease_id', $tenantLeaseIds)->pluck('id')->toArray();
        $tenantReviewIds = Review::whereIn('lease_id', $tenantLeaseIds)->pluck('id')->toArray();
        $tenantReviewReplyIds = ReviewReply::whereIn('review_id', $tenantReviewIds)->pluck('id')->toArray();
        $tenantPaymentIds = Payment::whereIn('invoice_id', $tenantInvoiceIds)
            ->pluck('id')
            ->toArray();
        $tenantTicketLogIds = DB::table('ticket_logs')
            ->whereIn('ticket_id', $tenantTicketIds)
            ->pluck('id')
            ->toArray();
        
        // Query notifications với filter dựa trên audit_log → Chỉ lấy notifications về entities của tenant
        $notifications = Notification::with(['channel', 'auditLog']) // Eager load relationships → Tránh N+1 query
            ->where('to_user_id', $tenantId) // Chỉ lấy notifications của tenant
            ->where('channel_id', 1) // Chỉ lấy in-app notifications
            ->where(function($q) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                // Filter theo entities của tenant qua audit_log → Đảm bảo chỉ lấy notifications của tenant
                $q->whereHas('auditLog', function($subQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                    $subQ->where(function($entityQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                        $entityQ->where(function($leaseQ) use ($tenantLeaseIds) {
                            $leaseQ->where('entity_type', 'lease')->whereIn('entity_id', $tenantLeaseIds);
                        })
                        ->orWhere(function($invoiceQ) use ($tenantInvoiceIds) {
                            $invoiceQ->where('entity_type', 'invoice')->whereIn('entity_id', $tenantInvoiceIds);
                        })
                        ->orWhere(function($invoiceItemQ) use ($tenantInvoiceItemIds) {
                            $invoiceItemQ->where('entity_type', 'invoiceitem')->whereIn('entity_id', $tenantInvoiceItemIds);
                        })
                        ->orWhere(function($paymentQ) use ($tenantPaymentIds) {
                            $paymentQ->where('entity_type', 'payment')->whereIn('entity_id', $tenantPaymentIds);
                        })
                        ->orWhere(function($ticketQ) use ($tenantTicketIds) {
                            $ticketQ->where('entity_type', 'ticket')->whereIn('entity_id', $tenantTicketIds);
                        })
                        ->orWhere(function($ticketLogQ) use ($tenantTicketLogIds) {
                            $ticketLogQ->where('entity_type', 'ticketlog')->whereIn('entity_id', $tenantTicketLogIds);
                        })
                        ->orWhere(function($reviewQ) use ($tenantReviewIds) {
                            $reviewQ->where('entity_type', 'review')->whereIn('entity_id', $tenantReviewIds);
                        })
                        ->orWhere(function($reviewReplyQ) use ($tenantReviewReplyIds) {
                            $reviewReplyQ->where('entity_type', 'reviewreply')->whereIn('entity_id', $tenantReviewReplyIds);
                        });
                    });
                })
                ->orWhereNull('audit_log_id'); // Fallback cho notifications cũ không có audit_log
            })
            ->orderBy('created_at', 'desc') // Sắp xếp theo thời gian tạo (mới nhất trước)
            ->limit(5) // Chỉ lấy 5 notifications gần đây nhất → Dùng để hiển thị trong header dropdown
            ->get();
        
        // Xử lý notifications để thêm type, icon, và entity link → Dùng để hiển thị trong header dropdown
        $processedNotifications = $notifications->map(function ($notification) {
            $entityLink = null; // Entity link mặc định = null → Dùng để link đến entity liên quan
            if ($notification->audit_log_id && $notification->auditLog) {
                $entityLink = $this->getEntityLink($notification->auditLog); // Tạo link đến entity → Dùng để điều hướng
            }
            
            return [
                'id' => $notification->id, // ID của notification → Dùng để identify
                'subject' => $notification->subject, // Tiêu đề → Dùng để hiển thị
                'content' => $notification->content, // Nội dung → Dùng để hiển thị
                'status' => $notification->status, // Trạng thái → Dùng để xác định đã đọc/chưa đọc
                'created_at' => $notification->created_at, // Thời gian tạo → Dùng để hiển thị
                'type' => $this->getNotificationType($notification), // Loại notification → Dùng để hiển thị icon
                'icon' => $this->getNotificationIcon($notification), // Icon → Dùng để hiển thị
                'is_unread' => $notification->status === 'queued', // Có phải chưa đọc không → Dùng để highlight
                'entity_link' => $entityLink // Link đến entity → Dùng để điều hướng
            ];
        });
        
        // Đếm số notifications chưa đọc → Dùng để hiển thị badge trên header
        $unreadCount = Notification::where('to_user_id', $tenantId) // Tìm notifications của tenant
            ->where('channel_id', 1) // Chỉ lấy in-app notifications
            ->where('status', 'queued') // Chỉ lấy chưa đọc
            ->where(function($q) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                // Filter theo entities của tenant qua audit_log → Đảm bảo chỉ đếm notifications của tenant
                $q->whereHas('auditLog', function($subQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                    $subQ->where(function($entityQ) use ($tenantLeaseIds, $tenantInvoiceIds, $tenantInvoiceItemIds, $tenantTicketIds, $tenantReviewIds, $tenantReviewReplyIds, $tenantPaymentIds, $tenantTicketLogIds) {
                        $entityQ->where(function($leaseQ) use ($tenantLeaseIds) {
                            $leaseQ->where('entity_type', 'lease')->whereIn('entity_id', $tenantLeaseIds);
                        })
                        ->orWhere(function($invoiceQ) use ($tenantInvoiceIds) {
                            $invoiceQ->where('entity_type', 'invoice')->whereIn('entity_id', $tenantInvoiceIds);
                        })
                        ->orWhere(function($invoiceItemQ) use ($tenantInvoiceItemIds) {
                            $invoiceItemQ->where('entity_type', 'invoiceitem')->whereIn('entity_id', $tenantInvoiceItemIds);
                        })
                        ->orWhere(function($paymentQ) use ($tenantPaymentIds) {
                            $paymentQ->where('entity_type', 'payment')->whereIn('entity_id', $tenantPaymentIds);
                        })
                        ->orWhere(function($ticketQ) use ($tenantTicketIds) {
                            $ticketQ->where('entity_type', 'ticket')->whereIn('entity_id', $tenantTicketIds);
                        })
                        ->orWhere(function($ticketLogQ) use ($tenantTicketLogIds) {
                            $ticketLogQ->where('entity_type', 'ticketlog')->whereIn('entity_id', $tenantTicketLogIds);
                        })
                        ->orWhere(function($reviewQ) use ($tenantReviewIds) {
                            $reviewQ->where('entity_type', 'review')->whereIn('entity_id', $tenantReviewIds);
                        })
                        ->orWhere(function($reviewReplyQ) use ($tenantReviewReplyIds) {
                            $reviewReplyQ->where('entity_type', 'reviewreply')->whereIn('entity_id', $tenantReviewReplyIds);
                        });
                    });
                })
                ->orWhereNull('audit_log_id'); // Fallback cho notifications cũ không có audit_log
            })
            ->count(); // Đếm số lượng → Dùng để hiển thị badge
        
        return response()->json([
            'success' => true,
            'notifications' => $processedNotifications, // Danh sách notifications gần đây → Dùng để hiển thị trong dropdown
            'unread_count' => $unreadCount // Số lượng chưa đọc → Dùng để hiển thị badge
        ]);
    }
    
    /**
     * Lấy chi tiết notification
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin chi tiết của notification và tự động đánh dấu đã đọc khi xem
     * 
     * INPUT:
     * - Request: id (notification ID)
     * - Session: user_id (từ Auth::id())
     * - Database: notifications
     * 
     * OUTPUT:
     * - JSON: {success: true, notification: {...}} hoặc {success: false, message: "..."}
     * - Database: Cập nhật status và sent_at nếu notification chưa đọc
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tenant ID từ session → Dùng để verify ownership
     * 2. Tìm notification theo ID và verify thuộc về tenant → Đảm bảo security
     * 3. Nếu notification chưa đọc: Cập nhật status thành 'sent' → Tự động đánh dấu đã đọc
     * 4. Lấy entity link từ audit_log → Dùng để điều hướng đến entity liên quan
     * 5. Trả về JSON với thông tin notification đầy đủ → Dùng để hiển thị trong modal
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng notifications: Tìm notification theo ID và tenant ID
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Cập nhật status = 'sent' và sent_at = now() nếu chưa đọc
     * 
     * LƯU Ý:
     * - Chỉ tenant sở hữu notification mới có thể xem chi tiết
     * - Tự động đánh dấu đã đọc khi xem chi tiết
     */
    public function show($id)
    {
        $tenantId = Auth::id(); // Lấy ID của tenant đang đăng nhập → Dùng để verify ownership
        
        $notification = Notification::with(['channel', 'auditLog']) // Eager load relationships → Tránh N+1 query
            ->where('id', $id) // Tìm notification theo ID
            ->where('to_user_id', $tenantId) // Chỉ lấy notification của tenant này → Đảm bảo security
            ->where('channel_id', 1) // Chỉ lấy in-app notifications
            ->first(); // Lấy notification đầu tiên → Dùng để hiển thị chi tiết
        
        if (!$notification) { // Nếu không tìm thấy notification
            return response()->json([
                'success' => false,
                'message' => 'Thông báo không tồn tại'
            ], 404); // Trả về lỗi 404
        }
        
        // Đánh dấu đã đọc nếu chưa đọc → Tự động đánh dấu khi xem chi tiết
        if ($notification->status === 'queued') {
            $notification->update([
                'status' => 'sent', // Cập nhật trạng thái thành 'sent' (đã đọc) → Đánh dấu đã xem
                'sent_at' => now() // Lưu thời gian đánh dấu đã đọc → Dùng để tracking
            ]);
        }
        
        // Lấy entity link từ audit_log → Dùng để điều hướng đến entity liên quan
        $entityLink = null;
        if ($notification->audit_log_id && $notification->auditLog) {
            $entityLink = $this->getEntityLink($notification->auditLog); // Tạo link đến entity → Dùng để điều hướng
        }
        
        return response()->json([
            'success' => true,
            'notification' => [
                'id' => $notification->id, // ID của notification → Dùng để identify
                'subject' => $notification->subject, // Tiêu đề → Dùng để hiển thị
                'content' => $notification->content, // Nội dung → Dùng để hiển thị
                'status' => $notification->status, // Trạng thái → Dùng để xác định đã đọc/chưa đọc
                'created_at' => $notification->created_at, // Thời gian tạo → Dùng để hiển thị
                'type' => $this->getNotificationType($notification), // Loại notification → Dùng để hiển thị icon
                'icon' => $this->getNotificationIcon($notification), // Icon → Dùng để hiển thị
                'entity_link' => $entityLink, // Link đến entity → Dùng để điều hướng
                'entity_type' => $notification->auditLog->entity_type ?? null, // Loại entity → Dùng để hiển thị
                'entity_id' => $notification->auditLog->entity_id ?? null, // ID của entity → Dùng để hiển thị
            ]
        ]);
    }
    
    /**
     * Tạo entity link từ audit log
     * 
     * MỤC ĐÍCH:
     * Tạo URL link đến entity liên quan dựa trên audit log (entity_type và entity_id)
     * 
     * INPUT:
     * - AuditLog $auditLog: Audit log chứa entity_type và entity_id
     * 
     * OUTPUT:
     * - string|null: URL đến entity hoặc null nếu không tạo được
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra audit log có đầy đủ thông tin không → Trả về null nếu thiếu
     * 2. Kiểm tra entity có tồn tại không → Trả về null nếu không tồn tại
     * 3. Tạo route dựa trên entity_type → Tạo URL đến trang chi tiết entity
     * 4. Trả về URL hoặc null nếu có lỗi → Dùng để điều hướng
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - AuditLog: Lấy entity_type và entity_id
     * - Database: Kiểm tra entity có tồn tại không
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log warning nếu không tạo được link
     */
    private function getEntityLink($auditLog): ?string
    {
        if (!$auditLog || !$auditLog->entity_type || !$auditLog->entity_id) { // Nếu audit log không đầy đủ thông tin
            return null; // Trả về null → Không tạo được link
        }

        try {
            $entityType = strtolower($auditLog->entity_type); // Chuyển entity_type về lowercase → Dùng để match
            $entityId = $auditLog->entity_id; // Lấy entity ID → Dùng để tạo route

            // Kiểm tra entity có tồn tại không trước khi tạo link → Đảm bảo link hợp lệ
            if (!$this->entityExists($entityType, $entityId)) {
                return null; // Entity không tồn tại → Không tạo link
            }

            return match($entityType) { // Match entity_type để tạo route tương ứng
                'lease' => route('tenant.contracts.show', $entityId), // Link đến trang chi tiết hợp đồng
                'invoice' => route('tenant.invoices.show', $entityId), // Link đến trang chi tiết hóa đơn
                'invoiceitem' => $this->getInvoiceItemLink($entityId), // Link đến invoice (qua invoice item)
                'payment' => route('tenant.payments.status', $entityId), // Link đến trang trạng thái thanh toán
                'ticket' => route('tenant.tickets.show', $entityId), // Link đến trang chi tiết ticket
                'review' => route('tenant.reviews.show', $entityId), // Link đến trang chi tiết review
                'reviewreply' => $this->getReviewReplyLink($entityId), // Link đến review (qua review reply)
                'ticketlog' => $this->getTicketLogLink($entityId), // Link đến ticket (qua ticket log)
                default => null, // Không có route cho entity type này → Trả về null
            };
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to generate entity link for tenant', [
                'audit_log_id' => $auditLog->id ?? null,
                'entity_type' => $auditLog->entity_type ?? null,
                'entity_id' => $auditLog->entity_id ?? null,
                'error' => $e->getMessage()
            ]); // Ghi log warning → Để debug, nhưng không throw exception
            return null; // Trả về null nếu có lỗi → Đảm bảo không crash
        }
    }
    
    /**
     * Lấy link đến review từ review reply
     * 
     * MỤC ĐÍCH:
     * Tạo URL link đến trang chi tiết review từ review reply ID (vì review reply không có trang riêng)
     * 
     * INPUT:
     * - int $reviewReplyId: ID của review reply
     * 
     * OUTPUT:
     * - string|null: URL đến trang chi tiết review hoặc null nếu không tìm thấy
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm review reply theo ID → Lấy review_id
     * 2. Tạo route đến trang chi tiết review → Dùng review_id
     * 3. Trả về URL hoặc null nếu có lỗi → Dùng để điều hướng
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng review_replies: Tìm review reply theo ID
     */
    private function getReviewReplyLink($reviewReplyId): ?string
    {
        try {
            $reviewReply = ReviewReply::find($reviewReplyId); // Tìm review reply theo ID → Lấy review_id
            if ($reviewReply && $reviewReply->review_id) { // Nếu tìm thấy và có review_id
                return route('tenant.reviews.show', $reviewReply->review_id); // Tạo route đến trang chi tiết review → Dùng để điều hướng
            }
        } catch (\Exception $e) {
            // Ignore errors → Không throw exception
        }
        return null; // Trả về null nếu không tìm thấy → Đảm bảo không crash
    }
    
    /**
     * Lấy link đến ticket từ ticket log
     * 
     * MỤC ĐÍCH:
     * Tạo URL link đến trang chi tiết ticket từ ticket log ID (vì ticket log không có trang riêng)
     * 
     * INPUT:
     * - int $ticketLogId: ID của ticket log
     * 
     * OUTPUT:
     * - string|null: URL đến trang chi tiết ticket hoặc null nếu không tìm thấy
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm ticket log theo ID → Lấy ticket_id
     * 2. Tạo route đến trang chi tiết ticket → Dùng ticket_id
     * 3. Trả về URL hoặc null nếu có lỗi → Dùng để điều hướng
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng ticket_logs: Tìm ticket log theo ID
     */
    private function getTicketLogLink($ticketLogId): ?string
    {
        try {
            $ticketLog = \App\Models\TicketLog::find($ticketLogId); // Tìm ticket log theo ID → Lấy ticket_id
            if ($ticketLog && $ticketLog->ticket_id) { // Nếu tìm thấy và có ticket_id
                return route('tenant.tickets.show', $ticketLog->ticket_id); // Tạo route đến trang chi tiết ticket → Dùng để điều hướng
            }
        } catch (\Exception $e) {
            // Ignore errors → Không throw exception
        }
        return null; // Trả về null nếu không tìm thấy → Đảm bảo không crash
    }
    
    /**
     * Lấy link đến invoice từ invoice item
     * 
     * MỤC ĐÍCH:
     * Tạo URL link đến trang chi tiết invoice từ invoice item ID (vì invoice item không có trang riêng)
     * 
     * INPUT:
     * - int $invoiceItemId: ID của invoice item
     * 
     * OUTPUT:
     * - string|null: URL đến trang chi tiết invoice hoặc null nếu không tìm thấy
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm invoice item theo ID → Lấy invoice_id
     * 2. Tạo route đến trang chi tiết invoice → Dùng invoice_id
     * 3. Trả về URL hoặc null nếu có lỗi → Dùng để điều hướng
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng invoice_items: Tìm invoice item theo ID
     */
    private function getInvoiceItemLink($invoiceItemId): ?string
    {
        try {
            $invoiceItem = \App\Models\InvoiceItem::find($invoiceItemId); // Tìm invoice item theo ID → Lấy invoice_id
            if ($invoiceItem && $invoiceItem->invoice_id) { // Nếu tìm thấy và có invoice_id
                return route('tenant.invoices.show', $invoiceItem->invoice_id); // Tạo route đến trang chi tiết invoice → Dùng để điều hướng
            }
        } catch (\Exception $e) {
            // Ignore errors → Không throw exception
        }
        return null; // Trả về null nếu không tìm thấy → Đảm bảo không crash
    }
    
    /**
     * Kiểm tra entity có tồn tại không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra xem một entity có tồn tại trong database không trước khi tạo link
     * 
     * INPUT:
     * - string $entityType: Loại entity (lease, invoice, ticket, etc.)
     * - int $entityId: ID của entity
     * 
     * OUTPUT:
     * - bool: true nếu entity tồn tại, false nếu không
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy model class từ entity type → Xác định model cần kiểm tra
     * 2. Kiểm tra model class có tồn tại không → Trả về false nếu không
     * 3. Kiểm tra entity có tồn tại trong database không → Trả về true/false
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Bảng của entity (leases, invoices, tickets, etc.)
     */
    private function entityExists(string $entityType, $entityId): bool
    {
        try {
            $modelClass = $this->getModelClass($entityType); // Lấy model class từ entity type → Xác định model cần kiểm tra
            if (!$modelClass || !class_exists($modelClass)) { // Nếu model class không tồn tại
                return false; // Trả về false → Entity không tồn tại
            }

            return $modelClass::where('id', $entityId)->exists(); // Kiểm tra entity có tồn tại không → Trả về true/false
        } catch (\Exception $e) {
            return false; // Trả về false nếu có lỗi → Đảm bảo không crash
        }
    }
    
    /**
     * Lấy model class từ entity type
     * 
     * MỤC ĐÍCH:
     * Ánh xạ entity type (string) sang tên class của Eloquent model tương ứng
     * 
     * INPUT:
     * - string $entityType: Loại entity (lease, invoice, ticket, etc.)
     * 
     * OUTPUT:
     * - string|null: Tên class của model hoặc null nếu không tìm thấy
     * 
     * LUỒNG XỬ LÝ:
     * 1. Dùng map để ánh xạ entity type sang model class → Xác định model class
     * 2. Trả về tên class hoặc null → Dùng để load model
     */
    private function getModelClass(string $entityType): ?string
    {
        $modelMap = [
            'lease' => Lease::class, // Ánh xạ 'lease' sang Lease model
            'invoice' => Invoice::class, // Ánh xạ 'invoice' sang Invoice model
            'invoiceitem' => \App\Models\InvoiceItem::class, // Ánh xạ 'invoiceitem' sang InvoiceItem model
            'payment' => Payment::class, // Ánh xạ 'payment' sang Payment model
            'ticket' => Ticket::class, // Ánh xạ 'ticket' sang Ticket model
            'ticketlog' => \App\Models\TicketLog::class, // Ánh xạ 'ticketlog' sang TicketLog model
            'review' => Review::class, // Ánh xạ 'review' sang Review model
            'reviewreply' => ReviewReply::class, // Ánh xạ 'reviewreply' sang ReviewReply model
        ];
        
        return $modelMap[strtolower($entityType)] ?? null; // Trả về model class hoặc null nếu không tìm thấy → Dùng để load model
    }
    
    /**
     * Xóa notification
     * 
     * MỤC ĐÍCH:
     * Xóa một notification cụ thể của tenant
     * 
     * INPUT:
     * - Request: id (notification ID)
     * - Session: user_id (từ Auth::id())
     * - Database: notifications
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} hoặc {success: false, message: "..."}
     * - Database: Xóa notification khỏi database
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tenant ID từ session → Dùng để verify ownership
     * 2. Tìm notification theo ID và verify thuộc về tenant → Đảm bảo security
     * 3. Xóa notification → Xóa khỏi database
     * 4. Trả về JSON success → Dùng để thông báo cho user
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng notifications: Tìm notification theo ID và tenant ID
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Xóa notification khỏi database
     * 
     * LƯU Ý:
     * - Chỉ tenant sở hữu notification mới có thể xóa
     */
    public function destroy($id)
    {
        $tenantId = Auth::id(); // Lấy ID của tenant đang đăng nhập → Dùng để verify ownership
        
        $notification = Notification::where('id', $id) // Tìm notification theo ID
            ->where('to_user_id', $tenantId) // Chỉ lấy notification của tenant này → Đảm bảo security
            ->where('channel_id', 1) // Chỉ lấy in-app notifications
            ->first(); // Lấy notification đầu tiên → Dùng để xóa
        
        if (!$notification) { // Nếu không tìm thấy notification
            return response()->json([
                'success' => false,
                'message' => 'Thông báo không tồn tại'
            ], 404); // Trả về lỗi 404
        }
        
        $notification->delete(); // Xóa notification → Xóa khỏi database
        
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa thông báo'
        ]);
    }
    
    /**
     * Get notification settings
     */
    public function getSettings()
    {
        $user = Auth::user();
        $preferenceService = app(NotificationPreferenceService::class);
        
        // Khởi tạo preferences mặc định nếu chưa có
        $preferenceService->initializeDefaults($user);
        
        $preferences = $preferenceService->getAllPreferences($user);
        
        // Map entity types to Vietnamese labels
        $labels = [
            'lease' => 'Hợp đồng',
            'invoice' => 'Hóa đơn',
            'payment' => 'Thanh toán',
            'ticket' => 'Ticket',
            'ticketlog' => 'Nhật ký ticket',
            'depositrefund' => 'Hoàn tiền cọc',
            'review' => 'Đánh giá',
            'reviewreply' => 'Phản hồi đánh giá',
        ];
        
        $formattedPreferences = [];
        foreach ($preferences as $entityType => $settings) {
            $formattedPreferences[] = [
                'entity_type' => $entityType,
                'label' => $labels[$entityType] ?? ucfirst($entityType),
                'email_enabled' => $settings['email_enabled'],
                'in_app_enabled' => $settings['in_app_enabled'],
            ];
        }
        
        return response()->json([
            'success' => true,
            'preferences' => $formattedPreferences
        ]);
    }
    
    /**
     * Update notification settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.entity_type' => 'required|string|in:lease,invoice,payment,ticket,ticketlog,depositrefund,review,reviewreply',
            'preferences.*.email_enabled' => 'required|boolean',
            'preferences.*.in_app_enabled' => 'required|boolean',
        ]);
        
        $user = Auth::user();
        $preferenceService = app(NotificationPreferenceService::class);
        
        $preferences = [];
        foreach ($request->preferences as $pref) {
            $preferences[$pref['entity_type']] = [
                'email_enabled' => $pref['email_enabled'],
                'in_app_enabled' => $pref['in_app_enabled'],
            ];
        }
        
        $success = $preferenceService->updateAllPreferences($user, $preferences);
        
        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Cài đặt đã được lưu thành công'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi lưu cài đặt'
        ], 500);
    }
    
    /**
     * Create a test notification (for development)
     */
    public function createTestNotification(Request $request)
    {
        $tenantId = Auth::id();
        
        $request->validate([
            'type' => 'required|in:payment,contract,appointment,review,maintenance',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'important' => 'boolean'
        ]);
        
        $notification = Notification::create([
            'channel_id' => 1, // in_app
            'to_user_id' => $tenantId,
            'subject' => $request->title,
            'content' => $request->message,
            'status' => 'queued',
            'created_at' => now(),
        ]);
        
        // Broadcast notification event for real-time updates (sync, không dùng queue)
        // Tạm thời tắt broadcast để tránh lỗi khi không có bảng jobs
        // Có thể bật lại sau khi setup broadcasting đầy đủ
        /*
        if ($notification && $notification->channel_id == 1) {
            try {
                broadcast(new \App\Events\NotificationCreated($notification));
            } catch (\Exception $e) {
                // Log error but don't fail the notification creation
                \Illuminate\Support\Facades\Log::warning('Failed to broadcast notification event', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        */
        
        return response()->json([
            'success' => true,
            'message' => 'Thông báo test đã được tạo',
            'notification' => $notification
        ]);
    }
    
    /**
     * Get notification type based on subject/content
     */
    public function getNotificationType($notification)
    {
        $subject = strtolower($notification->subject);
        $content = strtolower($notification->content);
        
        if (str_contains($subject, 'thanh toán') || str_contains($content, 'hóa đơn')) {
            return 'payment';
        } elseif (str_contains($subject, 'hợp đồng') || str_contains($content, 'hợp đồng')) {
            return 'contract';
        } elseif (str_contains($subject, 'lịch hẹn') || str_contains($content, 'lịch hẹn')) {
            return 'appointment';
        } elseif (str_contains($subject, 'đánh giá') || str_contains($content, 'đánh giá')) {
            return 'review';
        } elseif (str_contains($subject, 'sửa chữa') || str_contains($content, 'sửa chữa')) {
            return 'maintenance';
        }
        
        return 'system';
    }
    
    /**
     * Format notification stats for stats-cards component
     */
    private function formatNotificationStats($stats, $type, $status, $search)
    {
        return [
            [
                'icon' => 'fas fa-bell',
                'value' => $stats['total'] ?? 0,
                'label' => 'Tất cả',
                'active' => $status === 'all',
                'data-filter' => 'all',
                'statusClass' => 'total',
                'hx-get' => route('tenant.notifications', ['type' => $type, 'status' => 'all', 'search' => $search]),
                'hx-target' => '#notifications-list-container',
                'hx-swap' => 'innerHTML',
                'hx-push-url' => 'true',
                'hx-indicator' => '#htmx-loading',
                'title' => 'Click để xem tất cả thông báo'
            ],
            [
                'icon' => 'fas fa-check-circle',
                'value' => $stats['read'] ?? 0,
                'label' => 'Đã đọc',
                'active' => $status === 'read',
                'data-filter' => 'read',
                'statusClass' => 'read',
                'hx-get' => route('tenant.notifications', ['type' => $type, 'status' => 'read', 'search' => $search]),
                'hx-target' => '#notifications-list-container',
                'hx-swap' => 'innerHTML',
                'hx-push-url' => 'true',
                'hx-indicator' => '#htmx-loading',
                'title' => 'Click để xem thông báo đã đọc'
            ],
            [
                'icon' => 'fas fa-circle',
                'value' => $stats['unread'] ?? 0,
                'label' => 'Chưa đọc',
                'active' => $status === 'unread',
                'data-filter' => 'unread',
                'statusClass' => 'unread',
                'hx-get' => route('tenant.notifications', ['type' => $type, 'status' => 'unread', 'search' => $search]),
                'hx-target' => '#notifications-list-container',
                'hx-swap' => 'innerHTML',
                'hx-push-url' => 'true',
                'hx-indicator' => '#htmx-loading',
                'title' => 'Click để xem thông báo chưa đọc'
            ],
            [
                'icon' => 'fas fa-exclamation-triangle',
                'value' => $stats['important'] ?? 0,
                'label' => 'Quan trọng',
                'active' => $status === 'important',
                'data-filter' => 'important',
                'statusClass' => 'important',
                'hx-get' => route('tenant.notifications', ['type' => $type, 'status' => 'important', 'search' => $search]),
                'hx-target' => '#notifications-list-container',
                'hx-swap' => 'innerHTML',
                'hx-push-url' => 'true',
                'hx-indicator' => '#htmx-loading',
                'title' => 'Click để xem thông báo quan trọng'
            ]
        ];
    }
    
    /**
     * Get notification icon based on type
     */
    public function getNotificationIcon($notification)
    {
        $type = $this->getNotificationType($notification);
        
        switch ($type) {
            case 'payment':
                return 'fas fa-credit-card';
            case 'contract':
                return 'fas fa-file-contract';
            case 'appointment':
                return 'fas fa-calendar';
            case 'review':
                return 'fas fa-star';
            case 'maintenance':
                return 'fas fa-tools';
            default:
                return 'fas fa-bell';
        }
    }
}
