<?php

namespace Database\Seeders;

use App\Models\AccessCard;
use App\Models\AccessEvent;
use App\Models\Campaign;
use App\Models\CashRegister;
use App\Models\CashShift;
use App\Models\CashTransaction;
use App\Models\CorporateAccount;
use App\Models\CorporateMember;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\CustomerInteraction;
use App\Models\CustomerWallet;
use App\Models\DocumentTemplate;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Lead;
use App\Models\Locker;
use App\Models\LockerRental;
use App\Models\MaintenanceTask;
use App\Models\MedicalClearance;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\PayrollRule;
use App\Models\PoolLane;
use App\Models\PoolWaterLog;
use App\Models\PoolZone;
use App\Models\Product;
use App\Models\ScheduleSlot;
use App\Models\StaffShift;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;

class PoolCrmSeeder extends Seeder
{
    public function run(): void
    {
        $admin=User::where('email','admin@greecya.local')->firstOrFail();
        $manager=User::where('email','manager@greecya.local')->firstOrFail();
        $demo=Customer::where('email','client@greecya.local')->firstOrFail();
        $olga=Customer::where('email','olga@example.com')->firstOrFail();
        $ivan=Customer::where('email','ivan@example.com')->firstOrFail();
        $maria=Customer::where('email','maria@example.com')->firstOrFail();

        foreach([$demo,$olga,$ivan,$maria] as $index=>$customer){$customer->update(['gender'=>$index%2?'female':'male','emergency_contact'=>'+7 900 999-00-0'.$index,'marketing_consent'=>true]);}

        $main=PoolZone::updateOrCreate(['code'=>'POOL25'],['name'=>'Основной бассейн 25 м','type'=>'pool','capacity'=>36,'is_active'=>true]);
        $kids=PoolZone::updateOrCreate(['code'=>'KIDS'],['name'=>'Детский бассейн','type'=>'pool','capacity'=>12,'is_active'=>true]);
        $spa=PoolZone::updateOrCreate(['code'=>'SPA'],['name'=>'SPA и восстановление','type'=>'spa','capacity'=>16,'is_active'=>true]);
        for($i=1;$i<=6;$i++)PoolLane::updateOrCreate(['pool_zone_id'=>$main->id,'number'=>$i],['name'=>'Дорожка '.$i,'length_meters'=>25,'capacity'=>6,'status'=>'open','is_active'=>true]);
        for($i=1;$i<=2;$i++)PoolLane::updateOrCreate(['pool_zone_id'=>$kids->id,'number'=>$i],['name'=>'Детская дорожка '.$i,'length_meters'=>12.5,'capacity'=>6,'status'=>'open','is_active'=>true]);

        $mainLanes=$main->lanes()->orderBy('number')->get();
        ScheduleSlot::with('service')->whereBetween('starts_at',[today(),now()->addDays(21)->endOfDay()])->get()->each(function($slot)use($main,$kids,$spa,$mainLanes){
            if($slot->service->slug==='kids-training'){$slot->update(['pool_zone_id'=>$kids->id,'session_type'=>'kids_training','online_booking'=>true]);$slot->lanes()->syncWithoutDetaching([$kids->lanes()->first()->id=>['capacity'=>1]]);}
            elseif(in_array($slot->service->category,['pool','training'],true)){$type=$slot->service->slug==='free-swimming'?'free_swim':($slot->service->slug==='aqua-fitness'?'group':'personal');$slot->update(['pool_zone_id'=>$main->id,'session_type'=>$type,'online_booking'=>true]);$lanes=$type==='free_swim'?$mainLanes->pluck('id')->all():[$mainLanes[0]->id];foreach($lanes as $laneId)$slot->lanes()->syncWithoutDetaching([$laneId=>['capacity'=>$type==='free_swim'?6:1]]);}
            else{$slot->update(['pool_zone_id'=>$spa->id,'session_type'=>'spa','online_booking'=>true]);}
        });

        $plan8=MembershipPlan::updateOrCreate(['code'=>'POOL8'],['product_id'=>Product::where('slug','pool-8')->value('id'),'name'=>'8 посещений бассейна','type'=>'package','duration_days'=>60,'visits_included'=>8,'price'=>4800,'freeze_days'=>7,'guest_visits'=>0,'is_active'=>true]);
        $plan12=MembershipPlan::updateOrCreate(['code'=>'POOL12'],['product_id'=>Product::where('slug','pool-12')->value('id'),'name'=>'12 посещений бассейна','type'=>'package','duration_days'=>90,'visits_included'=>12,'price'=>6600,'freeze_days'=>14,'guest_visits'=>1,'is_active'=>true]);
        $unlimited=MembershipPlan::updateOrCreate(['code'=>'UNLIMITED30'],['name'=>'Безлимит 30 дней','type'=>'membership','duration_days'=>30,'visits_included'=>null,'price'=>7900,'freeze_days'=>7,'guest_visits'=>1,'access_from'=>'07:00','access_to'=>'22:00','is_active'=>true]);
        $memberships=[
            [$demo,$plan8,'MEM-DEMO-001',today()->subDays(10),2],[$olga,$unlimited,'MEM-DEMO-002',today()->subDays(5),0],[$maria,$plan12,'MEM-DEMO-003',today()->subDays(20),4],
        ];
        foreach($memberships as [$customer,$plan,$number,$start,$used])Membership::updateOrCreate(['number'=>$number],['customer_id'=>$customer->id,'membership_plan_id'=>$plan->id,'status'=>'active','starts_on'=>$start->toDateString(),'ends_on'=>$start->copy()->addDays($plan->duration_days-1)->toDateString(),'visits_total'=>$plan->visits_included,'visits_used'=>$used,'freeze_days_total'=>$plan->freeze_days,'freeze_days_used'=>0,'guest_visits_left'=>$plan->guest_visits,'auto_renew'=>$plan->type==='membership','price_paid'=>$plan->price]);

        foreach([[$demo,1500,250,'silver'],[$olga,3000,500,'gold'],[$maria,750,120,'base'],[$ivan,0,0,'base']] as [$customer,$deposit,$bonus,$level]){$wallet=CustomerWallet::updateOrCreate(['customer_id'=>$customer->id],['deposit_balance'=>$deposit,'bonus_balance'=>$bonus,'loyalty_level'=>$level]);WalletTransaction::updateOrCreate(['customer_wallet_id'=>$wallet->id,'wallet_type'=>'deposit','direction'=>'credit','description'=>'Демо-начисление'],['amount'=>$deposit?:1,'created_by'=>$manager->id]);}

        foreach([[$demo,'QR-DEMO-CLIENT'],[$olga,'QR-OLGA-001'],[$maria,'NFC-MARIA-001']] as [$customer,$code])AccessCard::updateOrCreate(['code'=>$code],['customer_id'=>$customer->id,'type'=>str_starts_with($code,'NFC')?'nfc':'qr','status'=>'active','issued_at'=>now()->subMonth(),'expires_at'=>now()->addYear()]);
        foreach([$demo,$olga,$maria] as $customer)MedicalClearance::updateOrCreate(['customer_id'=>$customer->id,'type'=>'pool'],['issued_on'=>today()->subMonth(),'expires_on'=>today()->addMonths(5),'status'=>'valid','notes'=>'Допуск для демонстрационной базы']);
        MedicalClearance::updateOrCreate(['customer_id'=>$ivan->id,'type'=>'pool'],['issued_on'=>today()->subYear(),'expires_on'=>today()->subDay(),'status'=>'expired','notes'=>'Требуется обновление']);

        for($i=101;$i<=112;$i++)Locker::updateOrCreate(['number'=>(string)$i],['zone'=>$i<=106?'мужская раздевалка':'женская раздевалка','gender'=>$i<=106?'male':'female','status'=>'available','is_active'=>true]);
        $locker=Locker::where('number','107')->first();$rental=LockerRental::updateOrCreate(['locker_id'=>$locker->id,'customer_id'=>$olga->id,'status'=>'active'],['started_at'=>now()->subHour(),'ends_at'=>now()->addHours(2),'deposit'=>500]);$locker->update(['status'=>'occupied']);

        foreach([[$demo,$main,'enter','allowed',null,now()->subDays(2)->setTime(18,2)],[$demo,$main,'exit','allowed',null,now()->subDays(2)->setTime(19,1)],[$ivan,$main,'enter','denied','Нет действующего медицинского допуска',now()->subDay()->setTime(12,15)]] as [$customer,$zone,$type,$result,$reason,$at])AccessEvent::updateOrCreate(['customer_id'=>$customer->id,'event_type'=>$type,'occurred_at'=>$at],['pool_zone_id'=>$zone->id,'result'=>$result,'reason'=>$reason]);

        $lead=Lead::where('phone','+7 900 555-55-55')->first();
        CrmTask::updateOrCreate(['title'=>'Предложить продление абонемента','customer_id'=>$demo->id],['assigned_to'=>$manager->id,'type'=>'renewal','description'=>'До окончания пакета осталось мало посещений','due_at'=>now()->addDay()->setTime(11,0),'status'=>'open']);
        CrmTask::updateOrCreate(['title'=>'Отправить варианты корпоративного тарифа','lead_id'=>$lead?->id],['assigned_to'=>$manager->id,'type'=>'message','due_at'=>now()->addHours(5),'status'=>'open']);
        CustomerInteraction::updateOrCreate(['customer_id'=>$demo->id,'subject'=>'Продление членства','occurred_at'=>now()->subDays(3)->setTime(15,0)],['user_id'=>$manager->id,'channel'=>'phone','direction'=>'out','body'=>'Клиент заинтересован в пакете на 12 посещений.']);
        CustomerInteraction::updateOrCreate(['customer_id'=>$olga->id,'subject'=>'Перенос тренировки','occurred_at'=>now()->subDay()->setTime(10,30)],['user_id'=>$manager->id,'channel'=>'telegram','direction'=>'in','body'=>'Перенесли занятие на вечер четверга.']);

        $anna=Trainer::where('name','Анна Соколова')->firstOrFail();$mikhail=Trainer::where('name','Михаил Волков')->firstOrFail();
        foreach([[$anna,9,18],[$mikhail,10,19]] as [$trainer,$from,$to])StaffShift::updateOrCreate(['trainer_id'=>$trainer->id,'starts_at'=>today()->setTime($from,0)],['ends_at'=>today()->setTime($to,0),'type'=>'work','status'=>'worked','worked_minutes'=>($to-$from)*60]);
        PayrollRule::updateOrCreate(['trainer_id'=>$anna->id,'name'=>'Персональное занятие'],['calc_type'=>'session','rate'=>600,'is_active'=>true]);
        PayrollRule::updateOrCreate(['trainer_id'=>$mikhail->id,'name'=>'Процент от персональных услуг'],['calc_type'=>'percent_service','rate'=>30,'is_active'=>true]);

        $register=CashRegister::updateOrCreate(['code'=>'MAIN'],['name'=>'Касса ресепшена','location'=>'Главная стойка','is_active'=>true]);
        $shift=CashShift::updateOrCreate(['cash_register_id'=>$register->id,'opened_at'=>today()->setTime(8,0)],['opened_by'=>$manager->id,'opening_cash'=>5000,'status'=>'open']);
        foreach([['Продажа разового посещения','sale','card',700,$ivan],['Пополнение депозита','deposit','cash',1500,$demo],['Продажа шапочки','sale','cash',450,$olga]] as [$desc,$type,$method,$amount,$customer])CashTransaction::updateOrCreate(['cash_shift_id'=>$shift->id,'description'=>$desc],['user_id'=>$manager->id,'customer_id'=>$customer->id,'type'=>$type,'method'=>$method,'amount'=>$amount,'occurred_at'=>now()->subMinutes(rand(10,180))]);

        $items=[['CAP-BLUE','Шапочка для плавания','retail','шт',180,450,22,5],['GOG-01','Очки для плавания','retail','шт',650,1200,9,3],['CL-20','Средство контроля воды','chemical','л',900,0,4,5],['TOWEL','Полотенце прокатное','pool','шт',500,0,35,10]];
        foreach($items as [$sku,$name,$category,$unit,$buy,$sell,$stock,$min]){$item=InventoryItem::updateOrCreate(['sku'=>$sku],['name'=>$name,'category'=>$category,'unit'=>$unit,'purchase_price'=>$buy,'sale_price'=>$sell,'stock_qty'=>$stock,'min_stock'=>$min,'is_active'=>true]);InventoryMovement::updateOrCreate(['inventory_item_id'=>$item->id,'type'=>'in','note'=>'Начальный остаток'],['user_id'=>$admin->id,'quantity'=>$stock,'unit_cost'=>$buy,'occurred_at'=>today()->subMonth()->setTime(9,0)]);}

        $corp=CorporateAccount::updateOrCreate(['name'=>'ООО «АкваТех»'],['tax_id'=>'1650000000','contact_name'=>'Николай Иванов','phone'=>'+7 900 888-88-88','email'=>'office@example.com','discount_percent'=>12,'credit_limit'=>50000,'status'=>'active']);
        CorporateMember::updateOrCreate(['corporate_account_id'=>$corp->id,'customer_id'=>$ivan->id],['employee_number'=>'AT-104','status'=>'active']);
        $template=DocumentTemplate::updateOrCreate(['name'=>'Договор на посещение бассейна'],['type'=>'membership_contract','body'=>'Договор от {{date}}. Клиент {{name}}, телефон {{phone}}, email {{email}}. Правила посещения бассейна являются неотъемлемой частью договора.','is_active'=>true]);
        CustomerDocument::updateOrCreate(['number'=>'DOC-DEMO-001'],['customer_id'=>$demo->id,'document_template_id'=>$template->id,'type'=>'contract','status'=>'signed','sign_method'=>'manual','signed_at'=>now()->subDays(10),'content'=>'Демонстрационный подписанный договор клиента '.$demo->name]);
        Campaign::updateOrCreate(['name'=>'Напоминание о продлении'],['channel'=>'email','subject'=>'Ваш абонемент в бассейн','body'=>'Напоминаем о возможности продлить членство и сохранить привычное расписание.','audience'=>['marketing_consent'=>true],'status'=>'draft']);

        foreach([[32.0,7.25,0.55,720],[31.8,7.30,0.50,715],[32.1,7.28,0.52,725]] as $index=>[$temp,$ph,$chlorine,$redox])PoolWaterLog::updateOrCreate(['pool_zone_id'=>$main->id,'measured_at'=>today()->subDays(2-$index)->setTime(8,0)],['user_id'=>$admin->id,'temperature'=>$temp,'ph'=>$ph,'free_chlorine'=>$chlorine,'redox'=>$redox,'turbidity'=>0.2,'notes'=>'Показатели в рабочем диапазоне']);
        MaintenanceTask::updateOrCreate(['title'=>'Промывка фильтра основного бассейна'],['pool_zone_id'=>$main->id,'assigned_to'=>$admin->id,'type'=>'maintenance','due_at'=>now()->addDays(2)->setTime(7,0),'status'=>'open','notes'=>'Выполнить до открытия первой смены.']);
        MaintenanceTask::updateOrCreate(['title'=>'Проверка креплений разделительных дорожек'],['pool_zone_id'=>$main->id,'pool_lane_id'=>$mainLanes[0]->id,'assigned_to'=>$admin->id,'type'=>'inspection','due_at'=>now()->addDay()->setTime(21,30),'status'=>'open']);

        $fullSlot=ScheduleSlot::where('pool_zone_id',$main->id)->whereDate('starts_at',today()->addDays(2))->orderBy('starts_at')->first();if($fullSlot)WaitlistEntry::updateOrCreate(['schedule_slot_id'=>$fullSlot->id,'customer_id'=>$ivan->id],['people'=>1,'priority'=>10,'status'=>'waiting']);
    }
}
