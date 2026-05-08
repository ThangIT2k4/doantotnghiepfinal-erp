<?php

namespace App\Services;

use App\Models\BookingDeposit;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use App\Support\MailHelper;
use Illuminate\Support\Facades\Log;
use App\Mail\BookingDepositPaymentMail;
use App\Mail\BookingDepositInvoiceMail;

/**
 * Service chỉ xử lý GỬI EMAIL cho booking deposit
 * KHÔNG tạo notifications thủ công - notifications sẽ được tạo tự động từ audit_log
 */
class BookingDepositNotificationService
{
    /**
     * Gửi email payment link cho lead/tenant sau khi booking deposit được approved
     */
    public function sendPaymentLinkEmail(BookingDeposit $bookingDeposit): array
    {
        try {
            // Lấy invoice
            $invoice = $bookingDeposit->invoices()->first();
            
            if (!$invoice) {
                throw new \Exception('Không tìm thấy hóa đơn cho booking deposit');
            }

            // Generate payment token nếu chưa có
            $paymentToken = $invoice->getActivePaymentToken();
            if (!$paymentToken) {
                $paymentToken = $invoice->generatePaymentToken();
            }

            // Lấy thông tin tenant (lead hoặc user)
            $tenantInfo = $bookingDeposit->getTenantInfo();
            
            if (!$tenantInfo || empty($tenantInfo['email'])) {
                throw new \Exception('Không tìm thấy email của khách hàng');
            }

            // Lấy payment URL
            $paymentUrl = $invoice->getGuestPaymentUrl();

            // Lấy thông tin unit và property
            $unit = $bookingDeposit->unit;
            $property = $unit->property;

            // Lấy tên tổ chức
            $organizationName = 'ZoroRMS Team';
            try {
                $organization = \App\Models\Organization::find($bookingDeposit->organization_id);
                if ($organization) {
                    $organizationName = $organization->name ?? 'ZoroRMS Team';
                }
            } catch (\Exception $e) {
                // Use default if error
            }
            
            // Chuẩn bị dữ liệu email
            $emailData = [
                'tenant_name' => $tenantInfo['name'],
                'property_name' => $property->name,
                'unit_code' => $unit->code,
                'booking_reference' => $bookingDeposit->reference_number,
                'invoice_no' => $invoice->invoice_no,
                'amount' => $bookingDeposit->amount,
                'payment_due_date' => $bookingDeposit->payment_due_date,
                'hold_until' => $bookingDeposit->hold_until,
                'payment_url' => $paymentUrl,
                'organization_name' => $organizationName,
            ];

            MailHelper::sendWithOptionalOrgMail(
                new BookingDepositPaymentMail($emailData),
                $tenantInfo['email'],
                $bookingDeposit->organization_id
            );

            // KHÔNG tạo notification thủ công nữa - sẽ được tạo tự động từ audit_log

            Log::info(MailHelper::wantsQueuedDispatch() ? 'Payment link email queued' : 'Payment link email sent successfully', [
                'booking_deposit_id' => $bookingDeposit->id,
                'email' => $tenantInfo['email'],
                'payment_url' => $paymentUrl
            ]);

            return [
                'success' => true,
                'message' => 'Email payment link đã được gửi thành công',
                'email' => $tenantInfo['email'],
                'payment_url' => $paymentUrl,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send payment link email', [
                'booking_deposit_id' => $bookingDeposit->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Không thể gửi email: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Gửi email reminder trước khi hết hạn thanh toán
     */
    public function sendPaymentReminderEmail(BookingDeposit $bookingDeposit): array
    {
        try {
            $invoice = $bookingDeposit->invoice ?? $bookingDeposit->invoices()->first();
            
            if (!$invoice) {
                throw new \Exception('Không tìm thấy hóa đơn');
            }

            $tenantInfo = $bookingDeposit->getTenantInfo();
            
            if (!$tenantInfo || empty($tenantInfo['email'])) {
                throw new \Exception('Không tìm thấy email của khách hàng');
            }

            $paymentUrl = $invoice->getGuestPaymentUrl();
            $unit = $bookingDeposit->unit;
            $property = $unit->property;

            // Lấy tên tổ chức
            $organizationName = 'ZoroRMS Team';
            try {
                $organization = \App\Models\Organization::find($bookingDeposit->organization_id);
                if ($organization) {
                    $organizationName = $organization->name ?? 'ZoroRMS Team';
                }
            } catch (\Exception $e) {
                // Use default if error
            }
            
            $emailData = [
                'tenant_name' => $tenantInfo['name'],
                'property_name' => $property->name,
                'unit_code' => $unit->code,
                'booking_reference' => $bookingDeposit->reference_number,
                'amount' => $bookingDeposit->amount,
                'payment_due_date' => $bookingDeposit->payment_due_date,
                'payment_url' => $paymentUrl,
                'is_reminder' => true,
                'organization_name' => $organizationName,
            ];

            MailHelper::sendWithOptionalOrgMail(
                new BookingDepositPaymentMail($emailData),
                $tenantInfo['email'],
                $bookingDeposit->organization_id
            );

            Log::info(MailHelper::wantsQueuedDispatch() ? 'Payment reminder email queued' : 'Payment reminder email sent', [
                'booking_deposit_id' => $bookingDeposit->id,
                'email' => $tenantInfo['email']
            ]);

            return [
                'success' => true,
                'message' => 'Email nhắc nhở đã được gửi thành công',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send reminder email', [
                'booking_deposit_id' => $bookingDeposit->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Không thể gửi email: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Gửi email xác nhận thanh toán thành công
     */
    public function sendPaymentSuccessEmail(BookingDeposit $bookingDeposit): array
    {
        try {
            $tenantInfo = $bookingDeposit->getTenantInfo();
            
            if (!$tenantInfo || empty($tenantInfo['email'])) {
                throw new \Exception('Không tìm thấy email của khách hàng');
            }

            $unit = $bookingDeposit->unit;
            $property = $unit->property;

            // Lấy tên tổ chức
            $organizationName = 'ZoroRMS Team';
            try {
                $organization = \App\Models\Organization::find($bookingDeposit->organization_id);
                if ($organization) {
                    $organizationName = $organization->name ?? 'ZoroRMS Team';
                }
            } catch (\Exception $e) {
                // Use default if error
            }
            
            $emailData = [
                'tenant_name' => $tenantInfo['name'],
                'property_name' => $property->name,
                'unit_code' => $unit->code,
                'booking_reference' => $bookingDeposit->reference_number,
                'amount' => $bookingDeposit->amount,
                'paid_at' => $bookingDeposit->paid_at,
                'is_success' => true,
                'organization_name' => $organizationName,
            ];

            MailHelper::sendWithOptionalOrgMail(
                new BookingDepositPaymentMail($emailData),
                $tenantInfo['email'],
                $bookingDeposit->organization_id
            );

            Log::info(MailHelper::wantsQueuedDispatch() ? 'Payment success email queued' : 'Payment success email sent', [
                'booking_deposit_id' => $bookingDeposit->id,
                'email' => $tenantInfo['email']
            ]);

            return [
                'success' => true,
                'message' => 'Email xác nhận thanh toán đã được gửi',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send success email', [
                'booking_deposit_id' => $bookingDeposit->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Không thể gửi email: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Gửi email hóa đơn cho Lead khi invoice được tạo từ booking deposit
     */
    public function sendInvoiceEmailForLead(Invoice $invoice): array
    {
        try {
            // Kiểm tra invoice có từ booking deposit không
            if (!$invoice->booking_deposit_id) {
                throw new \Exception('Invoice không phải từ booking deposit');
            }

            $bookingDeposit = $invoice->bookingDeposit;
            if (!$bookingDeposit) {
                throw new \Exception('Không tìm thấy booking deposit');
            }

            // Lấy thông tin Lead (bypass global scopes để tránh filter theo organization)
            $lead = Lead::withoutGlobalScopes()->find($bookingDeposit->lead_id);
            if (!$lead) {
                throw new \Exception('Booking deposit không có lead');
            }

            // Kiểm tra Lead có email không
            if (empty($lead->email)) {
                throw new \Exception('Lead không có email');
            }

            // Validate email format
            if (!filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Email của Lead không hợp lệ: ' . $lead->email);
            }

            // Lấy thông tin unit và property
            $unit = $bookingDeposit->unit;
            if (!$unit) {
                throw new \Exception('Booking deposit không có unit');
            }

            $property = $unit->property;
            if (!$property) {
                throw new \Exception('Unit không có property');
            }

            // Generate payment token nếu chưa có
            $paymentToken = $invoice->getActivePaymentToken();
            if (!$paymentToken) {
                $paymentToken = $invoice->generatePaymentToken();
            }

            // Lấy payment URL
            $paymentUrl = $invoice->getGuestPaymentUrl();

            // Lấy tên tổ chức
            $organizationName = 'ZoroRMS Team';
            try {
                $organization = \App\Models\Organization::find($bookingDeposit->organization_id);
                if ($organization) {
                    $organizationName = $organization->name ?? 'ZoroRMS Team';
                }
            } catch (\Exception $e) {
                // Use default if error
            }
            
            // Chuẩn bị dữ liệu email
            $emailData = [
                'lead_name' => $lead->name,
                'lead_email' => $lead->email,
                'property_name' => $property->name,
                'unit_code' => $unit->code,
                'booking_reference' => $bookingDeposit->reference_number,
                'invoice_no' => $invoice->invoice_no,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount,
                'issue_date' => $invoice->issue_date,
                'due_date' => $invoice->due_date,
                'payment_due_date' => $bookingDeposit->payment_due_date,
                'hold_until' => $bookingDeposit->hold_until,
                'payment_url' => $paymentUrl,
                'organization_name' => $organizationName,
            ];

            // Gửi email
            try {
                // Log trước khi gửi
                Log::info('Attempting to send invoice email to Lead', [
                    'to' => $lead->email,
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'lead_id' => $lead->id,
                ]);

                MailHelper::sendWithOptionalOrgMail(
                    new BookingDepositInvoiceMail($emailData),
                    $lead->email,
                    $bookingDeposit->organization_id
                );
                
                Log::info('Email sent successfully', [
                    'to' => $lead->email,
                    'invoice_id' => $invoice->id,
                ]);
            } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $transportException) {
                // Lỗi SMTP/Transport
                Log::error('Mail transport failed', [
                    'to' => $lead->email,
                    'invoice_id' => $invoice->id,
                    'error' => $transportException->getMessage(),
                    'code' => $transportException->getCode(),
                ]);
                throw new \Exception('Lỗi kết nối SMTP: ' . $transportException->getMessage());
            } catch (\Exception $mailException) {
                // Lỗi khác
                Log::error('Mail sending failed', [
                    'to' => $lead->email,
                    'invoice_id' => $invoice->id,
                    'error' => $mailException->getMessage(),
                    'trace' => $mailException->getTraceAsString()
                ]);
                throw new \Exception('Lỗi gửi email: ' . $mailException->getMessage());
            }

            // KHÔNG tạo notification thủ công nữa - sẽ được tạo tự động từ audit_log

            Log::info('Invoice email sent to Lead successfully', [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'lead_id' => $lead->id,
                'lead_email' => $lead->email,
                'booking_deposit_id' => $bookingDeposit->id,
            ]);

            return [
                'success' => true,
                'message' => 'Email hóa đơn đã được gửi thành công cho Lead',
                'lead_email' => $lead->email,
                'payment_url' => $paymentUrl,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send invoice email to Lead', [
                'invoice_id' => $invoice->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Không thể gửi email hóa đơn cho Lead: ' . $e->getMessage(),
            ];
        }
    }
}

