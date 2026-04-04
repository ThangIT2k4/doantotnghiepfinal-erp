<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Services\SepayWebhookService;
use App\Services\WebhooksPermissionService;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SepayController extends Controller
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
     * This method should be called at the beginning of each sepay method.
     */
    protected function checkWebhooksPermission(): void
    {
        $organizationId = $this->getCurrentOrganizationId();
        if ($organizationId) {
            $this->webhooksPermissionService->requireWebhooksPermission($organizationId);
        }
    }

    /**
     * Hiển thị dashboard quản lý Sepay
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access Sepay management
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền truy cập Sepay Management.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Statistics for today
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Get all invoice numbers of the organization to filter webhooks
        $organizationInvoiceNos = Invoice::where('organization_id', $organizationId)
            ->pluck('invoice_no')
            ->map(function($invoiceNo) {
                // Remove dashes from invoice number for matching with content
                return str_replace('-', '', $invoiceNo);
            })
            ->toArray();
        
        // Helper function to filter webhooks by organization invoices
        $filterByOrganization = function($query) use ($organizationId, $organizationInvoiceNos) {
            $query->where(function($q) use ($organizationId, $organizationInvoiceNos) {
                // Match by invoice relationship
                $q->whereHas('invoice', function($invoiceQuery) use ($organizationId) {
                    $invoiceQuery->where('organization_id', $organizationId);
                });
                
                // OR match by content containing any invoice number from this organization
                if (count($organizationInvoiceNos) > 0) {
                    $q->orWhere(function($contentQuery) use ($organizationInvoiceNos) {
                        foreach ($organizationInvoiceNos as $invoiceNo) {
                            $contentQuery->orWhere('content', 'like', "%{$invoiceNo}%");
                        }
                    });
                }
            });
        };
        
        $stats = [
            'today' => [
                'transactions' => WebhookLog::whereDate('created_at', $today)
                    ->where($filterByOrganization)
                    ->count(),
                'amount' => WebhookLog::whereDate('created_at', $today)
                    ->where('status', 'processed')
                    ->where($filterByOrganization)
                    ->sum('amount'),
                'success_rate' => $this->calculateSuccessRate($today, $today->copy()->endOfDay(), $organizationId, $organizationInvoiceNos)
            ],
            'yesterday' => [
                'transactions' => WebhookLog::whereDate('created_at', $yesterday)
                    ->where($filterByOrganization)
                    ->count(),
                'amount' => WebhookLog::whereDate('created_at', $yesterday)
                    ->where('status', 'processed')
                    ->where($filterByOrganization)
                    ->sum('amount'),
                'success_rate' => $this->calculateSuccessRate($yesterday, $yesterday->copy()->endOfDay(), $organizationId, $organizationInvoiceNos)
            ],
            'this_month' => [
                'transactions' => WebhookLog::where('created_at', '>=', $thisMonth)
                    ->where($filterByOrganization)
                    ->count(),
                'amount' => WebhookLog::where('created_at', '>=', $thisMonth)
                    ->where('status', 'processed')
                    ->where($filterByOrganization)
                    ->sum('amount'),
                'success_rate' => $this->calculateSuccessRate($thisMonth, Carbon::now(), $organizationId, $organizationInvoiceNos)
            ],
            'last_month' => [
                'transactions' => WebhookLog::whereBetween('created_at', [$lastMonth, $thisMonth])
                    ->where($filterByOrganization)
                    ->count(),
                'amount' => WebhookLog::whereBetween('created_at', [$lastMonth, $thisMonth])
                    ->where('status', 'processed')
                    ->where($filterByOrganization)
                    ->sum('amount'),
                'success_rate' => $this->calculateSuccessRate($lastMonth, $thisMonth, $organizationId, $organizationInvoiceNos)
            ],
            'total' => [
                'transactions' => WebhookLog::where($filterByOrganization)->count(),
                'amount' => WebhookLog::where('status', 'processed')
                    ->where($filterByOrganization)
                    ->sum('amount'),
                'pending' => WebhookLog::where('status', 'pending')
                    ->where($filterByOrganization)
                    ->count(),
                'failed' => WebhookLog::where('status', 'failed')
                    ->where($filterByOrganization)
                    ->count(),
                'duplicate' => WebhookLog::where('status', 'duplicate')
                    ->where($filterByOrganization)
                    ->count(),
            ]
        ];

        // Recent transactions
        $recentTransactions = WebhookLog::with(['invoice', 'payment'])
            ->where($filterByOrganization)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Chart data for last 30 days
        $chartData = $this->getChartData($organizationId, $organizationInvoiceNos);

        return view('staff.sepay.sepay.index', compact('stats', 'recentTransactions', 'chartData'));
    }

    /**
     * Hiển thị danh sách giao dịch Sepay
     */
    public function transactions(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access Sepay management
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền truy cập Sepay Management.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Get all invoice numbers of the organization to filter webhooks
        $organizationInvoiceNos = Invoice::where('organization_id', $organizationId)
            ->pluck('invoice_no')
            ->map(function($invoiceNo) {
                // Remove dashes from invoice number for matching with content
                return str_replace('-', '', $invoiceNo);
            })
            ->toArray();
        
        // Filter webhooks by organization - show webhooks that match by invoice OR by content
        $query = WebhookLog::with(['invoice', 'payment'])
            ->where(function($q) use ($organizationId, $organizationInvoiceNos) {
                // Match by invoice relationship
                $q->whereHas('invoice', function($invoiceQuery) use ($organizationId) {
                    $invoiceQuery->where('organization_id', $organizationId);
                });
                
                // OR match by content containing any invoice number from this organization
                if (count($organizationInvoiceNos) > 0) {
                    $q->orWhere(function($contentQuery) use ($organizationInvoiceNos) {
                        foreach ($organizationInvoiceNos as $invoiceNo) {
                            $contentQuery->orWhere('content', 'like', "%{$invoiceNo}%");
                        }
                    });
                }
            })
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('amount_from')) {
            $query->where('amount', '>=', $request->amount_from);
        }

        if ($request->filled('amount_to')) {
            $query->where('amount', '<=', $request->amount_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search, $organizationId) {
                $q->where('sepay_transaction_id', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('reference_code', 'like', "%{$search}%")
                  ->orWhereHas('invoice', function($invoiceQuery) use ($search, $organizationId) {
                      $invoiceQuery->where('organization_id', $organizationId)
                                   ->where('invoice_no', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->paginate(20);

        $statuses = [
            'pending' => 'Đang chờ',
            'processed' => 'Thành công',
            'failed' => 'Thất bại',
            'duplicate' => 'Trùng lặp'
        ];

        return view('staff.sepay.sepay.transactions', compact('transactions', 'statuses'));
    }

    /**
     * Hiển thị chi tiết giao dịch
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can view Sepay transaction details
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền xem chi tiết giao dịch Sepay.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        $transaction = WebhookLog::with([
            'invoice.lease.tenant', 
            'invoice.items', 
            'payment.payerUser'
        ])->findOrFail($id);
        
        // Get all invoice numbers of the organization to check webhook ownership
        $organizationInvoiceNos = Invoice::where('organization_id', $organizationId)
            ->pluck('invoice_no')
            ->map(function($invoiceNo) {
                // Remove dashes from invoice number for matching with content
                return str_replace('-', '', $invoiceNo);
            })
            ->toArray();
        
        // Check if transaction belongs to organization
        // Match by invoice relationship OR by content containing invoice number
        $belongsToOrganization = false;
        
        // Check via invoice relationship
        if ($transaction->invoice && $transaction->invoice->organization_id === $organizationId) {
            $belongsToOrganization = true;
        }
        
        // Check via content containing invoice number (mã thanh toán trùng với mã hóa đơn)
        if (!$belongsToOrganization && count($organizationInvoiceNos) > 0 && $transaction->content) {
            $contentWithoutDashes = str_replace('-', '', $transaction->content);
            foreach ($organizationInvoiceNos as $invoiceNo) {
                if (strpos($contentWithoutDashes, $invoiceNo) !== false) {
                    $belongsToOrganization = true;
                    break;
                }
            }
        }
        
        if (!$belongsToOrganization) {
            abort(403, 'Bạn không có quyền xem giao dịch này.');
        }

        return view('staff.sepay.sepay.show', compact('transaction'));
    }

    /**
     * Cài đặt Sepay
     */
    public function settings()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can view Sepay settings
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền xem cài đặt Sepay.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $sepayMethod = PaymentMethod::where('name', 'SePay')->first();
        $webhookUrl = config('services.sepay.webhook_url');
        $apiKey = config('services.sepay.api_key') ? '***' . substr(config('services.sepay.api_key'), -4) : 'Chưa cấu hình';

        return view('staff.sepay.sepay.settings', compact('sepayMethod', 'webhookUrl', 'apiKey'));
    }

    /**
     * Cập nhật cài đặt Sepay
     */
    public function updateSettings(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update Sepay settings
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền cập nhật Sepay Settings.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        $request->validate([
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $sepayMethod = PaymentMethod::where('name', 'SePay')->first();
        
        if ($sepayMethod) {
            $sepayMethod->update([
                'is_active' => $request->boolean('is_active'),
                'description' => $request->description,
            ]);
        }

        return redirect()->route('staff.sepay.settings')
            ->with('success', 'Cài đặt SePay đã được cập nhật thành công!');
    }

    /**
     * Thử lại giao dịch thất bại
     */
    public function retry($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can retry Sepay transactions
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền retry Sepay Transactions.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        $transaction = WebhookLog::findOrFail($id);

        if (!$transaction->isFailed()) {
            return redirect()->back()
                ->with('error', 'Chỉ có thể thử lại giao dịch có trạng thái thất bại.');
        }

        $result = $this->sepayService->retryWebhook($transaction);

        if ($result['success']) {
            return redirect()->route('staff.sepay.show', $transaction->id)
                ->with('success', 'Giao dịch đã được xử lý thành công!');
        } else {
            return redirect()->route('staff.sepay.show', $transaction->id)
                ->with('error', 'Không thể xử lý giao dịch: ' . $result['message']);
        }
    }

    /**
     * Xuất báo cáo giao dịch
     */
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can export Sepay data
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền xuất Sepay Data.');
        
        // Check subscription permission for webhooks
        $this->checkWebhooksPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        // Get all invoice numbers of the organization to filter webhooks
        $organizationInvoiceNos = Invoice::where('organization_id', $organizationId)
            ->pluck('invoice_no')
            ->map(function($invoiceNo) {
                // Remove dashes from invoice number for matching with content
                return str_replace('-', '', $invoiceNo);
            })
            ->toArray();
        
        // Filter by organization
        $query = WebhookLog::with(['invoice', 'payment'])
            ->where(function($q) use ($organizationId, $organizationInvoiceNos) {
                // Match by invoice relationship
                $q->whereHas('invoice', function($invoiceQuery) use ($organizationId) {
                    $invoiceQuery->where('organization_id', $organizationId);
                });
                
                // OR match by content containing any invoice number from this organization
                if (count($organizationInvoiceNos) > 0) {
                    $q->orWhere(function($contentQuery) use ($organizationInvoiceNos) {
                        foreach ($organizationInvoiceNos as $invoiceNo) {
                            $contentQuery->orWhere('content', 'like', "%{$invoiceNo}%");
                        }
                    });
                }
            })
            ->orderBy('created_at', 'desc');

        // Apply same filters as transactions method
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->get();

        // Generate CSV
        $filename = 'sepay_transactions_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID Giao Dịch',
                'Ngày Giao Dịch',
                'Ngân Hàng',
                'Số Tiền',
                'Nội Dung',
                'Hóa Đơn',
                'Trạng Thái',
                'Ngày Tạo'
            ]);

            // CSV Data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->sepay_transaction_id,
                    $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : '',
                    $transaction->gateway ?? '',
                    number_format($transaction->amount),
                    $transaction->content,
                    $transaction->invoice ? $transaction->invoice->invoice_no : '',
                    $transaction->status,
                    $transaction->created_at->format('d/m/Y H:i')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Tính tỷ lệ thành công
     */
    private function calculateSuccessRate($startDate, $endDate, $organizationId, $organizationInvoiceNos)
    {
        $total = WebhookLog::whereBetween('created_at', [$startDate, $endDate])
            ->where(function($q) use ($organizationId, $organizationInvoiceNos) {
                // Match by invoice relationship
                $q->whereHas('invoice', function($invoiceQuery) use ($organizationId) {
                    $invoiceQuery->where('organization_id', $organizationId);
                });
                
                // OR match by content containing any invoice number from this organization
                if (count($organizationInvoiceNos) > 0) {
                    $q->orWhere(function($contentQuery) use ($organizationInvoiceNos) {
                        foreach ($organizationInvoiceNos as $invoiceNo) {
                            $contentQuery->orWhere('content', 'like', "%{$invoiceNo}%");
                        }
                    });
                }
            })
            ->count();
        if ($total == 0) return 0;
        
        $success = WebhookLog::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'processed')
            ->where(function($q) use ($organizationId, $organizationInvoiceNos) {
                // Match by invoice relationship
                $q->whereHas('invoice', function($invoiceQuery) use ($organizationId) {
                    $invoiceQuery->where('organization_id', $organizationId);
                });
                
                // OR match by content containing any invoice number from this organization
                if (count($organizationInvoiceNos) > 0) {
                    $q->orWhere(function($contentQuery) use ($organizationInvoiceNos) {
                        foreach ($organizationInvoiceNos as $invoiceNo) {
                            $contentQuery->orWhere('content', 'like', "%{$invoiceNo}%");
                        }
                    });
                }
            })
            ->count();
            
        return round(($success / $total) * 100, 1);
    }

    /**
     * Lấy dữ liệu cho biểu đồ
     */
    private function getChartData($organizationId, $organizationInvoiceNos)
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();
            
            $filterByOrg = function($q) use ($organizationId, $organizationInvoiceNos) {
                // Match by invoice relationship
                $q->whereHas('invoice', function($invoiceQuery) use ($organizationId) {
                    $invoiceQuery->where('organization_id', $organizationId);
                });
                
                // OR match by content containing any invoice number from this organization
                if (count($organizationInvoiceNos) > 0) {
                    $q->orWhere(function($contentQuery) use ($organizationInvoiceNos) {
                        foreach ($organizationInvoiceNos as $invoiceNo) {
                            $contentQuery->orWhere('content', 'like', "%{$invoiceNo}%");
                        }
                    });
                }
            };
            
            $data[] = [
                'date' => $date->format('d/m'),
                'transactions' => WebhookLog::whereBetween('created_at', [$dayStart, $dayEnd])
                    ->where($filterByOrg)
                    ->count(),
                'amount' => WebhookLog::whereBetween('created_at', [$dayStart, $dayEnd])
                    ->where('status', 'processed')
                    ->where($filterByOrg)
                    ->sum('amount'),
                'success' => WebhookLog::whereBetween('created_at', [$dayStart, $dayEnd])
                    ->where('status', 'processed')
                    ->where($filterByOrg)
                    ->count(),
                'failed' => WebhookLog::whereBetween('created_at', [$dayStart, $dayEnd])
                    ->where('status', 'failed')
                    ->where($filterByOrg)
                    ->count(),
            ];
        }
        
        return $data;
    }
}
