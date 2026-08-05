<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

final class SiteSettings
{
    public static function all(): array
    {
        if (! Schema::hasTable('site_settings')) {
            return self::defaults();
        }

        return Cache::remember('site-settings.public', 3600, function () {
            $values = self::defaults();

            foreach (SiteSetting::query()->where('is_public', true)->get() as $setting) {
                $values[$setting->key] = self::castValue($setting->value, $setting->type);
            }

            return $values;
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function flush(): void
    {
        Cache::forget('site-settings.public');
    }

    public static function defaults(): array
    {
        return [
            'site_name' => config('app.name', 'Комплекс Греция'),
            'site_short_name' => 'Греция',
            'site_tagline' => 'бассейн · SPA',
            'site_description' => 'Бассейн с морской водой и SPA-комплекс в Васильево.',
            'footer_text' => 'Отдых, движение и восстановление в бассейне с морской водой и SPA-пространстве.',
            'promo_enabled' => true,
            'promo_text' => 'Подпишитесь и напишите слово «БАССЕЙН» — сеанс соляной комнаты в подарок',
            'phone' => config('app.complex.phone', '+7 (965) 587-77-99'),
            'phone_alt' => '',
            'email' => 'info@greecya.ru',
            'booking_email' => 'booking@greecya.ru',
            'address_full' => config('app.complex.address', 'Республика Татарстан, Зеленодольский район, пгт Васильево, ул. Ленина, 57а'),
            'address_short' => 'пгт Васильево, ул. Ленина, 57а',
            'city' => 'Васильево',
            'map_url' => '',
            'map_embed' => '',
            'working_hours_weekdays' => 'Пн–Пт: 07:00–22:00',
            'working_hours_weekends' => 'Сб–Вс: 08:00–21:00',
            'social_handle' => config('app.complex.social', 'kompleksgrecia'),
            'social_vk' => '',
            'social_telegram' => '',
            'social_whatsapp' => '',
            'social_instagram' => '',
            'company_name' => 'Комплекс «Греция»',
            'legal_name' => '',
            'inn' => '',
            'kpp' => '',
            'ogrn' => '',
            'legal_address' => '',
            'bank_details' => '',
            'director' => '',
            'seo_default_title' => 'Комплекс Греция — бассейн и SPA',
            'seo_default_description' => 'Бассейн с морской водой, тренировки и SPA-процедуры в комплексе «Греция».',
            'seo_default_keywords' => 'бассейн, SPA, Васильево, плавание, массаж, сауна',
            'seo_allow_indexing' => true,
            'default_og_image' => '',
            'site_logo' => '',
            'favicon' => '',
        ];
    }

    private static function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? (float) $value : null,
            'json' => json_decode((string) $value, true) ?: [],
            default => $value,
        };
    }
}
