<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\ScheduleSlot;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DynamicPricingService
{
    public function forService(Service $service, ?ScheduleSlot $slot = null, ?Customer $customer = null): array
    {
        $at = $slot?->starts_at ?: now();
        $occupancy = $slot && $slot->capacity > 0 ? ((float) $slot->booked_count / (float) $slot->capacity) * 100 : null;

        return $this->calculate((float) $service->price, 'service', $service->id, $at, $occupancy, $customer);
    }

    public function forProduct(Product $product, ?Customer $customer = null): array
    {
        return $this->calculate((float) $product->price, 'product', $product->id, now(), null, $customer);
    }

    private function calculate(float $base, string $targetType, int $targetId, $at, ?float $occupancy, ?Customer $customer): array
    {
        $at = Carbon::parse($at);
        $rules = PricingRule::query()
            ->where('is_active', true)
            ->where('target_type', $targetType)
            ->where(function ($q) use ($targetType, $targetId) {
                $column = $targetType === 'service' ? 'service_id' : 'product_id';
                $q->whereNull($column)->orWhere($column, $targetId);
            })
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $price = $base;
        $applied = [];

        foreach ($rules as $rule) {
            if (! $this->matches($rule, $at, $occupancy, $customer)) {
                continue;
            }

            $before = $price;
            $value = (float) $rule->adjustment_value;
            if ($rule->adjustment_type === 'override') {
                $price = $value;
            } elseif ($rule->adjustment_type === 'fixed') {
                $price += $value;
            } else {
                $price *= 1 + ($value / 100);
            }

            if ($rule->min_price !== null) $price = max($price, (float) $rule->min_price);
            if ($rule->max_price !== null) $price = min($price, (float) $rule->max_price);
            $price = max(0, round($price, 2));

            $applied[] = [
                'id' => $rule->id,
                'name' => $rule->name,
                'type' => $rule->adjustment_type,
                'value' => $value,
                'before' => round($before, 2),
                'after' => $price,
            ];

            if (! $rule->combinable) break;
        }

        return [
            'base' => round($base, 2),
            'price' => round($price, 2),
            'difference' => round($price - $base, 2),
            'rules' => $applied,
        ];
    }

    private function matches(PricingRule $rule, Carbon $at, ?float $occupancy, ?Customer $customer): bool
    {
        if ($rule->starts_on && $at->lt($rule->starts_on->startOfDay())) return false;
        if ($rule->ends_on && $at->gt($rule->ends_on->endOfDay())) return false;

        if ($rule->weekdays && ! in_array($at->dayOfWeekIso, array_map('intval', $rule->weekdays), true)) return false;

        if ($rule->time_from || $rule->time_to) {
            $current = $at->format('H:i:s');
            $from = $rule->time_from ? substr((string) $rule->time_from, 0, 8) : '00:00:00';
            $to = $rule->time_to ? substr((string) $rule->time_to, 0, 8) : '23:59:59';
            if ($from <= $to) {
                if ($current < $from || $current > $to) return false;
            } else {
                if ($current < $from && $current > $to) return false;
            }
        }

        if ($rule->occupancy_min_pct !== null && ($occupancy === null || $occupancy < (float) $rule->occupancy_min_pct)) return false;
        if ($rule->occupancy_max_pct !== null && ($occupancy === null || $occupancy > (float) $rule->occupancy_max_pct)) return false;

        return $this->matchesSegment($rule->customer_segment, $customer);
    }

    private function matchesSegment(string $segment, ?Customer $customer): bool
    {
        if ($segment === 'all') return true;
        if (! $customer) return false;

        $age = $customer->age();
        return match ($segment) {
            'child' => $age !== null && $age < 18,
            'senior' => $age !== null && $age >= 60,
            'family' => $customer->familyMemberships()->exists(),
            'corporate' => DB::table('corporate_members')->where('customer_id', $customer->id)->where('status', 'active')->exists(),
            default => false,
        };
    }
}
