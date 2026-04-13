<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use App\Models\Lease;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LeaseServiceSet;
use App\Models\LeaseServiceSetItem;
use App\Models\Ticket;
use App\Models\Payment;
use App\Models\ReviewReply;
use App\Services\NotificationPreferenceService;
use App\Services\NotificationEmailService;
use App\Events\NotificationCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service: NotificationFromAuditService
 * 
 * MỤC ĐÍCH:
 * Tự động tạo notifications từ audit_logs khi có entity thay đổi. Service này xác định users cần nhận
 * notifications dựa trên audit log (action, entity_type, organization_id) và tạo notifications cho họ.
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. createNotificationsFromAuditLog(): Tạo notifications từ audit log → Xác định recipients và tạo notifications
 * 2. getRecipients(): Lấy danh sách users cần nhận notifications (tenants, managers, agents, staff)
 * 3. createNotification(): Tạo notification record trong database → Lưu notification và gửi email (nếu cần)
 * 4. generateSubject() và generateContent(): Tạo subject và content cho notification → Dùng để hiển thị
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: AuditLog (bảng audit_logs) - Audit log tạo notifications
 * - Model: Lease, Invoice, Ticket, Payment, Review - Entities để xác định recipients
 * - Model: User (bảng users) - Users nhận notifications
 * - Model: UserNotificationPreference (bảng user_notification_preferences) - Preferences của users
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng notifications: Tạo notifications mới cho users
 * - Logs: Ghi log quá trình xử lý
 * - Email: Gửi email notifications cho tenants (nếu được bật)
 * 
 * LƯU Ý:
 * - Manager LUÔN nhận notifications cho MỌI audit_log trong organization của họ
 * - Tenant chỉ nhận notifications về entities thuộc về mình (qua lease relationship)
 * - Agent nhận notifications về entities liên quan đến họ (lease, ticket, viewing)
 * - Notifications được tạo dựa trên IMPORTANT_ACTIONS - chỉ các action quan trọng mới tạo notifications
 * - Sử dụng audit_logs làm source of truth thay vì tạo notifications trực tiếp từ entity observers
 */
class NotificationFromAuditService
{
    /**
     * Các action quan trọng cần tạo notification cho các user khác (không phải manager)
     * 
     * MỤC ĐÍCH:
     * Định nghĩa các action và recipients cần nhận notifications. Manager LUÔN nhận notifications
     * cho MỌI audit_log trong organization của họ, không cần trong danh sách này.
     * 
     * FORMAT:
     * 'entity_action' => ['recipient_type1', 'recipient_type2', ...]
     * 
     * RECIPIENT TYPES:
     * - 'tenant': Tenant sở hữu entity (qua lease relationship)
     * - 'agent': Agent liên quan đến entity (lease agent, property agent)
     * - 'staff': Tất cả staff trong organization (manager + staff roles)
     * 
     * LƯU Ý:
     * - Manager không cần trong danh sách vì họ LUÔN nhận notifications
     * - Chỉ các action quan trọng mới tạo notifications cho tenants/agents/staff
     */
    private const IMPORTANT_ACTIONS = [
        // Lease
        'lease_created' => ['tenant', 'agent'],
        'lease_updated' => ['tenant', 'agent'],
        'lease_deleted' => ['tenant', 'agent'],
        
        // Invoice
        'invoice_created' => ['tenant'],
        'invoice_updated' => ['tenant'],
        'invoice_deleted' => ['tenant'],
        
        // Invoice Item
        'invoiceitem_created' => ['tenant'],
        'invoiceitem_updated' => ['tenant'],
        'invoiceitem_deleted' => ['tenant'],
        
        // Payment
        'payment_created' => ['tenant'],
        'payment_updated' => ['tenant'],
        'payment_deleted' => ['tenant'],
        
        // Ticket
        'ticket_created' => ['tenant', 'agent'],
        'ticket_updated' => ['tenant', 'agent'],
        'ticket_deleted' => ['tenant', 'agent'],
        
        // Ticket Log
        'ticketlog_created' => ['tenant'],
        'ticketlog_updated' => ['tenant'],
        'ticketlog_deleted' => ['tenant'],
        
        // Review
        'review_created' => ['tenant'],
        'review_updated' => ['tenant'],
        'review_deleted' => ['tenant'],
        
        // Review Reply
        'reviewreply_created' => ['tenant'],
        'reviewreply_updated' => ['tenant'],
        'reviewreply_deleted' => ['tenant'],
        
        // Viewing
        'viewing_created' => ['agent'],
        'viewing_updated' => ['agent'],
        'viewing_deleted' => ['agent'],
        
        // Lead
        'lead_created' => ['agent'],
        'lead_updated' => ['agent'],
        'lead_deleted' => ['agent'],
        
        // Booking Deposit (chỉ agent, không gửi cho tenant)
        'bookingdeposit_created' => ['agent'],
        'bookingdeposit_updated' => ['agent'],
        'bookingdeposit_deleted' => ['agent'],
        
        // Deposit Refund
        'depositrefund_created' => ['tenant'],
        'depositrefund_updated' => ['tenant'],
        'depositrefund_deleted' => ['tenant'],
        
        // Property
        'property_created' => ['staff'],
        'property_updated' => ['staff'],
        'property_deleted' => ['staff'],
        
        // Unit
        'unit_created' => ['staff'],
        'unit_updated' => ['staff'],
        'unit_deleted' => ['staff'],
        
        // Lease Service Set (chỉ staff, không gửi cho tenant)
        'leaseserviceset_created' => ['staff'],
        'leaseserviceset_updated' => ['staff'],
        'leaseserviceset_deleted' => ['staff'],
        
        // Lease Service Set Item (chỉ staff, không gửi cho tenant)
        'leaseservicesetitem_created' => ['staff'],
        'leaseservicesetitem_updated' => ['staff'],
        'leaseservicesetitem_deleted' => ['staff'],
    ];

