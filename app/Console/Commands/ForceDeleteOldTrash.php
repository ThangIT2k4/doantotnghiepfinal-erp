<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ForceDeleteOldTrash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trash:force-delete-old {--days=30 : Số ngày sau khi xóa mềm để xóa vĩnh viễn}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa vĩnh viễn các bản ghi đã xóa mềm sau một số ngày nhất định (mặc định 30 ngày)';

    /**
     * Danh sách các bảng có soft delete
     */
    private $softDeleteTables = [
        'leads' => 'App\Models\Lead',
        'viewings' => 'App\Models\Viewing',
        'properties' => 'App\Models\Property',
        'units' => 'App\Models\Unit',
        'users' => 'App\Models\User',
        'organizations' => 'App\Models\Organization',
        'leases' => 'App\Models\Lease',
        'booking_deposits' => 'App\Models\BookingDeposit',
        'invoices' => 'App\Models\Invoice',
        'documents' => 'App\Models\Document',
        'reviews' => 'App\Models\Review',
        'tickets' => 'App\Models\Ticket',
        'payments' => 'App\Models\Payment',
        // 'commissions' => 'App\Models\Commission', // Bảng không tồn tại
        'salary_contracts' => 'App\Models\SalaryContract',
        'salary_advances' => 'App\Models\SalaryAdvance',
        'vendors' => 'App\Models\Vendor',
        'services' => 'App\Models\Service',
        'property_types' => 'App\Models\PropertyType',
        'locations' => 'App\Models\Location',
        'locations_2025' => 'App\Models\Location2025',
        'amenities' => 'App\Models\Amenity',
        'deposit_refunds' => 'App\Models\DepositRefund',
        'company_invoices' => 'App\Models\CompanyInvoice',
        'commission_events' => 'App\Models\CommissionEvent',
        'commission_policies' => 'App\Models\CommissionPolicy',
        'payroll_payslips' => 'App\Models\PayrollPayslip',
        'ticket_logs' => 'App\Models\TicketLog',
        'review_replies' => 'App\Models\ReviewReply',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Bắt đầu xóa vĩnh viễn các bản ghi đã xóa mềm trước {$cutoffDate->format('d/m/Y H:i:s')}...");
        
        $totalDeleted = 0;
        $errors = [];
        
        foreach ($this->softDeleteTables as $table => $modelClass) {
            try {
                if (!class_exists($modelClass)) {
                    $this->warn("Model {$modelClass} không tồn tại, bỏ qua...");
                    continue;
                }
                
                // Get records deleted before cutoff date
                $records = $modelClass::onlyTrashed()
                    ->where('deleted_at', '<', $cutoffDate)
                    ->get();
                
                $count = $records->count();
                
                if ($count > 0) {
                    $deleted = 0;
                    foreach ($records as $record) {
                        try {
                            $record->forceDelete();
                            $deleted++;
                        } catch (\Exception $e) {
                            $errors[] = "Lỗi xóa {$table} ID {$record->id}: " . $e->getMessage();
                            Log::error("Force delete failed for {$table} ID {$record->id}: " . $e->getMessage());
                        }
                    }
                    
                    $totalDeleted += $deleted;
                    $this->info("✓ {$table}: Đã xóa vĩnh viễn {$deleted}/{$count} bản ghi");
                } else {
                    $this->line("  {$table}: Không có bản ghi nào cần xóa");
                }
            } catch (\Exception $e) {
                $errors[] = "Lỗi xử lý bảng {$table}: " . $e->getMessage();
                Log::error("Force delete old trash failed for {$table}: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info("Hoàn thành! Tổng cộng đã xóa vĩnh viễn: {$totalDeleted} bản ghi");
        
        if (!empty($errors)) {
            $this->newLine();
            $this->error("Có một số lỗi xảy ra:");
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }
        
        Log::info("Force delete old trash completed", [
            'total_deleted' => $totalDeleted,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'errors_count' => count($errors)
        ]);
        
        return 0;
    }
}

