<?php

namespace App\Services;

use App\Models\PricingRule;
use Illuminate\Support\Collection;

class PublicDiscountService
{
    public function forHomepage(int $limit = 6): Collection
    {
        return PricingRule::query()
            ->with([
                'service:id,name,slug,price,is_active,online_booking',
                'product:id,name,slug,type,price,is_active',
            ])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', today());
            })
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->filter(fn (PricingRule $rule) => $this->isPublicDiscount($rule))
            ->sortBy(fn (PricingRule $rule) => [
                $rule->starts_on && $rule->starts_on->isAfter(today()) ? 1 : 0,
                (int) $rule->priority,
                (int) $rule->id,
            ])
            ->map(fn (PricingRule $rule) => $this->present($rule))
            ->filter()
            ->take($limit)
            ->values();
    }

    private function isPublicDiscount(PricingRule $rule): bool
    {
        if ($rule->service_id && (! $rule->service || ! $rule->service->is_active)) {
            return false;
        }

        if ($rule->product_id && (! $rule->product || ! $rule->product->is_active)) {
            return false;
        }

        $value = (float) $rule->adjustment_value;

        if (in_array($rule->adjustment_type, ['percent', 'fixed'], true)) {
            return $value < 0;
        }

        if ($rule->adjustment_type !== 'override') {
            return false;
        }

        $base = $this->basePrice($rule);

        // Для общего override без конкретного объекта нельзя честно утверждать,
        // что это скидка: для части услуг такая цена может оказаться повышением.
        return $base !== null && $value >= 0 && $value < $base;
    }

    private function present(PricingRule $rule): ?array
    {
        $base = $this->basePrice($rule);
        $discounted = $base !== null ? $this->standalonePrice($rule, $base) : null;

        if ($base !== null && ($discounted === null || $discounted >= $base)) {
            return null;
        }

        $target = $this->target($rule);
        if (! $target) {
            return null;
        }

        return [
            'id' => $rule->id,
            'name' => $rule->name,
            'badge' => $this->badge($rule),
            'target' => $target['label'],
            'url' => $target['url'],
            'cta' => $target['cta'],
            'base_price' => $base,
            'discounted_price' => $discounted,
            'conditions' => $this->conditions($rule),
            'upcoming' => $rule->starts_on && $rule->starts_on->isAfter(today()),
        ];
    }

    private function target(PricingRule $rule): ?array
    {
        if ($rule->target_type === 'service') {
            if ($rule->service_id) {
                if (! $rule->service) {
                    return null;
                }

                return [
                    'label' => $rule->service->name,
                    'url' => $rule->service->online_booking
                        ? route('booking.index', ['service' => $rule->service->id])
                        : route('services.show', $rule->service),
                    'cta' => $rule->service->online_booking ? 'Выбрать время' : 'Подробнее',
                ];
            }

            return [
                'label' => 'Услуги комплекса',
                'url' => route('services.index'),
                'cta' => 'Смотреть услуги',
            ];
        }

        if ($rule->target_type === 'product') {
            if ($rule->product_id && ! $rule->product) {
                return null;
            }

            return [
                'label' => $rule->product?->name ?: 'Билеты и абонементы',
                'url' => route('catalog.index'),
                'cta' => 'Смотреть тарифы',
            ];
        }

        return null;
    }

    private function badge(PricingRule $rule): string
    {
        $value = abs((float) $rule->adjustment_value);

        return match ($rule->adjustment_type) {
            'percent' => '−'.rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'%',
            'fixed' => '−'.number_format($value, 0, ',', ' ').' ₽',
            'override' => 'СПЕЦЦЕНА',
            default => 'ВЫГОДНО',
        };
    }

    private function conditions(PricingRule $rule): array
    {
        $conditions = [];

        $segment = match ($rule->customer_segment) {
            'child' => 'детям до 18 лет',
            'senior' => 'гостям 60+',
            'family' => 'семьям',
            'corporate' => 'корпоративным клиентам',
            default => null,
        };
        if ($segment) {
            $conditions[] = $segment;
        }

        if ($rule->weekdays) {
            $days = collect($rule->weekdays)->map(fn ($day) => (int) $day)->sort()->values()->all();
            $conditions[] = match ($days) {
                [1, 2, 3, 4, 5] => 'по будням',
                [6, 7] => 'по выходным',
                default => collect($days)->map(fn ($day) => [1=>'Пн',2=>'Вт',3=>'Ср',4=>'Чт',5=>'Пт',6=>'Сб',7=>'Вс'][$day] ?? '')->filter()->implode(', '),
            };
        }

        if ($rule->time_from || $rule->time_to) {
            $from = $rule->time_from ? substr((string) $rule->time_from, 0, 5) : '00:00';
            $to = $rule->time_to ? substr((string) $rule->time_to, 0, 5) : '23:59';
            $conditions[] = $from.'–'.$to;
        }

        if ($rule->occupancy_min_pct !== null && $rule->occupancy_max_pct !== null) {
            $conditions[] = 'при загрузке '.(float) $rule->occupancy_min_pct.'–'.(float) $rule->occupancy_max_pct.'%';
        } elseif ($rule->occupancy_max_pct !== null) {
            $conditions[] = 'при загрузке до '.(float) $rule->occupancy_max_pct.'%';
        } elseif ($rule->occupancy_min_pct !== null) {
            $conditions[] = 'при загрузке от '.(float) $rule->occupancy_min_pct.'%';
        }

        if ($rule->starts_on && $rule->ends_on) {
            $conditions[] = 'с '.$rule->starts_on->format('d.m.Y').' по '.$rule->ends_on->format('d.m.Y');
        } elseif ($rule->starts_on) {
            $conditions[] = 'с '.$rule->starts_on->format('d.m.Y');
        } elseif ($rule->ends_on) {
            $conditions[] = 'до '.$rule->ends_on->format('d.m.Y');
        }

        return $conditions ?: ['действует без ограничения по времени'];
    }

    private function basePrice(PricingRule $rule): ?float
    {
        if ($rule->target_type === 'service' && $rule->service_id && $rule->service) {
            return (float) $rule->service->price;
        }

        if ($rule->target_type === 'product' && $rule->product_id && $rule->product) {
            return (float) $rule->product->price;
        }

        return null;
    }

    private function standalonePrice(PricingRule $rule, float $base): float
    {
        $value = (float) $rule->adjustment_value;
        $price = match ($rule->adjustment_type) {
            'override' => $value,
            'fixed' => $base + $value,
            default => $base * (1 + ($value / 100)),
        };

        if ($rule->min_price !== null) {
            $price = max($price, (float) $rule->min_price);
        }
        if ($rule->max_price !== null) {
            $price = min($price, (float) $rule->max_price);
        }

        return max(0, round($price, 2));
    }
}
