<?php

namespace App\Services;

use App\Models\CorporateMember;
use App\Models\Customer;
use App\Models\FamilyMember;
use App\Models\Membership;
use App\Models\PoolZone;
use App\Models\ScheduleSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MembershipEligibilityService
{
    public function candidates(Customer $customer): Collection
    {
        $familyIds = FamilyMember::where('customer_id', $customer->id)->pluck('family_id');

        return Membership::query()
            ->with(['plan','family.members'])
            ->where(function ($q) use ($customer, $familyIds) {
                $q->where('customer_id', $customer->id)
                    ->orWhere('primary_holder_id', $customer->id);
                if ($familyIds->isNotEmpty()) {
                    $q->orWhereIn('family_id', $familyIds);
                }
            })
            ->whereIn('status', ['active','frozen'])
            ->orderBy('ends_on')
            ->get();
    }

    public function findUsable(Customer $customer, ?ScheduleSlot $slot = null, ?PoolZone $zone = null, ?Carbon $at = null): ?Membership
    {
        foreach ($this->candidates($customer) as $membership) {
            [$ok] = $this->check($membership, $customer, $slot, $zone, $at);
            if ($ok) {
                return $membership;
            }
        }
        return null;
    }

    public function check(Membership $membership, Customer $customer, ?ScheduleSlot $slot = null, ?PoolZone $zone = null, ?Carbon $at = null): array
    {
        $at ??= now();
        $plan = $membership->plan;

        if (! $membership->belongsToCustomer($customer)) {
            return [false, 'Абонемент не принадлежит клиенту или его семье'];
        }
        if ($membership->status !== 'active') {
            return [false, 'Абонемент не активен'];
        }
        if ($membership->starts_on->gt($at->copy()->startOfDay()) || $membership->ends_on->lt($at->copy()->startOfDay())) {
            return [false, 'Абонемент вне срока действия'];
        }
        if ($membership->visits_total !== null && $membership->visits_used >= $membership->visits_total) {
            return [false, 'Лимит посещений исчерпан'];
        }
        if (! $plan->allowsWeekday($at->isoWeekday())) {
            return [false, 'Тариф не действует в этот день недели'];
        }
        if ($plan->access_from && $at->format('H:i:s') < (string) $plan->access_from) {
            return [false, 'Тариф ещё не действует в это время'];
        }
        if ($plan->access_to && $at->format('H:i:s') > (string) $plan->access_to) {
            return [false, 'Время доступа по тарифу закончилось'];
        }
        if ($slot && ! $plan->allowsService($slot->service_id)) {
            return [false, 'Услуга не входит в тариф'];
        }
        if ($zone && ! $plan->allowsZone($zone->id)) {
            return [false, 'Зона бассейна не входит в тариф'];
        }
        if ($plan->personal_trainer_id && $slot && (int) $slot->trainer_id !== (int) $plan->personal_trainer_id) {
            return [false, 'Тариф закреплён за другим тренером'];
        }
        if ($plan->audience_type === 'family' && ! $membership->family_id) {
            return [false, 'Семейный тариф не привязан к семье'];
        }
        if ($plan->audience_type === 'individual' && (int) $membership->customer_id !== (int) $customer->id && (int) $membership->primary_holder_id !== (int) $customer->id) {
            return [false, 'Индивидуальный тариф нельзя использовать другому члену семьи'];
        }
        if ($plan->corporate_required || $plan->audience_type === 'corporate') {
            $corporate = CorporateMember::where('customer_id', $customer->id)->where('status', 'active')->exists();
            if (! $corporate) {
                return [false, 'Тариф доступен только корпоративным клиентам'];
            }
        }
        if ($plan->weekly_visit_limit) {
            $usedThisWeek = $membership->visits()
                ->whereBetween('visited_at', [$at->copy()->startOfWeek(), $at->copy()->endOfWeek()])
                ->count();
            if ($usedThisWeek >= $plan->weekly_visit_limit) {
                return [false, 'Недельный лимит посещений исчерпан'];
            }
        }
        if ($plan->family_member_limit && $membership->family_id) {
            $activeMembers = FamilyMember::where('family_id', $membership->family_id)->count();
            if ($activeMembers > $plan->family_member_limit) {
                return [false, 'Количество участников семьи превышает лимит тарифа'];
            }
        }

        return [true, null];
    }
}
