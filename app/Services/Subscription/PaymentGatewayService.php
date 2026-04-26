<?php

namespace App\Services\Subscription;

use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Services\WebhooksPermissionService;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    protected $webhooksPermissionService;

    public function __construct(WebhooksPermissionService $webhooksPermissionService)
    {
        $this->webhooksPermissionService = $webhooksPermissionService;
    }

    /**
     * Create a subscription in the payment gateway.
     */
    public function createSubscription(
        OrganizationSubscription $subscription,
        string $gateway
    ): array {
        // Check if sepay is allowed
        if ($gateway === 'sepay') {
            $organization = $subscription->organization;
            if (!$this->webhooksPermissionService->canUseSepay($organization)) {
                throw new \Exception('Gói dịch vụ của bạn không hỗ trợ phương thức thanh toán SePay. Vui lòng nâng cấp gói để sử dụng tính năng Webhooks.');
            }
        }

        switch ($gateway) {
            case 'vnpay':
                return $this->createVNPaySubscription($subscription);
            case 'momo':
                return $this->createMomoSubscription($subscription);
            case 'sepay':
                return $this->createSepaySubscription($subscription);
            case 'manual':
                return ['success' => true, 'message' => 'Manual payment selected'];
            default:
                throw new \Exception("Unsupported payment gateway: {$gateway}");
        }
    }

    /**
     * Cancel a subscription in the payment gateway.
     */
    public function cancelGatewaySubscription(OrganizationSubscription $subscription): bool
    {
        if (!$subscription->payment_gateway || $subscription->payment_gateway === 'manual') {
            return true;
        }

        try {
            switch ($subscription->payment_gateway) {
                case 'vnpay':
                    return $this->cancelVNPaySubscription($subscription);
                case 'momo':
                    return $this->cancelMomoSubscription($subscription);
                case 'sepay':
                    return $this->cancelSepaySubscription($subscription);
                default:
                    Log::warning("Unknown payment gateway: {$subscription->payment_gateway}");
                    return true;
            }
        } catch (\Exception $e) {
            Log::error('Error canceling gateway subscription: ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
                'gateway' => $subscription->payment_gateway,
            ]);
            return false;
        }
    }

    /**
     * Handle webhook from payment gateway.
     */
    public function handleWebhook(string $gateway, array $payload): array
    {
        Log::info("Received {$gateway} webhook", ['payload' => $payload]);

        try {
            switch ($gateway) {
                case 'vnpay':
                    return $this->handleVNPayWebhook($payload);
                case 'momo':
                    return $this->handleMomoWebhook($payload);
                case 'sepay':
                    return $this->handleSepayWebhook($payload);
                default:
                    throw new \Exception("Unsupported payment gateway: {$gateway}");
            }
        } catch (\Exception $e) {
            Log::error('Error handling webhook: ' . $e->getMessage(), [
                'gateway' => $gateway,
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Create payment URL for an invoice.
     */
    public function createPaymentUrl(
        SubscriptionInvoice $invoice,
        string $gateway,
        string $returnUrl,
        string $cancelUrl
    ): string {
        // Check if sepay is allowed
        if ($gateway === 'sepay') {
            $organization = $invoice->subscription->organization ?? null;
            if (!$this->webhooksPermissionService->canUseSepay($organization)) {
                throw new \Exception('Gói dịch vụ của bạn không hỗ trợ phương thức thanh toán SePay. Vui lòng nâng cấp gói để sử dụng tính năng Webhooks.');
            }
        }

        switch ($gateway) {
            case 'vnpay':
                return $this->createVNPayPaymentUrl($invoice, $returnUrl, $cancelUrl);
            case 'momo':
                return $this->createMomoPaymentUrl($invoice, $returnUrl, $cancelUrl);
            case 'sepay':
                return $this->createSepayPaymentUrl($invoice, $returnUrl, $cancelUrl);
            default:
                throw new \Exception("Unsupported payment gateway: {$gateway}");
        }
    }

    /**
     * VNPay subscription creation (placeholder).
     */
    protected function createVNPaySubscription(OrganizationSubscription $subscription): array
    {
        // TODO: Implement VNPay subscription creation
        // This would integrate with VNPay's recurring payment API
        
        Log::info('VNPay subscription creation requested', [
            'subscription_id' => $subscription->id
        ]);

        return [
            'success' => true,
            'gateway_subscription_id' => 'vnpay_' . uniqid(),
            'message' => 'VNPay subscription created (placeholder)',
        ];
    }

    /**
     * Momo subscription creation (placeholder).
     */
    protected function createMomoSubscription(OrganizationSubscription $subscription): array
    {
        // TODO: Implement Momo subscription creation
        
        Log::info('Momo subscription creation requested', [
            'subscription_id' => $subscription->id
        ]);

        return [
            'success' => true,
            'gateway_subscription_id' => 'momo_' . uniqid(),
            'message' => 'Momo subscription created (placeholder)',
        ];
    }

    /**
     * Sepay subscription creation (placeholder).
     */
    protected function createSepaySubscription(OrganizationSubscription $subscription): array
    {
        // TODO: Implement Sepay subscription creation
        
        Log::info('Sepay subscription creation requested', [
            'subscription_id' => $subscription->id
        ]);

        return [
            'success' => true,
            'gateway_subscription_id' => 'sepay_' . uniqid(),
            'message' => 'Sepay subscription created (placeholder)',
        ];
    }

    /**
     * Cancel VNPay subscription (placeholder).
     */
    protected function cancelVNPaySubscription(OrganizationSubscription $subscription): bool
    {
        // TODO: Implement VNPay subscription cancellation
        
        Log::info('VNPay subscription cancellation requested', [
            'subscription_id' => $subscription->id
        ]);

        return true;
    }

    /**
     * Cancel Momo subscription (placeholder).
     */
    protected function cancelMomoSubscription(OrganizationSubscription $subscription): bool
    {
        // TODO: Implement Momo subscription cancellation
        
        Log::info('Momo subscription cancellation requested', [
            'subscription_id' => $subscription->id
        ]);

        return true;
    }

    /**
     * Cancel Sepay subscription (placeholder).
     */
    protected function cancelSepaySubscription(OrganizationSubscription $subscription): bool
    {
        // TODO: Implement Sepay subscription cancellation
        
        Log::info('Sepay subscription cancellation requested', [
            'subscription_id' => $subscription->id
        ]);

        return true;
    }

    /**
     * Handle VNPay webhook (placeholder).
     */
    protected function handleVNPayWebhook(array $payload): array
    {
        // TODO: Implement VNPay webhook handling
        // Verify signature, update invoice/subscription status
        
        return [
            'success' => true,
            'message' => 'VNPay webhook handled (placeholder)',
        ];
    }

    /**
     * Handle Momo webhook (placeholder).
     */
    protected function handleMomoWebhook(array $payload): array
    {
        // TODO: Implement Momo webhook handling
        
        return [
            'success' => true,
            'message' => 'Momo webhook handled (placeholder)',
        ];
    }

    /**
     * Handle Sepay webhook (placeholder).
     */
    protected function handleSepayWebhook(array $payload): array
    {
        // TODO: Implement Sepay webhook handling
        
        return [
            'success' => true,
            'message' => 'Sepay webhook handled (placeholder)',
        ];
    }

    /**
     * Create VNPay payment URL (placeholder).
     */
    protected function createVNPayPaymentUrl(
        SubscriptionInvoice $invoice,
        string $returnUrl,
        string $cancelUrl
    ): string {
        // TODO: Implement VNPay payment URL generation
        
        return 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?...';
    }

    /**
     * Create Momo payment URL (placeholder).
     */
    protected function createMomoPaymentUrl(
        SubscriptionInvoice $invoice,
        string $returnUrl,
        string $cancelUrl
    ): string {
        // TODO: Implement Momo payment URL generation
        
        return 'https://test-payment.momo.vn/...';
    }

    /**
     * Create Sepay payment URL (placeholder).
     */
    protected function createSepayPaymentUrl(
        SubscriptionInvoice $invoice,
        string $returnUrl,
        string $cancelUrl
    ): string {
        // TODO: Implement Sepay payment URL generation
        
        return 'https://sepay.vn/payment/...';
    }
}

