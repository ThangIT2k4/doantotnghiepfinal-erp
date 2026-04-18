<?php

namespace App\Services\BusinessRules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Business Rules Validation Service
 * 
 * Cổng kiểm tra độc lập cho tất cả business rules
 * Hoạt động như một middleware validation layer
 */
class BusinessRulesService
{
    protected array $validators = [];

    public function __construct()
    {
        // Đăng ký các validators
        $this->validators = [
            \App\Models\Invoice::class => InvoiceRulesValidator::class,
            \App\Models\Payment::class => PaymentRulesValidator::class,
            \App\Models\Lease::class => LeaseRulesValidator::class,
            \App\Models\DepositRefund::class => DepositRefundRulesValidator::class,
            \App\Models\CompanyInvoice::class => CompanyInvoiceRulesValidator::class,
            \App\Models\BookingDeposit::class => BookingDepositRulesValidator::class,
            \App\Models\PayrollPayslip::class => PayrollRulesValidator::class,
            \App\Models\Viewing::class => ViewingRulesValidator::class,
            \App\Models\Ticket::class => TicketRulesValidator::class,
            \App\Models\TicketLog::class => TicketLogRulesValidator::class,
            // Validators for preventing deletion of used records
            \App\Models\Service::class => ServiceRulesValidator::class,
            \App\Models\PropertyType::class => PropertyTypeRulesValidator::class,
            \App\Models\PaymentCycle::class => PaymentCycleRulesValidator::class,
            \App\Models\LeaseServiceSet::class => LeaseServiceSetRulesValidator::class,
            \App\Models\Property::class => PropertyRulesValidator::class,
            \App\Models\Unit::class => UnitRulesValidator::class,
        ];
    }

    /**
     * Validate model before saving
     * 
     * @param Model $model
     * @param string $action 'creating', 'updating', 'deleting'
     * @throws ValidationException
     */
    public function validate(Model $model, string $action = 'saving'): void
    {
        $modelClass = get_class($model);
        
        if (!isset($this->validators[$modelClass])) {
            // Không có validator cho model này, skip
            return;
        }

        $validatorClass = $this->validators[$modelClass];
        $validator = app($validatorClass);

        try {
            switch ($action) {
                case 'creating':
                    $validator->validateCreating($model);
                    break;
                case 'updating':
                    $validator->validateUpdating($model);
                    break;
                case 'deleting':
                    $validator->validateDeleting($model);
                    break;
                default:
                    $validator->validateSaving($model);
            }
        } catch (ValidationException $e) {
            Log::warning('Business rule validation failed', [
                'model' => $modelClass,
                'action' => $action,
                'model_id' => $model->id ?? null,
                'errors' => $e->errors()
            ]);
            throw $e;
        }
    }

    /**
     * Check if model can be soft deleted
     * 
     * @param Model $model
     * @return bool
     */
    public function canSoftDelete(Model $model): bool
    {
        $modelClass = get_class($model);
        
        if (!isset($this->validators[$modelClass])) {
            return true; // Default allow
        }

        $validatorClass = $this->validators[$modelClass];
        $validator = app($validatorClass);

        if (method_exists($validator, 'canSoftDelete')) {
            return $validator->canSoftDelete($model);
        }

        return true;
    }
}