    /**
     * Tạo notifications từ audit log
     * 
     * MỤC ĐÍCH:
     * Tạo notifications cho users liên quan dựa trên audit log. Manager LUÔN nhận notifications cho
     * MỌI audit_log trong organization của họ. Các users khác (tenant, agent, staff) chỉ nhận
     * notifications cho các action quan trọng được định nghĩa trong IMPORTANT_ACTIONS.
     * 
     * INPUT:
     * - AuditLog $auditLog: Audit log chứa thông tin về entity thay đổi (action, entity_type, entity_id, organization_id)
     * 
     * OUTPUT:
     * - Database: Tạo notifications mới trong bảng notifications cho users liên quan
     * - Logs: Ghi log quá trình xử lý
     * - Return: bool (true nếu có tạo ít nhất 1 notification, false nếu không)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Ghi log bắt đầu xử lý → Để tracking
     * 2. Lấy danh sách managers trong organization → Manager LUÔN nhận notifications
     * 3. Tạo notifications cho tất cả managers → Đảm bảo managers luôn được thông báo
     * 4. Kiểm tra action có trong IMPORTANT_ACTIONS → Chỉ tạo notifications cho action quan trọng
     * 5. Lấy entity từ audit log → Dùng để xác định recipients
     * 6. Lấy recipients theo type (tenant, agent, staff) → Xác định users cần nhận notifications
     * 7. Tạo notifications cho từng recipient → Lưu vào database và gửi email (nếu cần)
     * 8. Ghi log kết quả → Để tracking
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng audit_logs: Audit log tạo notifications
     * - Bảng organization_users: Lấy managers, staff trong organization
     * - Bảng leases: Lấy tenants qua lease relationship
     * - Bảng users: Lấy thông tin users nhận notifications
     * - Bảng user_notification_preferences: Kiểm tra preferences của users
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Tạo notifications mới cho users
     * - Logs: Ghi log quá trình xử lý
     * 
     * LƯU Ý:
     * - Manager LUÔN nhận notifications cho MỌI audit_log trong organization của họ
     * - Tenant chỉ nhận notifications về entities thuộc về mình (qua lease relationship)
     * - Chỉ các action trong IMPORTANT_ACTIONS mới tạo notifications cho tenants/agents/staff
     */
    public function createNotificationsFromAuditLog(AuditLog $auditLog): bool
    {
        try {
            Log::info('NotificationFromAuditService: Starting notification creation', [
                'audit_log_id' => $auditLog->id,
                'action' => $auditLog->action,
                'entity_type' => $auditLog->entity_type,
                'entity_id' => $auditLog->entity_id,
                'organization_id' => $auditLog->organization_id
            ]); // Ghi log bắt đầu xử lý → Để tracking và debug
            
            // Lấy danh sách managers trong organization → Manager LUÔN nhận notifications cho MỌI audit_log
            $managers = $this->getManagerRecipients($auditLog->organization_id);
            $created = 0; // Đếm số notifications đã tạo → Dùng để tracking
            
            Log::info('NotificationFromAuditService: Found managers', [
                'audit_log_id' => $auditLog->id,
                'managers_count' => count($managers),
                'manager_ids' => array_map(fn($m) => $m->id, $managers)
            ]); // Ghi log số lượng managers → Để tracking
            
            // Tạo notifications cho tất cả managers → Đảm bảo managers luôn được thông báo
            foreach ($managers as $manager) {
                $entity = $this->getEntity($auditLog); // Lấy entity từ audit log → Dùng để tạo subject/content
                $notification = $this->createNotification($auditLog, $manager, $entity); // Tạo notification cho manager → Lưu vào database
                if ($notification) {
                    $created++; // Tăng counter nếu tạo thành công → Dùng để tracking
                }
            }

            // Kiểm tra action có trong IMPORTANT_ACTIONS → Chỉ tạo notifications cho action quan trọng
            Log::info('Checking if action is in IMPORTANT_ACTIONS', [
                'audit_log_id' => $auditLog->id,
                'action' => $auditLog->action,
                'entity_type' => $auditLog->entity_type,
                'entity_id' => $auditLog->entity_id,
                'is_in_important_actions' => isset(self::IMPORTANT_ACTIONS[$auditLog->action]),
                'available_actions' => array_keys(self::IMPORTANT_ACTIONS)
            ]);
            
            if (isset(self::IMPORTANT_ACTIONS[$auditLog->action])) {
                $recipients = self::IMPORTANT_ACTIONS[$auditLog->action];
                
                Log::info('Processing important action for notifications', [
                    'audit_log_id' => $auditLog->id,
                    'action' => $auditLog->action,
                    'entity_type' => $auditLog->entity_type,
                    'entity_id' => $auditLog->entity_id,
                    'recipients' => $recipients
                ]);
                
                // Lấy entity để xác định recipients
                $entity = $this->getEntity($auditLog);
                if ($entity) {
                    Log::info('Entity loaded successfully', [
                        'audit_log_id' => $auditLog->id,
                        'entity_type' => $auditLog->entity_type,
                        'entity_id' => $auditLog->entity_id,
                        'entity_class' => get_class($entity)
                    ]);
                    
                    // Tạo notifications cho từng recipient type (tenant, agent, etc.)
                    foreach ($recipients as $recipientType) {
                        $users = $this->getRecipients($recipientType, $auditLog, $entity);
                        
                        Log::info('Recipients found', [
                            'audit_log_id' => $auditLog->id,
                            'recipient_type' => $recipientType,
                            'users_count' => count($users),
                            'user_ids' => array_map(fn($u) => $u->id, $users)
                        ]);
                        
                        foreach ($users as $user) {
                            Log::info('Attempting to create notification for user', [
                                'audit_log_id' => $auditLog->id,
                                'user_id' => $user->id,
                                'user_email' => $user->email,
                                'recipient_type' => $recipientType,
                                'entity_type' => $auditLog->entity_type,
                                'entity_id' => $auditLog->entity_id
                            ]);
                            
                            $notification = $this->createNotification($auditLog, $user, $entity);
                            if ($notification) {
                                $created++;
                                Log::info('Notification created successfully for user', [
                                    'audit_log_id' => $auditLog->id,
                                    'user_id' => $user->id,
                                    'notification_id' => $notification->id,
                                    'recipient_type' => $recipientType
                                ]);
                            } else {
                                Log::warning('Failed to create notification for user - check logs above for reason', [
                                    'audit_log_id' => $auditLog->id,
                                    'user_id' => $user->id,
                                    'recipient_type' => $recipientType,
                                    'entity_type' => $auditLog->entity_type,
                                    'entity_id' => $auditLog->entity_id
                                ]);
                            }
                        }
                    }
                } else {
                    Log::warning('Entity not found for audit log', [
                        'audit_log_id' => $auditLog->id,
                        'entity_type' => $auditLog->entity_type,
                        'entity_id' => $auditLog->entity_id
                    ]);
                }
            }

            if ($created > 0) {
                Log::info('Notifications created from audit log', [
                    'audit_log_id' => $auditLog->id,
                    'action' => $auditLog->action,
                    'organization_id' => $auditLog->organization_id,
                    'created_count' => $created,
                    'managers_count' => count($managers)
                ]);
            }

            return $created > 0;

        } catch (\Exception $e) {
            Log::error('Failed to create notifications from audit log', [
                'audit_log_id' => $auditLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Lấy entity từ audit_log với eager loading relationships
     */
    private function getEntity(AuditLog $auditLog)
    {
        try {
            $modelClass = $this->getModelClass($auditLog->entity_type);
            if (!$modelClass || !class_exists($modelClass)) {
                return null;
            }

            // Eager load relationships dựa trên entity type
            $entity = $this->loadEntityWithRelationships($modelClass, $auditLog->entity_id, $auditLog->entity_type);
            
            return $entity;
        } catch (\Exception $e) {
            Log::warning('Failed to load entity for audit log', [
                'audit_log_id' => $auditLog->id,
                'entity_type' => $auditLog->entity_type,
                'entity_id' => $auditLog->entity_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Load entity với relationships cần thiết
     */
    private function loadEntityWithRelationships(string $modelClass, $entityId, string $entityType)
    {
        $relationships = $this->getRequiredRelationships($entityType);
        
        if (!empty($relationships)) {
            return $modelClass::with($relationships)->find($entityId);
        }
        
        return $modelClass::find($entityId);
    }

    /**
     * Lấy danh sách relationships cần eager load cho từng entity type
     */
    private function getRequiredRelationships(string $entityType): array
    {
        $relationships = [
            'lease' => ['tenant.userProfile', 'residents.user', 'unit.property', 'agent'],
            // 'leaseservice' => ['lease.tenant.userProfile', 'lease.unit.property', 'service'], // DEPRECATED - LeaseService model removed
            'leaseserviceset' => ['organization', 'items.service', 'leases.tenant', 'leases.unit.property'],
            'leaseservicesetitem' => ['leaseServiceSet.organization', 'leaseServiceSet.leases.tenant', 'leaseServiceSet.leases.unit.property', 'service'],
            'invoice' => ['lease.tenant.userProfile', 'lease.residents.user', 'lease.unit.property'],
            'invoiceitem' => ['invoice.lease.tenant.userProfile', 'invoice.lease.residents.user', 'invoice.lease.unit.property'],
            'payment' => ['lease', 'invoice.lease.tenant.userProfile', 'invoice.lease.residents.user'],
            'ticket' => ['lease.tenant.userProfile', 'lease.residents.user', 'lease.unit.property', 'unit.property'],
            'ticketlog' => ['ticket.lease.tenant.userProfile', 'ticket.lease.residents.user', 'ticket.lease.unit.property'],
            'review' => ['lease.tenant.userProfile', 'lease.residents.user', 'lease.unit.property', 'tenant'],
            'reviewreply' => ['review.lease.tenant.userProfile', 'review.lease.residents.user', 'review.lease.unit.property', 'review.tenant'],
            'depositrefund' => ['lease.tenant.userProfile', 'lease.residents.user', 'lease.unit.property', 'tenant.userProfile', 'unit.property'],
            'property' => ['location'],
            'lead' => [],
            'viewing' => ['property'],
        ];

        return $relationships[strtolower($entityType)] ?? [];
    }

    /**
     * Lấy danh sách recipients dựa trên type và entity
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách users cần nhận notifications dựa trên recipient type (tenant, manager, agent, staff)
     * và entity liên quan
     * 
     * INPUT:
     * - string $recipientType: Loại recipient (tenant, manager, agent, staff, property_owner)
     * - AuditLog $auditLog: Audit log chứa thông tin organization_id
     * - mixed $entity: Entity liên quan (Lease, Invoice, Ticket, Payment, Review, etc.)
     * 
     * OUTPUT:
     * - array: Mảng User models cần nhận notifications
     * 
     * LUỒNG XỬ LÝ:
     * 1. Switch theo recipient type → Gọi method tương ứng để lấy recipients
     * 2. Nếu có lỗi: Ghi log và trả về mảng rỗng → Đảm bảo không crash
     * 3. Filter null values → Loại bỏ users không hợp lệ
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_users: Lấy managers, staff trong organization
     * - Bảng leases: Lấy tenants qua lease relationship
     * - Bảng users: Lấy thông tin users
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log lỗi nếu có
     */
    private function getRecipients(string $recipientType, AuditLog $auditLog, $entity): array
    {
        $users = []; // Mảng users cần nhận notifications → Dùng để trả về

        try {
            switch ($recipientType) { // Switch theo recipient type → Gọi method tương ứng
                case 'tenant':
                    $users = $this->getTenantRecipients($entity, $auditLog->organization_id); // Lấy tenants sở hữu entity → Dùng để gửi notifications
                    break;
                    
                case 'manager':
                    $users = $this->getManagerRecipients($auditLog->organization_id); // Lấy managers trong organization → Dùng để gửi notifications
                    break;
                    
                case 'agent':
                    $users = $this->getAgentRecipients($entity, $auditLog->organization_id); // Lấy agents liên quan đến entity → Dùng để gửi notifications
                    break;
                    
                case 'staff':
                    $users = $this->getStaffRecipients($auditLog->organization_id); // Lấy staff trong organization → Dùng để gửi notifications
                    break;
                    
                case 'property_owner':
                    $users = $this->getPropertyOwnerRecipients($entity); // Lấy property owners → Dùng để gửi notifications (chưa implement)
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error getting recipients', [
                'recipient_type' => $recipientType,
                'entity_type' => $auditLog->entity_type,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Để debug, nhưng không throw exception
        }

        return array_filter($users); // Loại bỏ null values → Đảm bảo chỉ trả về users hợp lệ
    }

    /**
     * Lấy tất cả recipients từ lease (tenant + lease_residents)
     */
    private function getLeaseRecipients(Lease $lease): array
    {
        $recipients = [];
        
        // Add main tenant
        if ($lease->tenant_id) {
            $tenant = User::find($lease->tenant_id);
            if ($tenant) {
                $recipients[] = $tenant;
            }
        }
        
        // Add lease residents
        $residents = \App\Models\LeaseResident::where('lease_id', $lease->id)
            ->whereNotNull('user_id')
            ->with('user')
            ->get();
        
        foreach ($residents as $resident) {
            if ($resident->user && !in_array($resident->user, $recipients, true)) {
                $recipients[] = $resident->user;
            }
        }
        
        return $recipients;
    }

    /**
     * Lấy danh sách tenant recipients - CHỈ trả về tenant và lease_residents nếu họ là owner của entity
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách tenants sở hữu entity (qua lease relationship). Tenant chỉ nhận notifications
     * về entities thuộc về mình (lease, invoice, ticket, payment, review).
     * 
     * INPUT:
     * - mixed $entity: Entity liên quan (Lease, Invoice, Ticket, Payment, Review, etc.)
     * - int|null $organizationId: ID của organization (optional)
     * 
     * OUTPUT:
     * - array: Mảng User models là tenants sở hữu entity
     * 
     * LUỒNG XỬ LÝ:
     * 1. Xác định entity type và lấy lease từ entity → Dùng để tìm tenants
     * 2. Lấy tenants từ lease (tenant + lease_residents) → Lấy tất cả tenants liên quan
     * 3. Verify mỗi tenant có role 'tenant' trong organization → Đảm bảo chỉ trả về tenants hợp lệ
     * 4. Trả về danh sách tenants hợp lệ → Dùng để gửi notifications
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leases: Lấy tenants qua lease relationship
     * - Bảng lease_residents: Lấy residents (cũng là tenants)
     * - Bảng organization_users: Verify role 'tenant'
     * - Bảng users: Lấy thông tin User models
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log warning nếu user không phải tenant
     * 
     * LƯU Ý:
     * - Chỉ trả về tenants sở hữu entity (qua lease relationship)
     * - Verify role 'tenant' trong organization để đảm bảo chỉ trả về tenants hợp lệ
     * - Hỗ trợ nhiều loại entity: Lease, Invoice, Payment, Ticket, Review, ReviewReply, InvoiceItem, LeaseServiceSet, LeaseServiceSetItem
     */
    private function getTenantRecipients($entity, ?int $organizationId = null): array
    {
        $tenants = [];
        $lease = null;
        
        if ($entity instanceof Lease) {
            $lease = $entity;
        } elseif ($entity instanceof Invoice) {
            if ($entity->lease_id) {
                $lease = Lease::with('residents.user')->find($entity->lease_id);
            }
        } elseif ($entity instanceof Payment) {
            // Payment chỉ có invoice_id, không có lease_id
            if ($entity->invoice_id) {
                $invoice = Invoice::find($entity->invoice_id);
                if ($invoice && $invoice->lease_id) {
                    $lease = Lease::with('residents.user')->find($invoice->lease_id);
                }
            }
        } elseif ($entity instanceof Ticket) {
            if ($entity->lease_id) {
                $lease = Lease::with('residents.user')->find($entity->lease_id);
            }
        } elseif ($entity instanceof \App\Models\TicketLog) {
            // TicketLog có ticket_id, ticket có lease_id
            if ($entity->ticket_id) {
                $ticket = Ticket::find($entity->ticket_id);
                if ($ticket && $ticket->lease_id) {
                    $lease = Lease::with('residents.user')->find($ticket->lease_id);
                }
            }
        } elseif ($entity instanceof \App\Models\DepositRefund) {
            // DepositRefund có tenant_id trực tiếp và lease_id
            if ($entity->tenant_id) {
                $tenant = User::find($entity->tenant_id);
                if ($tenant) {
                    $tenants[] = $tenant;
                }
            }
            // Cũng có thể lấy từ lease để bao gồm residents
            if ($entity->lease_id) {
                $lease = Lease::with('residents.user')->find($entity->lease_id);
            }
        } elseif ($entity instanceof InvoiceItem) {
            // InvoiceItem có invoice_id, invoice có lease_id
            if ($entity->invoice_id) {
                $invoice = Invoice::find($entity->invoice_id);
                if ($invoice && $invoice->lease_id) {
                    $lease = Lease::with('residents.user')->find($invoice->lease_id);
                }
            }
        } elseif ($entity instanceof \App\Models\Review) {
            // Review có thể có lease_id hoặc tenant_id trực tiếp
            if ($entity->lease_id) {
                $lease = Lease::with('residents.user')->find($entity->lease_id);
            } elseif ($entity->tenant_id) {
                // Nếu không có lease_id, lấy tenant trực tiếp từ review
                $tenant = User::find($entity->tenant_id);
                if ($tenant) {
                    $tenants[] = $tenant;
                }
                return $tenants;
            }
        } elseif ($entity instanceof ReviewReply) {
            // ReviewReply có review_id, review có thể có lease_id hoặc tenant_id
            if ($entity->review_id) {
                $review = \App\Models\Review::with('tenant')->find($entity->review_id);
                if ($review) {
                    if ($review->lease_id) {
                        $lease = Lease::with('residents.user')->find($review->lease_id);
                    } elseif ($review->tenant_id) {
                        // Nếu review không có lease_id, lấy tenant trực tiếp từ review
                        $tenant = $review->tenant;
                        if ($tenant) {
                            $tenants[] = $tenant;
                        }
                        return $tenants;
                    }
                }
            }
        } elseif ($entity instanceof LeaseServiceSet) {
            // LeaseServiceSet: lấy tất cả tenants từ các leases đang sử dụng service set này
            $tenants = [];
            if ($entity->relationLoaded('leases')) {
                foreach ($entity->leases as $lease) {
                    if ($lease->tenant_id) {
                        $tenantUser = User::find($lease->tenant_id);
                        if ($tenantUser) {
                            $tenants[] = $tenantUser;
                        }
                    }
                }
            } else {
                // Nếu chưa load relationships, query trực tiếp
                $leases = Lease::where('lease_services_id', $entity->id)->get();
                foreach ($leases as $lease) {
                    if ($lease->tenant_id) {
                        $tenantUser = User::find($lease->tenant_id);
                        if ($tenantUser) {
                            $tenants[] = $tenantUser;
                        }
                    }
                }
            }
            return array_unique($tenants, SORT_REGULAR);
        } elseif ($entity instanceof LeaseServiceSetItem) {
            // LeaseServiceSetItem: lấy tất cả tenants từ các leases đang sử dụng service set này
            $tenants = [];
            if ($entity->lease_service_set_id) {
                $leaseServiceSet = $entity->leaseServiceSet;
                if ($leaseServiceSet) {
                    if ($leaseServiceSet->relationLoaded('leases')) {
                        foreach ($leaseServiceSet->leases as $lease) {
                            if ($lease->tenant_id) {
                                $tenantUser = User::find($lease->tenant_id);
                                if ($tenantUser) {
                                    $tenants[] = $tenantUser;
                                }
                            }
                        }
                    } else {
                        // Nếu chưa load relationships, query trực tiếp
                        $leases = Lease::where('lease_services_id', $leaseServiceSet->id)->get();
                        foreach ($leases as $lease) {
                            if ($lease->tenant_id) {
                                $tenantUser = User::find($lease->tenant_id);
                                if ($tenantUser) {
                                    $tenants[] = $tenantUser;
                                }
                            }
                        }
                    }
                }
            }
            return array_unique($tenants, SORT_REGULAR);
        }

        // If we have a lease, get all recipients (tenant + residents)
        if ($lease) {
            $tenants = $this->getLeaseRecipients($lease);
            
            // Verify each user has tenant role in organization
            $orgId = $lease->organization_id ?? $organizationId;
            $validTenants = [];
            
            foreach ($tenants as $tenant) {
                // QUAN TRỌNG: Kiểm tra role tenant trong organization CỤ THỂ
                // User có thể là manager ở org A nhưng tenant ở org B
                // Cần check role trong organization của lease/entity, không phải organization khác
                $query = DB::table('organization_users')
                    ->join('roles', 'organization_users.role_id', '=', 'roles.id')
                    ->where('organization_users.user_id', $tenant->id)
                    ->where('roles.key_code', 'tenant')
                    ->where('organization_users.status', 'active')
                    ->whereNull('organization_users.deleted_at');
                
                // BẮT BUỘC phải có organization_id để check role trong organization cụ thể
                if ($orgId) {
                    $query->where('organization_users.organization_id', $orgId);
                } else {
                    // Nếu không có organization_id, không thể verify role → Skip user này
                    Log::warning('Cannot verify tenant role - missing organization_id', [
                        'user_id' => $tenant->id,
                        'entity_type' => $entity instanceof Lease ? 'lease' : get_class($entity),
                        'entity_id' => $entity->id ?? null
                    ]);
                    continue;
                }
                
                $isTenant = $query->exists();
                
                if ($isTenant) {
                    $validTenants[] = $tenant;
                } else {
                    // Log để debug - user có thể có role khác (manager, agent) trong organization này
                    Log::info('User is not a tenant in this organization (may have other role)', [
                        'user_id' => $tenant->id,
                        'organization_id' => $orgId,
                        'entity_type' => $entity instanceof Lease ? 'lease' : get_class($entity),
                        'entity_id' => $entity->id ?? null,
                        'note' => 'User may be manager/agent in this org but tenant in another org'
                    ]);
                }
            }
            
            Log::info('Tenant recipients found', [
                'lease_id' => $lease->id,
                'lease_tenant_id' => $lease->tenant_id,
                'organization_id' => $orgId,
                'total_recipients' => count($validTenants),
                'valid_tenant_ids' => array_map(fn($t) => $t->id, $validTenants),
                'entity_type' => $entity instanceof Lease ? 'lease' : get_class($entity),
                'entity_id' => $entity->id ?? null
            ]);
            
            if (empty($validTenants)) {
                Log::warning('No valid tenant recipients found after role verification', [
                    'lease_id' => $lease->id,
                    'lease_tenant_id' => $lease->tenant_id,
                    'organization_id' => $orgId,
                    'entity_type' => $entity instanceof Lease ? 'lease' : get_class($entity),
                    'entity_id' => $entity->id ?? null,
                    'note' => 'Check if tenant has active tenant role in this organization'
                ]);
            }
            
            return $validTenants;
        } else {
            Log::warning('No lease found for entity', [
                'entity_type' => $entity instanceof Lease ? 'lease' : get_class($entity),
                'entity_id' => $entity->id ?? null
            ]);
        }

        return [];
    }

    /**
     * Lấy danh sách manager recipients trong organization
     * 
     * MỤC ĐÍCH:
     * Lấy tất cả managers trong organization để gửi notifications. Manager LUÔN nhận notifications
     * cho MỌI audit_log trong organization của họ.
     * 
     * INPUT:
     * - int|null $organizationId: ID của organization
     * 
     * OUTPUT:
     * - array: Mảng User models là managers trong organization
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra organization ID có hợp lệ không → Trả về mảng rỗng nếu không có
     * 2. Query managers từ organization_users với role 'manager' → Lấy danh sách managers
     * 3. Load User models từ IDs → Trả về User objects
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_users: Lấy managers trong organization
     * - Bảng roles: Filter theo role 'manager'
     * - Bảng users: Lấy thông tin User models
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    private function getManagerRecipients(?int $organizationId): array
    {
        if (!$organizationId) { // Nếu không có organization ID
            return []; // Trả về mảng rỗng → Không có managers
        }

        $managers = DB::table('organization_users') // Query từ bảng organization_users
            ->join('users', 'organization_users.user_id', '=', 'users.id') // Join với users → Lấy thông tin users
            ->join('roles', 'organization_users.role_id', '=', 'roles.id') // Join với roles → Filter theo role
            ->where('organization_users.organization_id', $organizationId) // Filter theo organization → Chỉ lấy managers trong organization này
            ->whereNull('organization_users.deleted_at') // Chỉ lấy chưa bị xóa → Bỏ qua soft deleted
            ->where('roles.key_code', 'manager') // Chỉ lấy role 'manager' → Filter managers
            ->where('organization_users.status', 'active') // Chỉ lấy status 'active' → Bỏ qua inactive
            ->select('users.id') // Chỉ lấy user ID → Dùng để load User models
            ->get(); // Lấy tất cả kết quả → Dùng để load User models

        $users = []; // Mảng User models → Dùng để trả về
        foreach ($managers as $managerData) { // Lặp qua danh sách manager IDs
            $user = User::find($managerData->id); // Load User model từ ID → Dùng để gửi notifications
            if ($user) {
                $users[] = $user; // Thêm vào mảng nếu tìm thấy → Dùng để trả về
            }
        }

        return $users; // Trả về danh sách managers → Dùng để gửi notifications
    }

    /**
     * Lấy staff recipients (manager và staff roles)
     */
    private function getStaffRecipients(?int $organizationId): array
    {
        if (!$organizationId) {
            return [];
        }

        $staff = DB::table('organization_users')
            ->join('users', 'organization_users.user_id', '=', 'users.id')
            ->join('roles', 'organization_users.role_id', '=', 'roles.id')
            ->where('organization_users.organization_id', $organizationId)
            ->whereNull('organization_users.deleted_at')
            ->whereIn('roles.key_code', ['manager', 'staff'])
            ->where('organization_users.status', 'active')
            ->select('users.id')
            ->get();

        $users = [];
        foreach ($staff as $staffData) {
            $user = User::find($staffData->id);
            if ($user) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Lấy agent recipients
     */
    private function getAgentRecipients($entity, ?int $organizationId): array
    {
        $users = [];

        // Lấy agent từ entity
        if ($entity instanceof Lease) {
            if ($entity->agent_id) {
                $agent = User::find($entity->agent_id);
                if ($agent) {
                    $users[] = $agent;
                }
            }
            
            // Lấy agents từ property
            if ($entity->unit_id) {
                $unit = $entity->unit;
                if ($unit && $unit->property_id) {
                    $property = $unit->property;
                    if ($property) {
                        $propertyAgentsData = DB::table('properties_user')
                            ->join('users', 'properties_user.user_id', '=', 'users.id')
                            ->where('properties_user.property_id', $property->id)
                            ->where('properties_user.role_key', 'agent')
                            ->whereNull('properties_user.deleted_at')
                            ->select('users.id')
                            ->get();
                        
                        $propertyAgents = [];
                        foreach ($propertyAgentsData as $agentData) {
                            $agent = User::find($agentData->id);
                            if ($agent) {
                                $propertyAgents[] = $agent;
                            }
                        }
                        
                        $users = array_merge($users, $propertyAgents);
                    }
                }
            }
        }

        // Remove duplicates
        $uniqueUsers = [];
        $seenIds = [];
        foreach ($users as $user) {
            if ($user && !in_array($user->id, $seenIds)) {
                $uniqueUsers[] = $user;
                $seenIds[] = $user->id;
            }
        }

        return $uniqueUsers;
    }

    /**
     * Lấy property owner recipients
     */
    private function getPropertyOwnerRecipients($entity): array
    {
        // TODO: Implement based on property ownership model
        return [];
    }

    /**
     * Tạo notification record trong database
     * 
     * MỤC ĐÍCH:
     * Tạo notification record cho user dựa trên audit log. Method này kiểm tra quyền, preferences,
     * tạo subject/content và lưu notification vào database, đồng thời gửi email nếu cần.
     * 
     * INPUT:
     * - AuditLog $auditLog: Audit log tạo notification
     * - User $user: User nhận notification
     * - mixed $entity: Entity liên quan (Lease, Invoice, Ticket, Payment, Review, etc.)
     * 
     * OUTPUT:
     * - Notification|null: Notification record đã tạo hoặc null nếu không tạo được
     * - Database: Tạo record mới trong bảng notifications
     * - Email: Gửi email notification cho tenant (nếu được bật)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra user có quyền nhận notification không → Đảm bảo security
     * 2. Kiểm tra preferences của user → Quyết định có tạo notification không
     * 3. Tạo subject và content từ audit log và entity → Dùng để hiển thị
     * 4. Tạo notification record trong database → Lưu notification
     * 5. Gửi email notification cho tenant (nếu được bật) → Thông báo qua email
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng user_notification_preferences: Kiểm tra preferences của user
     * - AuditLog: Lấy thông tin để tạo subject/content
     * - Entity: Lấy thông tin để tạo subject/content
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Tạo notification record mới
     * - Email: Gửi email notification (nếu được bật)
     * - Logs: Ghi log lỗi nếu có
     * 
     * LƯU Ý:
     * - Chỉ tạo notification nếu user có quyền và preferences cho phép
     * - Nếu cả email và in-app đều tắt → Không tạo notification
     * - Email chỉ gửi cho tenant, không gửi cho manager/agent
     */
    private function createNotification(AuditLog $auditLog, User $user, $entity): ?Notification
    {
        try {
            // Kiểm tra user có quyền nhận notification này không → Đảm bảo security (tenant chỉ nhận notifications về dữ liệu của mình)
            $shouldReceive = $this->shouldUserReceiveNotification($user, $auditLog, $entity);
            if (!$shouldReceive) {
                Log::warning('User should not receive notification - permission check failed', [
                    'audit_log_id' => $auditLog->id,
                    'user_id' => $user->id,
                    'entity_type' => $auditLog->entity_type,
                    'entity_id' => $auditLog->entity_id,
                    'entity_class' => $entity ? get_class($entity) : 'null'
                ]);
                return null; // Không có quyền → Không tạo notification
            }
            
            $preferenceService = app(NotificationPreferenceService::class); // Lấy service quản lý preferences → Dùng để kiểm tra preferences
            $entityType = $auditLog->entity_type; // Lấy entity type từ audit log → Dùng để kiểm tra preferences
            
            $shouldSendInApp = $preferenceService->shouldSendInApp($user, $entityType);
            $shouldSendEmail = $preferenceService->shouldSendEmail($user, $entityType);
            
            Log::info('Checking notification preferences', [
                'audit_log_id' => $auditLog->id,
                'user_id' => $user->id,
                'entity_type' => $entityType,
                'should_send_in_app' => $shouldSendInApp,
                'should_send_email' => $shouldSendEmail
            ]);
            
            // Kiểm tra in-app preference → Quyết định có tạo in-app notification không
            if (!$shouldSendInApp) {
                // Nếu không bật in-app, kiểm tra email → Nếu email cũng tắt thì không tạo notification
                if (!$shouldSendEmail) {
                    Log::warning('Both in-app and email notifications are disabled', [
                        'audit_log_id' => $auditLog->id,
                        'user_id' => $user->id,
                        'entity_type' => $entityType
                    ]);
                    return null; // Cả email và in-app đều tắt → Không tạo notification
                }
            }
            
            $subject = $this->generateSubject($auditLog, $entity); // Tạo subject từ audit log và entity → Dùng để hiển thị tiêu đề
            $content = $this->generateContent($auditLog, $entity); // Tạo content từ audit log và entity → Dùng để hiển thị nội dung

            // Tạo in-app notification record → Lưu vào database (action_url sẽ generate từ audit_log khi cần)
            $notification = Notification::create([
                'audit_log_id' => $auditLog->id, // Link với audit log → Dùng để tạo entity link
                'channel_id' => 1, // Channel ID = 1 (in-app) → Xác định kênh gửi
                'to_user_id' => $user->id, // User nhận notification → Xác định user nhận
                'subject' => $subject, // Tiêu đề notification → Hiển thị trong UI
                'content' => $content, // Nội dung notification → Hiển thị trong UI
                'status' => 'queued', // Trạng thái mặc định = 'queued' (chưa đọc) → Dùng để filter
                'created_at' => now(), // Thời gian tạo → Dùng để sắp xếp
            ]);
            
            // Broadcast notification event for real-time updates (sync, không dùng queue)
            // Tạm thời tắt broadcast để tránh lỗi khi không có bảng jobs
            // Có thể bật lại sau khi setup broadcasting đầy đủ
            /*
            if ($notification && $notification->channel_id == 1) {
                try {
                    // Dùng broadcastNow() để broadcast sync, không cần bảng jobs
                    broadcast(new NotificationCreated($notification));
                } catch (\Exception $e) {
                    // Log error but don't fail the notification creation
                    Log::warning('Failed to broadcast notification event', [
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            */
            
            // Gửi email notification nếu được bật và user là tenant → Thông báo qua email
            if ($preferenceService->shouldSendEmail($user, $entityType)) {
                // QUAN TRỌNG: Lấy role của user trong organization CỤ THỂ của audit log
                // User có thể là manager ở org A nhưng tenant ở org B
                // Cần check role trong organization của audit log, không phải organization khác
                $userRole = $this->getUserRole($user, $auditLog->organization_id); // Lấy role của user trong organization cụ thể
                
                // Chỉ gửi email cho tenant trong organization này
                // Không quan tâm user có role gì ở organization khác
                if ($userRole === 'tenant' && !empty($user->email)) { // Nếu user là tenant trong organization này và có email
                    // Tạo action URL cho email → Dùng để link đến entity trong email
                    $actionUrl = $this->generateActionUrl($auditLog, $entity);
                    $this->sendEmailNotification($user, $auditLog, $entity, $subject, $content, $actionUrl); // Gửi email notification → Thông báo qua email
                } else {
                    // Log để debug nếu không gửi email
                    Log::info('Email not sent - user role check', [
                        'user_id' => $user->id,
                        'user_role_in_org' => $userRole,
                        'organization_id' => $auditLog->organization_id,
                        'entity_type' => $auditLog->entity_type,
                        'has_email' => !empty($user->email),
                        'reason' => $userRole !== 'tenant' ? 'not_tenant_in_org' : 'no_email'
                    ]);
                }
            }
            
            return $notification; // Trả về notification đã tạo → Dùng để tracking
        } catch (\Exception $e) {
            Log::error('Failed to create notification', [
                'audit_log_id' => $auditLog->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Để debug, nhưng không throw exception
            return null; // Trả về null nếu có lỗi → Đảm bảo không crash
        }
    }

    /**
     * Gửi email notification cho tenant
     */
    private function sendEmailNotification(User $user, AuditLog $auditLog, $entity, string $subject, string $content, ?string $actionUrl = null): void
    {
        try {
            // Lấy organization_id từ audit log để apply mail config
            $organizationId = $auditLog->organization_id;
            
            $emailService = app(NotificationEmailService::class);
            
            // Xác định type dựa trên entity type
            $type = match($auditLog->entity_type) {
                'lease' => 'info',
                'invoice' => 'success',
                'payment' => 'success',
                'ticket' => 'warning',
                'ticketlog' => 'info',
                'depositrefund' => 'success',
                'review' => 'info',
                'reviewreply' => 'info',
                default => 'info',
            };
            
            // Sử dụng actionUrl đã được tạo hoặc tạo mới nếu chưa có
            if (!$actionUrl) {
                $actionUrl = $this->generateActionUrl($auditLog, $entity);
            }
            
            Log::info('Sending email notification to tenant', [
                'audit_log_id' => $auditLog->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'entity_type' => $auditLog->entity_type,
                'entity_id' => $auditLog->entity_id,
                'organization_id' => $organizationId,
                'has_action_url' => !empty($actionUrl),
            ]);
            
            // Gửi email với organization_id để apply mail config
            $result = $emailService->sendNotificationWithOrgConfig(
                $user,
                $subject,
                $content,
                $type,
                $actionUrl,
                'Xem chi tiết',
                false, // Không lưu vào database vì đã có notification record
                $organizationId // Pass organization_id để apply mail config
            );
            
            if ($result['success']) {
                Log::info('Email notification sent to tenant successfully', [
                    'audit_log_id' => $auditLog->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'entity_type' => $auditLog->entity_type,
                ]);
            } else {
                Log::warning('Email notification failed but not blocking', [
                    'audit_log_id' => $auditLog->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            // KHÔNG throw exception - email failure không nên block notification creation
            Log::error('Failed to send email notification (non-blocking)', [
                'audit_log_id' => $auditLog->id,
                'user_id' => $user->id,
                'user_email' => $user->email ?? 'N/A',
                'entity_type' => $auditLog->entity_type ?? 'N/A',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * Tạo action URL dựa trên entity type
     */
    private function generateActionUrl(AuditLog $auditLog, $entity): ?string
    {
        try {
            return match($auditLog->entity_type) {
                'lease' => $entity ? route('tenant.contracts.show', $entity->id) : null,
                'invoice' => $entity ? route('tenant.invoices.show', $entity->id) : null,
                'payment' => $entity ? route('tenant.payments.status', $entity->id) : null,
                'ticket' => $entity ? route('tenant.tickets.show', $entity->id) : null,
                'ticketlog' => $entity && $entity->ticket_id ? route('tenant.tickets.show', $entity->ticket_id) : null,
                'review' => $entity ? route('tenant.reviews.show', $entity->id) : null,
                'reviewreply' => $entity && $entity->review_id ? route('tenant.reviews.show', $entity->review_id) : null,
                'depositrefund' => $entity && $entity->lease_id ? route('tenant.contracts.show', $entity->lease_id) : null,
                default => null,
            };
        } catch (\Exception $e) {
            Log::warning('Failed to generate action URL', [
                'audit_log_id' => $auditLog->id ?? null,
                'entity_type' => $auditLog->entity_type ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Kiểm tra xem user có nên nhận notification này không
     */
    private function shouldUserReceiveNotification(User $user, AuditLog $auditLog, $entity): bool
    {
        // Kiểm tra role của user
        $userRole = $this->getUserRole($user, $auditLog->organization_id);
        
        Log::info('Checking if user should receive notification', [
            'user_id' => $user->id,
            'user_role' => $userRole,
            'audit_log_id' => $auditLog->id,
            'entity_type' => $auditLog->entity_type,
            'action' => $auditLog->action
        ]);
        
        // Manager luôn nhận notifications trong organization của họ
        if ($userRole === 'manager') {
            Log::info('User is manager, allowing notification', [
                'user_id' => $user->id,
                'audit_log_id' => $auditLog->id
            ]);
            return true;
        }
        
        // Tenant chỉ nhận notifications về dữ liệu của mình
        if ($userRole === 'tenant') {
            $isOwner = $this->isEntityOwnedByTenant($user, $entity, $auditLog->entity_type);
            Log::info('User is tenant, checking ownership', [
                'user_id' => $user->id,
                'audit_log_id' => $auditLog->id,
                'is_owner' => $isOwner,
                'entity_type' => $auditLog->entity_type,
                'entity_id' => $auditLog->entity_id,
                'entity_class' => $entity ? get_class($entity) : 'null',
                'entity_data' => $entity ? [
                    'id' => $entity->id ?? null,
                    'lease_id' => $entity->lease_id ?? null,
                    'tenant_id' => $entity->tenant_id ?? null,
                ] : null
            ]);
            return $isOwner;
        }
        
        // Staff (agent) nhận notifications trong organization của họ
        if ($userRole === 'agent' || $userRole === 'staff') {
            Log::info('User is agent/staff, allowing notification', [
                'user_id' => $user->id,
                'audit_log_id' => $auditLog->id
            ]);
            return true; // Hoặc có thể thêm logic filter nếu cần
        }
        
        Log::warning('User should not receive notification', [
            'user_id' => $user->id,
            'user_role' => $userRole,
            'audit_log_id' => $auditLog->id
        ]);
        
        return false;
    }

    /**
     * Kiểm tra xem entity có thuộc về tenant không
     */
    private function isEntityOwnedByTenant(User $tenant, $entity, string $entityType): bool
    {
        $tenantId = $tenant->id;
        
        if (!$entity) {
            Log::warning('isEntityOwnedByTenant: Entity is null', [
                'tenant_id' => $tenantId,
                'entity_type' => $entityType
            ]);
            return false;
        }
        
        switch ($entityType) {
            case 'lease':
                if ($entity instanceof Lease) {
                    // Convert cả hai về int để so sánh (tránh type mismatch)
                    $leaseTenantId = $entity->tenant_id ? (int) $entity->tenant_id : null;
                    $tenantIdInt = (int) $tenantId;
                    $result = $leaseTenantId === $tenantIdInt;
                    
                    Log::info('isEntityOwnedByTenant: lease check', [
                        'tenant_id' => $tenantId,
                        'tenant_id_int' => $tenantIdInt,
                        'lease_id' => $entity->id,
                        'lease_tenant_id' => $entity->tenant_id,
                        'lease_tenant_id_int' => $leaseTenantId,
                        'result' => $result,
                        'comparison' => "{$leaseTenantId} === {$tenantIdInt}"
                    ]);
                    return $result;
                }
                Log::warning('isEntityOwnedByTenant: entity is not Lease instance', [
                    'tenant_id' => $tenantId,
                    'entity_class' => get_class($entity),
                    'entity_id' => $entity->id ?? null
                ]);
                return false;
                
            case 'invoice':
                if ($entity instanceof Invoice) {
                    if (!$entity->lease_id) {
                        Log::warning('isEntityOwnedByTenant: invoice has no lease_id', [
                            'tenant_id' => $tenantId,
                            'invoice_id' => $entity->id,
                            'lease_id' => null
                        ]);
                        return false;
                    }
                    
                    // Sử dụng relationship nếu đã load, nếu không thì query
                    $lease = $entity->relationLoaded('lease') ? $entity->lease : 
                             Lease::find($entity->lease_id);
                    
                    if (!$lease) {
                        Log::warning('isEntityOwnedByTenant: lease not found for invoice', [
                            'tenant_id' => $tenantId,
                            'invoice_id' => $entity->id,
                            'lease_id' => $entity->lease_id
                        ]);
                        return false;
                    }
                    
                    // Convert cả hai về int để so sánh (tránh type mismatch)
                    $leaseTenantId = (int) $lease->tenant_id;
                    $tenantIdInt = (int) $tenantId;
                    $result = $leaseTenantId === $tenantIdInt;
                    
                    Log::info('isEntityOwnedByTenant: invoice check', [
                        'tenant_id' => $tenantId,
                        'tenant_id_int' => $tenantIdInt,
                        'invoice_id' => $entity->id,
                        'lease_id' => $entity->lease_id,
                        'lease_tenant_id' => $lease->tenant_id,
                        'lease_tenant_id_int' => $leaseTenantId,
                        'result' => $result,
                        'comparison' => "{$leaseTenantId} === {$tenantIdInt}"
                    ]);
                    return $result;
                }
                Log::warning('isEntityOwnedByTenant: entity is not Invoice instance', [
                    'tenant_id' => $tenantId,
                    'entity_class' => get_class($entity),
                    'entity_id' => $entity->id ?? null
                ]);
                return false;
                
            case 'invoiceitem':
                if ($entity instanceof InvoiceItem) {
                    if (!$entity->invoice_id) {
                        Log::warning('isEntityOwnedByTenant: invoiceitem has no invoice_id', [
                            'tenant_id' => $tenantId,
                            'invoiceitem_id' => $entity->id,
                            'invoice_id' => null
                        ]);
                        return false;
                    }
                    
                    // Sử dụng relationship nếu đã load
                    $invoice = $entity->relationLoaded('invoice') ? $entity->invoice : 
                               Invoice::find($entity->invoice_id);
                    
                    if (!$invoice) {
                        Log::warning('isEntityOwnedByTenant: invoice not found for invoiceitem', [
                            'tenant_id' => $tenantId,
                            'invoiceitem_id' => $entity->id,
                            'invoice_id' => $entity->invoice_id
                        ]);
                        return false;
                    }
                    
                    if (!$invoice->lease_id) {
                        Log::warning('isEntityOwnedByTenant: invoice has no lease_id', [
                            'tenant_id' => $tenantId,
                            'invoiceitem_id' => $entity->id,
                            'invoice_id' => $invoice->id,
                            'lease_id' => null
                        ]);
                        return false;
                    }
                    
                    $lease = $invoice->relationLoaded('lease') ? $invoice->lease : 
                             Lease::find($invoice->lease_id);
                    
                    if (!$lease) {
                        Log::warning('isEntityOwnedByTenant: lease not found from invoice', [
                            'tenant_id' => $tenantId,
                            'invoiceitem_id' => $entity->id,
                            'invoice_id' => $invoice->id,
                            'lease_id' => $invoice->lease_id
                        ]);
                        return false;
                    }
                    
                    // Convert cả hai về int để so sánh (tránh type mismatch)
                    $leaseTenantId = (int) $lease->tenant_id;
                    $tenantIdInt = (int) $tenantId;
                    $result = $leaseTenantId === $tenantIdInt;
                    
                    Log::info('isEntityOwnedByTenant: invoiceitem check', [
                        'tenant_id' => $tenantId,
                        'tenant_id_int' => $tenantIdInt,
                        'invoiceitem_id' => $entity->id,
                        'invoice_id' => $entity->invoice_id,
                        'lease_id' => $invoice->lease_id,
                        'lease_tenant_id' => $lease->tenant_id,
                        'lease_tenant_id_int' => $leaseTenantId,
                        'result' => $result,
                        'comparison' => "{$leaseTenantId} === {$tenantIdInt}"
                    ]);
                    return $result;
                }
                Log::warning('isEntityOwnedByTenant: entity is not InvoiceItem instance', [
                    'tenant_id' => $tenantId,
                    'entity_class' => get_class($entity),
                    'entity_id' => $entity->id ?? null
                ]);
                return false;
                
            case 'payment':
                if ($entity instanceof Payment) {
                    // Payment chỉ có invoice_id, không có lease_id
                    if (!$entity->invoice_id) {
                        Log::warning('isEntityOwnedByTenant: payment has no invoice_id', [
                            'tenant_id' => $tenantId,
                            'payment_id' => $entity->id,
                            'invoice_id' => null
                        ]);
                        return false;
                    }
                    
                    // Sử dụng relationship nếu đã load
                    $invoice = $entity->relationLoaded('invoice') ? $entity->invoice : 
                               Invoice::find($entity->invoice_id);
                    
                    if (!$invoice) {
                        Log::warning('isEntityOwnedByTenant: invoice not found for payment', [
                            'tenant_id' => $tenantId,
                            'payment_id' => $entity->id,
                            'invoice_id' => $entity->invoice_id
                        ]);
                        return false;
                    }
                    
                    if (!$invoice->lease_id) {
                        Log::warning('isEntityOwnedByTenant: invoice has no lease_id', [
                            'tenant_id' => $tenantId,
                            'payment_id' => $entity->id,
                            'invoice_id' => $invoice->id,
                            'lease_id' => null
                        ]);
                        return false;
                    }
                    
                    $lease = $invoice->relationLoaded('lease') ? $invoice->lease : 
                             Lease::find($invoice->lease_id);
                    
                    if (!$lease) {
                        Log::warning('isEntityOwnedByTenant: lease not found from invoice', [
                            'tenant_id' => $tenantId,
                            'payment_id' => $entity->id,
                            'invoice_id' => $invoice->id,
                            'lease_id' => $invoice->lease_id
                        ]);
                        return false;
                    }
                    
                    // Convert cả hai về int để so sánh (tránh type mismatch)
                    $leaseTenantId = (int) $lease->tenant_id;
                    $tenantIdInt = (int) $tenantId;
                    $result = $leaseTenantId === $tenantIdInt;
                    
                    Log::info('isEntityOwnedByTenant: payment check', [
                        'tenant_id' => $tenantId,
                        'tenant_id_int' => $tenantIdInt,
                        'payment_id' => $entity->id,
                        'invoice_id' => $entity->invoice_id,
                        'lease_id' => $invoice->lease_id,
                        'lease_tenant_id' => $lease->tenant_id,
                        'lease_tenant_id_int' => $leaseTenantId,
                        'result' => $result,
                        'comparison' => "{$leaseTenantId} === {$tenantIdInt}"
                    ]);
                    return $result;
                }
                Log::warning('isEntityOwnedByTenant: entity is not Payment instance', [
                    'tenant_id' => $tenantId,
                    'entity_class' => get_class($entity),
                    'entity_id' => $entity->id ?? null
                ]);
                return false;
                
            case 'ticket':
                if ($entity instanceof Ticket) {
                    if (!$entity->lease_id) {
                        Log::warning('isEntityOwnedByTenant: ticket has no lease_id', [
                            'tenant_id' => $tenantId,
                            'ticket_id' => $entity->id,
                            'lease_id' => null
                        ]);
                        return false;
                    }
                    
                    // Sử dụng relationship nếu đã load
                    $lease = $entity->relationLoaded('lease') ? $entity->lease : 
                             Lease::find($entity->lease_id);
                    
                    if (!$lease) {
                        Log::warning('isEntityOwnedByTenant: lease not found', [
                            'tenant_id' => $tenantId,
                            'ticket_id' => $entity->id,
                            'lease_id' => $entity->lease_id
                        ]);
                        return false;
                    }
                    
                    // Convert cả hai về int để so sánh (tránh type mismatch)
                    $leaseTenantId = (int) $lease->tenant_id;
                    $tenantIdInt = (int) $tenantId;
                    $result = $leaseTenantId === $tenantIdInt;
                    
                    Log::info('isEntityOwnedByTenant: ticket check', [
                        'tenant_id' => $tenantId,
                        'tenant_id_int' => $tenantIdInt,
                        'ticket_id' => $entity->id,
                        'lease_id' => $entity->lease_id,
                        'lease_tenant_id' => $lease->tenant_id,
                        'lease_tenant_id_int' => $leaseTenantId,
                        'result' => $result,
                        'comparison' => "{$leaseTenantId} === {$tenantIdInt}"
                    ]);
                    return $result;
                }
                Log::warning('isEntityOwnedByTenant: entity is not Ticket instance', [
                    'tenant_id' => $tenantId,
                    'entity_class' => get_class($entity),
                    'entity_id' => $entity->id ?? null
                ]);
                return false;
                
            case 'ticketlog':
                if ($entity instanceof \App\Models\TicketLog) {
                    if (!$entity->ticket_id) {
                        Log::warning('isEntityOwnedByTenant: ticketlog has no ticket_id', [
                            'tenant_id' => $tenantId,
                            'ticketlog_id' => $entity->id,
                            'ticket_id' => null
                        ]);
                        return false;
                    }
                    
                    // Sử dụng relationship nếu đã load
                    $ticket = $entity->relationLoaded('ticket') ? $entity->ticket : 
                              Ticket::find($entity->ticket_id);
                    
                    if (!$ticket) {
                        Log::warning('isEntityOwnedByTenant: ticket not found', [
                            'tenant_id' => $tenantId,
                            'ticketlog_id' => $entity->id,
                            'ticket_id' => $entity->ticket_id
                        ]);
                        return false;
                    }
                    
                    if (!$ticket->lease_id) {
                        Log::warning('isEntityOwnedByTenant: ticket has no lease_id', [
                            'tenant_id' => $tenantId,
                            'ticketlog_id' => $entity->id,
                            'ticket_id' => $ticket->id,
                            'lease_id' => null
                        ]);
                        return false;
                    }
                    
                    $lease = $ticket->relationLoaded('lease') ? $ticket->lease : 
                             Lease::find($ticket->lease_id);
                    
                    if (!$lease) {
                        Log::warning('isEntityOwnedByTenant: lease not found from ticket', [
                            'tenant_id' => $tenantId,
                            'ticketlog_id' => $entity->id,
                            'ticket_id' => $ticket->id,
                            'lease_id' => $ticket->lease_id
                        ]);
                        return false;
                    }
                    
                    // Convert cả hai về int để so sánh (tránh type mismatch)
                    $leaseTenantId = (int) $lease->tenant_id;
                    $tenantIdInt = (int) $tenantId;
                    $result = $leaseTenantId === $tenantIdInt;
                    
                    Log::info('isEntityOwnedByTenant: ticketlog check', [
                        'tenant_id' => $tenantId,
                        'tenant_id_int' => $tenantIdInt,
                        'ticketlog_id' => $entity->id,
                        'ticket_id' => $entity->ticket_id,
                        'lease_id' => $ticket->lease_id,
                        'lease_tenant_id' => $lease->tenant_id,
                        'lease_tenant_id_int' => $leaseTenantId,
                        'result' => $result,
                        'comparison' => "{$leaseTenantId} === {$tenantIdInt}"
                    ]);
                    return $result;
                }
                Log::warning('isEntityOwnedByTenant: entity is not TicketLog instance', [
                    'tenant_id' => $tenantId,
                    'entity_class' => get_class($entity),
                    'entity_id' => $entity->id ?? null
                ]);
                return false;
                
            case 'depositrefund':
                if ($entity instanceof \App\Models\DepositRefund) {
                    // DepositRefund có tenant_id trực tiếp
                    if ($entity->tenant_id) {
                        $depositRefundTenantId = (int) $entity->tenant_id;
                        $tenantIdInt = (int) $tenantId;
                        $result = $depositRefundTenantId === $tenantIdInt;
                        
                        Log::info('isEntityOwnedByTenant: depositrefund check by tenant_id', [
                            'tenant_id' => $tenantId,
                            'tenant_id_int' => $tenantIdInt,
                            'depositrefund_id' => $entity->id,
                            'depositrefund_tenant_id' => $entity->tenant_id,
                            'depositrefund_tenant_id_int' => $depositRefundTenantId,
                            'result' => $result,
                        ]);
                        return $result;
                    }
                    
                    // Fallback: Kiểm tra qua lease_id
                    if ($entity->lease_id) {
                        $lease = $entity->relationLoaded('lease') ? $entity->lease : 
                                 Lease::find($entity->lease_id);
                        
                        if ($lease && $lease->tenant_id) {
                            $leaseTenantId = (int) $lease->tenant_id;
                            $tenantIdInt = (int) $tenantId;
                            $result = $leaseTenantId === $tenantIdInt;
                            
                            Log::info('isEntityOwnedByTenant: depositrefund check by lease_id', [
                                'tenant_id' => $tenantId,
                                'tenant_id_int' => $tenantIdInt,
                                'depositrefund_id' => $entity->id,
                                'lease_id' => $entity->lease_id,
                                'lease_tenant_id' => $lease->tenant_id,
                                'lease_tenant_id_int' => $leaseTenantId,
                                'result' => $result,
                            ]);
                            return $result;
                        }
                    }
                    
                    Log::warning('isEntityOwnedByTenant: depositrefund has no tenant_id or lease_id', [
                        'tenant_id' => $tenantId,
                        'depositrefund_id' => $entity->id,
                        'depositrefund_tenant_id' => $entity->tenant_id,
                        'depositrefund_lease_id' => $entity->lease_id,
                    ]);
                    return false;
                }
                return false;
                
            case 'review':
                if ($entity instanceof \App\Models\Review) {
                    if (!$entity->lease_id) {
                        Log::warning('isEntityOwnedByTenant: review has no lease_id', [
                            'tenant_id' => $tenantId,
                            'review_id' => $entity->id,
                            'lease_id' => null
                        ]);
                        return false;
                    }
                    
                    $lease = $entity->relationLoaded('lease') ? $entity->lease : 
                             Lease::find($entity->lease_id);
                    
                    if (!$lease) {
                        Log::warning('isEntityOwnedByTenant: lease not found for review', [
                            'tenant_id' => $tenantId,
                            'review_id' => $entity->id,
                            'lease_id' => $entity->lease_id
                        ]);
                        return false;
                    }
                    
                    // Convert cả hai về int để so sánh (tránh type mismatch)
                    $leaseTenantId = (int) $lease->tenant_id;
                    $tenantIdInt = (int) $tenantId;
                    $result = $leaseTenantId === $tenantIdInt;
                    
                    Log::info('isEntityOwnedByTenant: review check', [
                        'tenant_id' => $tenantId,
                        'tenant_id_int' => $tenantIdInt,
                        'review_id' => $entity->id,
                        'lease_id' => $entity->lease_id,
                        'lease_tenant_id' => $lease->tenant_id,
                        'lease_tenant_id_int' => $leaseTenantId,
                        'result' => $result,
                        'comparison' => "{$leaseTenantId} === {$tenantIdInt}"
                    ]);
                    return $result;
                }
                Log::warning('isEntityOwnedByTenant: entity is not Review instance', [
                    'tenant_id' => $tenantId,
                    'entity_class' => get_class($entity),
                    'entity_id' => $entity->id ?? null
                ]);
                return false;
                
            case 'reviewreply':
                if ($entity instanceof ReviewReply) {
                    if (!$entity->review_id) {
                        Log::warning('isEntityOwnedByTenant: reviewreply has no review_id', [
                            'tenant_id' => $tenantId,
                            'reviewreply_id' => $entity->id,
                            'review_id' => null
                        ]);
                        return false;
                    }
                    
                    $review = $entity->relationLoaded('review') ? $entity->review : 
                              \App\Models\Review::find($entity->review_id);
                    
                    if (!$review) {
                        Log::warning('isEntityOwnedByTenant: review not found for reviewreply', [
                            'tenant_id' => $tenantId,
                            'reviewreply_id' => $entity->id,
                            'review_id' => $entity->review_id
                        ]);
                        return false;
                    }
                    
                    if (!$review->lease_id) {
                        Log::warning('isEntityOwnedByTenant: review has no lease_id', [
                            'tenant_id' => $tenantId,
                            'reviewreply_id' => $entity->id,
                            'review_id' => $review->id,
                            'lease_id' => null
                        ]);
                        return false;
                    }
                    
                    $lease = $review->relationLoaded('lease') ? $review->lease : 
                             Lease::find($review->lease_id);
                    
                    if (!$lease) {
                        Log::warning('isEntityOwnedByTenant: lease not found from review', [
                            'tenant_id' => $tenantId,
                            'reviewreply_id' => $entity->id,
                            'review_id' => $review->id,
                            'lease_id' => $review->lease_id
                        ]);
                        return false;
                    }
                    
                    // Convert cả hai về int để so sánh (tránh type mismatch)
                    $leaseTenantId = (int) $lease->tenant_id;
                    $tenantIdInt = (int) $tenantId;
                    $result = $leaseTenantId === $tenantIdInt;
                    
                    Log::info('isEntityOwnedByTenant: reviewreply check', [
                        'tenant_id' => $tenantId,
                        'tenant_id_int' => $tenantIdInt,
                        'reviewreply_id' => $entity->id,
                        'review_id' => $entity->review_id,
                        'lease_id' => $review->lease_id,
                        'lease_tenant_id' => $lease->tenant_id,
                        'lease_tenant_id_int' => $leaseTenantId,
                        'result' => $result,
                        'comparison' => "{$leaseTenantId} === {$tenantIdInt}"
                    ]);
                    return $result;
                }
                Log::warning('isEntityOwnedByTenant: entity is not ReviewReply instance', [
                    'tenant_id' => $tenantId,
                    'entity_class' => get_class($entity),
                    'entity_id' => $entity->id ?? null
                ]);
                return false;
                
            default:
                return false;
        }
    }

    /**
     * Lấy role của user trong organization
     */
    private function getUserRole(User $user, ?int $organizationId): ?string
    {
        if (!$organizationId) {
            return null;
        }
        
        $orgUser = DB::table('organization_users')
            ->join('roles', 'organization_users.role_id', '=', 'roles.id')
            ->where('organization_users.user_id', $user->id)
            ->where('organization_users.organization_id', $organizationId)
            ->where('organization_users.status', 'active')
            ->whereNull('organization_users.deleted_at')
            ->select('roles.key_code')
            ->first();
        
        return $orgUser?->key_code;
    }

    /**
     * Generate notification subject
     */
    private function generateSubject(AuditLog $auditLog, $entity): string
    {
        $actionText = $this->getActionText($auditLog->action);
        $entityName = $this->getEntityName($auditLog, $entity);
        $entityTypeText = $this->getEntityTypeText($auditLog->entity_type);

        return match($auditLog->entity_type) {
            'lease' => "Hợp đồng {$actionText} - {$entityName}",
            'invoice' => "Hóa đơn {$actionText} - {$entityName}",
            'payment' => "Thanh toán {$actionText} - {$entityName}",
            'ticket' => "Ticket {$actionText} - {$entityName}",
            'review' => "Đánh giá {$actionText} - {$entityName}",
            'viewing' => "Xem bất động sản {$actionText} - {$entityName}",
            'bookingdeposit' => "Đặt cọc {$actionText} - {$entityName}",
            'ticketlog' => "Nhật ký ticket {$actionText} - {$entityName}",
            'depositrefund' => "Hoàn tiền cọc {$actionText} - {$entityName}",
            'companyinvoice' => "Hóa đơn công ty {$actionText} - {$entityName}",
            'billingpolicy' => "Chính sách thanh toán {$actionText} - {$entityName}",
            'reviewreply' => "Phản hồi đánh giá {$actionText} - {$entityName}",
            'payrollpayslip' => "Phiếu lương {$actionText} - {$entityName}",
            'salaryadvance' => "Tạm ứng lương {$actionText} - {$entityName}",
            // 'leaseservice' => "Dịch vụ hợp đồng {$actionText} - {$entityName}", // DEPRECATED - LeaseService model removed
            'leaseserviceset' => "Bộ dịch vụ hợp đồng {$actionText} - {$entityName}",
            'leaseservicesetitem' => "Dịch vụ trong bộ {$actionText} - {$entityName}",
            'masterlease' => "Hợp đồng chính {$actionText} - {$entityName}",
            'property' => "Bất động sản {$actionText} - {$entityName}",
            'lead' => "Khách hàng tiềm năng {$actionText} - {$entityName}",
            default => "{$entityTypeText} {$actionText} - {$entityName}",
        };
    }

    /**
     * Get entity type text in Vietnamese
     */
    private function getEntityTypeText(string $entityType): string
    {
        $entityTypeMap = [
            'lease' => 'Hợp đồng',
            'invoice' => 'Hóa đơn',
            'payment' => 'Thanh toán',
            'ticket' => 'Ticket',
            'review' => 'Đánh giá',
            'viewing' => 'Xem bất động sản',
            'bookingdeposit' => 'Đặt cọc',
            'ticketlog' => 'Nhật ký ticket',
            'depositrefund' => 'Hoàn tiền cọc',
            'companyinvoice' => 'Hóa đơn công ty',
            'billingpolicy' => 'Chính sách thanh toán',
            'reviewreply' => 'Phản hồi đánh giá',
            'payrollpayslip' => 'Phiếu lương',
            'salaryadvance' => 'Tạm ứng lương',
            // 'leaseservice' => 'Dịch vụ hợp đồng', // DEPRECATED - LeaseService model removed
            'leaseserviceset' => 'Bộ dịch vụ hợp đồng',
            'leaseservicesetitem' => 'Dịch vụ trong bộ',
            'masterlease' => 'Hợp đồng chính',
            'property' => 'Bất động sản',
            'lead' => 'Khách hàng tiềm năng',
        ];

        return $entityTypeMap[strtolower($entityType)] ?? ucfirst($entityType);
    }

    /**
     * Generate notification content
     */
    private function generateContent(AuditLog $auditLog, $entity): string
    {
        $actionText = $this->getActionText($auditLog->action);
        $entityName = $this->getEntityName($auditLog, $entity);
        $entityTypeText = $this->getEntityTypeText($auditLog->entity_type);
        
        // Actor info
        $actorName = 'Hệ thống';
        if ($auditLog->actor_id) {
            try {
                $actor = $auditLog->actor ?? User::find($auditLog->actor_id);
                if ($actor) {
                    $actorName = $actor->userProfile->full_name ?? $actor->email ?? 'Người dùng #' . $actor->id;
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }
        
        $content = "{$entityTypeText} {$entityName} đã được {$actionText} bởi {$actorName}.";
        
        // Ưu tiên lấy thông tin từ changes_json (đặc biệt cho created/updated)
        $detailsFromChanges = $this->getDetailsFromChanges($auditLog);
        
        if ($detailsFromChanges) {
            // Nếu có changes_json, sử dụng nó
            $content .= "\n\n" . $detailsFromChanges;
        } elseif ($entity) {
            // Fallback sang entity details nếu không có changes_json
            $details = $this->getEntityDetails($auditLog, $entity);
            if ($details) {
                $content .= "\n\n" . $details;
            }
        }

        return $content;
    }

    /**
     * Lấy thông tin chi tiết từ changes_json
     */
    private function getDetailsFromChanges(AuditLog $auditLog): ?string
    {
        $details = [];
        
        try {
            // Lấy changes từ changes_json hoặc after_json (cho created)
            $changes = null;
            $afterData = null;
            
            if ($auditLog->changes_json) {
                $changes = is_string($auditLog->changes_json) 
                    ? json_decode($auditLog->changes_json, true) 
                    : $auditLog->changes_json;
            }
            
            // Cho created action, lấy từ after_json
            if ($auditLog->action === 'created' && $auditLog->after_json) {
                $afterData = is_string($auditLog->after_json) 
                    ? json_decode($auditLog->after_json, true) 
                    : $auditLog->after_json;
            }
            
            // Cho updated action, lấy từ changes_json
            if ($auditLog->action === 'updated' && $changes) {
                // DEPRECATED: leaseservice entity type - LeaseService model removed
                // if ($auditLog->entity_type === 'leaseservice') {
                //     // Lấy thông tin từ before_json hoặc after_json để có thông tin đầy đủ
                //     $serviceInfo = $this->getLeaseServiceInfo($auditLog);
                //     if ($serviceInfo) {
                //         $details[] = $serviceInfo;
                //     }
                //     
                //     // Thêm các thay đổi
                //     foreach ($changes as $field => $value) {
                //         $fieldName = $this->getFieldName($field);
                //         $formattedValue = $this->formatFieldValue($field, $value);
                //         $details[] = "{$fieldName}: {$formattedValue}";
                //     }
                // } else {
                    // Cho các entity khác, chỉ hiển thị changes
                    foreach ($changes as $field => $value) {
                        $fieldName = $this->getFieldName($field);
                        $formattedValue = $this->formatFieldValue($field, $value);
                        $details[] = "{$fieldName}: {$formattedValue}";
                    }
                // }
            }
            
            // Cho created action, lấy từ after_json
            if ($auditLog->action === 'created' && $afterData) {
                $details = $this->extractDetailsFromData($afterData, $auditLog->entity_type);
            }
            
            // Cho deleted action, lấy từ before_json
            if ($auditLog->action === 'deleted' && $auditLog->before_json) {
                $beforeData = is_string($auditLog->before_json) 
                    ? json_decode($auditLog->before_json, true) 
                    : $auditLog->before_json;
                if ($beforeData) {
                    $details = $this->extractDetailsFromData($beforeData, $auditLog->entity_type);
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Failed to extract details from changes_json', [
                'audit_log_id' => $auditLog->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return !empty($details) ? implode("\n", $details) : null;
    }

    /**
     * Extract details từ data (after_json hoặc before_json)
     */
    private function extractDetailsFromData(array $data, string $entityType): array
    {
        $details = [];
        
        try {
            switch ($entityType) {
                // DEPRECATED: leaseservice case - LeaseService model removed
                // case 'leaseservice':
                //     // Lấy thông tin từ data
                //     if (isset($data['service_id'])) {
                //         try {
                //             $service = \App\Models\Service::find($data['service_id']);
                //             if ($service) {
                //                 // Hiển thị mã dịch vụ và tên dịch vụ
                //                 $serviceDisplay = $service->name ?? 'N/A';
                //                 if ($service->key_code) {
                //                     $serviceDisplay = "{$service->key_code} - {$serviceDisplay}";
                //                 }
                //                 $details[] = "Mã dịch vụ: " . $serviceDisplay;
                //             } else {
                //                 $details[] = "Dịch vụ ID: " . $data['service_id'];
                //             }
                //         } catch (\Exception $e) {
                //             $details[] = "Dịch vụ ID: " . $data['service_id'];
                //         }
                //     }
                //     
                //     if (isset($data['price'])) {
                //         $details[] = "Giá: " . number_format((float)$data['price'], 0, ',', '.') . " VNĐ";
                //     }
                //     
                //     if (isset($data['lease_id'])) {
                //         try {
                //             $lease = \App\Models\Lease::with(['tenant.userProfile', 'unit.property'])->find($data['lease_id']);
                //             if ($lease) {
                //                 $details[] = "Số hợp đồng: " . ($lease->contract_no ?? 'Hợp đồng #' . $lease->id);
                //                 
                //                 if ($lease->tenant_id && $lease->tenant) {
                //                     $tenant = $lease->tenant;
                //                     $tenantName = $tenant->userProfile->full_name ?? $tenant->email ?? 'N/A';
                //                     $details[] = "Khách thuê: " . $tenantName;
                //                 }
                //                 
                //                 if ($lease->unit_id && $lease->unit) {
                //                     $unit = $lease->unit;
                //                     $details[] = "Đơn vị: " . ($unit->code ?? $unit->name ?? 'N/A');
                //                     
                //                     if ($unit->property) {
                //                         $details[] = "Bất động sản: " . ($unit->property->name ?? 'N/A');
                //                     }
                //                 }
                //             } else {
                //                 $details[] = "Số hợp đồng: #" . $data['lease_id'];
                //             }
                //         } catch (\Exception $e) {
                //             $details[] = "Số hợp đồng: #" . $data['lease_id'];
                //         }
                //     }
                //     break;
                    
                case 'leaseserviceset':
                    if (isset($data['name'])) {
                        $details[] = "Tên bộ dịch vụ: " . $data['name'];
                    }
                    if (isset($data['description'])) {
                        $details[] = "Mô tả: " . $data['description'];
                    }
                    if (isset($data['organization_id'])) {
                        try {
                            $organization = \App\Models\Organization::find($data['organization_id']);
                            if ($organization) {
                                $details[] = "Tổ chức: " . ($organization->name ?? 'N/A');
                            }
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }
                    break;
                    
                case 'leaseservicesetitem':
                    if (isset($data['service_id'])) {
                        try {
                            $service = \App\Models\Service::find($data['service_id']);
                            if ($service) {
                                $serviceDisplay = $service->name ?? 'N/A';
                                if ($service->key_code) {
                                    $serviceDisplay = "{$service->key_code} - {$serviceDisplay}";
                                }
                                $details[] = "Dịch vụ: " . $serviceDisplay;
                            }
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }
                    if (isset($data['price'])) {
                        $details[] = "Giá: " . number_format((float)$data['price'], 0, ',', '.') . " VNĐ";
                    }
                    if (isset($data['lease_service_set_id'])) {
                        try {
                            $set = \App\Models\LeaseServiceSet::find($data['lease_service_set_id']);
                            if ($set) {
                                $details[] = "Bộ dịch vụ: " . ($set->name ?? 'N/A');
                            }
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }
                    break;
                    
                case 'lease':
                    if (isset($data['contract_no'])) {
                        $details[] = "Số hợp đồng: " . $data['contract_no'];
                    }
                    if (isset($data['rent_amount'])) {
                        $details[] = "Tiền thuê: " . number_format((float)$data['rent_amount'], 0, ',', '.') . " VNĐ/tháng";
                    }
                    if (isset($data['deposit_amount'])) {
                        $details[] = "Tiền cọc: " . number_format((float)$data['deposit_amount'], 0, ',', '.') . " VNĐ";
                    }
                    if (isset($data['status'])) {
                        $details[] = "Trạng thái: " . $this->getStatusLabel($data['status'], 'lease');
                    }
                    if (isset($data['tenant_id'])) {
                        try {
                            $tenant = \App\Models\User::with('userProfile')->find($data['tenant_id']);
                            if ($tenant) {
                                $tenantName = $tenant->userProfile->full_name ?? $tenant->email ?? 'N/A';
                                $details[] = "Khách thuê: " . $tenantName;
                            }
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }
                    break;
                    
                case 'invoice':
                    if (isset($data['invoice_no'])) {
                        $details[] = "Số hóa đơn: " . $data['invoice_no'];
                    }
                    if (isset($data['total_amount'])) {
                        $details[] = "Tổng tiền: " . number_format((float)$data['total_amount'], 0, ',', '.') . " VNĐ";
                    }
                    if (isset($data['status'])) {
                        $details[] = "Trạng thái: " . $this->getStatusLabel($data['status'], 'invoice');
                    }
                    if (isset($data['due_date'])) {
                        try {
                            $details[] = "Hạn thanh toán: " . \Carbon\Carbon::parse($data['due_date'])->format('d/m/Y');
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }
                    break;
                    
                case 'payment':
                    if (isset($data['amount'])) {
                        $details[] = "Số tiền: " . number_format((float)$data['amount'], 0, ',', '.') . " VNĐ";
                    }
                    if (isset($data['status'])) {
                        $details[] = "Trạng thái: " . $this->getStatusLabel($data['status'], 'payment');
                    }
                    if (isset($data['payment_date'])) {
                        try {
                            $details[] = "Ngày thanh toán: " . \Carbon\Carbon::parse($data['payment_date'])->format('d/m/Y');
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }
                    break;
                    
                case 'ticket':
                    if (isset($data['title'])) {
                        $details[] = "Tiêu đề: " . $data['title'];
                    }
                    if (isset($data['description'])) {
                        $details[] = "Mô tả: " . $data['description'];
                    }
                    if (isset($data['status'])) {
                        $details[] = "Trạng thái: " . $this->getStatusLabel($data['status'], 'ticket');
                    }
                    if (isset($data['priority_id'])) {
                        try {
                            $priority = \App\Models\TicketPriority::find($data['priority_id']);
                            if ($priority) {
                                $details[] = "Độ ưu tiên: " . $priority->name;
                            }
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }
                    break;
                    
                case 'ticketlog':
                    if (isset($data['log_content'])) {
                        $details[] = "Nội dung: " . $data['log_content'];
                    }
                    if (isset($data['cost_amount']) && $data['cost_amount'] > 0) {
                        $details[] = "Chi phí: " . number_format((float)$data['cost_amount'], 0, ',', '.') . " VNĐ";
                    }
                    break;
                    
                case 'depositrefund':
                    if (isset($data['refund_amount'])) {
                        $details[] = "Số tiền hoàn: " . number_format((float)$data['refund_amount'], 0, ',', '.') . " VNĐ";
                    }
                    if (isset($data['status'])) {
                        $details[] = "Trạng thái: " . $this->getStatusLabel($data['status'], 'depositrefund');
                    }
                    if (isset($data['refund_method'])) {
                        $refundMethodLabels = [
                            'cash' => 'Tiền mặt',
                            'bank_transfer' => 'Chuyển khoản',
                            'wallet' => 'Ví điện tử',
                        ];
                        $details[] = "Phương thức: " . ($refundMethodLabels[$data['refund_method']] ?? $data['refund_method']);
                    }
                    break;
                    
                default:
                    // Cho các entity type khác, lấy các field quan trọng
                    $importantFields = ['name', 'title', 'status', 'amount', 'price', 'total_amount'];
                    foreach ($importantFields as $field) {
                        if (isset($data[$field])) {
                            $fieldName = $this->getFieldName($field);
                            $formattedValue = $this->formatFieldValue($field, $data[$field]);
                            $details[] = "{$fieldName}: {$formattedValue}";
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract details from data', [
                'entity_type' => $entityType,
                'error' => $e->getMessage()
            ]);
        }
        
        return $details;
    }

    /**
     * Lấy thông tin dịch vụ hợp đồng cho updated action
     */
    private function getLeaseServiceInfo(AuditLog $auditLog): ?string
    {
        try {
            $info = [];
            
            // Lấy thông tin từ before_json hoặc after_json
            $data = null;
            if ($auditLog->after_json) {
                $data = is_string($auditLog->after_json) 
                    ? json_decode($auditLog->after_json, true) 
                    : $auditLog->after_json;
            } elseif ($auditLog->before_json) {
                $data = is_string($auditLog->before_json) 
                    ? json_decode($auditLog->before_json, true) 
                    : $auditLog->before_json;
            }
            
            if (!$data) {
                return null;
            }
            
            // Lấy thông tin dịch vụ
            if (isset($data['service_id'])) {
                try {
                    $service = \App\Models\Service::find($data['service_id']);
                    if ($service) {
                        $serviceDisplay = $service->name ?? 'N/A';
                        if ($service->key_code) {
                            $serviceDisplay = "{$service->key_code} - {$serviceDisplay}";
                        }
                        $info[] = "Mã dịch vụ: " . $serviceDisplay;
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }
            
            // Lấy số hợp đồng
            if (isset($data['lease_id'])) {
                try {
                    $lease = \App\Models\Lease::find($data['lease_id']);
                    if ($lease && $lease->contract_no) {
                        $info[] = "Số hợp đồng: " . $lease->contract_no;
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }
            
            return !empty($info) ? implode("\n", $info) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get entity details for notification (fallback khi không có changes_json)
     */
    private function getEntityDetails(AuditLog $auditLog, $entity): ?string
    {
        $details = [];

        try {
            switch ($auditLog->entity_type) {
                case 'lease':
                    if ($entity instanceof \App\Models\Lease) {
                        $details[] = "Số hợp đồng: " . ($entity->contract_no ?? 'N/A');
                        $details[] = "Tiền thuê: " . number_format($entity->rent_amount ?? 0, 0, ',', '.') . " VNĐ/tháng";
                        $details[] = "Tiền cọc: " . number_format($entity->deposit_amount ?? 0, 0, ',', '.') . " VNĐ";
                        $details[] = "Trạng thái: " . $this->getStatusLabel($entity->status ?? 'N/A', 'lease');
                        
                        // Thông tin tenant
                        if ($entity->tenant_id && $entity->tenant) {
                            $tenantName = $entity->tenant->userProfile->full_name ?? $entity->tenant->email ?? 'N/A';
                            $details[] = "Khách thuê: " . $tenantName;
                        }
                        
                        // Thông tin bất động sản
                        if ($entity->unit_id) {
                            $entity->loadMissing(['unit.property']);
                            if ($entity->unit) {
                                $details[] = "Đơn vị: " . ($entity->unit->code ?? $entity->unit->name ?? 'N/A');
                                if ($entity->unit->property) {
                                    $details[] = "Bất động sản: " . ($entity->unit->property->name ?? 'N/A');
                                }
                            }
                        }
                    }
                    break;
                    
                // DEPRECATED: leaseservice case - LeaseService model removed
                // case 'leaseservice':
                //     if ($entity instanceof \App\Models\LeaseService) {
                //         // Thông tin dịch vụ (mã + tên)
                //         if ($entity->service) {
                //             $serviceDisplay = $entity->service->name ?? 'N/A';
                //             if ($entity->service->key_code) {
                //                 $serviceDisplay = "{$entity->service->key_code} - {$serviceDisplay}";
                //             }
                //             $details[] = "Mã dịch vụ: " . $serviceDisplay;
                //         }
                //         
                //         // Giá dịch vụ
                //         if ($entity->price) {
                //             $details[] = "Giá: " . number_format((float)$entity->price, 0, ',', '.') . " VNĐ";
                //         }
                //         
                //         // Thông tin hợp đồng
                //         if ($entity->lease_id && $entity->lease) {
                //             $details[] = "Số hợp đồng: " . ($entity->lease->contract_no ?? 'Hợp đồng #' . $entity->lease_id);
                //             
                //             // Thông tin tenant
                //             if ($entity->lease->tenant_id && $entity->lease->tenant) {
                //                 $tenant = $entity->lease->tenant;
                //                 $tenantName = $tenant->userProfile->full_name ?? $tenant->email ?? 'N/A';
                //                 $details[] = "Khách thuê: " . $tenantName;
                //             }
                //             
                //             // Thông tin bất động sản
                //             if ($entity->lease->unit_id && $entity->lease->unit) {
                //                 $unit = $entity->lease->unit;
                //                 $details[] = "Đơn vị: " . ($unit->code ?? $unit->name ?? 'N/A');
                //                 
                //                 if ($unit->property) {
                //                     $details[] = "Bất động sản: " . ($unit->property->name ?? 'N/A');
                //                 }
                //             }
                //         } else {
                //             // Nếu không có lease, ít nhất hiển thị lease_id
                //             $details[] = "Số hợp đồng: #" . $entity->lease_id;
                //         }
                //     }
                //     break;
                    
                case 'leaseserviceset':
                    if ($entity instanceof \App\Models\LeaseServiceSet) {
                        $details[] = "Tên bộ dịch vụ: " . ($entity->name ?? 'N/A');
                        if ($entity->description) {
                            $details[] = "Mô tả: " . $entity->description;
                        }
                        if ($entity->organization_id && $entity->organization) {
                            $details[] = "Tổ chức: " . ($entity->organization->name ?? 'N/A');
                        }
                        if ($entity->items) {
                            $itemsCount = $entity->items->count();
                            $details[] = "Số lượng dịch vụ: " . $itemsCount;
                        }
                    }
                    break;
                    
                case 'leaseservicesetitem':
                    if ($entity instanceof \App\Models\LeaseServiceSetItem) {
                        if ($entity->service) {
                            $serviceDisplay = $entity->service->name ?? 'N/A';
                            if ($entity->service->key_code) {
                                $serviceDisplay = "{$entity->service->key_code} - {$serviceDisplay}";
                            }
                            $details[] = "Dịch vụ: " . $serviceDisplay;
                        }
                        if ($entity->price) {
                            $details[] = "Giá: " . number_format((float)$entity->price, 0, ',', '.') . " VNĐ";
                        }
                        if ($entity->leaseServiceSet) {
                            $details[] = "Bộ dịch vụ: " . ($entity->leaseServiceSet->name ?? 'N/A');
                        }
                    }
                    break;
                    
                case 'invoice':
                    if ($entity instanceof \App\Models\Invoice) {
                        $details[] = "Số hóa đơn: " . ($entity->invoice_no ?? 'N/A');
                        $details[] = "Tổng tiền: " . number_format($entity->total_amount ?? 0, 0, ',', '.') . " VNĐ";
                        $details[] = "Trạng thái: " . $this->getStatusLabel($entity->status ?? 'N/A', 'invoice');
                        
                        // Thông tin hợp đồng
                        if ($entity->lease_id && $entity->lease) {
                            $details[] = "Hợp đồng: " . ($entity->lease->contract_no ?? 'Hợp đồng #' . $entity->lease_id);
                            
                            // Thông tin tenant
                            if ($entity->lease->tenant_id && $entity->lease->tenant) {
                                $tenantName = $entity->lease->tenant->userProfile->full_name ?? $entity->lease->tenant->email ?? 'N/A';
                                $details[] = "Khách thuê: " . $tenantName;
                            }
                        }
                        
                        // Ngày hết hạn
                        if ($entity->due_date) {
                            $details[] = "Hạn thanh toán: " . \Carbon\Carbon::parse($entity->due_date)->format('d/m/Y');
                        }
                    }
                    break;
                    
                case 'payment':
                    if ($entity instanceof \App\Models\Payment) {
                        $details[] = "Số tiền: " . number_format($entity->amount ?? 0, 0, ',', '.') . " VNĐ";
                        $details[] = "Trạng thái: " . $this->getStatusLabel($entity->status ?? 'N/A', 'payment');
                        
                        // Thông tin hợp đồng
                        if ($entity->lease_id && $entity->lease) {
                            $details[] = "Hợp đồng: " . ($entity->lease->contract_no ?? 'Hợp đồng #' . $entity->lease_id);
                        }
                        
                        // Thông tin hóa đơn
                        if ($entity->invoice_id && $entity->invoice) {
                            $details[] = "Hóa đơn: " . ($entity->invoice->invoice_no ?? 'Hóa đơn #' . $entity->invoice_id);
                        }
                        
                        // Ngày thanh toán
                        if ($entity->payment_date) {
                            $details[] = "Ngày thanh toán: " . \Carbon\Carbon::parse($entity->payment_date)->format('d/m/Y');
                        }
                    }
                    break;
                    
                case 'ticket':
                    if ($entity instanceof \App\Models\Ticket) {
                        $details[] = "Tiêu đề: " . ($entity->title ?? 'N/A');
                        
                        if ($entity->description) {
                            $details[] = "Mô tả: " . $entity->description;
                        }
                        
                        $details[] = "Trạng thái: " . $this->getStatusLabel($entity->status ?? 'N/A', 'ticket');
                        
                        // Độ ưu tiên
                        if ($entity->priority_id) {
                            $entity->loadMissing('priorityRelation');
                            if ($entity->priorityRelation) {
                                $details[] = "Độ ưu tiên: " . $entity->priorityRelation->name;
                            }
                        }
                        
                        // Thông tin bất động sản
                        if ($entity->unit_id) {
                            $entity->loadMissing(['unit.property']);
                            if ($entity->unit) {
                                $details[] = "Phòng: " . ($entity->unit->code ?? $entity->unit->name ?? 'N/A');
                                if ($entity->unit->property) {
                                    $details[] = "Bất động sản: " . ($entity->unit->property->name ?? 'N/A');
                                }
                            }
                        }
                    }
                    break;
                    
                case 'ticketlog':
                    if ($entity instanceof \App\Models\TicketLog) {
                        if ($entity->ticket_id) {
                            $entity->loadMissing('ticket');
                            if ($entity->ticket) {
                                $details[] = "Ticket: " . ($entity->ticket->title ?? '#' . $entity->ticket_id);
                            }
                        }
                        
                        if ($entity->log_content) {
                            $details[] = "Nội dung cập nhật: " . $entity->log_content;
                        }
                        
                        if ($entity->cost_amount && $entity->cost_amount > 0) {
                            $details[] = "Chi phí: " . number_format((float)$entity->cost_amount, 0, ',', '.') . " VNĐ";
                        }
                        
                        // Thông tin người cập nhật
                        if ($entity->created_by) {
                            $entity->loadMissing('createdBy.userProfile');
                            if ($entity->createdBy) {
                                $createdByName = $entity->createdBy->userProfile->full_name ?? $entity->createdBy->email ?? 'N/A';
                                $details[] = "Người cập nhật: " . $createdByName;
                            }
                        }
                    }
                    break;
                    
                case 'depositrefund':
                    if ($entity instanceof \App\Models\DepositRefund) {
                        $details[] = "Số tiền hoàn: " . number_format((float)$entity->refund_amount, 0, ',', '.') . " VNĐ";
                        $details[] = "Trạng thái: " . $this->getStatusLabel($entity->status, 'depositrefund');
                        
                        // Phương thức hoàn tiền
                        $refundMethodLabels = [
                            'cash' => 'Tiền mặt',
                            'bank_transfer' => 'Chuyển khoản',
                            'wallet' => 'Ví điện tử',
                        ];
                        $details[] = "Phương thức: " . ($refundMethodLabels[$entity->refund_method] ?? $entity->refund_method);
                        
                        // Thông tin hợp đồng
                        if ($entity->lease_id) {
                            $entity->loadMissing(['lease.unit.property']);
                            if ($entity->lease) {
                                $details[] = "Hợp đồng: " . ($entity->lease->contract_no ?? '#' . $entity->lease_id);
                                if ($entity->lease->unit) {
                                    $details[] = "Phòng: " . ($entity->lease->unit->code ?? $entity->lease->unit->name ?? 'N/A');
                                    if ($entity->lease->unit->property) {
                                        $details[] = "Bất động sản: " . ($entity->lease->unit->property->name ?? 'N/A');
                                    }
                                }
                            }
                        }
                    }
                    break;
                    
                case 'property':
                    if ($entity instanceof \App\Models\Property) {
                        $details[] = "Tên: " . ($entity->name ?? 'N/A');
                        $details[] = "Trạng thái: " . $this->getStatusLabel($entity->status ?? 'N/A', 'property');
                        
                        // Thông tin địa chỉ
                        if ($entity->location_id && $entity->location) {
                            $details[] = "Địa chỉ: " . ($entity->location->full_address ?? 'N/A');
                        }
                        
                        // Số tầng
                        if ($entity->total_floors) {
                            $details[] = "Số tầng: " . $entity->total_floors;
                        }
                    }
                    break;
                    
                case 'lead':
                    if ($entity instanceof \App\Models\Lead) {
                        $details[] = "Tên: " . ($entity->name ?? 'N/A');
                        $details[] = "Số điện thoại: " . ($entity->phone ?? 'N/A');
                        if ($entity->email) {
                            $details[] = "Email: " . $entity->email;
                        }
                        $details[] = "Trạng thái: " . $this->getStatusLabel($entity->status ?? 'N/A', 'lead');
                    }
                    break;
                    
                case 'viewing':
                    if ($entity instanceof \App\Models\Viewing) {
                        $details[] = "Trạng thái: " . $this->getStatusLabel($entity->status ?? 'N/A', 'viewing');
                        
                        // Thông tin bất động sản
                        if ($entity->property_id && $entity->property) {
                            $details[] = "Bất động sản: " . ($entity->property->name ?? 'N/A');
                        }
                        
                        // Ngày xem
                        if ($entity->viewing_date) {
                            $details[] = "Ngày xem: " . \Carbon\Carbon::parse($entity->viewing_date)->format('d/m/Y H:i');
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return !empty($details) ? implode("\n", $details) : null;
    }

    /**
     * Get status label in Vietnamese
     */
    private function getStatusLabel($status, string $entityType): string
    {
        $statusMap = [
            'lease' => [
                'draft' => 'Nháp',
                'active' => 'Đang hoạt động',
                'expired' => 'Hết hạn',
                'terminated' => 'Đã chấm dứt',
                'cancelled' => 'Đã hủy',
            ],
            'invoice' => [
                'draft' => 'Nháp',
                'issued' => 'Đã phát hành',
                'paid' => 'Đã thanh toán',
                'overdue' => 'Quá hạn',
                'cancelled' => 'Đã hủy',
            ],
            'payment' => [
                'pending' => 'Chờ xử lý',
                'completed' => 'Hoàn thành',
                'failed' => 'Thất bại',
                'cancelled' => 'Đã hủy',
            ],
            'ticket' => [
                'open' => 'Mở',
                'in_progress' => 'Đang xử lý',
                'resolved' => 'Đã giải quyết',
                'closed' => 'Đã đóng',
            ],
            'property' => [
                '1' => 'Hoạt động',
                '0' => 'Không hoạt động',
            ],
            'lead' => [
                'new' => 'Mới',
                'contacted' => 'Đã liên hệ',
                'qualified' => 'Đủ điều kiện',
                'converted' => 'Đã chuyển đổi',
                'lost' => 'Đã mất',
            ],
            'viewing' => [
                'scheduled' => 'Đã lên lịch',
                'completed' => 'Hoàn thành',
                'cancelled' => 'Đã hủy',
            ],
            'depositrefund' => [
                'pending' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'paid' => 'Đã thanh toán',
                'cancelled' => 'Đã hủy',
            ],
        ];

        $statusStr = is_string($status) ? strtolower($status) : (string)$status;
        return $statusMap[$entityType][$statusStr] ?? ucfirst($statusStr);
    }

    /**
     * Format field value for display
     */
    private function formatFieldValue(string $field, $value): string
    {
        // Format money fields
        if (in_array($field, ['rent_amount', 'deposit_amount', 'total_amount', 'amount', 'price'])) {
            return number_format((float)$value, 0, ',', '.') . " VNĐ";
        }

        // Format date fields
        if (in_array($field, ['start_date', 'end_date', 'due_date', 'issue_date', 'created_at', 'updated_at'])) {
            try {
                return \Carbon\Carbon::parse($value)->format('d/m/Y');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // Format status
        if ($field === 'status') {
            return ucfirst($value);
        }

        return $value;
    }

    /**
     * Get action text in Vietnamese
     */
    private function getActionText(string $action): string
    {
        return match($action) {
            'lease_created', 'invoice_created', 'payment_created', 'ticket_created', 'review_created' => 'được tạo',
            'lease_updated', 'invoice_updated', 'payment_updated', 'ticket_updated', 'review_updated' => 'được cập nhật',
            'lease_deleted', 'invoice_deleted', 'payment_deleted', 'ticket_deleted', 'review_deleted' => 'bị xóa',
            default => 'thay đổi',
        };
    }

    /**
     * Get entity name for display
     */
    private function getEntityName(AuditLog $auditLog, $entity): string
    {
        // DEPRECATED: leaseservice entity type - LeaseService model removed
        // if ($auditLog->entity_type === 'leaseservice') {
        //     // Thử lấy từ entity
        //     if ($entity && $entity->lease_id && $entity->lease) {
        //         return $entity->lease->contract_no ?? "Hợp đồng #{$entity->lease_id}";
        //     }
        //     
        //     // Thử lấy từ after_json hoặc before_json
        //     $data = null;
        //     if ($auditLog->after_json) {
        //         $data = is_string($auditLog->after_json) 
        //             ? json_decode($auditLog->after_json, true) 
        //             : $auditLog->after_json;
        //     } elseif ($auditLog->before_json) {
        //         $data = is_string($auditLog->before_json) 
        //             ? json_decode($auditLog->before_json, true) 
        //             : $auditLog->before_json;
        //     }
        //     
        //     if ($data && isset($data['lease_id'])) {
        //         try {
        //             $lease = \App\Models\Lease::find($data['lease_id']);
        //             if ($lease) {
        //                 return $lease->contract_no ?? "Hợp đồng #{$data['lease_id']}";
        //             }
        //         } catch (\Exception $e) {
        //             // Ignore
        //         }
        //         return "Hợp đồng #{$data['lease_id']}";
        //     }
        //     
        //     return "#{$auditLog->entity_id}";
        // }
        
        // For leaseserviceset, use name
        if ($auditLog->entity_type === 'leaseserviceset') {
            if ($entity && $entity instanceof \App\Models\LeaseServiceSet) {
                return $entity->name ?? "Bộ dịch vụ #{$auditLog->entity_id}";
            }
            
            // Try to get from after_json or before_json
            $data = null;
            if ($auditLog->after_json) {
                $data = is_string($auditLog->after_json) 
                    ? json_decode($auditLog->after_json, true) 
                    : $auditLog->after_json;
            } elseif ($auditLog->before_json) {
                $data = is_string($auditLog->before_json) 
                    ? json_decode($auditLog->before_json, true) 
                    : $auditLog->before_json;
            }
            
            if ($data && isset($data['name'])) {
                return $data['name'];
            }
            
            return "Bộ dịch vụ #{$auditLog->entity_id}";
        }
        
        // For leaseservicesetitem, use service name
        if ($auditLog->entity_type === 'leaseservicesetitem') {
            if ($entity && $entity instanceof \App\Models\LeaseServiceSetItem) {
                if ($entity->service) {
                    $serviceDisplay = $entity->service->name ?? 'N/A';
                    if ($entity->service->key_code) {
                        $serviceDisplay = "{$entity->service->key_code} - {$serviceDisplay}";
                    }
                    return $serviceDisplay;
                }
            }
            
            // Try to get from after_json or before_json
            $data = null;
            if ($auditLog->after_json) {
                $data = is_string($auditLog->after_json) 
                    ? json_decode($auditLog->after_json, true) 
                    : $auditLog->after_json;
            } elseif ($auditLog->before_json) {
                $data = is_string($auditLog->before_json) 
                    ? json_decode($auditLog->before_json, true) 
                    : $auditLog->before_json;
            }
            
            if ($data && isset($data['service_id'])) {
                try {
                    $service = \App\Models\Service::find($data['service_id']);
                    if ($service) {
                        $serviceDisplay = $service->name ?? 'N/A';
                        if ($service->key_code) {
                            $serviceDisplay = "{$service->key_code} - {$serviceDisplay}";
                        }
                        return $serviceDisplay;
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }
            
            return "Dịch vụ #{$auditLog->entity_id}";
        }
        
        if (!$entity) {
            return "#{$auditLog->entity_id}";
        }

        return match($auditLog->entity_type) {
            'lease' => $entity->contract_no ?? "#{$entity->id}",
            'invoice' => $entity->invoice_no ?? "#{$entity->id}",
            'payment' => "#{$entity->id}",
            'ticket' => $entity->title ?? "#{$entity->id}",
            'review' => $entity->title ?? "#{$entity->id}",
            'depositrefund' => $entity->refund_reference ?? "#{$entity->id}",
            default => "#{$entity->id}",
        };
    }

    /**
     * Get field name in Vietnamese
     */
    private function getFieldName(string $field): string
    {
        $fieldMap = [
            'rent_amount' => 'Tiền thuê',
            'deposit_amount' => 'Tiền cọc',
            'status' => 'Trạng thái',
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'total_amount' => 'Tổng tiền',
            'amount' => 'Số tiền',
            'price' => 'Giá',
            'service_id' => 'Dịch vụ',
            'lease_id' => 'Hợp đồng',
            'title' => 'Tiêu đề',
            'description' => 'Mô tả',
            'meta_json' => 'Thông tin bổ sung',
        ];

        return $fieldMap[$field] ?? $field;
    }

    /**
     * Get model class from entity type
     */
    private function getModelClass(string $entityType): ?string
    {
        $modelMap = [
            'lease' => Lease::class,
            'ticket' => Ticket::class,
            'ticketlog' => \App\Models\TicketLog::class,
            'invoice' => Invoice::class,
            'payment' => Payment::class,
            'depositrefund' => \App\Models\DepositRefund::class,
            'review' => \App\Models\Review::class,
            'reviewreply' => ReviewReply::class,
            'viewing' => \App\Models\Viewing::class,
        ];

        return $modelMap[strtolower($entityType)] ?? null;
    }

    /**
     * Process notifications for new audit logs (có thể gọi từ scheduled job)
     * 
     * Manager sẽ nhận notifications cho MỌI audit_log trong organization của họ
     */
    public function processNewAuditLogs(): int
    {
        $processed = 0;
        
        // Lấy các audit_logs mới chưa có notification cho managers
        // Manager cần notifications cho MỌI audit_log trong organization của họ
        $auditLogs = AuditLog::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('notifications')
                ->whereColumn('notifications.audit_log_id', 'audit_logs.id')
                ->whereColumn('notifications.to_user_id', DB::raw('(
                    SELECT ou.user_id 
                    FROM organization_users ou
                    JOIN roles r ON ou.role_id = r.id
                    WHERE ou.organization_id = audit_logs.organization_id
                    AND r.key_code = "manager"
                    AND ou.status = "active"
                    LIMIT 1
                )'));
        })
        ->whereNotNull('organization_id')
        ->where('created_at', '>=', now()->subHours(24)) // Chỉ xử lý logs trong 24h qua
        ->get();

        foreach ($auditLogs as $auditLog) {
            if ($this->createNotificationsFromAuditLog($auditLog)) {
                $processed++;
            }
        }

        return $processed;
    }
}

