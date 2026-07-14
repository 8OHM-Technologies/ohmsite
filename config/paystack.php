<?php

return [
  "public_key"        => env("PAYSTACK_PUBLIC_KEY"),
  "secret_key"        => env("PAYSTACK_SECRET_KEY"),
  "url"               => env("PAYSTACK_URL", 'https://api.paystack.co'),
  "merchant_email"    => env("PAYSTACK_MERCHANT_EMAIL"),

  "route" => [
    "middleware"        => ["paystack_route_disabled", "api"], // For injecting middleware to the package's routes
    "prefix"            => "api", // For injecting middleware to the package's routes
    "hook_middleware"   => ["validate_paystack_hook", "api"]
  ],

  "plans" => [
    "developer-api" => [
        "monthly" => env("PAYSTACK_PLAN_DEV_API_MONTHLY", "PLN_6boiths7ayehqxk"),
        "annually" => env("PAYSTACK_PLAN_DEV_API_ANNUALLY", "PLN_skqc1pit99d1qrw"),
    ],
    "analytics-dashboard" => [
        "monthly" => env("PAYSTACK_PLAN_ANALYTICS_MONTHLY", "PLN_q8f4zhybxlk9efv"),
        "annually" => env("PAYSTACK_PLAN_ANALYTICS_ANNUALLY", "PLN_4f8wx4z1zz68ck2"),
    ],
    "managed-data-pipeline" => [
        "quarterly" => env("PAYSTACK_PLAN_PIPELINE_QUARTERLY", "PLN_nhjogt30eluzplc"),
        "biannually" => env("PAYSTACK_PLAN_PIPELINE_BIANNUALLY", "PLN_rxqrqcutqngi07y"),
        "annually" => env("PAYSTACK_PLAN_PIPELINE_ANNUALLY", "PLN_gcwm81fcaj87woy"),
        "monthly" => env("PAYSTACK_PLAN_PIPELINE_MONTHLY", "PLN_gcwm81fcaj87woy"),
    ],
  ],
];
