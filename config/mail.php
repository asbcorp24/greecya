<?php

return [
    'default' => env('MAIL_MAILER', 'log'),
    'mailers' => [
        'smtp' => ['transport' => 'smtp', 'host' => env('MAIL_HOST', 'localhost'), 'port' => env('MAIL_PORT', 1025), 'encryption' => env('MAIL_ENCRYPTION'), 'username' => env('MAIL_USERNAME'), 'password' => env('MAIL_PASSWORD'), 'timeout' => null, 'local_domain' => env('MAIL_EHLO_DOMAIN')],
        'log' => ['transport' => 'log', 'channel' => env('MAIL_LOG_CHANNEL')],
        'array' => ['transport' => 'array'],
    ],
    'from' => ['address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'), 'name' => env('MAIL_FROM_NAME', 'Комплекс Греция')],
    'markdown' => ['theme' => 'default', 'paths' => [resource_path('views/vendor/mail')]],
];
