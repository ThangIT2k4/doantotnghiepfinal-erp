<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CompanyInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyInvoiceController extends Controller
{
    /**
     * Display a listing of company invoices.
     */
    public function index(Request $request)
    {
        $query = CompanyInvoice::with([
            'vendor',
            'organization',
            'creator',
            'masterLease',
            'ticket',
            'ticketLog',
            'depositRefund',
            'payrollPayslip',
            'cashOutflows'
        ]);

        // Search by invoice number, description, or vendor name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function($vendorQuery) use ($search) {
                      $vendorQuery->where('name', 'like', "%{$search}%")
                                 ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('organization', function($orgQuery) use ($search) {
                      $orgQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        // Filter by organization
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['invoice_no', 'total_amount', 'status', 'issue_date', 'due_date', 'created_at', 'paid_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $invoices = $query->paginate(15);

        // Get filter options
        $organizations = \App\Models\Organization::active()->orderBy('name')->get();
        $vendors = \App\Models\Vendor::orderBy('name')->get();

        return view('superadmin.company-invoices.index', compact('invoices', 'organizations', 'vendors'));
    }

    /**
     * Display the specified invoice.
     */
    public function show(CompanyInvoice $companyInvoice)
    {
        $companyInvoice->load([
            'vendor',
            'organization',
            'creator',
            'masterLease',
            'ticket',
            'ticketLog',
            'depositRefund',
            'payrollPayslip',
            'cashOutflows',
            'items'
        ]);
        
        return view('superadmin.company-invoices.show', compact('companyInvoice'));
    }
}

