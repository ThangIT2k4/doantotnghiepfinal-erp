<?php

namespace App\Services;

use App\Models\CommissionEvent;
use App\Models\CommissionPolicy;
use App\Models\Lease;
use App\Models\BookingDeposit;
use App\Models\Viewing;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CommissionEventService
{
    /**
     * Tạo sự kiện hoa hồng cho hợp đồng thuê
     */
    public function createCommissionEventsForLease(Lease $lease)
    {
        try {
            DB::beginTransaction();

            $organization = $lease->organization;
            if (!$organization) {
                Log::warning('No organization found for lease, skipping commission events', [
                    'lease_id' => $lease->id
                ]);
                DB::rollBack();
                return false;
            }

            $createdEvents = [];

            // 1. Tạo sự kiện hoa hồng ký hợp đồng (lease_signed)
            $leaseEvents = $this->createLeaseSignedEvents($lease, $organization);
            $createdEvents = array_merge($createdEvents, $leaseEvents);

            // 2. Tạo sự kiện hoa hồng tiền cọc (deposit_paid) nếu có tiền cọc
            if ($lease->deposit_amount > 0) {
                $depositEvents = $this->createDepositPaidEvents($lease, $organization);
                $createdEvents = array_merge($createdEvents, $depositEvents);
            }

            DB::commit();

            Log::info('Commission events created successfully for lease', [
                'lease_id' => $lease->id,
                'organization_id' => $organization->id,
                'created_events_count' => count($createdEvents),
                'events' => $createdEvents
            ]);

            return $createdEvents;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating commission events for lease: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Tạo sự kiện hoa hồng ký hợp đồng
     */
    private function createLeaseSignedEvents(Lease $lease, $organization)
    {
        $events = [];

        // Lấy các chính sách hoa hồng cho trigger 'lease_signed'
        $leasePolicies = CommissionPolicy::where('organization_id', $organization->id)
            ->where('trigger_event', 'lease_signed')
            ->where('active', true)
            ->get();

        Log::info('Found ' . $leasePolicies->count() . ' lease_signed commission policies for organization ' . $organization->id);

        foreach ($leasePolicies as $policy) {
            $baseAmount = $lease->rent_amount;
            $commissionTotal = $this->calculateCommission($policy, $baseAmount);

            if ($commissionTotal > 0) {
                $event = CommissionEvent::create([
                    'policy_id' => $policy->id,
                    'organization_id' => $organization->id,
                    'trigger_event' => 'lease_signed',
                    'ref_type' => 'lease',
                    'ref_id' => $lease->id,
                    'lease_id' => $lease->id,
                    'unit_id' => $lease->unit_id,
                    'agent_id' => $lease->agent_id,
                    'occurred_at' => $lease->signed_at ?? now(),
                    'amount_base' => $baseAmount,
                    'commission_total' => $commissionTotal,
                    'status' => 'pending'
                ]);

                $events[] = [
                    'id' => $event->id,
                    'type' => 'lease_signed',
                    'amount' => $commissionTotal,
                    'policy_id' => $policy->id
                ];

                Log::info('Created lease_signed commission event', [
                    'event_id' => $event->id,
                    'lease_id' => $lease->id,
                    'policy_id' => $policy->id,
                    'amount' => $commissionTotal
                ]);
            }
        }

        return $events;
    }

    /**
     * Tạo sự kiện hoa hồng tiền cọc
     */
    private function createDepositPaidEvents(Lease $lease, $organization)
    {
        $events = [];

        // Lấy các chính sách hoa hồng cho trigger 'deposit_paid'
        $depositPolicies = CommissionPolicy::where('organization_id', $organization->id)
            ->where('trigger_event', 'deposit_paid')
            ->where('active', true)
            ->get();

        Log::info('Found ' . $depositPolicies->count() . ' deposit_paid commission policies for organization ' . $organization->id);

        foreach ($depositPolicies as $policy) {
            $baseAmount = $lease->deposit_amount;
            $commissionTotal = $this->calculateCommission($policy, $baseAmount);

            if ($commissionTotal > 0) {
                $event = CommissionEvent::create([
                    'policy_id' => $policy->id,
                    'organization_id' => $organization->id,
                    'trigger_event' => 'deposit_paid',
                    'ref_type' => 'lease',
                    'ref_id' => $lease->id,
                    'lease_id' => $lease->id,
                    'unit_id' => $lease->unit_id,
                    'agent_id' => $lease->agent_id,
                    'occurred_at' => $lease->signed_at ?? now(),
                    'amount_base' => $baseAmount,
                    'commission_total' => $commissionTotal,
                    'status' => 'pending'
                ]);

                $events[] = [
                    'id' => $event->id,
                    'type' => 'deposit_paid',
                    'amount' => $commissionTotal,
                    'policy_id' => $policy->id
                ];

                Log::info('Created deposit_paid commission event', [
                    'event_id' => $event->id,
                    'lease_id' => $lease->id,
                    'policy_id' => $policy->id,
                    'amount' => $commissionTotal
                ]);
            }
        }

        return $events;
    }

    /**
     * Tạo sự kiện hoa hồng cho viewing khi được hoàn thành
     */
    public function createCommissionEventsForViewing(Viewing $viewing)
    {
        try {
            DB::beginTransaction();

            $organization = $viewing->organization;
            if (!$organization) {
                Log::warning('No organization found for viewing, skipping commission events', [
                    'viewing_id' => $viewing->id
                ]);
                DB::rollBack();
                return false;
            }

            // Chỉ tạo sự kiện hoa hồng khi viewing được hoàn thành
            if ($viewing->status !== 'done') {
                Log::info('Viewing not completed yet, skipping commission events', [
                    'viewing_id' => $viewing->id,
                    'status' => $viewing->status
                ]);
                DB::rollBack();
                return false;
            }

            $events = [];

            // Lấy các chính sách hoa hồng cho trigger 'viewing_done'
            $viewingPolicies = CommissionPolicy::where('organization_id', $organization->id)
                ->where('trigger_event', 'viewing_done')
                ->where('active', true)
                ->get();

            Log::info('Found ' . $viewingPolicies->count() . ' viewing_done commission policies for viewing organization ' . $organization->id);

            foreach ($viewingPolicies as $policy) {
                // Đối với viewing, base amount thường là flat amount hoặc có thể dựa trên giá thuê của property
                $baseAmount = $this->getViewingBaseAmount($viewing, $policy);
                $commissionTotal = $this->calculateCommission($policy, $baseAmount);

                if ($commissionTotal > 0) {
                    $event = CommissionEvent::create([
                        'policy_id' => $policy->id,
                        'organization_id' => $organization->id,
                        'trigger_event' => 'viewing_done',
                        'ref_type' => 'viewing',
                        'ref_id' => $viewing->id,
                        'lease_id' => null, // Viewing chưa có lease
                        'unit_id' => $viewing->unit_id,
                        'agent_id' => $viewing->agent_id,
                        'occurred_at' => $viewing->updated_at ?? now(),
                        'amount_base' => $baseAmount,
                        'commission_total' => $commissionTotal,
                        'status' => 'pending'
                    ]);

                    $events[] = [
                        'id' => $event->id,
                        'type' => 'viewing_done',
                        'amount' => $commissionTotal,
                        'policy_id' => $policy->id
                    ];

                    Log::info('Created viewing_done commission event', [
                        'event_id' => $event->id,
                        'viewing_id' => $viewing->id,
                        'policy_id' => $policy->id,
                        'amount' => $commissionTotal
                    ]);
                }
            }

            DB::commit();

            Log::info('Commission events created successfully for viewing', [
                'viewing_id' => $viewing->id,
                'organization_id' => $organization->id,
                'created_events_count' => count($events),
                'events' => $events
            ]);

            return $events;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating commission events for viewing: ' . $e->getMessage(), [
                'viewing_id' => $viewing->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Tạo sự kiện hoa hồng cho booking deposit khi được thanh toán
     */
    public function createCommissionEventsForBookingDeposit(BookingDeposit $bookingDeposit)
    {
        try {
            DB::beginTransaction();

            $organization = $bookingDeposit->organization;
            if (!$organization) {
                Log::warning('No organization found for booking deposit, skipping commission events', [
                    'booking_deposit_id' => $bookingDeposit->id
                ]);
                DB::rollBack();
                return false;
            }

            // Chỉ tạo sự kiện hoa hồng khi deposit được thanh toán
            if ($bookingDeposit->payment_status !== 'paid') {
                Log::info('Booking deposit not paid yet, skipping commission events', [
                    'booking_deposit_id' => $bookingDeposit->id,
                    'payment_status' => $bookingDeposit->payment_status
                ]);
                DB::rollBack();
                return false;
            }

            $events = [];

            // Lấy các chính sách hoa hồng cho trigger 'deposit_paid'
            $depositPolicies = CommissionPolicy::where('organization_id', $organization->id)
                ->where('trigger_event', 'deposit_paid')
                ->where('active', true)
                ->get();

            Log::info('Found ' . $depositPolicies->count() . ' deposit_paid commission policies for booking deposit organization ' . $organization->id);

            foreach ($depositPolicies as $policy) {
                $baseAmount = $bookingDeposit->amount;
                $commissionTotal = $this->calculateCommission($policy, $baseAmount);

                if ($commissionTotal > 0) {
                    $event = CommissionEvent::create([
                        'policy_id' => $policy->id,
                        'organization_id' => $organization->id,
                        'trigger_event' => 'deposit_paid',
                        'ref_type' => 'booking_deposit',
                        'ref_id' => $bookingDeposit->id,
                        'lease_id' => $bookingDeposit->lead ? $bookingDeposit->lead->lease_id : null,
                        'unit_id' => $bookingDeposit->unit_id,
                        'agent_id' => $bookingDeposit->agent_id,
                        'occurred_at' => $bookingDeposit->paid_at ?? now(),
                        'amount_base' => $baseAmount,
                        'commission_total' => $commissionTotal,
                        'status' => 'pending'
                    ]);

                    $events[] = [
                        'id' => $event->id,
                        'type' => 'deposit_paid',
                        'amount' => $commissionTotal,
                        'policy_id' => $policy->id
                    ];

                    Log::info('Created deposit_paid commission event for booking deposit', [
                        'event_id' => $event->id,
                        'booking_deposit_id' => $bookingDeposit->id,
                        'policy_id' => $policy->id,
                        'amount' => $commissionTotal
                    ]);
                }
            }

            DB::commit();

            Log::info('Commission events created successfully for booking deposit', [
                'booking_deposit_id' => $bookingDeposit->id,
                'organization_id' => $organization->id,
                'created_events_count' => count($events),
                'events' => $events
            ]);

            return $events;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating commission events for booking deposit: ' . $e->getMessage(), [
                'booking_deposit_id' => $bookingDeposit->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Tính toán hoa hồng dựa trên chính sách
     */
    private function calculateCommission($policy, $baseAmount)
    {
        $commission = 0;

        switch ($policy->calc_type) {
            case 'percent':
                $commission = $baseAmount * ($policy->percent_value / 100);
                break;
            case 'flat':
                $commission = $policy->flat_amount;
                break;
            case 'tiered':
                // Implement tiered calculation logic here if needed
                $commission = 0;
                break;
            default:
                $commission = 0;
        }

        // Áp dụng min và cap
        if ($policy->min_amount && $commission < $policy->min_amount) {
            $commission = $policy->min_amount;
        }
        if ($policy->cap_amount && $commission > $policy->cap_amount) {
            $commission = $policy->cap_amount;
        }

        return $commission;
    }

    /**
     * Cập nhật sự kiện hoa hồng khi hợp đồng thay đổi
     */
    public function updateCommissionEventsForLease(Lease $lease)
    {
        try {
            // Tìm các sự kiện hoa hồng liên quan đến hợp đồng này
            $events = CommissionEvent::where('lease_id', $lease->id)
                ->where('status', 'pending')
                ->get();

            foreach ($events as $event) {
                $policy = $event->policy;
                if (!$policy) {
                    continue;
                }

                // Tính lại hoa hồng dựa trên trigger event
                $baseAmount = 0;
                if ($event->trigger_event === 'lease_signed') {
                    $baseAmount = $lease->rent_amount;
                } elseif ($event->trigger_event === 'deposit_paid') {
                    $baseAmount = $lease->deposit_amount;
                }

                if ($baseAmount > 0) {
                    $newCommission = $this->calculateCommission($policy, $baseAmount);
                    
                    $event->update([
                        'amount_base' => $baseAmount,
                        'commission_total' => $newCommission,
                    ]);

                    Log::info('Updated commission event', [
                        'event_id' => $event->id,
                        'old_amount' => $event->getOriginal('commission_total'),
                        'new_amount' => $newCommission
                    ]);
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error updating commission events for lease: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Xóa sự kiện hoa hồng khi hợp đồng bị xóa
     */
    public function deleteCommissionEventsForLease(Lease $lease)
    {
        try {
            $events = CommissionEvent::where('lease_id', $lease->id)
                ->where('status', 'pending')
                ->get();

            foreach ($events as $event) {
                $event->delete();
            }

            Log::info('Deleted commission events for lease', [
                'lease_id' => $lease->id,
                'deleted_count' => $events->count()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error deleting commission events for lease: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Tạo sự kiện hoa hồng cho invoice khi được thanh toán
     */
    public function createInvoicePaidEvents(Invoice $invoice)
    {
        try {
            DB::beginTransaction();

            // Chỉ xử lý invoice có lease và đã thanh toán đủ
            if (!$invoice->lease_id || $invoice->status !== 'paid') {
                Log::info('Invoice does not have lease or not fully paid, skipping commission events', [
                    'invoice_id' => $invoice->id,
                    'lease_id' => $invoice->lease_id,
                    'status' => $invoice->status
                ]);
                DB::rollBack();
                return false;
            }

            $lease = $invoice->lease;
            $organization = $invoice->organization;

            if (!$organization) {
                Log::warning('No organization found for invoice, skipping commission events', [
                    'invoice_id' => $invoice->id,
                    'lease_id' => $lease->id
                ]);
                DB::rollBack();
                return false;
            }

            // Lấy các chính sách hoa hồng cho trigger 'invoice_paid'
            $invoicePolicies = CommissionPolicy::where('organization_id', $organization->id)
                ->where('trigger_event', 'invoice_paid')
                ->where('active', true)
                ->get();

            Log::info('Found ' . $invoicePolicies->count() . ' invoice_paid commission policies for organization ' . $organization->id);

            $events = [];

            // Ghi chú: apply_limit_months sẽ được kiểm tra khi tính trong payroll, 
            // không phải khi tạo commission event
            // Commission events được tạo với status 'pending' khi invoice được thanh toán
            // Khi tính payroll, chỉ tính các events có status 'paid' và trong giới hạn tháng
            
            foreach ($invoicePolicies as $policy) {

                // Kiểm tra xem đã có commission event cho invoice này chưa (tránh trùng lặp)
                $existingEvent = CommissionEvent::where('policy_id', $policy->id)
                    ->where('lease_id', $lease->id)
                    ->where('ref_type', 'invoice')
                    ->where('ref_id', $invoice->id)
                    ->where('trigger_event', 'invoice_paid')
                    ->first();

                if ($existingEvent) {
                    Log::info('Commission event already exists for this invoice and policy', [
                        'invoice_id' => $invoice->id,
                        'policy_id' => $policy->id,
                        'existing_event_id' => $existingEvent->id
                    ]);
                    continue;
                }

                // Tính toán base amount: thường là tổng tiền thuê trong invoice
                // (có thể là rent + services)
                $baseAmount = $invoice->subtotal ?? $invoice->total_amount;

                // Nếu policy có filters_json, kiểm tra filters
                // (có thể filter theo property, unit type, etc.)
                $shouldApply = $this->checkPolicyFilters($policy, $lease, $invoice);
                if (!$shouldApply) {
                    Log::info('Policy filters not matched, skipping commission event', [
                        'invoice_id' => $invoice->id,
                        'policy_id' => $policy->id
                    ]);
                    continue;
                }

                $commissionTotal = $this->calculateCommission($policy, $baseAmount);

                if ($commissionTotal > 0) {
                    $event = CommissionEvent::create([
                        'policy_id' => $policy->id,
                        'organization_id' => $organization->id,
                        'trigger_event' => 'invoice_paid',
                        'ref_type' => 'invoice',
                        'ref_id' => $invoice->id,
                        'lease_id' => $lease->id,
                        'unit_id' => $lease->unit_id,
                        'agent_id' => $lease->agent_id,
                        'occurred_at' => $invoice->updated_at ?? now(),
                        'amount_base' => $baseAmount,
                        'commission_total' => $commissionTotal,
                        'status' => 'pending'
                    ]);

                    $events[] = [
                        'id' => $event->id,
                        'type' => 'invoice_paid',
                        'amount' => $commissionTotal,
                        'policy_id' => $policy->id
                    ];

                    Log::info('Created invoice_paid commission event', [
                        'event_id' => $event->id,
                        'invoice_id' => $invoice->id,
                        'lease_id' => $lease->id,
                        'policy_id' => $policy->id,
                        'amount' => $commissionTotal,
                        'apply_limit_months' => $policy->apply_limit_months,
                        'note' => 'apply_limit_months will be checked when calculating payroll'
                    ]);
                }
            }

            DB::commit();

            Log::info('Commission events created successfully for invoice', [
                'invoice_id' => $invoice->id,
                'lease_id' => $lease->id,
                'organization_id' => $organization->id,
                'created_events_count' => count($events),
                'events' => $events
            ]);

            return $events;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating commission events for invoice: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Kiểm tra policy filters có match với lease và invoice không
     */
    private function checkPolicyFilters($policy, Lease $lease, Invoice $invoice)
    {
        // Nếu không có filters, áp dụng cho tất cả
        if (!$policy->filters_json || empty($policy->filters_json)) {
            return true;
        }

        $filters = $policy->filters_json;

        // Kiểm tra property_id filter
        if (isset($filters['property_id']) && !empty($filters['property_id'])) {
            $propertyId = $lease->unit ? $lease->unit->property_id : null;
            if ($propertyId && !in_array($propertyId, (array)$filters['property_id'])) {
                return false;
            }
        }

        // Kiểm tra unit_type filter
        if (isset($filters['unit_type']) && !empty($filters['unit_type'])) {
            $unitType = $lease->unit ? $lease->unit->unit_type : null;
            if ($unitType && !in_array($unitType, (array)$filters['unit_type'])) {
                return false;
            }
        }

        // Có thể thêm các filters khác ở đây
        // Ví dụ: agent_id, min_amount, max_amount, etc.

        return true;
    }

    /**
     * Tính tổng commission cho agent trong khoảng thời gian, có kiểm tra apply_limit_months
     * Dùng cho payroll calculation
     * Chỉ tính các commission events đã approved (chưa paid) có occurred_at trong tháng payroll
     */
    public function calculateCommissionForPayroll($agentId, $periodStart, $periodEnd, $organizationId = null)
    {
        // Chỉ lấy các commission events đã approved (chưa paid) có occurred_at trong tháng payroll
        $query = CommissionEvent::where('agent_id', $agentId)
            ->where('status', 'approved') // Chỉ tính approved, không tính paid (paid đã được tính rồi)
            ->whereBetween('occurred_at', [$periodStart, $periodEnd]);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $events = $query->get();
        $totalCommission = 0;

        foreach ($events as $event) {
            $policy = $event->policy;
            
            // Nếu không có policy hoặc không phải invoice_paid, tính bình thường
            if (!$policy || $event->trigger_event !== 'invoice_paid') {
                $totalCommission += $event->commission_total;
                continue;
            }

            // Kiểm tra apply_limit_months cho invoice_paid events
            if ($policy->apply_limit_months !== null && $event->lease_id) {
                // Đếm số invoice đã thanh toán của lease tính đến thời điểm invoice này
                $invoice = Invoice::find($event->ref_id);
                if ($invoice) {
                    // Đếm số invoice đã thanh toán của lease này (theo thứ tự thanh toán)
                    // Chỉ tính các invoice đã thanh toán TRƯỚC hoặc CÙNG LÚC với invoice này
                    $paidInvoicesCount = Invoice::where('lease_id', $event->lease_id)
                        ->where('status', 'paid')
                        ->where('invoice_type', 'tenant')
                        ->whereNull('deleted_at')
                        ->where('updated_at', '<=', $invoice->updated_at) // Tính đến thời điểm thanh toán
                        ->count();

                    // Chỉ tính commission nếu số invoice <= apply_limit_months
                    if ($paidInvoicesCount <= $policy->apply_limit_months) {
                        $totalCommission += $event->commission_total;
                        Log::info('Commission included in payroll (within apply_limit_months)', [
                            'event_id' => $event->id,
                            'lease_id' => $event->lease_id,
                            'invoice_id' => $event->ref_id,
                            'paid_invoices_count' => $paidInvoicesCount,
                            'apply_limit_months' => $policy->apply_limit_months,
                            'commission' => $event->commission_total
                        ]);
                    } else {
                        Log::info('Commission excluded from payroll (exceeded apply_limit_months)', [
                            'event_id' => $event->id,
                            'lease_id' => $event->lease_id,
                            'invoice_id' => $event->ref_id,
                            'paid_invoices_count' => $paidInvoicesCount,
                            'apply_limit_months' => $policy->apply_limit_months,
                            'commission' => $event->commission_total
                        ]);
                    }
                } else {
                    // Nếu không tìm thấy invoice, tính bình thường (fallback)
                    $totalCommission += $event->commission_total;
                }
            } else {
                // Không có apply_limit_months hoặc không phải invoice_paid, tính bình thường
                $totalCommission += $event->commission_total;
            }
        }

        return $totalCommission;
    }

    /**
     * Lấy danh sách commission events đã approved có occurred_at trong tháng payroll
     * Dùng để cập nhật status thành 'paid' sau khi tạo payslip
     */
    public function getCommissionEventsForPayroll($agentId, $periodStart, $periodEnd, $organizationId = null)
    {
        // Chỉ lấy các commission events đã approved (chưa paid) có occurred_at trong tháng payroll
        $query = CommissionEvent::where('agent_id', $agentId)
            ->where('status', 'approved') // Chỉ lấy approved, không lấy paid (paid đã được tính rồi)
            ->whereBetween('occurred_at', [$periodStart, $periodEnd]);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $events = $query->get();
        $validEvents = [];

        foreach ($events as $event) {
            $policy = $event->policy;
            
            // Nếu không có policy hoặc không phải invoice_paid, tính bình thường
            if (!$policy || $event->trigger_event !== 'invoice_paid') {
                $validEvents[] = $event;
                continue;
            }

            // Kiểm tra apply_limit_months cho invoice_paid events
            if ($policy->apply_limit_months !== null && $event->lease_id) {
                $invoice = Invoice::find($event->ref_id);
                if ($invoice) {
                    $paidInvoicesCount = Invoice::where('lease_id', $event->lease_id)
                        ->where('status', 'paid')
                        ->where('invoice_type', 'tenant')
                        ->whereNull('deleted_at')
                        ->where('updated_at', '<=', $invoice->updated_at)
                        ->count();

                    // Chỉ thêm vào danh sách nếu số invoice <= apply_limit_months
                    if ($paidInvoicesCount <= $policy->apply_limit_months) {
                        $validEvents[] = $event;
                    }
                } else {
                    // Nếu không tìm thấy invoice, thêm vào danh sách (fallback)
                    $validEvents[] = $event;
                }
            } else {
                // Không có apply_limit_months hoặc không phải invoice_paid, thêm vào danh sách
                $validEvents[] = $event;
            }
        }

        return $validEvents;
    }

    /**
     * Lấy base amount cho viewing commission
     */
    private function getViewingBaseAmount(Viewing $viewing, $policy)
    {
        // Đối với viewing, base amount có thể là:
        // 1. Flat amount từ policy (nếu calc_type = 'flat')
        // 2. Giá thuê của property (nếu calc_type = 'percent')
        // 3. Một giá trị cố định
        
        if ($policy->calc_type === 'flat') {
            return $policy->flat_amount ?? 0;
        }
        
        // Nếu là percent, có thể dựa trên giá thuê của property
        if ($viewing->property) {
            // Ưu tiên sử dụng property_id trực tiếp
            return $viewing->property->price ?? 1000000; // Default 1M VND
        } elseif ($viewing->unit && $viewing->unit->property) {
            // Fallback sử dụng property từ unit
            return $viewing->unit->property->price ?? 1000000; // Default 1M VND
        }
        
        // Default base amount
        return 1000000; // 1M VND
    }
}
