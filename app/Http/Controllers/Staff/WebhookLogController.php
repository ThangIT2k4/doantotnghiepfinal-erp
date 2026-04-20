<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Services\SepayWebhookService;
use App\Services\WebhooksPermissionService;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebhookLogController extends Controller
{
    use ChecksCapabilities;
    
    protected $sepayService;
    protected $webhooksPermissionService;

    public function __construct(
        SepayWebhookService $sepayService,
        WebhooksPermissionService $webhooksPermissionService
    ) {
        $this->sepayService = $sepayService;
        $this->webhooksPermissionService = $webhooksPermissionService;
    }
    
    /**
     * Check webhooks permission for current organization.
     * This method should be called at the beginning of each webhook log method.
     */
    protected function checkWebhooksPermission(): void
    {
        $organizationId = $this->getCurrentOrganizationId();
        if ($organizationId) {
            $this->webhooksPermissionService->requireWebhooksPermission($organizationId);
        }
    }

    /**
     * Hiển thị danh sách webhook logs
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access webhook logs
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền truy cập Webhook Logs.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Lấy danh sách invoice numbers của organization (regular invoices)
        $organizationInvoiceIds = \App\Models\Invoice::where('organization_id', $organizationId)
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách subscription invoice numbers của organization
        $organizationSubscriptionInvoiceIds = \App\Models\SubscriptionInvoice::whereHas('subscription', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->pluck('id')
            ->toArray();
        
        // Lấy danh sách invoice numbers để tìm trong content
        $organizationInvoiceNos = \App\Models\Invoice::where('organization_id', $organizationId)
            ->pluck('invoice_no')
            ->toArray();
        
        $organizationSubscriptionInvoiceNos = \App\Models\SubscriptionInvoice::whereHas('subscription', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->pluck('invoice_number')
            ->toArray();
        
        // Filter webhook logs theo organization - chỉ lấy webhooks liên quan đến invoices của organization
        $query = WebhookLog::with(['invoice', 'payment'])
            ->where(function($q) use ($organizationId, $organizationInvoiceIds, $organizationSubscriptionInvoiceIds, $organizationInvoiceNos, $organizationSubscriptionInvoiceNos) {
                // Match by regular invoice relationship
                $q->whereHas('invoice', function($invoiceQuery) use ($organizationId) {
                    $invoiceQuery->where('organization_id', $organizationId);
                })
                // OR match by subscription invoice (tìm trong content)
                ->orWhere(function($contentQuery) use ($organizationInvoiceNos, $organizationSubscriptionInvoiceNos) {
                    // Tìm trong content có chứa invoice number của organization
                    foreach ($organizationInvoiceNos as $invoiceNo) {
                        $cleanInvoiceNo = str_replace('-', '', $invoiceNo);
                        $contentQuery->orWhere('content', 'like', "%{$invoiceNo}%")
                                    ->orWhere('content', 'like', "%{$cleanInvoiceNo}%");
                    }
                    // Tìm trong content có chứa subscription invoice number của organization
                    foreach ($organizationSubscriptionInvoiceNos as $subInvoiceNo) {
                        $cleanSubInvoiceNo = str_replace('-', '', $subInvoiceNo);
                        $contentQuery->orWhere('content', 'like', "%{$subInvoiceNo}%")
                                    ->orWhere('content', 'like', "%{$cleanSubInvoiceNo}%");
                    }
                });
            })
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by transaction ID, invoice number, or content (chỉ trong phạm vi organization)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search, $organizationId, $organizationInvoiceNos, $organizationSubscriptionInvoiceNos) {
                $q->where('sepay_transaction_id', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('reference_code', 'like', "%{$search}%")
                  ->orWhereHas('invoice', function($invoiceQuery) use ($search, $organizationId) {
                      $invoiceQuery->where('organization_id', $organizationId)
                                   ->where('invoice_no', 'like', "%{$search}%");
                  });
                
                // Tìm subscription invoice trong content
                foreach ($organizationSubscriptionInvoiceNos as $subInvoiceNo) {
                    $cleanSubInvoiceNo = str_replace('-', '', $subInvoiceNo);
                    if (stripos($subInvoiceNo, $search) !== false || stripos($cleanSubInvoiceNo, $search) !== false) {
                        $q->orWhere('content', 'like', "%{$subInvoiceNo}%")
                          ->orWhere('content', 'like', "%{$cleanSubInvoiceNo}%");
                    }
                }
            });
        }

        $webhookLogs = $query->paginate(20);
        
        // Tìm subscription invoice cho mỗi webhook log (nếu không có invoice_id)
        foreach ($webhookLogs as $log) {
            if (!$log->invoice_id && $log->content) {
                // Tìm subscription invoice từ content
                $sepayService = new \App\Services\SepayWebhookService();
                $invoiceResult = $sepayService->findInvoiceByContent($log->content);
                if ($invoiceResult && $invoiceResult['type'] === 'subscription') {
                    $log->subscriptionInvoice = $invoiceResult['invoice'];
                }
            }
        }

        // Statistics - chỉ tính cho organization hiện tại
        $statsBaseQuery = WebhookLog::where(function($q) use ($organizationId, $organizationInvoiceIds, $organizationInvoiceNos, $organizationSubscriptionInvoiceNos) {
            $q->whereHas('invoice', function($invoiceQuery) use ($organizationId) {
                $invoiceQuery->where('organization_id', $organizationId);
            })
            ->orWhere(function($contentQuery) use ($organizationInvoiceNos, $organizationSubscriptionInvoiceNos) {
                foreach ($organizationInvoiceNos as $invoiceNo) {
                    $cleanInvoiceNo = str_replace('-', '', $invoiceNo);
                    $contentQuery->orWhere('content', 'like', "%{$invoiceNo}%")
                                ->orWhere('content', 'like', "%{$cleanInvoiceNo}%");
                }
                foreach ($organizationSubscriptionInvoiceNos as $subInvoiceNo) {
                    $cleanSubInvoiceNo = str_replace('-', '', $subInvoiceNo);
                    $contentQuery->orWhere('content', 'like', "%{$subInvoiceNo}%")
                                ->orWhere('content', 'like', "%{$cleanSubInvoiceNo}%");
                }
            });
        });
        
        $statsFormatted = [
            'total' => [
                'value' => (clone $statsBaseQuery)->count(),
                'label' => 'Tổng Webhook',
                'icon' => 'fa-list',
                'color' => 'primary',
                'filter' => ''
            ],
            'pending' => [
                'value' => (clone $statsBaseQuery)->where('status', 'pending')->count(),
                'label' => 'Đang Chờ',
                'icon' => 'fa-clock',
                'color' => 'warning',
                'filter' => 'pending'
            ],
            'processed' => [
                'value' => (clone $statsBaseQuery)->where('status', 'processed')->count(),
                'label' => 'Thành Công',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'filter' => 'processed'
            ],
            'failed' => [
                'value' => (clone $statsBaseQuery)->where('status', 'failed')->count(),
                'label' => 'Thất Bại',
                'icon' => 'fa-times-circle',
                'color' => 'danger',
                'filter' => 'failed'
            ],
            'duplicate' => [
                'value' => (clone $statsBaseQuery)->where('status', 'duplicate')->count(),
                'label' => 'Trùng Lặp',
                'icon' => 'fa-copy',
                'color' => 'info',
                'filter' => 'duplicate'
            ],
            'total_amount' => [
                'value' => (clone $statsBaseQuery)->where('status', 'processed')->sum('amount') ?? 0,
                'label' => 'Tổng Tiền',
                'icon' => 'fa-money-bill-wave',
                'color' => 'primary',
                'filter' => '',
                'format' => 'currency'
            ]
        ];

        return view('staff.sepay.webhook-logs.index', compact('webhookLogs', 'statsFormatted'));
    }

    /**
     * Hiển thị chi tiết webhook log
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access webhook logs
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền truy cập Webhook Logs.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        $webhookLog = WebhookLog::with(['invoice.lease.tenant', 'invoice.items', 'payment'])
            ->findOrFail($id);

        return view('staff.sepay.webhook-logs.show', compact('webhookLog'));
    }

    /**
     * Thử xử lý lại webhook thất bại
     */
    public function retry($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can retry webhook logs
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền retry Webhook Logs.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        $webhookLog = WebhookLog::findOrFail($id);

        if (!$webhookLog->isFailed()) {
            return redirect()->back()
                ->with('error', 'Chỉ có thể thử lại webhook có trạng thái thất bại.');
        }

        $result = $this->sepayService->retryWebhook($webhookLog);

        if ($result['success']) {
            return redirect()->route('staff.webhook-logs.show', $webhookLog->id)
                ->with('success', 'Webhook đã được xử lý thành công!');
        } else {
            return redirect()->route('staff.webhook-logs.show', $webhookLog->id)
                ->with('error', 'Không thể xử lý webhook: ' . $result['message']);
        }
    }
}

