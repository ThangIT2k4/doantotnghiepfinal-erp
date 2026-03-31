<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the tenant's invoices
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query using JOINs and proper index order
        $query = Invoice::select([
            'invoices.*',
            'leases.contract_no as lease_contract_no',
            'units.code as unit_code',
            'properties.name as property_name'
        ])
        ->join('leases', 'invoices.lease_id', '=', 'leases.id')
        ->leftJoin('units', 'leases.unit_id', '=', 'units.id')
        ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
        ->whereIn('leases.id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
        ->whereNull('invoices.deleted_at') // Uses idx_invoices_deleted_at_status
        ->whereNull('leases.deleted_at'); // Uses idx_leases_deleted_at_status

        // Apply search filter - search by invoice number, unit code, and property name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoices.invoice_no', 'like', "%{$search}%")
                  ->orWhere('invoices.id', 'like', "%{$search}%")
                  ->orWhere('units.code', 'like', "%{$search}%") // Tìm theo tên phòng
                  ->orWhere('properties.name', 'like', "%{$search}%"); // Tìm theo tên bất động sản
            });
        }

        // Apply status filter - uses idx_invoices_deleted_at_status
        if ($request->filled('status') && $request->status !== 'all') {
            $status = $request->status;
            if ($status === 'paid') {
                $query->where('invoices.status', 'paid');
            } elseif ($status === 'pending') {
                $query->where('invoices.status', 'issued')
                      ->where('invoices.due_date', '>=', Carbon::now());
            } elseif ($status === 'overdue') {
                $query->where('invoices.status', 'issued')
                      ->where('invoices.due_date', '<', Carbon::now());
            } elseif ($status === 'draft') {
                $query->where('invoices.status', 'draft');
            } elseif ($status === 'cancelled') {
                $query->where('invoices.status', 'cancelled');
            }
        }

        // Apply month filter - uses idx_invoices_issue_date
        if ($request->filled('month')) {
            $month = $request->month;
            $query->whereYear('invoices.issue_date', substr($month, 0, 4))
                  ->whereMonth('invoices.issue_date', substr($month, 5, 2));
        }

        $invoices = $query->latest('invoices.issue_date')->paginate(10);
        
        // Eager load relationships for display
        $invoices->load([
            'lease.unit.property.location',
            'lease.unit.property.location2025',
            'lease.unit.property.propertyType',
            'items'
        ]);

        // Calculate statistics
        $stats = $this->calculateInvoiceStats($user->id);

        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) {
            // Return only the invoices list partial for HTMX
            return response()
                ->view('tenant.invoice.partials.invoices-list', compact('invoices'))
                ->header('HX-Push-Url', $request->fullUrl());
        }

        return view('tenant.invoice.index', compact('invoices', 'stats'));
    }

    /**
     * Display the specified invoice
     */
    public function show($id)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query using JOIN - select all invoice fields explicitly
        $invoice = Invoice::select('invoices.*')
        ->join('leases', 'invoices.lease_id', '=', 'leases.id')
        ->where('invoices.id', $id)
        ->whereIn('leases.id', $accessibleLeaseIds)
        ->whereNull('invoices.deleted_at') // Uses idx_invoices_deleted_at_status
        ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
        ->firstOrFail();
        
        // Eager load relationships for display
        $invoice->load([
            'lease.unit.property.location',
            'lease.unit.property.location2025',
            'lease.unit.property.propertyType',
            'lease.leaseServiceSet.items.service',
            'items',
            'lease.agent',
            'lease.tenant'
        ]);

        // Check if invoice is overdue
        $isOverdue = $invoice->status === 'issued' && $invoice->due_date < Carbon::now();

        return view('tenant.invoice.show', compact('invoice', 'isOverdue'));
    }

    /**
     * Process payment for an invoice
     */
    public function pay(Request $request, $id)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query using JOIN
        $invoice = Invoice::join('leases', 'invoices.lease_id', '=', 'leases.id')
            ->where('invoices.id', $id)
            ->whereIn('leases.id', $accessibleLeaseIds)
            ->whereNull('invoices.deleted_at') // Uses idx_invoices_deleted_at_status
            ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
            ->firstOrFail();

        if ($invoice->status !== 'issued') {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này không thể thanh toán'
            ], 400);
        }

        $request->validate([
            'payment_method' => 'required|in:momo,bank,vnpay,zalopay',
            'payment_reference' => 'nullable|string|max:255'
        ]);

        // Update invoice status
        $invoice->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thanh toán thành công',
            'invoice' => $invoice
        ]);
    }

    /**
     * Download invoice PDF
     */
    public function download($id)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query using JOIN
        $invoice = Invoice::join('leases', 'invoices.lease_id', '=', 'leases.id')
        ->where('invoices.id', $id)
        ->whereIn('leases.id', $accessibleLeaseIds)
        ->whereNull('invoices.deleted_at') // Uses idx_invoices_deleted_at_status
        ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
        ->firstOrFail();
        
        // Eager load relationships for display
        $invoice->load([
            'organization',
            'lease.unit.property.location',
            'lease.unit.property.location2025',
            'lease.leaseServiceSet.items.service',
            'items',
            'lease.agent',
            'lease.tenant'
        ]);

        // Generate PDF using facade
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', compact('invoice'));
        
        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('enable-local-file-access', true);
        
        // Return PDF download
        $filename = 'hoa-don-' . $invoice->invoice_no . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export invoices to Excel
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query using JOINs
        $query = Invoice::select([
            'invoices.*',
            'leases.contract_no as lease_contract_no',
            'units.code as unit_code',
            'properties.name as property_name'
        ])
        ->join('leases', 'invoices.lease_id', '=', 'leases.id')
        ->leftJoin('units', 'leases.unit_id', '=', 'units.id')
        ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
        ->whereIn('leases.id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
        ->whereNull('invoices.deleted_at') // Uses idx_invoices_deleted_at_status
        ->whereNull('leases.deleted_at'); // Uses idx_leases_deleted_at_status

        // Apply same filters as index method
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoices.invoice_no', 'like', "%{$search}%")
                  ->orWhere('invoices.id', 'like', "%{$search}%")
                  ->orWhere('units.code', 'like', "%{$search}%") // Tìm theo tên phòng
                  ->orWhere('properties.name', 'like', "%{$search}%"); // Tìm theo tên bất động sản
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $status = $request->status;
            if ($status === 'paid') {
                $query->where('invoices.status', 'paid');
            } elseif ($status === 'pending') {
                $query->where('invoices.status', 'issued')
                      ->where('invoices.due_date', '>=', Carbon::now());
            } elseif ($status === 'overdue') {
                $query->where('invoices.status', 'issued')
                      ->where('invoices.due_date', '<', Carbon::now());
            } elseif ($status === 'draft') {
                $query->where('invoices.status', 'draft');
            } elseif ($status === 'cancelled') {
                $query->where('invoices.status', 'cancelled');
            }
        }

        if ($request->filled('month')) {
            $month = $request->month;
            $query->whereYear('invoices.issue_date', substr($month, 0, 4))
                  ->whereMonth('invoices.issue_date', substr($month, 5, 2));
        }

        $invoices = $query->latest('invoices.issue_date')->get();
        
        // Eager load relationships for display
        $invoices->load([
            'lease.unit.property.location',
            'lease.unit.property.location2025',
            'lease.unit.property.propertyType',
            'items'
        ]);

        // For now, return a simple response
        // In a real application, you would generate an Excel file
        return response()->json([
            'success' => true,
            'message' => 'Export thành công',
            'count' => $invoices->count()
        ]);
    }

    /**
     * Calculate invoice statistics
     */
    private function calculateInvoiceStats($tenantId)
    {
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = \App\Models\Lease::getAccessibleLeaseIds($tenantId);
        
        // Optimized stats queries using JOINs
        $now = Carbon::now();
        
        $baseQuery = Invoice::join('leases', 'invoices.lease_id', '=', 'leases.id')
            ->whereIn('leases.id', $accessibleLeaseIds)
            ->whereNull('invoices.deleted_at') // Uses idx_invoices_deleted_at_status
            ->whereNull('leases.deleted_at'); // Uses idx_leases_deleted_at_status

        $paid = (clone $baseQuery)->where('invoices.status', 'paid')->count();
        $paidAmount = (clone $baseQuery)->where('invoices.status', 'paid')->sum('invoices.total_amount');

        $pending = (clone $baseQuery)
            ->where('invoices.status', 'issued')
            ->where('invoices.due_date', '>=', $now)
            ->count();

        $pendingAmount = (clone $baseQuery)
            ->where('invoices.status', 'issued')
            ->where('invoices.due_date', '>=', $now)
            ->sum('invoices.total_amount');

        $overdue = (clone $baseQuery)
            ->where('invoices.status', 'issued')
            ->where('invoices.due_date', '<', $now)
            ->count();

        $overdueAmount = (clone $baseQuery)
            ->where('invoices.status', 'issued')
            ->where('invoices.due_date', '<', $now)
            ->sum('invoices.total_amount');

        $total = (clone $baseQuery)->count();
        $totalAmount = (clone $baseQuery)->sum('invoices.total_amount');

        return [
            'paid' => $paid,
            'paid_amount' => $paidAmount,
            'pending' => $pending,
            'pending_amount' => $pendingAmount,
            'overdue' => $overdue,
            'overdue_amount' => $overdueAmount,
            'total' => $total,
            'total_amount' => $totalAmount
        ];
    }
}
