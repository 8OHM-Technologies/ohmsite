<?php

return [
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'url' => env('PAYSTACK_URL', 'https://api.paystack.co'),
    'merchant_email' => env('PAYSTACK_MERCHANT_EMAIL'),

    'route' => [
        'middleware' => ['paystack_route_disabled', 'api'],
        'prefix' => 'api',
        'hook_middleware' => ['validate_paystack_hook', 'api'],
    ],

    'plans' => [
        'developer-api' => [
            'monthly' => env('PAYSTACK_PLAN_DEV_API_MONTHLY'),
            'annually' => env('PAYSTACK_PLAN_DEV_API_ANNUALLY'),
        ],
        'pro-analytics' => [
            'monthly' => env('PAYSTACK_PLAN_ANALYTICS_MONTHLY'),
            'annually' => env('PAYSTACK_PLAN_ANALYTICS_ANNUALLY'),
        ],
        'managed-data-pipeline' => [
            'quarterly' => env('PAYSTACK_PLAN_PIPELINE_QUARTERLY'),
            'biannually' => env('PAYSTACK_PLAN_PIPELINE_BIANNUALLY'),
            'annually' => env('PAYSTACK_PLAN_PIPELINE_ANNUALLY'),
            'monthly' => env('PAYSTACK_PLAN_PIPELINE_MONTHLY'),
        ],
    ],
];
