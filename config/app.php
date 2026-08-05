<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [
    'name' => env('APP_NAME', 'Комплекс Греция'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'Europe/Moscow',
    'locale' => 'ru',
    'fallback_locale' => 'ru',
    'faker_locale' => 'ru_RU',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => ['driver' => 'file'],
    'providers' => ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])->toArray(),
    'aliases' => Facade::defaultAliases()->merge([])->toArray(),
    'complex' => [
        'phone' => env('COMPLEX_PHONE', '+7 (965) 587-77-99'),
        'social' => env('COMPLEX_SOCIAL', 'kompleksgrecia'),
        'address' => env('COMPLEX_ADDRESS', 'Республика Татарстан, Зеленодольский район, пгт Васильево, ул. Ленина, 57а'),
    ],
];
