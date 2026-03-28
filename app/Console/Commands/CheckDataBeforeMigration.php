<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command: CheckDataBeforeMigration
 * 
 * MỤC ĐÍCH:
 * Kiểm tra dữ liệu trước khi chạy migration thêm constraints (NOT NULL, UNIQUE, CHECK).
 * Command này được dùng để đảm bảo dữ liệu hợp lệ trước khi thêm constraints vào database.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Kiểm tra NOT NULL constraints: Tìm các bản ghi có giá trị NULL ở các cột sẽ được thêm NOT NULL
 * 2. Kiểm tra UNIQUE constraints: Tìm các bản ghi trùng lặp ở các cột sẽ được thêm UNIQUE
 * 3. Kiểm tra CHECK constraints: Tìm các bản ghi vi phạm các điều kiện sẽ được thêm CHECK
 * 4. Tổng hợp kết quả và hiển thị
 * 5. Nếu có vi phạm: Có thể export ra file (với --export)
 * 
 * CÁCH CHẠY:
 * php artisan data:check-before-migration [--export]
 * 
 * Options:
 * --export: Xuất kết quả vi phạm ra file text
 * 
 * LƯU Ý:
 * - Command này chỉ KIỂM TRA, không thay đổi dữ liệu
 * - Phải sửa tất cả vi phạm trước khi chạy migration thêm constraints
 */
