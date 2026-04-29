<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SepayManagementController extends Controller
{
    /**
     * Hiển thị dashboard SePay toàn hệ thống
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $organizationId = $request->input('organization_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $status = $request->input('status');
        $search = $request->input('search');

        // Base query
        $query = WebhookLog::with(['invoice.organization', 'invoice.lease.tenant', 'payment']);

        // Filter by organization
        if ($organizationId) {
            $query->whereHas('invoice', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom));
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo));
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('sepay_transaction_id', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('reference_code', 'like', "%{$search}%")
                  ->orWhereHas('invoice', function($invoiceQuery) use ($search) {
                      $invoiceQuery->where('invoice_no', 'like', "%{$search}%");
                  });
            });
        }

        // Paginate
        $transactions = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Statistics
        $stats = $this->getStatistics($organizationId, $dateFrom, $dateTo);

        // Get all organizations for filter dropdown
        $organizations = Organization::orderBy('name')->get();

        return view('superadmin.sepay.index', compact(
            'transactions',
            'stats',
            'organizations',
            'organizationId',
            'dateFrom',
            'dateTo',
            'status',
            'search'
        ));
    }

    /**
     * Chi tiết giao dịch
     */
    public function show($id)
    {
        $transaction = WebhookLog::with([
            'invoice.organization',
            'invoice.lease.tenant',
            'invoice.lease.unit.property',
            'invoice.items',
            'payment.payerUser'
        ])->findOrFail($id);

        return view('superadmin.sepay.show', compact('transaction'));
    }

    /**
     * Xuất báo cáo quyết toán theo tổ chức
     */
    public function export(Request $request)
    {
        $organizationId = $request->input('organization_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Build query
        $query = WebhookLog::with(['invoice.organization', 'invoice.lease.tenant', 'payment'])
            ->where('status', 'processed'); // Chỉ xuất giao dịch thành công

        if ($organizationId) {
            $query->whereHas('invoice', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom));
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo));
        }

        $transactions = $query->orderBy('created_at', 'asc')->get();

        // Generate CSV
        $filename = 'sepay_settlement_';
        if ($organizationId) {
            $org = Organization::find($organizationId);
            $filename .= ($org ? $org->code : 'ORG' . $organizationId) . '_';
        }
        $filename .= date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'Mã giao dịch SePay',
                'Ngày giao dịch',
                'Tổ chức',
                'Mã tổ chức',
                'Hóa đơn',
                'Khách hàng',
                'Ngân hàng',
                'Số tài khoản',
                'Số tiền',
                'Nội dung',
                'Mã tham chiếu',
                'Trạng thái',
                'Ngày xử lý'
            ]);

            // CSV Data
            foreach ($transactions as $transaction) {
                $organization = $transaction->invoice->organization ?? null;
                $tenant = $transaction->invoice->lease->tenant ?? null;
                
                fputcsv($file, [
                    $transaction->sepay_transaction_id,
                    $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : '',
                    $organization ? $organization->name : 'N/A',
                    $organization ? $organization->code : 'N/A',
                    $transaction->invoice->invoice_no ?? 'N/A',
                    $tenant ? $tenant->name : 'N/A',
                    $transaction->gateway ?? '',
                    $transaction->account_number ?? '',
                    $transaction->amount,
                    $transaction->content,
                    $transaction->reference_code ?? '',
                    $this->getStatusText($transaction->status),
                    $transaction->processed_at ? $transaction->processed_at->format('d/m/Y H:i') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Báo cáo quyết toán theo tổ chức
     */
    public function settlementReport(Request $request)
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        // Get settlement data per organization
        $settlements = DB::table('webhook_logs')
            ->join('invoices', 'webhook_logs.invoice_id', '=', 'invoices.id')
            ->join('organizations', 'invoices.organization_id', '=', 'organizations.id')
            ->select(
                'organizations.id as organization_id',
                'organizations.name as organization_name',
                'organizations.code as organization_code',
                DB::raw('COUNT(webhook_logs.id) as total_transactions'),
                DB::raw('SUM(webhook_logs.amount) as total_amount'),
                DB::raw('COUNT(CASE WHEN webhook_logs.status = "processed" THEN 1 END) as success_count'),
                DB::raw('SUM(CASE WHEN webhook_logs.status = "processed" THEN webhook_logs.amount ELSE 0 END) as success_amount')
            )
            ->whereDate('webhook_logs.created_at', '>=', Carbon::parse($dateFrom))
            ->whereDate('webhook_logs.created_at', '<=', Carbon::parse($dateTo))
            ->groupBy('organizations.id', 'organizations.name', 'organizations.code')
            ->orderBy('success_amount', 'desc')
            ->get();

        return view('superadmin.sepay.settlement', compact('settlements', 'dateFrom', 'dateTo'));
    }

    /**
     * Get statistics
     */
    private function getStatistics($organizationId = null, $dateFrom = null, $dateTo = null)
    {
        $query = WebhookLog::query();

        if ($organizationId) {
            $query->whereHas('invoice', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom));
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo));
        }

        return [
            'total' => (clone $query)->count(),
            'processed' => (clone $query)->where('status', 'processed')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'duplicate' => (clone $query)->where('status', 'duplicate')->count(),
            'total_amount' => (clone $query)->sum('amount'),
            'processed_amount' => (clone $query)->where('status', 'processed')->sum('amount'),
        ];
    }

    /**
     * Get status text in Vietnamese
     */
    private function getStatusText($status)
    {
        return match($status) {
            'pending' => 'Đang chờ',
            'processed' => 'Thành công',
            'failed' => 'Thất bại',
            'duplicate' => 'Trùng lặp',
            default => $status
        };
    }
}
