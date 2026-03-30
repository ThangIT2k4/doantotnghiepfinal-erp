<?php

namespace App\Services;

use App\Models\Viewing;
use App\Support\MailHelper;
use Illuminate\Support\Facades\Log;
use App\Mail\ViewingCreatedMail;
use App\Mail\ViewingUpdatedMail;

/**
 * Service: ViewingNotificationService
 * 
 * MỤC ĐÍCH:
 * Service xử lý gửi email thông báo cho viewing (lịch hẹn xem phòng)
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. sendViewingCreatedEmail(): Gửi email thông báo khi có lịch hẹn mới được tạo cho lead
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: Viewing, Lead, Property, Unit, User (agent), Organization
 * 
 * DỮ LIỆU GHI VÀO:
 * - Logs: Ghi log quá trình gửi email
 * 
 * LƯU Ý:
 * - Chỉ gửi email cho lead (customer_type = 'lead')
 * - Cần có email của lead để gửi
 * - Áp dụng organization mail config nếu có
 */
class ViewingNotificationService
{
    /**
     * Gửi email thông báo khi có lịch hẹn mới được tạo cho lead
     * 
     * MỤC ĐÍCH:
     * Gửi email xác nhận lịch hẹn xem phòng cho lead khi có viewing mới được tạo
     * 
     * INPUT:
     * - Viewing: Viewing model đã được tạo
     * 
     * OUTPUT:
     * - Array: ['success' => true/false, 'message' => '...']
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra viewing có lead_id hoặc lead_email không
     * 2. Lấy thông tin lead (từ lead_id hoặc từ viewing fields)
     * 3. Kiểm tra có email không
     * 4. Lấy thông tin property, unit, agent
     * 5. Lấy tên organization
     * 6. Áp dụng organization mail config
     * 7. Gửi email
     * 8. Ghi log
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Thông tin viewing
     * - Bảng leads: Thông tin lead (nếu có lead_id)
     * - Bảng properties: Thông tin property
     * - Bảng units: Thông tin unit
     * - Bảng users: Thông tin agent
     * - Bảng organizations: Tên organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log quá trình gửi email
     * 
     * LƯU Ý:
     * - Chỉ gửi email cho lead (không gửi cho tenant)
     * - Cần có email của lead để gửi
     * - Nếu không có email, sẽ log warning và return false
     * 
     * @param Viewing $viewing Viewing đã được tạo
     * @return array ['success' => true/false, 'message' => '...']
     */
    public function sendViewingCreatedEmail(Viewing $viewing): array
    {
        try {
            // Chỉ gửi email cho lead (không gửi cho tenant)
            if ($viewing->tenant_id) {
                Log::info('Viewing email NOT sent - viewing is for tenant, not lead', [
                    'viewing_id' => $viewing->id,
                    'tenant_id' => $viewing->tenant_id
                ]);
                return [
                    'success' => false,
                    'message' => 'Không gửi email cho tenant'
                ];
            }

            // Lấy thông tin lead
            $lead = null;
            $leadEmail = null;
            $leadName = null;

            if ($viewing->lead_id) {
                // Lấy từ lead relationship
                $lead = $viewing->lead;
                if ($lead) {
                    $leadEmail = $lead->email;
                    $leadName = $lead->name;
                }
            }

            // Fallback sang viewing fields nếu không có lead relationship
            if (!$leadEmail && $viewing->lead_email) {
                $leadEmail = $viewing->lead_email;
                $leadName = $viewing->lead_name ?? 'Khách hàng';
            }

            // Kiểm tra có email không
            if (!$leadEmail || empty(trim($leadEmail))) {
                Log::warning('Viewing email NOT sent - no email found', [
                    'viewing_id' => $viewing->id,
                    'lead_id' => $viewing->lead_id,
                    'lead_email' => $viewing->lead_email
                ]);
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy email của lead'
                ];
            }

            // Lấy thông tin property và unit
            $property = $viewing->property;
            $unit = $viewing->unit;

            if (!$property || !$unit) {
                throw new \Exception('Không tìm thấy thông tin bất động sản hoặc phòng');
            }

            // Lấy thông tin agent (nếu có)
            $agentName = null;
            if ($viewing->agent_id) {
                $agent = $viewing->agent;
                if ($agent) {
                    $agentName = $agent->userProfile->full_name ?? $agent->full_name ?? $agent->name ?? $agent->email ?? 'N/A';
                }
            }

            // Lấy tên tổ chức
            $organizationName = 'ZoroRMS Team';
            try {
                $organization = $viewing->organization;
                if ($organization) {
                    $organizationName = $organization->name ?? 'ZoroRMS Team';
                }
            } catch (\Exception $e) {
                // Use default if error
            }

            // Chuẩn bị dữ liệu email
            $emailData = [
                'lead_name' => $leadName ?? 'Khách hàng',
                'property_name' => $property->name,
                'unit_code' => $unit->code,
                'schedule_at' => $viewing->schedule_at,
                'status' => $viewing->status,
                'note' => $viewing->note,
                'organization_name' => $organizationName,
            ];

            // Thêm agent name nếu có
            if ($agentName) {
                $emailData['agent_name'] = $agentName;
            }

            MailHelper::sendWithOptionalOrgMail(
                new ViewingCreatedMail($emailData),
                $leadEmail,
                $viewing->organization_id
            );

            Log::info(MailHelper::wantsQueuedDispatch() ? 'Viewing created email queued' : 'Viewing created email sent successfully', [
                'viewing_id' => $viewing->id,
                'email' => $leadEmail,
                'lead_name' => $leadName
            ]);

            return [
                'success' => true,
                'message' => 'Email đã được gửi thành công'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send viewing created email', [
                'viewing_id' => $viewing->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Không thể gửi email: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Gửi email thông báo khi viewing được cập nhật (thay đổi thông tin hoặc trạng thái)
     * 
     * MỤC ĐÍCH:
     * Gửi email thông báo cập nhật lịch hẹn xem phòng cho lead khi viewing được cập nhật
     * 
     * INPUT:
     * - Viewing: Viewing model đã được cập nhật
     * - array $changes: Mảng các thay đổi (optional) - format: ['field' => ['old' => ..., 'new' => ...]]
     * - string $updateType: Loại cập nhật (optional) - 'info', 'status', 'confirm', 'cancel', 'done'
     * 
     * OUTPUT:
     * - Array: ['success' => true/false, 'message' => '...']
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra viewing có lead_id hoặc lead_email không
     * 2. Lấy thông tin lead (từ lead_id hoặc từ viewing fields)
     * 3. Kiểm tra có email không
     * 4. Lấy thông tin property, unit, agent
     * 5. Lấy tên organization
     * 6. Chuẩn bị dữ liệu thay đổi để hiển thị
     * 7. Áp dụng organization mail config
     * 8. Gửi email
     * 9. Ghi log
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Thông tin viewing
     * - Bảng leads: Thông tin lead (nếu có lead_id)
     * - Bảng properties: Thông tin property
     * - Bảng units: Thông tin unit
     * - Bảng users: Thông tin agent
     * - Bảng organizations: Tên organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log quá trình gửi email
     * 
     * LƯU Ý:
     * - Chỉ gửi email cho lead (không gửi cho tenant)
     * - Cần có email của lead để gửi
     * - Nếu không có email, sẽ log warning và return false
     * - Có thể gửi email khi thay đổi thông tin hoặc trạng thái
     * 
     * @param Viewing $viewing Viewing đã được cập nhật
     * @param array $changes Mảng các thay đổi (optional)
     * @param string $updateType Loại cập nhật (optional)
     * @return array ['success' => true/false, 'message' => '...']
     */
    public function sendViewingUpdatedEmail(Viewing $viewing, array $changes = [], string $updateType = 'info'): array
    {
        try {
            // Chỉ gửi email cho lead (không gửi cho tenant)
            if ($viewing->tenant_id) {
                Log::info('Viewing update email NOT sent - viewing is for tenant, not lead', [
                    'viewing_id' => $viewing->id,
                    'tenant_id' => $viewing->tenant_id
                ]);
                return [
                    'success' => false,
                    'message' => 'Không gửi email cho tenant'
                ];
            }

            // Lấy thông tin lead
            $lead = null;
            $leadEmail = null;
            $leadName = null;

            if ($viewing->lead_id) {
                // Lấy từ lead relationship
                $lead = $viewing->lead;
                if ($lead) {
                    $leadEmail = $lead->email;
                    $leadName = $lead->name;
                }
            }

            // Fallback sang viewing fields nếu không có lead relationship
            if (!$leadEmail && $viewing->lead_email) {
                $leadEmail = $viewing->lead_email;
                $leadName = $viewing->lead_name ?? 'Khách hàng';
            }

            // Kiểm tra có email không
            if (!$leadEmail || empty(trim($leadEmail))) {
                Log::warning('Viewing update email NOT sent - no email found', [
                    'viewing_id' => $viewing->id,
                    'lead_id' => $viewing->lead_id,
                    'lead_email' => $viewing->lead_email
                ]);
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy email của lead'
                ];
            }

            // Lấy thông tin property và unit
            $property = $viewing->property;
            $unit = $viewing->unit;

            if (!$property || !$unit) {
                throw new \Exception('Không tìm thấy thông tin bất động sản hoặc phòng');
            }

            // Lấy thông tin agent (nếu có)
            $agentName = null;
            if ($viewing->agent_id) {
                $agent = $viewing->agent;
                if ($agent) {
                    $agentName = $agent->userProfile->full_name ?? $agent->full_name ?? $agent->name ?? $agent->email ?? 'N/A';
                }
            }

            // Lấy tên tổ chức
            $organizationName = 'ZoroRMS Team';
            try {
                $organization = $viewing->organization;
                if ($organization) {
                    $organizationName = $organization->name ?? 'ZoroRMS Team';
                }
            } catch (\Exception $e) {
                // Use default if error
            }

            // Chuẩn bị dữ liệu thay đổi để hiển thị
            $changesData = [];
            if (!empty($changes)) {
                foreach ($changes as $field => $change) {
                    $oldValue = $change['old'] ?? null;
                    $newValue = $change['new'] ?? null;

                    // Bỏ qua nếu không có thay đổi
                    if ($oldValue === $newValue) {
                        continue;
                    }

                    // Map field names to labels
                    $fieldLabels = [
                        'status' => 'Trạng thái',
                        'schedule_at' => 'Thời gian hẹn',
                        'property_id' => 'Bất động sản',
                        'unit_id' => 'Căn hộ',
                        'agent_id' => 'Agent phụ trách',
                        'note' => 'Ghi chú',
                    ];

                    $label = $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));

                    // Format values
                    if ($field === 'status') {
                        $statusLabels = [
                            'requested' => 'Chờ xác nhận',
                            'confirmed' => 'Đã xác nhận',
                            'cancelled' => 'Đã hủy',
                            'done' => 'Hoàn thành',
                            'no_show' => 'Không đến',
                        ];
                        $oldValue = $statusLabels[$oldValue] ?? $oldValue;
                        $newValue = $statusLabels[$newValue] ?? $newValue;
                    } elseif ($field === 'schedule_at') {
                        try {
                            if ($oldValue) {
                                $oldValue = \Carbon\Carbon::parse($oldValue)->format('d/m/Y H:i');
                            }
                            if ($newValue) {
                                $newValue = \Carbon\Carbon::parse($newValue)->format('d/m/Y H:i');
                            }
                        } catch (\Exception $e) {
                            // Keep original value if parsing fails
                        }
                    } elseif ($field === 'agent_id') {
                        // Try to get agent names
                        if ($oldValue) {
                            $oldAgent = \App\Models\User::find($oldValue);
                            $oldValue = $oldAgent ? ($oldAgent->userProfile->full_name ?? $oldAgent->full_name ?? $oldAgent->name ?? 'N/A') : 'N/A';
                        }
                        if ($newValue) {
                            $newAgent = \App\Models\User::find($newValue);
                            $newValue = $newAgent ? ($newAgent->userProfile->full_name ?? $newAgent->full_name ?? $newAgent->name ?? 'N/A') : 'N/A';
                        }
                    }

                    $changesData[] = [
                        'label' => $label,
                        'old_value' => $oldValue ?? 'N/A',
                        'new_value' => $newValue ?? 'N/A',
                    ];
                }
            }

            // Chuẩn bị dữ liệu email
            $emailData = [
                'lead_name' => $leadName ?? 'Khách hàng',
                'property_name' => $property->name,
                'unit_code' => $unit->code,
                'schedule_at' => $viewing->schedule_at,
                'status' => $viewing->status,
                'note' => $viewing->note,
                'organization_name' => $organizationName,
                'changes' => $changesData,
            ];

            // Thêm agent name nếu có
            if ($agentName) {
                $emailData['agent_name'] = $agentName;
            }

            // Thêm result_note nếu có (khi status = done)
            if ($viewing->status === 'done' && $viewing->result_note) {
                $emailData['result_note'] = $viewing->result_note;
            }

            MailHelper::sendWithOptionalOrgMail(
                new ViewingUpdatedMail($emailData),
                $leadEmail,
                $viewing->organization_id
            );

            Log::info(MailHelper::wantsQueuedDispatch() ? 'Viewing updated email queued' : 'Viewing updated email sent successfully', [
                'viewing_id' => $viewing->id,
                'email' => $leadEmail,
                'lead_name' => $leadName,
                'update_type' => $updateType,
                'changes_count' => count($changesData)
            ]);

            return [
                'success' => true,
                'message' => 'Email đã được gửi thành công'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send viewing updated email', [
                'viewing_id' => $viewing->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Không thể gửi email: ' . $e->getMessage(),
            ];
        }
    }
}