class CheckDataBeforeMigration extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Options:
     * - --export: Export results to file
     * 
     * @var string
     */
    protected $signature = 'data:check-before-migration {--export : Export results to file}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Kiểm tra dữ liệu trước khi chạy migration thêm constraints';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Gọi checkNotNullConstraints() để kiểm tra NOT NULL violations
     * 2. Gọi checkUniqueConstraints() để kiểm tra UNIQUE violations
     * 3. Gọi checkCheckConstraints() để kiểm tra CHECK violations
     * 4. Tổng hợp kết quả và hiển thị bảng tổng kết
     * 5. Nếu có vi phạm:
     *    - Hiển thị cảnh báo
     *    - Nếu có --export: Gọi exportViolations() để xuất ra file
     *    - Trả về 1 (lỗi)
     * 6. Nếu không có vi phạm: Trả về 0 (thành công)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Query từ các bảng để kiểm tra vi phạm
     * 
     * DỮ LIỆU GHI VÀO:
     * - File text (nếu có --export): Xuất kết quả vi phạm ra file
     * 
     * @return int 0 nếu không có vi phạm, 1 nếu có vi phạm
     */
    public function handle()
    {
        $this->info('🔍 Bắt đầu kiểm tra dữ liệu...');
        $this->newLine();

        $violations = [];
        $totalViolations = 0;

        // ============================================
        // PHẦN 1: Kiểm tra NOT NULL constraints
        // ============================================
        $this->info('📋 1. Kiểm tra NOT NULL constraints...');
        $notNullViolations = $this->checkNotNullConstraints();
        $violations['NOT NULL'] = $notNullViolations;
        $totalViolations += array_sum(array_column($notNullViolations, 'count'));

        // ============================================
        // PHẦN 2: Kiểm tra UNIQUE constraints
        // ============================================
        $this->info('🔑 2. Kiểm tra UNIQUE constraints...');
        $uniqueViolations = $this->checkUniqueConstraints();
        $violations['UNIQUE'] = $uniqueViolations;
        $totalViolations += array_sum(array_column($uniqueViolations, 'count'));

        // ============================================
        // PHẦN 3: Kiểm tra CHECK constraints
        // ============================================
        $this->info('✅ 3. Kiểm tra CHECK constraints...');
        $checkViolations = $this->checkCheckConstraints();
        $violations['CHECK'] = $checkViolations;
        $totalViolations += array_sum(array_column($checkViolations, 'count'));

        // ============================================
        // Tổng kết
        // ============================================
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('📊 TỔNG KẾT KIỂM TRA');
        $this->info('═══════════════════════════════════════════════════════');
        
        $this->table(
            ['Loại Constraint', 'Số Bảng', 'Tổng Vi Phạm'],
            [
                ['NOT NULL', count($violations['NOT NULL']), array_sum(array_column($violations['NOT NULL'], 'count'))],
                ['UNIQUE', count($violations['UNIQUE']), array_sum(array_column($uniqueViolations, 'count'))],
                ['CHECK', count($violations['CHECK']), array_sum(array_column($checkViolations, 'count'))],
                ['TỔNG CỘNG', '', $totalViolations],
            ]
        );

        if ($totalViolations > 0) {
            $this->error("⚠️  Phát hiện {$totalViolations} vi phạm! Vui lòng sửa dữ liệu trước khi chạy migration.");
            
            if ($this->option('export')) {
                $this->exportViolations($violations);
            } else {
                $this->warn('💡 Chạy với --export để xuất chi tiết ra file');
            }
            
            return 1;
        } else {
            $this->info('✅ Không có vi phạm nào! Có thể chạy migration an toàn.');
            return 0;
        }
    }

    /**
     * Kiểm tra NOT NULL constraints
     * 
     * LUỒNG XỬ LÝ:
     * 1. Định nghĩa danh sách các cột cần kiểm tra NOT NULL
     * 2. Với mỗi cột:
     *    - Query từ bảng để đếm số bản ghi có giá trị NULL
     *    - Nếu có NULL: Thêm vào danh sách vi phạm
     * 3. Trả về mảng các vi phạm
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Query COUNT(*) với WHERE column IS NULL từ các bảng
     * 
     * @return array Mảng các vi phạm NOT NULL với format: ['table' => string, 'column' => string, 'count' => int, 'type' => 'NOT NULL']
     */
    private function checkNotNullConstraints(): array
    {
        $checks = [
            // Leads
            ['table' => 'leads', 'column' => 'name', 'type' => 'string'],
            ['table' => 'leads', 'column' => 'phone', 'type' => 'string'],
            
            // Invoices
            ['table' => 'invoices', 'column' => 'organization_id', 'type' => 'bigint'],
            ['table' => 'invoices', 'column' => 'invoice_no', 'type' => 'string'],
            
            // Leases
            ['table' => 'leases', 'column' => 'organization_id', 'type' => 'bigint'],
            ['table' => 'leases', 'column' => 'contract_no', 'type' => 'string'],
            
            // Units
            ['table' => 'units', 'column' => 'code', 'type' => 'string'],
            
            // Organizations
            ['table' => 'organizations', 'column' => 'code', 'type' => 'string'],
            ['table' => 'organizations', 'column' => 'phone', 'type' => 'string'],
            ['table' => 'organizations', 'column' => 'email', 'type' => 'string'],
            
            // Payments
            ['table' => 'payments', 'column' => 'method_id', 'type' => 'bigint'],
            ['table' => 'payments', 'column' => 'payer_user_id', 'type' => 'bigint'],
            
            // Properties
            ['table' => 'properties', 'column' => 'organization_id', 'type' => 'bigint'],
            
            // Tickets
            ['table' => 'tickets', 'column' => 'organization_id', 'type' => 'bigint'],
            ['table' => 'tickets', 'column' => 'created_by', 'type' => 'bigint'],
            ['table' => 'tickets', 'column' => 'priority_id', 'type' => 'bigint'],
            
            // Company Invoices
            ['table' => 'company_invoices', 'column' => 'created_by', 'type' => 'bigint'],
            
            // Viewings
            ['table' => 'viewings', 'column' => 'organization_id', 'type' => 'bigint'],
            ['table' => 'viewings', 'column' => 'agent_id', 'type' => 'bigint'],
            
            // Invoice Items
            ['table' => 'invoice_items', 'column' => 'description', 'type' => 'string'],
            ['table' => 'invoice_items', 'column' => 'quantity', 'type' => 'decimal'],
            
            // Company Invoice Items
            ['table' => 'company_invoice_items', 'column' => 'description', 'type' => 'string'],
            
            // Meters
            ['table' => 'meters', 'column' => 'service_id', 'type' => 'bigint'],
            
            // Meter Readings
            ['table' => 'meter_readings', 'column' => 'taken_by', 'type' => 'bigint'],
            
            // Commission Policies
            ['table' => 'commission_policies', 'column' => 'code', 'type' => 'string'],
            
            // Commission Events
            ['table' => 'commission_events', 'column' => 'agent_id', 'type' => 'bigint'],
            
            // Master Leases
            ['table' => 'master_leases', 'column' => 'contract_no', 'type' => 'string'],
            
            // Payroll Payslip Items
            ['table' => 'payroll_payslip_items', 'column' => 'item_name', 'type' => 'string'],
            
            // Amenities
            ['table' => 'amenities', 'column' => 'key_code', 'type' => 'string'],
            
            // Services
            ['table' => 'services', 'column' => 'key_code', 'type' => 'string'],
            
            // Payment Methods
            ['table' => 'payment_methods', 'column' => 'key_code', 'type' => 'string'],
            
            // Payment Cycles
            ['table' => 'payment_cycles', 'column' => 'name', 'type' => 'string'],
            ['table' => 'payment_cycles', 'column' => 'billing_day', 'type' => 'integer'],
            
            // Payment Tokens
            ['table' => 'payment_tokens', 'column' => 'expires_at', 'type' => 'timestamp'],
            
            // Notifications
            ['table' => 'notifications', 'column' => 'to_user_id', 'type' => 'bigint'],
            ['table' => 'notifications', 'column' => 'subject', 'type' => 'string'],
            ['table' => 'notifications', 'column' => 'content', 'type' => 'text'],
            ['table' => 'notifications', 'column' => 'channel_id', 'type' => 'bigint'],
            
            // Audit Logs
            ['table' => 'audit_logs', 'column' => 'action', 'type' => 'string'],
            ['table' => 'audit_logs', 'column' => 'entity_type', 'type' => 'string'],
            ['table' => 'audit_logs', 'column' => 'entity_id', 'type' => 'bigint'],
            
            // Documents
            ['table' => 'documents', 'column' => 'file_name', 'type' => 'string'],
            ['table' => 'documents', 'column' => 'uploaded_by', 'type' => 'bigint'],
            
            // Ticket Logs
            ['table' => 'ticket_logs', 'column' => 'actor_id', 'type' => 'bigint'],
            ['table' => 'ticket_logs', 'column' => 'action', 'type' => 'string'],
            
            // Webhook Logs
            ['table' => 'webhook_logs', 'column' => 'transaction_date', 'type' => 'datetime'],
            ['table' => 'webhook_logs', 'column' => 'account_number', 'type' => 'string'],
            ['table' => 'webhook_logs', 'column' => 'gateway', 'type' => 'string'],
            
            // Ticket Priorities
            ['table' => 'ticket_priorities', 'column' => 'name', 'type' => 'string'],
            
            // User Profiles
            ['table' => 'user_profiles', 'column' => 'full_name', 'type' => 'string'],
        ];

        $violations = [];
        
        foreach ($checks as $check) {
            try {
                $count = DB::table($check['table'])
                    ->whereNull($check['column'])
                    ->count();
                
                if ($count > 0) {
                    $violations[] = [
                        'table' => $check['table'],
                        'column' => $check['column'],
                        'count' => $count,
                        'type' => 'NOT NULL',
                    ];
                    
                    $this->warn("  ⚠️  {$check['table']}.{$check['column']}: {$count} bản ghi NULL");
                }
            } catch (\Exception $e) {
                $this->warn("  ⚠️  Không thể kiểm tra {$check['table']}.{$check['column']}: " . $e->getMessage());
            }
        }

        return $violations;
    }

    /**
     * Kiểm tra UNIQUE constraints
     * 
     * LUỒNG XỬ LÝ:
     * 1. Định nghĩa danh sách các constraint UNIQUE cần kiểm tra (có thể là nhiều cột)
     * 2. Với mỗi constraint:
     *    - Query từ bảng để tìm các nhóm trùng lặp (GROUP BY các cột, HAVING COUNT(*) > 1)
     *    - Nếu có trùng lặp: Thêm vào danh sách vi phạm
     * 3. Trả về mảng các vi phạm
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Query GROUP BY với HAVING COUNT(*) > 1 từ các bảng
     * 
     * @return array Mảng các vi phạm UNIQUE với format: ['table' => string, 'columns' => string, 'count' => int, 'type' => 'UNIQUE', 'duplicates' => array]
     */
    private function checkUniqueConstraints(): array
    {
        $checks = [
            // Master Leases
            [
                'table' => 'master_leases',
                'columns' => ['contract_no'],
                'name' => 'contract_no',
            ],
            
            // Payroll Cycles (với deleted_at)
            [
                'table' => 'payroll_cycles',
                'columns' => ['organization_id', 'period_month', 'deleted_at'],
                'name' => 'organization_id + period_month + deleted_at',
            ],
            
            // Payroll Payslips (với deleted_at)
            [
                'table' => 'payroll_payslips',
                'columns' => ['payroll_cycle_id', 'user_id', 'deleted_at'],
                'name' => 'payroll_cycle_id + user_id + deleted_at',
            ],
            
            // Organization Banking
            [
                'table' => 'organization_banking',
                'columns' => ['organization_id', 'account_number'],
                'name' => 'organization_id + account_number',
            ],
            
            // Lease Service Sets (với deleted_at)
            [
                'table' => 'lease_service_sets',
                'columns' => ['organization_id', 'name', 'deleted_at'],
                'name' => 'organization_id + name + deleted_at',
            ],
            
            // User Profiles - ID Number
            [
                'table' => 'user_profiles',
                'columns' => ['id_number'],
                'name' => 'id_number',
                'whereNull' => false, // Cho phép NULL nhưng unique khi có giá trị
            ],
            
            // User Profiles - Bank Account
            [
                'table' => 'user_profiles',
                'columns' => ['sepay_bank_id', 'account_number'],
                'name' => 'sepay_bank_id + account_number',
            ],
        ];

        $violations = [];
        
        foreach ($checks as $check) {
            try {
                // Đếm các nhóm trùng lặp
                $query = DB::table($check['table'])
                    ->select($check['columns'])
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy($check['columns'])
                    ->havingRaw('COUNT(*) > 1');
                
                // Nếu có deleted_at, chỉ kiểm tra các bản ghi không bị xóa
                if (in_array('deleted_at', $check['columns'])) {
                    // Không cần filter vì deleted_at đã trong group by
                }
                
                // Nếu là id_number, chỉ kiểm tra khi không NULL
                if (isset($check['whereNull']) && !$check['whereNull']) {
                    $query->whereNotNull('id_number');
                }
                
                $duplicates = $query->get();
                
                if ($duplicates->count() > 0) {
                    $totalCount = $duplicates->sum('count');
                    $violations[] = [
                        'table' => $check['table'],
                        'columns' => $check['name'],
                        'count' => $totalCount,
                        'type' => 'UNIQUE',
                        'duplicates' => $duplicates->toArray(),
                    ];
                    
                    $this->warn("  ⚠️  {$check['table']} ({$check['name']}): {$totalCount} bản ghi trùng lặp");
                }
            } catch (\Exception $e) {
                $this->warn("  ⚠️  Không thể kiểm tra {$check['table']}: " . $e->getMessage());
            }
        }

        return $violations;
    }

    /**
     * Kiểm tra CHECK constraints
     * 
     * LUỒNG XỬ LÝ:
     * 1. Định nghĩa danh sách các constraint CHECK cần kiểm tra (SQL conditions)
     * 2. Với mỗi constraint:
     *    - Thực thi query SQL để đếm số bản ghi vi phạm điều kiện
     *    - Nếu có vi phạm: Thêm vào danh sách vi phạm
     * 3. Trả về mảng các vi phạm
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: Thực thi các query SQL để kiểm tra điều kiện CHECK
     * 
     * @return array Mảng các vi phạm CHECK với format: ['table' => string, 'constraint' => string, 'count' => int, 'type' => 'CHECK']
     */
    private function checkCheckConstraints(): array
    {
        $checks = [
            // Invoice Items
            [
                'table' => 'invoice_items',
                'constraint' => 'amount = quantity * unit_price AND amount >= 0 AND quantity > 0',
                'name' => 'amount calculation & positive values',
                'query' => "SELECT COUNT(*) as count FROM invoice_items WHERE NOT (amount = quantity * unit_price AND amount >= 0 AND quantity > 0)",
            ],
            
            // Company Invoice Items
            [
                'table' => 'company_invoice_items',
                'constraint' => 'amount = quantity * unit_price AND amount >= 0 AND quantity > 0',
                'name' => 'amount calculation & positive values',
                'query' => "SELECT COUNT(*) as count FROM company_invoice_items WHERE NOT (amount = quantity * unit_price AND amount >= 0 AND quantity > 0)",
            ],
            
            // Payroll Payslips
            [
                'table' => 'payroll_payslips',
                'constraint' => 'net_amount = gross_amount - deduction_amount',
                'name' => 'net_amount calculation',
                'query' => "SELECT COUNT(*) as count FROM payroll_payslips WHERE NOT (net_amount = gross_amount - deduction_amount)",
            ],
            [
                'table' => 'payroll_payslips',
                'constraint' => 'gross_amount >= 0 AND deduction_amount >= 0 AND net_amount >= 0',
                'name' => 'positive amounts',
                'query' => "SELECT COUNT(*) as count FROM payroll_payslips WHERE NOT (gross_amount >= 0 AND deduction_amount >= 0 AND net_amount >= 0)",
            ],
            
            // Payroll Payslip Items
            [
                'table' => 'payroll_payslip_items',
                'constraint' => 'sign IN (1, -1)',
                'name' => 'sign value',
                'query' => "SELECT COUNT(*) as count FROM payroll_payslip_items WHERE sign NOT IN (1, -1)",
            ],
            [
                'table' => 'payroll_payslip_items',
                'constraint' => 'amount >= 0',
                'name' => 'positive amount',
                'query' => "SELECT COUNT(*) as count FROM payroll_payslip_items WHERE amount < 0",
            ],
            
            // Salary Advances
            [
                'table' => 'salary_advances',
                'constraint' => 'remaining_amount = amount - repaid_amount',
                'name' => 'remaining_amount calculation',
                'query' => "SELECT COUNT(*) as count FROM salary_advances WHERE NOT (remaining_amount = amount - repaid_amount)",
            ],
            [
                'table' => 'salary_advances',
                'constraint' => 'repaid_amount >= 0 AND repaid_amount <= amount',
                'name' => 'repaid_amount range',
                'query' => "SELECT COUNT(*) as count FROM salary_advances WHERE NOT (repaid_amount >= 0 AND repaid_amount <= amount)",
            ],
            
            // Units
            [
                'table' => 'units',
                'constraint' => 'base_rent > 0',
                'name' => 'base_rent positive',
                'query' => "SELECT COUNT(*) as count FROM units WHERE base_rent <= 0",
            ],
            [
                'table' => 'units',
                'constraint' => 'deposit_amount >= 0',
                'name' => 'deposit_amount non-negative',
                'query' => "SELECT COUNT(*) as count FROM units WHERE deposit_amount < 0",
            ],
            [
                'table' => 'units',
                'constraint' => 'max_occupancy > 0',
                'name' => 'max_occupancy positive',
                'query' => "SELECT COUNT(*) as count FROM units WHERE max_occupancy <= 0",
            ],
            
            // Leases
            [
                'table' => 'leases',
                'constraint' => 'end_date > start_date',
                'name' => 'end_date after start_date',
                'query' => "SELECT COUNT(*) as count FROM leases WHERE end_date <= start_date",
            ],
            [
                'table' => 'leases',
                'constraint' => 'rent_amount > 0',
                'name' => 'rent_amount positive',
                'query' => "SELECT COUNT(*) as count FROM leases WHERE rent_amount <= 0",
            ],
            
            // Master Leases
            [
                'table' => 'master_leases',
                'constraint' => 'end_date > start_date',
                'name' => 'end_date after start_date',
                'query' => "SELECT COUNT(*) as count FROM master_leases WHERE end_date <= start_date",
            ],
            [
                'table' => 'master_leases',
                'constraint' => 'base_rent > 0',
                'name' => 'base_rent positive',
                'query' => "SELECT COUNT(*) as count FROM master_leases WHERE base_rent <= 0",
            ],
            
            // Reviews
            [
                'table' => 'reviews',
                'constraint' => 'overall_rating >= 0.0 AND overall_rating <= 5.0',
                'name' => 'overall_rating range',
                'query' => "SELECT COUNT(*) as count FROM reviews WHERE NOT (overall_rating >= 0.0 AND overall_rating <= 5.0)",
            ],
            
            // Meter Readings
            [
                'table' => 'meter_readings',
                'constraint' => 'value >= 0',
                'name' => 'value non-negative',
                'query' => "SELECT COUNT(*) as count FROM meter_readings WHERE value < 0",
            ],
            
            // Cash Outflows
            [
                'table' => 'cash_outflows',
                'constraint' => 'amount > 0',
                'name' => 'amount positive',
                'query' => "SELECT COUNT(*) as count FROM cash_outflows WHERE amount <= 0",
            ],
            
            // Webhook Logs
            [
                'table' => 'webhook_logs',
                'constraint' => 'amount > 0',
                'name' => 'amount positive',
                'query' => "SELECT COUNT(*) as count FROM webhook_logs WHERE amount <= 0",
            ],
            
            // Payment Cycles
            [
                'table' => 'payment_cycles',
                'constraint' => 'billing_day >= 1 AND billing_day <= 28',
                'name' => 'billing_day range',
                'query' => "SELECT COUNT(*) as count FROM payment_cycles WHERE NOT (billing_day >= 1 AND billing_day <= 28)",
            ],
            
            // Email OTPs
            [
                'table' => 'email_otps',
                'constraint' => 'LENGTH(otp_code) = 6 AND otp_code REGEXP \'^[0-9]{6}$\'',
                'name' => 'otp_code format',
                'query' => "SELECT COUNT(*) as count FROM email_otps WHERE NOT (LENGTH(otp_code) = 6 AND otp_code REGEXP '^[0-9]{6}$')",
            ],
            
            // Payment Tokens
            [
                'table' => 'payment_tokens',
                'constraint' => 'expires_at > created_at',
                'name' => 'expires_at after created_at',
                'query' => "SELECT COUNT(*) as count FROM payment_tokens WHERE expires_at <= created_at",
            ],
            
            // User Profiles
            [
                'table' => 'user_profiles',
                'constraint' => 'dob IS NULL OR dob <= CURRENT_DATE',
                'name' => 'dob not in future',
                'query' => "SELECT COUNT(*) as count FROM user_profiles WHERE dob IS NOT NULL AND dob > CURRENT_DATE",
            ],
            [
                'table' => 'user_profiles',
                'constraint' => 'id_issued_at IS NULL OR id_issued_at <= CURRENT_DATE',
                'name' => 'id_issued_at not in future',
                'query' => "SELECT COUNT(*) as count FROM user_profiles WHERE id_issued_at IS NOT NULL AND id_issued_at > CURRENT_DATE",
            ],
            [
                'table' => 'user_profiles',
                'constraint' => 'account_number IS NULL OR (sepay_bank_id IS NOT NULL AND account_holder_name IS NOT NULL)',
                'name' => 'banking info consistency',
                'query' => "SELECT COUNT(*) as count FROM user_profiles WHERE account_number IS NOT NULL AND (sepay_bank_id IS NULL OR account_holder_name IS NULL)",
            ],
        ];

        $violations = [];
        
        foreach ($checks as $check) {
            try {
                $result = DB::select($check['query']);
                $count = $result[0]->count ?? 0;
                
                if ($count > 0) {
                    $violations[] = [
                        'table' => $check['table'],
                        'constraint' => $check['name'],
                        'count' => $count,
                        'type' => 'CHECK',
                    ];
                    
                    $this->warn("  ⚠️  {$check['table']} ({$check['name']}): {$count} bản ghi vi phạm");
                }
            } catch (\Exception $e) {
                $this->warn("  ⚠️  Không thể kiểm tra {$check['table']}.{$check['name']}: " . $e->getMessage());
            }
        }

        return $violations;
    }

    /**
     * Xuất kết quả vi phạm ra file text
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo tên file với timestamp: data_violations_YYYY-MM-DD_HHmmss.txt
     * 2. Tạo nội dung file:
     *    - Header với thông tin ngày kiểm tra
     *    - Chi tiết NOT NULL violations
     *    - Chi tiết UNIQUE violations (kèm danh sách trùng lặp)
     *    - Chi tiết CHECK violations
     * 3. Ghi file vào storage/logs/
     * 4. Hiển thị đường dẫn file
     * 
     * DỮ LIỆU GHI VÀO:
     * - File: storage/logs/data_violations_YYYY-MM-DD_HHmmss.txt
     * 
     * @param array $violations Mảng các vi phạm với format: ['NOT NULL' => array, 'UNIQUE' => array, 'CHECK' => array]
     */
    private function exportViolations(array $violations): void
    {
        $filename = storage_path('logs/data_violations_' . date('Y-m-d_His') . '.txt');
        
        $content = "═══════════════════════════════════════════════════════\n";
        $content .= "BÁO CÁO VI PHẠM DỮ LIỆU\n";
        $content .= "Ngày kiểm tra: " . date('Y-m-d H:i:s') . "\n";
        $content .= "═══════════════════════════════════════════════════════\n\n";
        
        // NOT NULL violations
        if (!empty($violations['NOT NULL'])) {
            $content .= "1. NOT NULL VIOLATIONS\n";
            $content .= str_repeat('-', 50) . "\n";
            foreach ($violations['NOT NULL'] as $violation) {
                $content .= "Bảng: {$violation['table']}\n";
                $content .= "Cột: {$violation['column']}\n";
                $content .= "Số lượng: {$violation['count']}\n";
                $content .= "\n";
            }
            $content .= "\n";
        }
        
        // UNIQUE violations
        if (!empty($violations['UNIQUE'])) {
            $content .= "2. UNIQUE VIOLATIONS\n";
            $content .= str_repeat('-', 50) . "\n";
            foreach ($violations['UNIQUE'] as $violation) {
                $content .= "Bảng: {$violation['table']}\n";
                $content .= "Cột: {$violation['columns']}\n";
                $content .= "Số lượng: {$violation['count']}\n";
                if (isset($violation['duplicates'])) {
                    $content .= "Chi tiết:\n";
                    foreach ($violation['duplicates'] as $dup) {
                        $content .= "  - " . json_encode($dup) . "\n";
                    }
                }
                $content .= "\n";
            }
            $content .= "\n";
        }
        
        // CHECK violations
        if (!empty($violations['CHECK'])) {
            $content .= "3. CHECK VIOLATIONS\n";
            $content .= str_repeat('-', 50) . "\n";
            foreach ($violations['CHECK'] as $violation) {
                $content .= "Bảng: {$violation['table']}\n";
                $content .= "Constraint: {$violation['constraint']}\n";
                $content .= "Số lượng: {$violation['count']}\n";
                $content .= "\n";
            }
        }
        
        file_put_contents($filename, $content);
        $this->info("📄 Đã xuất báo cáo ra: {$filename}");
    }
}
