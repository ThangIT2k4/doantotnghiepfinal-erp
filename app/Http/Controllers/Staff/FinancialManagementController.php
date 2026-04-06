<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\CompanyInvoice;
use App\Models\Payment;
use App\Models\CashOutflow;
use App\Models\AuditLog;
use App\Traits\ChecksCapabilities;
use App\Services\NotificationEmailService;
use App\Services\ReportsPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FinancialManagementController extends Controller
{
    use ChecksCapabilities;
    
    protected $reportsPermissionService;
    
    public function __construct(ReportsPermissionService $reportsPermissionService)
    {
        $this->reportsPermissionService = $reportsPermissionService;
    }
    
    /**
     * Check advanced reports permission for current organization.
     * This method should be called at the beginning of each report method.
     */
    protected function checkReportsPermission(): void
    {
        $organizationId = $this->getCurrentOrganizationId();
        if ($organizationId) {
            $this->reportsPermissionService->requireReportsPermission($organizationId);
        }
    }
    
    /**
     * Display financial management dashboard
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access financial management
        $this->requireCapability('finance.report.view', 'Bạn không có quyền truy cập Financial Management.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check subscription permission for advanced reports
        $this->checkReportsPermission();

        // Quick stats
        $stats = $this->getQuickStats($organizationId);

        return view('staff.finance.financial.index', compact('stats'));
    }

    /**
     * Cash Flow Forecast - Dự báo dòng tiền
     */
    public function cashFlowForecast(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access financial management
        $this->requireCapability('finance.report.view', 'Bạn không có quyền truy cập Financial Management.');
        
        // Check subscription permission for advanced reports
        $this->checkReportsPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $period = $request->get('period', 'month'); // month, quarter, year
        $months = (int) $request->get('months', 6); // số tháng dự báo - ép kiểu thành int
        
        // Lấy dữ liệu thực tế trong 6 tháng qua
        $pastMonths = 6;
        $startDate = Carbon::now()->subMonths($pastMonths)->startOfMonth();
        $endDate = Carbon::now()->addMonths($months)->endOfMonth();
        
        // Thu nhập (Invoices đã thanh toán)
        $incomes = $this->getIncomeData($organizationId, $startDate, $endDate);
        
        // Chi phí (Cash Outflows & Company Invoices)
        $expenses = $this->getExpenseData($organizationId, $startDate, $endDate);
        
        // Dự báo dựa trên trung bình
        $forecast = $this->calculateForecast($incomes, $expenses, $months);
        
        // Cash flow summary
        $summary = $this->calculateCashFlowSummary($incomes, $expenses, $forecast);

        return view('staff.finance.financial.cash-flow-forecast', compact(
            'incomes', 
            'expenses', 
            'forecast', 
            'summary',
            'period',
            'months'
        ));
    }

    /**
     * Expense Tracking & Categorization - Theo dõi chi phí theo danh mục
     */
    public function expenseTracking(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access financial management
        $this->requireCapability('finance.report.view', 'Bạn không có quyền truy cập Financial Management.');
        
        // Check subscription permission for advanced reports
        $this->checkReportsPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Mặc định lấy 3 tháng gần nhất
        $dateFrom = $request->get('date_from', Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $category = $request->get('category', 'all');
        
        // Lấy chi phí từ company_invoices và cash_outflows
        $expenses = $this->getCategorizedExpenses($organizationId, $dateFrom, $dateTo, $category);
        
        // Thống kê theo danh mục
        $categoryStats = $this->getCategoryStatistics($organizationId, $dateFrom, $dateTo);
        
        // Tổng hợp theo vendor
        $vendorStats = $this->getVendorStatistics($organizationId, $dateFrom, $dateTo);

        return view('staff.finance.financial.expense-tracking', compact(
            'expenses',
            'categoryStats',
            'vendorStats',
            'dateFrom',
            'dateTo',
            'category'
        ));
    }

    /**
     * Payment Reminders - Nhắc nhở thanh toán tự động
     */
    public function paymentReminders(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access financial management
        $this->requireCapability('finance.report.view', 'Bạn không có quyền truy cập Financial Management.');
        
        // Check subscription permission for advanced reports
        $this->checkReportsPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $daysBefore = $request->get('days_before', 7); // Nhắc nhở 7 ngày trước
        $status = $request->get('status', 'all'); // all, upcoming, overdue
        $type = $request->get('type', 'tenant'); // tenant, company
        
        // Lấy invoices sắp đến hạn
        $upcomingInvoices = $this->getUpcomingInvoices($organizationId, $daysBefore);
        
        // Lấy invoices quá hạn
        $overdueInvoices = $this->getOverdueInvoices($organizationId);
        
        // Lấy company invoices sắp đến hạn
        $upcomingCompanyInvoices = $this->getUpcomingCompanyInvoices($organizationId, $daysBefore);
        
        // Lấy company invoices quá hạn
        $overdueCompanyInvoices = $this->getOverdueCompanyInvoices($organizationId);
        
        // Thống kê
        $stats = [
            'upcoming_count' => $upcomingInvoices->count(),
            'upcoming_total' => $upcomingInvoices->sum('total_amount'),
            'overdue_count' => $overdueInvoices->count(),
            'overdue_total' => $overdueInvoices->sum('total_amount'),
            'upcoming_company_count' => $upcomingCompanyInvoices->count(),
            'upcoming_company_total' => $upcomingCompanyInvoices->sum('total_amount'),
            'overdue_company_count' => $overdueCompanyInvoices->count(),
            'overdue_company_total' => $overdueCompanyInvoices->sum('total_amount'),
        ];

        return view('staff.finance.financial.payment-reminders', compact(
            'upcomingInvoices',
            'overdueInvoices',
            'upcomingCompanyInvoices',
            'overdueCompanyInvoices',
            'stats',
            'daysBefore',
            'status',
            'type'
        ));
    }

    /**
     * Reconciliation Reports - Đối soát thu chi
     */
    public function reconciliationReports(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access financial management
        $this->requireCapability('finance.report.view', 'Bạn không có quyền truy cập Financial Management.');
        
        // Check subscription permission for advanced reports
        $this->checkReportsPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Mặc định lấy 3 tháng gần nhất để có nhiều dữ liệu hơn
        // Nếu date_from không được cung cấp, lấy 3 tháng gần nhất
        $dateFrom = $request->get('date_from');
        if (!$dateFrom) {
            $dateFrom = Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        }
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Thu nhập (Invoices)
        $incomeData = $this->getReconciliationIncome($organizationId, $dateFrom, $dateTo);
        
        // Chi phí (Expenses)
        $expenseData = $this->getReconciliationExpense($organizationId, $dateFrom, $dateTo);
        
        // Payments thực tế
        $payments = $this->getReconciliationPayments($organizationId, $dateFrom, $dateTo);
        
        // Debug: Log số lượng records để kiểm tra
        Log::info('Reconciliation Debug', [
            'organization_id' => $organizationId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'income_count' => $incomeData->count(),
            'company_invoices_count' => $expenseData['company_invoices']->count(),
            'cash_outflows_count' => $expenseData['cash_outflows']->count(),
            'payments_count' => $payments->count(),
        ]);
        
        // Đối chiếu
        $reconciliation = $this->reconcileData($incomeData, $expenseData, $payments);

        return view('staff.finance.financial.reconciliation', compact(
            'incomeData',
            'expenseData',
            'payments',
            'reconciliation',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Tax Reports - Báo cáo thuế
     */
    public function taxReports(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access financial management
        $this->requireCapability('finance.report.view', 'Bạn không có quyền truy cập Financial Management.');
        
        // Check subscription permission for advanced reports
        $this->checkReportsPermission();
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $year = $request->get('year', Carbon::now()->year);
        $quarter = $request->get('quarter', null); // 1, 2, 3, 4 hoặc null (cả năm)
        $currency = $request->get('currency', 'all'); // all, VND, USD, etc.
        
        // Lấy dữ liệu thuế từ invoices
        $taxData = $this->getTaxData($organizationId, $year, $quarter, $currency);
        
        // Tổng hợp theo tháng/quý
        $summary = $this->getTaxSummary($taxData, $quarter);
        
        // Phân loại theo loại thuế (nếu có trong tương lai)
        $taxByType = $this->getTaxByType($organizationId, $year, $quarter);

        return view('staff.finance.financial.tax-reports', compact(
            'taxData',
            'summary',
            'taxByType',
            'year',
            'quarter',
            'currency'
        ));
    }

    // Helper Methods

    private function getQuickStats($organizationId)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = $thisMonth->copy()->subMonth();
        
        return [
            'total_revenue' => Invoice::where('organization_id', $organizationId)
                ->where('status', 'paid')
                ->whereNull('deleted_at')
                ->sum('total_amount'),
            'monthly_revenue' => Invoice::where('organization_id', $organizationId)
                ->where('status', 'paid')
                ->where('created_at', '>=', $thisMonth)
                ->whereNull('deleted_at')
                ->sum('total_amount'),
            'total_expenses' => CashOutflow::byOrganization($organizationId)
                ->where('status', 'success')
                ->whereNull('deleted_at')
                ->sum('amount') + 
                CompanyInvoice::where('organization_id', $organizationId)
                    ->where('status', 'paid')
                    ->whereNull('deleted_at')
                    ->sum('total_amount'),
            'monthly_expenses' => CashOutflow::byOrganization($organizationId)
                ->where('status', 'success')
                ->where('paid_at', '>=', $thisMonth)
                ->whereNull('deleted_at')
                ->sum('amount') +
                CompanyInvoice::where('organization_id', $organizationId)
                    ->where('status', 'paid')
                    ->where('created_at', '>=', $thisMonth)
                    ->whereNull('deleted_at')
                    ->sum('total_amount'),
            'upcoming_payments' => Invoice::where('organization_id', $organizationId)
                ->whereIn('status', ['issued', 'overdue']) // Chỉ tính issued và overdue, không tính draft và cancelled
                ->where('due_date', '>=', $today)
                ->where('due_date', '<=', $today->copy()->addDays(7))
                ->whereNull('deleted_at')
                ->count(),
            'overdue_payments' => Invoice::where('organization_id', $organizationId)
                ->whereIn('status', ['issued', 'overdue']) // Chỉ tính issued và overdue, không tính draft và cancelled
                ->where('due_date', '<', $today)
                ->whereNull('deleted_at')
                ->count(),
            // Note cách tính
            'calculation_note' => [
                'total_revenue' => 'Tổng doanh thu = Tổng các hóa đơn đã thanh toán (status = paid)',
                'monthly_revenue' => 'Doanh thu tháng này = Tổng các hóa đơn đã thanh toán trong tháng (status = paid)',
                'total_expenses' => 'Tổng chi phí = Tổng cash outflows (success) + Tổng company invoices đã thanh toán (paid)',
                'monthly_expenses' => 'Chi phí tháng này = Tổng cash outflows + company invoices đã thanh toán trong tháng',
                'upcoming_payments' => 'Thanh toán sắp tới (7 ngày) = Hóa đơn đã phát hành hoặc quá hạn (không tính draft, cancelled) với due_date trong 7 ngày tới',
                'overdue_payments' => 'Thanh toán quá hạn = Hóa đơn đã phát hành hoặc quá hạn (không tính draft, cancelled) với due_date < hôm nay',
            ],
        ];
    }

    /**
     * Lấy dữ liệu thu nhập thực tế (chỉ tính hóa đơn đã thanh toán)
     * 
     * Note: Chỉ tính các hóa đơn có status = 'paid' (đã thanh toán)
     * Không tính draft, cancelled, issued, overdue
     * 
     * @param int $organizationId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getIncomeData($organizationId, $startDate, $endDate)
    {
        // Ensure dates are Carbon instances
        if (!$startDate instanceof Carbon) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }
        if (!$endDate instanceof Carbon) {
            $endDate = Carbon::parse($endDate)->endOfDay();
        }
        
        // Chỉ tính thu nhập thực tế từ hóa đơn đã thanh toán
        return Invoice::where('organization_id', $organizationId)
            ->where('status', 'paid') // Chỉ tính hóa đơn đã thanh toán
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->select(
                DB::raw('DATE_FORMAT(issue_date, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as amount'),
                'currency'
            )
            ->groupBy('month', 'currency')
            ->orderBy('month')
            ->get();
    }

    /**
     * Lấy dữ liệu chi phí thực tế (chỉ tính các khoản đã thanh toán)
     * 
     * Note: 
     * - Cash outflows: chỉ tính status = 'success' (đã thanh toán thành công)
     * - Company invoices: chỉ tính status = 'paid' (đã thanh toán)
     * Không tính draft, cancelled, issued, overdue
     * 
     * @param int $organizationId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getExpenseData($organizationId, $startDate, $endDate)
    {
        // Ensure dates are Carbon instances
        if (!$startDate instanceof Carbon) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }
        if (!$endDate instanceof Carbon) {
            $endDate = Carbon::parse($endDate)->endOfDay();
        }
        
        // Cash outflows - chỉ tính các khoản đã thanh toán thành công
        $cashOutflows = CashOutflow::byOrganization($organizationId)
            ->where('status', 'success') // Chỉ tính đã thanh toán thành công
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->whereNull('deleted_at')
            ->select(
                DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Company invoices - chỉ tính các hóa đơn đã thanh toán
        $companyInvoices = CompanyInvoice::where('organization_id', $organizationId)
            ->where('status', 'paid') // Chỉ tính hóa đơn đã thanh toán
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->select(
                DB::raw('DATE_FORMAT(issue_date, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as amount'),
                'currency'
            )
            ->groupBy('month', 'currency')
            ->orderBy('month')
            ->get();

        return [
            'cash_outflows' => $cashOutflows,
            'company_invoices' => $companyInvoices
        ];
    }

    /**
     * Tính dự báo dòng tiền dựa trên trung bình lịch sử
     * 
     * Note:
     * - Dự báo dựa trên trung bình của dữ liệu lịch sử (thu nhập và chi phí thực tế)
     * - Thu nhập: trung bình từ hóa đơn đã thanh toán (status = 'paid')
     * - Chi phí: trung bình từ cash outflows (success) + company invoices (paid)
     * - Dòng tiền ròng = Thu nhập dự báo - Chi phí dự báo
     * 
     * @param \Illuminate\Support\Collection $incomes
     * @param array $expenses
     * @param int $months
     * @return array
     */
    private function calculateForecast($incomes, $expenses, $months)
    {
        // Tính trung bình từ dữ liệu lịch sử (chỉ tính thu nhập và chi phí thực tế đã thanh toán)
        $avgIncome = $incomes->avg('amount') ?? 0; // Trung bình thu nhập từ hóa đơn paid
        $avgExpense = collect($expenses['cash_outflows'])->avg('amount') ?? 0; // Trung bình cash outflows success
        $avgCompanyInvoice = collect($expenses['company_invoices'])->avg('amount') ?? 0; // Trung bình company invoices paid
        
        $forecast = [];
        $startMonth = Carbon::now()->addMonth();
        
        for ($i = 0; $i < $months; $i++) {
            $month = $startMonth->copy()->addMonths($i);
            $forecast[] = [
                'month' => $month->format('M Y'),
                'month_key' => $month->format('Y-m'),
                'forecasted_income' => $avgIncome, // Dự báo thu nhập = trung bình thu nhập thực tế
                'forecasted_expense' => $avgExpense + $avgCompanyInvoice, // Dự báo chi phí = trung bình chi phí thực tế
                'forecasted_net' => $avgIncome - ($avgExpense + $avgCompanyInvoice) // Dòng tiền ròng dự báo
            ];
        }
        
        return $forecast;
    }

    /**
     * Tính tổng hợp dòng tiền (quá khứ và dự báo)
     * 
     * Note:
     * - past_total_income: Tổng thu nhập thực tế (chỉ tính hóa đơn paid)
     * - past_total_expense: Tổng chi phí thực tế (cash outflows success + company invoices paid)
     * - past_net: Dòng tiền ròng quá khứ = Thu nhập thực tế - Chi phí thực tế
     * - forecast_total_income: Tổng thu nhập dự báo (dựa trên trung bình lịch sử)
     * - forecast_total_expense: Tổng chi phí dự báo (dựa trên trung bình lịch sử)
     * - forecast_net: Dòng tiền ròng dự báo = Thu nhập dự báo - Chi phí dự báo
     * 
     * @param \Illuminate\Support\Collection $incomes
     * @param array $expenses
     * @param array $forecast
     * @return array
     */
    private function calculateCashFlowSummary($incomes, $expenses, $forecast)
    {
        // Tổng thu nhập thực tế (chỉ tính hóa đơn paid)
        $totalIncome = $incomes->sum('amount');
        
        // Tổng chi phí thực tế (chỉ tính đã thanh toán)
        $totalExpense = collect($expenses['cash_outflows'])->sum('amount') + 
                        collect($expenses['company_invoices'])->sum('amount');
        
        // Tổng dự báo
        $totalForecastIncome = collect($forecast)->sum('forecasted_income');
        $totalForecastExpense = collect($forecast)->sum('forecasted_expense');
        
        return [
            'past_total_income' => $totalIncome, // Thu nhập thực tế (paid)
            'past_total_expense' => $totalExpense, // Chi phí thực tế (success + paid)
            'past_net' => $totalIncome - $totalExpense, // Dòng tiền ròng quá khứ
            'forecast_total_income' => $totalForecastIncome, // Thu nhập dự báo
            'forecast_total_expense' => $totalForecastExpense, // Chi phí dự báo
            'forecast_net' => $totalForecastIncome - $totalForecastExpense, // Dòng tiền ròng dự báo
            // Notes giải thích
            'calculation_note' => [
                'past_total_income' => 'Tổng thu nhập quá khứ = Tổng hóa đơn đã thanh toán (status = paid)',
                'past_total_expense' => 'Tổng chi phí quá khứ = Cash outflows (success) + Company invoices (paid)',
                'past_net' => 'Dòng tiền ròng quá khứ = Thu nhập thực tế - Chi phí thực tế',
                'forecast_total_income' => 'Tổng thu nhập dự báo = Trung bình thu nhập lịch sử × Số tháng',
                'forecast_total_expense' => 'Tổng chi phí dự báo = Trung bình chi phí lịch sử × Số tháng',
                'forecast_net' => 'Dòng tiền ròng dự báo = Thu nhập dự báo - Chi phí dự báo',
            ],
        ];
    }

    /**
     * Lấy chi phí theo danh mục (chỉ tính các khoản đã thanh toán)
     * 
     * Note: Chỉ tính chi phí thực tế đã thanh toán
     * - Company invoices: status = 'paid'
     * - Cash outflows: status = 'success'
     * Không tính draft, cancelled, issued, overdue
     * 
     * @param int $organizationId
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $category
     * @return \Illuminate\Support\Collection
     */
    private function getCategorizedExpenses($organizationId, $dateFrom, $dateTo, $category)
    {
        // Convert to Carbon for proper date comparison
        $startDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate = Carbon::parse($dateTo)->endOfDay();
        
        // Lấy từ CompanyInvoice - chỉ tính đã thanh toán
        $companyInvoices = CompanyInvoice::where('organization_id', $organizationId)
            ->where('status', 'paid') // Chỉ tính hóa đơn đã thanh toán
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->when($category !== 'all', function($q) use ($category) {
                $q->where('invoice_type', $category);
            })
            ->with(['vendor', 'user', 'payrollPayslip.user', 'ticket'])
            ->orderBy('issue_date', 'desc')
            ->get();
        
        // Lấy từ CashOutflow - chỉ tính đã thanh toán thành công
        $cashOutflows = CashOutflow::byOrganization($organizationId)
            ->where('status', 'success') // Chỉ tính đã thanh toán thành công
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->whereNull('deleted_at')
            ->whereNull('company_invoice_id') // Chỉ lấy những khoản chi độc lập
            ->with('vendor')
            ->orderBy('paid_at', 'desc')
            ->get();
        
        // Kết hợp và format dữ liệu
        $expenses = collect();
        
        foreach ($companyInvoices as $invoice) {
            // Lấy tên đối tượng tùy theo danh mục
            $recipientName = $this->getRecipientName($invoice);
            
            $expenses->push([
                'id' => $invoice->id,
                'type' => 'company_invoice',
                'invoice_no' => $invoice->invoice_no,
                'vendor' => $invoice->vendor,
                'vendor_name' => $recipientName, // Đổi tên field nhưng giữ key để tương thích
                'recipient_name' => $recipientName, // Tên mới
                'category' => $invoice->invoice_type,
                'date' => $invoice->issue_date,
                'amount' => $invoice->total_amount,
                'status' => $invoice->status,
                'description' => $invoice->description ?? 'Hóa đơn công ty'
            ]);
        }
        
        foreach ($cashOutflows as $outflow) {
            $expenses->push([
                'id' => $outflow->id,
                'type' => 'cash_outflow',
                'invoice_no' => 'OUT-' . $outflow->id,
                'vendor' => $outflow->vendor,
                'vendor_name' => $outflow->vendor->name ?? 'N/A',
                'category' => 'cash_outflow',
                'date' => $outflow->paid_at,
                'amount' => $outflow->amount,
                'status' => $outflow->status === 'success' ? 'paid' : $outflow->status,
                'description' => $outflow->note ?? 'Chi phí tiền mặt'
            ]);
        }
        
        // Sắp xếp theo ngày
        return $expenses->sortByDesc('date')->values();
    }

    /**
     * Lấy tên đối tượng nhận thanh toán tùy theo danh mục hóa đơn
     * 
     * @param \App\Models\CompanyInvoice $invoice
     * @return string
     */
    private function getRecipientName($invoice)
    {
        // Nếu có vendor_id, ưu tiên lấy từ vendor
        if ($invoice->vendor_id && $invoice->vendor) {
            return $invoice->vendor->name;
        }
        
        // Tùy theo invoice_type, lấy thông tin từ các nguồn khác nhau
        switch ($invoice->invoice_type) {
            case 'payroll_payslip':
                // Lương nhân viên: lấy từ payrollPayslip->user
                if ($invoice->payrollPayslip && $invoice->payrollPayslip->user) {
                    return $invoice->payrollPayslip->user->full_name ?? $invoice->payrollPayslip->user->name ?? 'N/A';
                }
                // Fallback: lấy từ user_id nếu có
                if ($invoice->user_id && $invoice->user) {
                    return $invoice->user->full_name ?? $invoice->user->name ?? 'N/A';
                }
                break;
                
            case 'master_lease':
                // Hợp đồng thuê chính: lấy từ user (chủ trọ)
                if ($invoice->user_id && $invoice->user) {
                    return $invoice->user->full_name ?? $invoice->user->name ?? 'N/A';
                }
                break;
                
            case 'ticket_cost':
                // Chi phí bảo trì: ưu tiên vendor, nếu không có thì lấy từ ticket
                if ($invoice->ticket) {
                    return 'Ticket #' . $invoice->ticket->id;
                }
                break;
                
            case 'deposit_refund':
                // Hoàn tiền cọc: có thể lấy từ depositRefund
                if ($invoice->depositRefund) {
                    return 'Hoàn tiền cọc #' . $invoice->depositRefund->id;
                }
                break;
        }
        
        // Fallback: nếu có user_id nhưng chưa được xử lý ở trên
        if ($invoice->user_id && $invoice->user) {
            return $invoice->user->full_name ?? $invoice->user->name ?? 'N/A';
        }
        
        return 'N/A';
    }

    /**
     * Thống kê chi phí theo danh mục (chỉ tính các khoản đã thanh toán)
     * 
     * Note: Chỉ tính chi phí thực tế đã thanh toán
     * - Company invoices: status = 'paid'
     * - Cash outflows: status = 'success'
     * 
     * @param int $organizationId
     * @param string $dateFrom
     * @param string $dateTo
     * @return \Illuminate\Support\Collection
     */
    private function getCategoryStatistics($organizationId, $dateFrom, $dateTo)
    {
        // Convert to Carbon for proper date comparison
        $startDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate = Carbon::parse($dateTo)->endOfDay();
        
        // Thống kê từ CompanyInvoice - chỉ tính đã thanh toán
        $companyInvoiceStats = CompanyInvoice::where('organization_id', $organizationId)
            ->where('status', 'paid') // Chỉ tính hóa đơn đã thanh toán
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->select(
                'invoice_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->groupBy('invoice_type')
            ->get();
        
        // Thống kê từ CashOutflow - chỉ tính đã thanh toán thành công
        $cashOutflowStats = CashOutflow::byOrganization($organizationId)
            ->where('status', 'success') // Chỉ tính đã thanh toán thành công
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->whereNull('deleted_at')
            ->whereNull('company_invoice_id')
            ->select(
                DB::raw("'cash_outflow' as invoice_type"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->first();
        
        // Kết hợp thống kê
        $stats = collect();
        
        foreach ($companyInvoiceStats as $stat) {
            $stats->push([
                'invoice_type' => $stat->invoice_type,
                'count' => $stat->count,
                'total_amount' => $stat->total_amount
            ]);
        }
        
        if ($cashOutflowStats && $cashOutflowStats->count > 0) {
            $stats->push([
                'invoice_type' => 'cash_outflow',
                'count' => $cashOutflowStats->count,
                'total_amount' => $cashOutflowStats->total_amount
            ]);
        }
        
        return $stats->sortByDesc('total_amount')->values();
    }

    /**
     * Thống kê chi phí theo đối tượng (chỉ tính các khoản đã thanh toán)
     * 
     * Note: Chỉ tính chi phí thực tế đã thanh toán
     * - Company invoices: status = 'paid'
     * - Cash outflows: status = 'success'
     * 
     * Đối tượng có thể là: vendor, user (nhân viên/chủ trọ), ticket, deposit refund, v.v.
     * 
     * @param int $organizationId
     * @param string $dateFrom
     * @param string $dateTo
     * @return \Illuminate\Support\Collection
     */
    private function getVendorStatistics($organizationId, $dateFrom, $dateTo)
    {
        // Convert to Carbon for proper date comparison
        $startDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate = Carbon::parse($dateTo)->endOfDay();
        
        // Lấy tất cả CompanyInvoice đã thanh toán (không chỉ có vendor_id)
        $companyInvoices = CompanyInvoice::where('organization_id', $organizationId)
            ->where('status', 'paid') // Chỉ tính hóa đơn đã thanh toán
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->with(['vendor', 'user', 'payrollPayslip.user', 'ticket', 'depositRefund'])
            ->get();
        
        // Lấy CashOutflow đã thanh toán thành công (không có company_invoice_id - chi phí độc lập)
        $cashOutflows = CashOutflow::byOrganization($organizationId)
            ->where('status', 'success') // Chỉ tính đã thanh toán thành công
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->whereNull('deleted_at')
            ->whereNull('company_invoice_id') // Chỉ lấy những khoản chi độc lập
            ->with('vendor')
            ->get();
        
        // Nhóm và tính tổng theo đối tượng (recipient name)
        $recipientStats = [];
        
        // Tổng hợp từ CompanyInvoice
        foreach ($companyInvoices as $invoice) {
            $recipientName = $this->getRecipientName($invoice);
            
            if (!isset($recipientStats[$recipientName])) {
                $recipientStats[$recipientName] = [
                    'recipient_name' => $recipientName,
                    'count' => 0,
                    'total_amount' => 0,
                    'vendor' => $invoice->vendor, // Giữ vendor để tương thích với view
                ];
            }
            $recipientStats[$recipientName]['count'] += 1;
            $recipientStats[$recipientName]['total_amount'] += $invoice->total_amount;
        }
        
        // Tổng hợp từ CashOutflow (chi phí độc lập)
        foreach ($cashOutflows as $outflow) {
            $recipientName = $outflow->vendor ? $outflow->vendor->name : 'N/A';
            
            if (!isset($recipientStats[$recipientName])) {
                $recipientStats[$recipientName] = [
                    'recipient_name' => $recipientName,
                    'count' => 0,
                    'total_amount' => 0,
                    'vendor' => $outflow->vendor,
                ];
            }
            $recipientStats[$recipientName]['count'] += 1;
            $recipientStats[$recipientName]['total_amount'] += $outflow->amount;
        }
        
        // Convert to collection, sắp xếp theo total_amount giảm dần và lấy top 10
        return collect($recipientStats)
            ->sortByDesc('total_amount')
            ->take(10)
            ->values();
    }

    /**
     * Lấy hóa đơn sắp đến hạn (chỉ tính issued và overdue, không tính draft/cancelled)
     * 
     * Note: Chỉ tính hóa đơn đã phát hành (issued) hoặc quá hạn (overdue)
     * Không tính draft, cancelled, paid
     * 
     * @param int $organizationId
     * @param int $daysBefore
     * @return \Illuminate\Support\Collection
     */
    private function getUpcomingInvoices($organizationId, $daysBefore)
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($daysBefore);
        
        $invoices = Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['issued', 'overdue']) // Chỉ tính issued và overdue
            ->whereBetween('due_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->with(['lease.tenant', 'bookingDeposit.lead'])
            ->orderBy('due_date', 'asc')
            ->get();
        
        // Thêm thông tin email đã gửi từ audit_log
        foreach ($invoices as $invoice) {
            $lastReminder = AuditLog::where('entity_type', 'invoice')
                ->where('entity_id', $invoice->id)
                ->where('action', 'invoice_reminder_sent')
                ->orderBy('created_at', 'desc')
                ->first();
            
            $invoice->last_reminder_sent_at = $lastReminder ? $lastReminder->created_at : null;
        }
        
        return $invoices;
    }

    /**
     * Lấy hóa đơn quá hạn (chỉ tính issued và overdue, không tính draft/cancelled)
     * 
     * Note: Chỉ tính hóa đơn đã phát hành (issued) hoặc quá hạn (overdue)
     * Không tính draft, cancelled, paid
     * 
     * @param int $organizationId
     * @return \Illuminate\Support\Collection
     */
    private function getOverdueInvoices($organizationId)
    {
        $invoices = Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['issued', 'overdue']) // Chỉ tính issued và overdue
            ->where('due_date', '<', Carbon::today())
            ->whereNull('deleted_at')
            ->with(['lease.tenant', 'bookingDeposit.lead'])
            ->orderBy('due_date', 'asc')
            ->get();
        
        // Thêm thông tin email đã gửi từ audit_log
        foreach ($invoices as $invoice) {
            $lastReminder = AuditLog::where('entity_type', 'invoice')
                ->where('entity_id', $invoice->id)
                ->where('action', 'invoice_reminder_sent')
                ->orderBy('created_at', 'desc')
                ->first();
            
            $invoice->last_reminder_sent_at = $lastReminder ? $lastReminder->created_at : null;
        }
        
        return $invoices;
    }

    /**
     * Lấy company invoices sắp đến hạn
     */
    private function getUpcomingCompanyInvoices($organizationId, $daysBefore)
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($daysBefore);
        
        $invoices = CompanyInvoice::where('organization_id', $organizationId)
            ->whereIn('status', ['pending', 'approved', 'overdue'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->with(['vendor', 'user'])
            ->orderBy('due_date', 'asc')
            ->get();
        
        // Thêm thông tin email đã gửi từ audit_log
        foreach ($invoices as $invoice) {
            $lastReminder = AuditLog::where('entity_type', 'companyinvoice')
                ->where('entity_id', $invoice->id)
                ->where('action', 'companyinvoice_reminder_sent')
                ->orderBy('created_at', 'desc')
                ->first();
            
            $invoice->last_reminder_sent_at = $lastReminder ? $lastReminder->created_at : null;
        }
        
        return $invoices;
    }

    /**
     * Lấy company invoices quá hạn
     */
    private function getOverdueCompanyInvoices($organizationId)
    {
        $invoices = CompanyInvoice::where('organization_id', $organizationId)
            ->whereIn('status', ['pending', 'approved', 'overdue'])
            ->where('due_date', '<', Carbon::today())
            ->whereNull('deleted_at')
            ->with(['vendor', 'user'])
            ->orderBy('due_date', 'asc')
            ->get();
        
        // Thêm thông tin email đã gửi từ audit_log
        foreach ($invoices as $invoice) {
            $lastReminder = AuditLog::where('entity_type', 'companyinvoice')
                ->where('entity_id', $invoice->id)
                ->where('action', 'companyinvoice_reminder_sent')
                ->orderBy('created_at', 'desc')
                ->first();
            
            $invoice->last_reminder_sent_at = $lastReminder ? $lastReminder->created_at : null;
        }
        
        return $invoices;
    }

    /**
     * Gửi email nhắc nhở thanh toán cho tenant invoice
     */
    public function sendInvoiceReminder(Request $request, Invoice $invoice)
    {
        try {
            $this->requireCapability('finance.report.view', 'Bạn không có quyền gửi email nhắc nhở.');
            
            $this->checkOrganizationAccess(
                $invoice->organization_id,
                'Unauthorized access to invoice.',
                'invoice',
                $invoice->id
            );

            $organizationId = $this->getCurrentOrganizationId();

            // Lấy người nhận (tenant hoặc lead)
            $recipient = null;
            $recipientName = null;
            $email = null;
            
            if ($invoice->lease && $invoice->lease->tenant) {
                $recipient = $invoice->lease->tenant;
                $email = $recipient->email ?? null;
                $recipientName = $recipient->full_name ?? $recipient->name ?? 'Người thuê';
            } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->lead_id) {
                // Load lead without global scopes để tránh filter theo organization
                $recipient = \App\Models\Lead::withoutGlobalScopes()->find($invoice->bookingDeposit->lead_id);
                $email = $recipient->email ?? null;
                $recipientName = $recipient->full_name ?? $recipient->name ?? 'Khách hàng';
            }

            if (!$recipient || empty($email)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người nhận hoặc email để gửi'
                ], 400);
            }

            // Tìm user thực sự từ email, nếu không có thì tạo user tạm thời (cho Lead)
            $user = \App\Models\User::where('email', $email)->first();
            
            if (!$user) {
                // Nếu không có user, đây có thể là Lead - gửi email trực tiếp không lưu notification
                $invoiceNo = $invoice->invoice_no ?? 'HD-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT);
                $dueDate = $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A';
                $daysOverdue = $invoice->due_date ? now()->diffInDays($invoice->due_date, false) : 0;
                
                $subject = $daysOverdue > 0 
                    ? "Nhắc nhở: Hóa đơn #{$invoiceNo} đã quá hạn thanh toán"
                    : "Nhắc nhở: Hóa đơn #{$invoiceNo} sắp đến hạn thanh toán";
                
                $content = "Kính gửi {$recipientName},\n\n";
                $content .= "Chúng tôi xin nhắc nhở về hóa đơn:\n\n";
                $content .= "Mã hóa đơn: {$invoiceNo}\n";
                $content .= "Số tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " VNĐ\n";
                $content .= "Hạn thanh toán: {$dueDate}\n";
                
                if ($daysOverdue > 0) {
                    $content .= "Số ngày quá hạn: {$daysOverdue} ngày\n";
                } else {
                    $content .= "Còn lại: " . abs($daysOverdue) . " ngày\n";
                }
                
                $content .= "\nVui lòng thanh toán đúng hạn để tránh gián đoạn dịch vụ.\n\n";
                $content .= "Trân trọng,\n";
                $content .= "Bộ phận Tài chính";

                \App\Support\MailHelper::sendWithOptionalOrgMail(
                    new \App\Mail\NotificationMail(
                        $subject,
                        $content,
                        $recipientName,
                        $daysOverdue > 0 ? 'error' : 'warning',
                        route('staff.invoices.show', $invoice->id),
                        'Xem hóa đơn'
                    ),
                    $email,
                    $organizationId
                );

                $result = [
                    'success' => true,
                    'message' => \App\Support\MailHelper::wantsQueuedDispatch()
                        ? 'Email đã được đưa vào hàng đợi'
                        : 'Email đã được gửi thành công',
                ];
            } else {
                // Có user thực sự - sử dụng NotificationEmailService
                $invoiceNo = $invoice->invoice_no ?? 'HD-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT);
                $dueDate = $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A';
                $daysOverdue = $invoice->due_date ? now()->diffInDays($invoice->due_date, false) : 0;
                
                $subject = $daysOverdue > 0 
                    ? "Nhắc nhở: Hóa đơn #{$invoiceNo} đã quá hạn thanh toán"
                    : "Nhắc nhở: Hóa đơn #{$invoiceNo} sắp đến hạn thanh toán";
                
                $content = "Kính gửi {$recipientName},\n\n";
                $content .= "Chúng tôi xin nhắc nhở về hóa đơn:\n\n";
                $content .= "Mã hóa đơn: {$invoiceNo}\n";
                $content .= "Số tiền: " . number_format($invoice->total_amount, 0, ',', '.') . " VNĐ\n";
                $content .= "Hạn thanh toán: {$dueDate}\n";
                
                if ($daysOverdue > 0) {
                    $content .= "Số ngày quá hạn: {$daysOverdue} ngày\n";
                } else {
                    $content .= "Còn lại: " . abs($daysOverdue) . " ngày\n";
                }
                
                $content .= "\nVui lòng thanh toán đúng hạn để tránh gián đoạn dịch vụ.\n\n";
                $content .= "Trân trọng,\n";
                $content .= "Bộ phận Tài chính";

                // Gửi email
                $emailService = app(NotificationEmailService::class);
                $result = $emailService->sendNotification(
                    $user,
                    $subject,
                    $content,
                    $daysOverdue > 0 ? 'error' : 'warning',
                    route('staff.invoices.show', $invoice->id),
                    'Xem hóa đơn'
                );
            }

            if ($result['success']) {
                // Ghi log vào audit_log
                try {
                    AuditLog::create([
                        'actor_id' => Auth::id(),
                        'organization_id' => $organizationId,
                        'action' => 'invoice_reminder_sent',
                        'entity_type' => 'invoice',
                        'entity_id' => $invoice->id,
                        'before_json' => null,
                        'after_json' => json_encode([
                            'invoice_no' => $invoiceNo,
                            'recipient_email' => $email,
                            'recipient_name' => $recipientName,
                            'subject' => $subject,
                            'days_overdue' => $daysOverdue,
                            'sent_at' => now()->toDateTimeString()
                        ]),
                        'changes_json' => null,
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'created_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log email reminder to audit_log: ' . $e->getMessage());
                }

                Log::info('Invoice payment reminder sent', [
                    'invoice_id' => $invoice->id,
                    'email' => $email
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email nhắc nhở đã được gửi thành công',
                    'sent_at' => now()->toDateTimeString()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Không thể gửi email nhắc nhở'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error sending invoice reminder: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi email nhắc nhở: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gửi email nhắc nhở thanh toán cho company invoice
     */
    public function sendCompanyInvoiceReminder(Request $request, CompanyInvoice $companyInvoice)
    {
        try {
            $this->requireCapability('finance.report.view', 'Bạn không có quyền gửi email nhắc nhở.');
            
            $this->checkOrganizationAccess(
                $companyInvoice->organization_id,
                'Unauthorized access to company invoice.',
                'company_invoice',
                $companyInvoice->id
            );

            $organizationId = $this->getCurrentOrganizationId();

            // Lấy người nhận (vendor hoặc user)
            $recipient = null;
            if ($companyInvoice->vendor_id) {
                $recipient = $companyInvoice->vendor;
            } elseif ($companyInvoice->user_id) {
                $recipient = $companyInvoice->user;
            }

            if (!$recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người nhận để gửi email'
                ], 400);
            }

            // Kiểm tra email
            $email = null;
            $recipientName = null;
            
            if ($companyInvoice->vendor_id && $recipient) {
                $email = $recipient->email ?? $recipient->contact_email ?? null;
                $recipientName = $recipient->name ?? 'Nhà cung cấp';
            } elseif ($companyInvoice->user_id && $recipient) {
                $email = $recipient->email ?? null;
                $recipientName = $recipient->full_name ?? $recipient->name ?? 'Người dùng';
            }

            if (empty($email)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người nhận chưa có địa chỉ email'
                ], 400);
            }

            // Tạo user object tạm thời để gửi email
            $user = $companyInvoice->user;
            if (!$user) {
                // Tạo user object tạm thời cho vendor
                $user = new \App\Models\User([
                    'email' => $email,
                    'name' => $recipientName,
                    'full_name' => $recipientName
                ]);
            }

            // Tạo nội dung email
            $invoiceNo = $companyInvoice->invoice_no ?? 'HDCT' . str_pad($companyInvoice->id, 6, '0', STR_PAD_LEFT);
            $dueDate = $companyInvoice->due_date ? $companyInvoice->due_date->format('d/m/Y') : 'N/A';
            $daysOverdue = $companyInvoice->due_date ? now()->diffInDays($companyInvoice->due_date, false) : 0;
            
            $subject = $daysOverdue > 0 
                ? "Nhắc nhở: Hóa đơn công ty #{$invoiceNo} đã quá hạn thanh toán"
                : "Nhắc nhở: Hóa đơn công ty #{$invoiceNo} sắp đến hạn thanh toán";
            
            $content = "Kính gửi {$recipientName},\n\n";
            $content .= "Chúng tôi xin nhắc nhở về hóa đơn công ty:\n\n";
            $content .= "Mã hóa đơn: {$invoiceNo}\n";
            $content .= "Số tiền: " . number_format($companyInvoice->total_amount, 0, ',', '.') . " VNĐ\n";
            $content .= "Hạn thanh toán: {$dueDate}\n";
            
            if ($daysOverdue > 0) {
                $content .= "Số ngày quá hạn: {$daysOverdue} ngày\n";
            } else {
                $content .= "Còn lại: " . abs($daysOverdue) . " ngày\n";
            }
            
            $content .= "\nVui lòng thanh toán đúng hạn để tránh gián đoạn dịch vụ.\n\n";
            $content .= "Trân trọng,\n";
            $content .= "Bộ phận Tài chính";

            // Gửi email
            $emailService = app(NotificationEmailService::class);
            $result = $emailService->sendNotification(
                $user,
                $subject,
                $content,
                $daysOverdue > 0 ? 'error' : 'warning',
                route('staff.company-invoices.show', $companyInvoice->id),
                'Xem hóa đơn'
            );

            if ($result['success']) {
                // Ghi log vào audit_log
                try {
                    AuditLog::create([
                        'actor_id' => Auth::id(),
                        'organization_id' => $organizationId,
                        'action' => 'companyinvoice_reminder_sent',
                        'entity_type' => 'companyinvoice',
                        'entity_id' => $companyInvoice->id,
                        'before_json' => null,
                        'after_json' => json_encode([
                            'invoice_no' => $invoiceNo,
                            'recipient_email' => $email,
                            'recipient_name' => $recipientName,
                            'subject' => $subject,
                            'days_overdue' => $daysOverdue,
                            'sent_at' => now()->toDateTimeString()
                        ]),
                        'changes_json' => null,
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'created_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log email reminder to audit_log: ' . $e->getMessage());
                }

                Log::info('Company invoice payment reminder sent', [
                    'company_invoice_id' => $companyInvoice->id,
                    'email' => $email
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email nhắc nhở đã được gửi thành công',
                    'sent_at' => now()->toDateTimeString()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Không thể gửi email nhắc nhở'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error sending company invoice reminder: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi email nhắc nhở: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy dữ liệu thu nhập cho đối soát (loại bỏ draft và cancelled)
     * 
     * Note: Chỉ lấy các hóa đơn có status hợp lệ (issued, overdue, paid)
     * Không tính draft và cancelled
     * Khi tính tổng thu nhập thực tế, chỉ tính status = 'paid'
     * 
     * @param int $organizationId
     * @param string $dateFrom
     * @param string $dateTo
     * @return \Illuminate\Support\Collection
     */
    private function getReconciliationIncome($organizationId, $dateFrom, $dateTo)
    {
        // Convert to Carbon for proper date comparison
        $startDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate = Carbon::parse($dateTo)->endOfDay();
        
        // Debug: Log query info
        Log::info('getReconciliationIncome', [
            'organization_id' => $organizationId,
            'date_from' => $startDate->format('Y-m-d H:i:s'),
            'date_to' => $endDate->format('Y-m-d H:i:s'),
        ]);
        
        // Chỉ lấy các hóa đơn có status hợp lệ (loại bỏ draft và cancelled)
        $query = Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['issued', 'overdue', 'paid']) // Chỉ tính issued, overdue, paid
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->whereNull('deleted_at');
        
        // Debug: Log raw SQL
        Log::info('Income Query SQL', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
        
        $results = $query->select(
                'id',
                'invoice_no',
                'issue_date',
                'due_date',
                'status',
                'total_amount',
                'currency'
            )
            ->get();
        
        Log::info('Income Results', ['count' => $results->count(), 'total' => $results->sum('total_amount')]);
        
        return $results;
    }

    /**
     * Lấy dữ liệu chi phí cho đối soát (chỉ tính các khoản đã thanh toán)
     * 
     * Note: Chỉ tính chi phí thực tế đã thanh toán
     * - Company invoices: status = 'paid'
     * - Cash outflows: status = 'success'
     * 
     * @param int $organizationId
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    private function getReconciliationExpense($organizationId, $dateFrom, $dateTo)
    {
        // Convert to Carbon for proper date comparison
        $startDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate = Carbon::parse($dateTo)->endOfDay();
        
        // Lấy CompanyInvoice - chỉ tính đã thanh toán
        $companyInvoiceQuery = CompanyInvoice::where('organization_id', $organizationId)
            ->where('status', 'paid') // Chỉ tính hóa đơn đã thanh toán
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->whereNull('deleted_at');
        
        Log::info('CompanyInvoice Query', [
            'organization_id' => $organizationId,
            'sql' => $companyInvoiceQuery->toSql(),
            'bindings' => $companyInvoiceQuery->getBindings()
        ]);
        
        $companyInvoices = $companyInvoiceQuery->get();
        
        Log::info('CompanyInvoice Results', [
            'count' => $companyInvoices->count(),
            'total' => $companyInvoices->sum('total_amount')
        ]);
            
        // Lấy CashOutflow - chỉ tính đã thanh toán thành công
        $cashOutflowQuery = CashOutflow::byOrganization($organizationId)
            ->where('status', 'success') // Chỉ tính đã thanh toán thành công
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->whereNull('deleted_at');
        
        Log::info('CashOutflow Query', [
            'organization_id' => $organizationId,
            'sql' => $cashOutflowQuery->toSql(),
            'bindings' => $cashOutflowQuery->getBindings()
        ]);
        
        $cashOutflows = $cashOutflowQuery->get();
        
        Log::info('CashOutflow Results', [
            'count' => $cashOutflows->count(),
            'total' => $cashOutflows->sum('amount')
        ]);
            
        return [
            'company_invoices' => $companyInvoices,
            'cash_outflows' => $cashOutflows
        ];
    }

    private function getReconciliationPayments($organizationId, $dateFrom, $dateTo)
    {
        // Convert to Carbon for proper date comparison
        $startDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate = Carbon::parse($dateTo)->endOfDay();
        
        return Payment::whereHas('invoice', function($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->where('status', 'success')
            ->with('invoice')
            ->get();
    }

    /**
     * Đối chiếu dữ liệu thu chi
     * 
     * Note:
     * - invoices_total: Tổng các hóa đơn hợp lệ (issued, overdue, paid) - KHÔNG tính draft, cancelled
     * - invoices_paid: Chỉ tính hóa đơn đã thanh toán (status = 'paid') - Thu nhập thực tế
     * - invoices_pending: Hóa đơn chưa thanh toán nhưng hợp lệ (issued, overdue) - KHÔNG tính draft, cancelled
     * - expenses_total: Chỉ tính chi phí đã thanh toán (company invoices paid + cash outflows success)
     * - payments_total: Tổng thanh toán thực tế từ bảng payments
     * - net_cash_flow: Dòng tiền ròng = Thu nhập thực tế (paid) - Chi phí thực tế (paid)
     * 
     * @param \Illuminate\Support\Collection $incomeData
     * @param array $expenseData
     * @param \Illuminate\Support\Collection $payments
     * @return array
     */
    private function reconcileData($incomeData, $expenseData, $payments)
    {
        // Tính tổng từ invoices (đã loại bỏ draft và cancelled)
        $totalInvoices = $incomeData->sum('total_amount'); // Tổng hóa đơn hợp lệ (issued, overdue, paid)
        $totalPaid = $incomeData->where('status', 'paid')->sum('total_amount'); // Chỉ tính đã thanh toán
        $totalPending = $incomeData->whereIn('status', ['issued', 'overdue'])->sum('total_amount'); // Chưa thanh toán nhưng hợp lệ
        
        // Tính tổng chi phí - chỉ tính đã thanh toán
        $companyInvoiceTotal = $expenseData['company_invoices']->sum('total_amount'); // Đã lọc status = 'paid'
        $cashOutflowTotal = $expenseData['cash_outflows']->sum('amount'); // Đã lọc status = 'success'
        $totalExpenses = $companyInvoiceTotal + $cashOutflowTotal;
        
        // Tính tổng thanh toán thực tế
        $totalPayments = $payments->sum('amount');
        
        // Tính chênh lệch và dòng tiền ròng
        $difference = $totalPaid - $totalPayments; // Chênh lệch giữa hóa đơn paid và payments thực tế
        $netCashFlow = $totalPaid - $totalExpenses; // Dòng tiền ròng = Thu nhập thực tế - Chi phí thực tế
        
        return [
            'invoices_total' => $totalInvoices,
            'invoices_paid' => $totalPaid,
            'invoices_pending' => $totalPending,
            'expenses_total' => $totalExpenses,
            'company_invoice_total' => $companyInvoiceTotal,
            'cash_outflow_total' => $cashOutflowTotal,
            'payments_total' => $totalPayments,
            'difference' => $difference,
            'net_cash_flow' => $netCashFlow,
            // Notes giải thích
            'calculation_note' => [
                'invoices_total' => 'Tổng các hóa đơn hợp lệ (issued, overdue, paid) - KHÔNG tính draft, cancelled',
                'invoices_paid' => 'Chỉ tính hóa đơn đã thanh toán (status = paid) - Thu nhập thực tế',
                'invoices_pending' => 'Hóa đơn chưa thanh toán nhưng hợp lệ (issued, overdue) - KHÔNG tính draft, cancelled',
                'expenses_total' => 'Tổng chi phí thực tế = Company invoices (paid) + Cash outflows (success)',
                'payments_total' => 'Tổng thanh toán thực tế từ bảng payments (status = success)',
                'net_cash_flow' => 'Dòng tiền ròng = Thu nhập thực tế (paid) - Chi phí thực tế (paid)',
            ],
        ];
    }

    /**
     * Lấy dữ liệu thuế VAT (chỉ tính hóa đơn đã thanh toán)
     * 
     * Note: Chỉ tính thuế VAT từ hóa đơn đã thanh toán (status = 'paid')
     * Không tính draft, cancelled, issued, overdue
     * 
     * @param int $organizationId
     * @param int $year
     * @param int|null $quarter
     * @param string $currency
     * @return \Illuminate\Support\Collection
     */
    private function getTaxData($organizationId, $year, $quarter, $currency)
    {
        // Chỉ tính thuế từ hóa đơn đã thanh toán
        $query = Invoice::where('organization_id', $organizationId)
            ->where('status', 'paid') // Chỉ tính hóa đơn đã thanh toán
            ->whereYear('issue_date', $year)
            ->whereNull('deleted_at');
            
        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            $query->whereBetween(DB::raw('MONTH(issue_date)'), [$startMonth, $endMonth]);
        }
        
        if ($currency !== 'all') {
            $query->where('currency', $currency);
        }
        
        return $query->select(
                DB::raw('DATE_FORMAT(issue_date, "%Y-%m") as month'),
                DB::raw('SUM(tax_amount) as total_tax'), // Tổng thuế VAT
                DB::raw('SUM(total_amount) as total_amount'), // Tổng giá trị hóa đơn
                'currency'
            )
            ->groupBy('month', 'currency')
            ->orderBy('month')
            ->get();
    }

    /**
     * Tổng hợp dữ liệu thuế VAT
     * 
     * Note: Chỉ tính thuế VAT từ hóa đơn đã thanh toán (status = 'paid')
     * - total_tax: Tổng thuế VAT
     * - total_amount: Tổng giá trị hóa đơn
     * - tax_rate: Tỷ lệ thuế = (Tổng thuế / Tổng giá trị) × 100
     * 
     * @param \Illuminate\Support\Collection $taxData
     * @param int|null $quarter
     * @return array|\Illuminate\Support\Collection
     */
    private function getTaxSummary($taxData, $quarter)
    {
        if ($quarter) {
            return [
                'total_tax' => $taxData->sum('total_tax'), // Tổng thuế VAT
                'total_amount' => $taxData->sum('total_amount'), // Tổng giá trị hóa đơn
                'tax_rate' => $taxData->sum('total_amount') > 0 
                    ? ($taxData->sum('total_tax') / $taxData->sum('total_amount')) * 100 
                    : 0, // Tỷ lệ thuế (%)
                // Notes giải thích
                'calculation_note' => [
                    'total_tax' => 'Tổng thuế VAT = Tổng tax_amount từ hóa đơn đã thanh toán (status = paid)',
                    'total_amount' => 'Tổng giá trị hóa đơn = Tổng total_amount từ hóa đơn đã thanh toán (status = paid)',
                    'tax_rate' => 'Tỷ lệ thuế = (Tổng thuế VAT / Tổng giá trị hóa đơn) × 100',
                ],
            ];
        }
        
        return $taxData->groupBy('month')->map(function($items) {
            return [
                'total_tax' => $items->sum('total_tax'),
                'total_amount' => $items->sum('total_amount'),
                'tax_rate' => $items->sum('total_amount') > 0 
                    ? ($items->sum('total_tax') / $items->sum('total_amount')) * 100 
                    : 0
            ];
        });
    }

    private function getTaxByType($organizationId, $year, $quarter)
    {
        // Hiện tại chỉ có VAT trong tax_amount
        // Có thể mở rộng trong tương lai nếu có các loại thuế khác
        return [];
    }
}

