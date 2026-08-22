<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Family;
use App\Models\FamilyConsent;
use App\Models\FamilyMember;
use App\Models\FamilyWallet;
use App\Models\FamilyWalletTransaction;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\PoolZone;
use App\Models\SwimAttendance;
use App\Models\SwimGroup;
use App\Models\SwimGroupMember;
use App\Models\SwimGroupSession;
use App\Models\SwimMakeup;
use App\Models\SwimProgress;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Seeder;

class FamilySwimSeeder extends Seeder
{
    public function run(): void
    {
        $olga = Customer::where('email', 'olga@example.com')->firstOrFail();
        $child = Customer::updateOrCreate(
            ['email' => 'alisa.petrova@example.com'],
            [
                'name' => 'Алиса Петрова',
                'phone' => '+7 900 111-11-12',
                'birth_date' => today()->subYears(9)->format('Y-m-d'),
                'gender' => 'female',
                'source' => 'family',
                'emergency_contact' => $olga->phone,
            ]
        );

        $family = Family::updateOrCreate(
            ['name' => 'Семья Петровых'],
            ['primary_customer_id' => $olga->id, 'status' => 'active', 'notes' => 'Демонстрационный семейный аккаунт.']
        );

        FamilyMember::updateOrCreate(
            ['family_id'=>$family->id,'customer_id'=>$olga->id],
            ['relation'=>'mother','is_guardian'=>true,'can_manage_bookings'=>true,'can_use_wallet'=>true]
        );
        FamilyMember::updateOrCreate(
            ['family_id'=>$family->id,'customer_id'=>$child->id],
            ['relation'=>'daughter','is_guardian'=>false,'can_manage_bookings'=>false,'can_use_wallet'=>true]
        );

        FamilyConsent::updateOrCreate(
            ['family_id'=>$family->id,'guardian_customer_id'=>$olga->id,'child_customer_id'=>$child->id,'type'=>'pool_visit'],
            ['status'=>'signed','signed_at'=>now()->subMonth(),'expires_on'=>today()->addYear(),'notes'=>'Согласие на посещение бассейна и занятия в детской группе.']
        );

        $wallet = FamilyWallet::updateOrCreate(['family_id'=>$family->id], ['deposit_balance'=>5000,'bonus_balance'=>600]);
        $manager = User::where('email','manager@greecya.local')->first();
        FamilyWalletTransaction::updateOrCreate(
            ['family_wallet_id'=>$wallet->id,'wallet_type'=>'deposit','direction'=>'credit','description'=>'Начальный семейный депозит'],
            ['customer_id'=>$olga->id,'created_by'=>$manager?->id,'amount'=>5000]
        );
        FamilyWalletTransaction::updateOrCreate(
            ['family_wallet_id'=>$wallet->id,'wallet_type'=>'bonus','direction'=>'credit','description'=>'Семейные приветственные бонусы'],
            ['customer_id'=>$olga->id,'created_by'=>$manager?->id,'amount'=>600]
        );

        $zone = PoolZone::where('code','POOL25')->firstOrFail();
        $trainer = Trainer::where('name','Анна Соколова')->firstOrFail();
        $lane = $zone->lanes()->orderBy('number')->skip(1)->first() ?: $zone->lanes()->first();

        $familyPlan = MembershipPlan::updateOrCreate(
            ['code'=>'FAMILY-POOL-12'],
            [
                'name'=>'Семейный бассейн 12 посещений',
                'type'=>'package',
                'audience_type'=>'family',
                'duration_days'=>60,
                'visits_included'=>12,
                'weekly_visit_limit'=>4,
                'price'=>7200,
                'freeze_days'=>14,
                'guest_visits'=>0,
                'access_from'=>'08:00',
                'access_to'=>'20:00',
                'allowed_weekdays'=>[1,2,3,4,5,6,7],
                'allowed_pool_zone_ids'=>[$zone->id],
                'family_member_limit'=>4,
                'corporate_required'=>false,
                'requires_medical_clearance'=>true,
                'is_active'=>true,
            ]
        );

        Membership::updateOrCreate(
            ['number'=>'MEM-FAMILY-DEMO'],
            [
                'customer_id'=>$olga->id,
                'family_id'=>$family->id,
                'primary_holder_id'=>$olga->id,
                'membership_plan_id'=>$familyPlan->id,
                'status'=>'active',
                'starts_on'=>today()->subDays(10),
                'ends_on'=>today()->addDays(49),
                'visits_total'=>12,
                'visits_used'=>2,
                'freeze_days_total'=>14,
                'freeze_days_used'=>0,
                'guest_visits_left'=>0,
                'price_paid'=>7200,
                'notes'=>'Общий семейный абонемент Ольги и Алисы.',
            ]
        );

        $group = SwimGroup::updateOrCreate(
            ['code'=>'DOLPHINS-7-10'],
            [
                'name'=>'Дельфины 7–10 лет',
                'age_min'=>7,
                'age_max'=>10,
                'level'=>'начальный+',
                'trainer_id'=>$trainer->id,
                'pool_zone_id'=>$zone->id,
                'pool_lane_id'=>$lane?->id,
                'season_start'=>today()->startOfMonth(),
                'season_end'=>today()->addMonths(4)->endOfMonth(),
                'max_members'=>10,
                'status'=>'active',
                'notes'=>'Школа плавания: техника кроля, дыхание, безопасность на воде.',
            ]
        );

        $member = SwimGroupMember::updateOrCreate(
            ['swim_group_id'=>$group->id,'customer_id'=>$child->id],
            ['guardian_customer_id'=>$olga->id,'joined_on'=>today()->subMonth(),'status'=>'active','notes'=>'Цель: уверенно проплывать 50 м кролем.']
        );

        $sessions = [];
        foreach ([-7,-5,-2,2,5] as $offset) {
            $start = today()->addDays($offset)->setTime(18, 0);
            $sessions[$offset] = SwimGroupSession::updateOrCreate(
                ['swim_group_id'=>$group->id,'starts_at'=>$start],
                ['pool_lane_id'=>$lane?->id,'ends_at'=>$start->copy()->addHour(),'status'=>$offset < 0 ? 'completed' : 'scheduled']
            );
        }

        SwimAttendance::updateOrCreate(
            ['swim_group_session_id'=>$sessions[-7]->id,'swim_group_member_id'=>$member->id],
            ['status'=>'present','checkin_at'=>$sessions[-7]->starts_at->copy()->subMinutes(5),'marked_by'=>$manager?->id]
        );
        SwimAttendance::updateOrCreate(
            ['swim_group_session_id'=>$sessions[-5]->id,'swim_group_member_id'=>$member->id],
            ['status'=>'excused','notes'=>'Болела','marked_by'=>$manager?->id]
        );
        SwimAttendance::updateOrCreate(
            ['swim_group_session_id'=>$sessions[-2]->id,'swim_group_member_id'=>$member->id],
            ['status'=>'present','checkin_at'=>$sessions[-2]->starts_at->copy()->subMinutes(3),'marked_by'=>$manager?->id]
        );

        SwimMakeup::updateOrCreate(
            ['swim_group_member_id'=>$member->id,'missed_session_id'=>$sessions[-5]->id],
            ['status'=>'available','expires_on'=>today()->addMonth(),'notes'=>'Отработка уважительного пропуска.']
        );

        SwimProgress::updateOrCreate(
            ['swim_group_member_id'=>$member->id,'recorded_on'=>today()->subDays(2),'skill'=>'Кроль 25 м'],
            ['trainer_id'=>$trainer->id,'score'=>4,'comment'=>'Уверенное положение тела, работаем над ритмом дыхания.']
        );
    }
}
