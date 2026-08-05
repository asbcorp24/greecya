<?php

return [
    'provider' => env('PAYMENT_PROVIDER', 'manual'),
    'return_url' => env('PAYMENT_RETURN_URL', env('APP_URL').'/orders/success'),
    'yookassa' => [
        'shop_id' => env('YOOKASSA_SHOP_ID'),
        'secret_key' => env('YOOKASSA_SECRET_KEY'),
    ],
];
