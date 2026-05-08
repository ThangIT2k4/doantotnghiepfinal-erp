<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Trial Days
    |--------------------------------------------------------------------------
    |
    | The default number of trial days for new subscriptions if not specified
    | in the plan.
    |
    */
    'default_trial_days' => env('SUBSCRIPTION_DEFAULT_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default currency for subscription plans and invoices.
    |
    */
    'default_currency' => env('SUBSCRIPTION_CURRENCY', 'VND'),
    
    'currencies' => [
        'VND' => 'Việt Nam Đồng',
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Available payment gateways for subscription payments.
    |
    */
    'payment_gateways' => [
        'manual' => [
            'name' => 'Thanh toán thủ công',
            'enabled' => true,
        ],
        'vnpay' => [
            'name' => 'VNPay',
            'enabled' => env('VNPAY_ENABLED', false),
            'merchant_id' => env('VNPAY_MERCHANT_ID'),
            'secret_key' => env('VNPAY_SECRET_KEY'),
        ],
        'momo' => [
            'name' => 'MoMo',
            'enabled' => env('MOMO_ENABLED', false),
            'partner_code' => env('MOMO_PARTNER_CODE'),
            'access_key' => env('MOMO_ACCESS_KEY'),
            'secret_key' => env('MOMO_SECRET_KEY'),
        ],
        'sepay' => [
            'name' => 'SePay',
            'enabled' => env('SEPAY_ENABLED', false),
            'api_key' => env('SEPAY_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Features
    |--------------------------------------------------------------------------
    |
    | List of available features that can be assigned to subscription plans.
    | Each feature has a key, name, type (limit/boolean/json), and default value.
    |
    */
    'available_features' => [
        // Resource Limits
        [
            'key' => 'max_properties',
            'name' => 'Số lượng bất động sản tối đa',
            'type' => 'limit',
            'default' => 10,
            'description' => 'Số lượng bất động sản tối đa có thể tạo',
        ],
        [
            'key' => 'max_units',
            'name' => 'Số lượng đơn vị tối đa',
            'type' => 'limit',
            'default' => 50,
            'description' => 'Số lượng đơn vị/phòng tối đa có thể tạo',
        ],
        [
            'key' => 'max_users',
            'name' => 'Số lượng người dùng tối đa',
            'type' => 'limit',
            'default' => 5,
            'description' => 'Số lượng người dùng tối đa trong tổ chức',
        ],
        [
            'key' => 'max_leases',
            'name' => 'Số lượng hợp đồng thuê tối đa',
            'type' => 'limit',
            'default' => 50,
            'description' => 'Số lượng hợp đồng thuê đồng thời tối đa',
        ],

        // Boolean Features
        [
            'key' => 'enable_reports',
            'name' => 'Báo cáo nâng cao',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Truy cập báo cáo và phân tích nâng cao',
        ],
        [
            'key' => 'enable_webhooks',
            'name' => 'Webhooks',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Nhận thông báo webhook cho các sự kiện',
        ],
        [
            'key' => 'enable_advanced_permissions',
            'name' => 'Phân quyền nâng cao',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Phân quyền chi tiết theo chức năng',
        ],
        [
            'key' => 'enable_priority_support',
            'name' => 'Hỗ trợ ưu tiên',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Nhận hỗ trợ kỹ thuật ưu tiên',
        ],
        [
            'key' => 'enable_data_export',
            'name' => 'Xuất dữ liệu',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Xuất dữ liệu ra Excel/CSV',
        ],
        [
            'key' => 'enable_chat',
            'name' => 'Chat với AI',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Trợ lý AI hỗ trợ hỏi đáp về hệ thống',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Statuses
    |--------------------------------------------------------------------------
    |
    | Available subscription statuses.
    |
    */
    'statuses' => [
        'trial' => [
            'label' => 'Dùng thử',
            'color' => 'info',
        ],
        'active' => [
            'label' => 'Hoạt động',
            'color' => 'success',
        ],
        'expired' => [
            'label' => 'Hết hạn',
            'color' => 'danger',
        ],
        'cancelled' => [
            'label' => 'Đã hủy',
            'color' => 'secondary',
        ],
        'suspended' => [
            'label' => 'Tạm dừng',
            'color' => 'warning',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Statuses
    |--------------------------------------------------------------------------
    |
    | Available invoice statuses.
    |
    */
    'invoice_statuses' => [
        'pending' => [
            'label' => 'Chờ thanh toán',
            'color' => 'warning',
        ],
        'paid' => [
            'label' => 'Đã thanh toán',
            'color' => 'success',
        ],
        'failed' => [
            'label' => 'Thất bại',
            'color' => 'danger',
        ],
        'refunded' => [
            'label' => 'Đã hoàn tiền',
            'color' => 'info',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days after subscription expires before completely blocking access.
    |
    */
    'grace_period_days' => env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Auto-Renew Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatic subscription renewal.
    |
    */
    'auto_renew' => [
        'enabled' => env('SUBSCRIPTION_AUTO_RENEW_ENABLED', true),
        'reminder_days' => [7, 3, 1], // Send reminders N days before expiration
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | Settings for invoice generation.
    |
    */
    'invoice' => [
        'prefix' => 'SUB',
        'due_days' => 7, // Days until invoice is due
    ],
];

