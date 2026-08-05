<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::query()->where('email', 'manager@greecya.local')->firstOrFail();

        $demo = Customer::query()->updateOrCreate(['phone'=>'+7 900 000-00-00'], ['name'=>'Демо-клиент','email'=>'client@greecya.local','birth_date'=>'1990-05-15','notes'=>'Тестовый клиент для проверки личного кабинета.','source'=>'demo','last_visit_at'=>now()->subDays(3)]);
        $olga = Customer::query()->updateOrCreate(['phone'=>'+7 900 111-11-11'], ['name'=>'Ольга Петрова','email'=>'olga@example.com','birth_date'=>'1987-09-21','source'=>'site','last_visit_at'=>now()->subDays(8)]);
        $ivan = Customer::query()->updateOrCreate(['phone'=>'+7 900 222-22-22'], ['name'=>'Иван Смирнов','email'=>'ivan@example.com','birth_date'=>'1994-02-10','source'=>'phone']);
        $maria = Customer::query()->updateOrCreate(['phone'=>'+7 900 333-33-33'], ['name'=>'Мария Кузнецова','email'=>'maria@example.com','birth_date'=>'1982-12-02','source'=>'social','last_visit_at'=>now()->subDays(1)]);

        User::query()->updateOrCreate(['email'=>'client@greecya.local'], ['customer_id'=>$demo->id,'name'=>$demo->name,'phone'=>$demo->phone,'role'=>'customer','password'=>Hash::make('ChangeMe123!')]);

        foreach([
            ['Екатерина','+7 900 444-44-44','site','Хочу записать ребёнка на обучение плаванию','new',now()->addDay(),'Перезвонить после 18:00'],
            ['Сергей','+7 900 555-55-55','phone','Интересует абонемент на 12 посещений','contacted',now()->addDays(2),'Отправлена информация по ценам'],
            ['Алина','+7 900 666-66-66','social','Массаж и прессотерапия на выходных','qualified',now()->addHours(6),'Подобрать два последовательных слота'],
            ['Николай','+7 900 777-77-77','site','Корпоративное посещение бассейна','won',null,'Согласована группа на 8 человек'],
        ] as [$name,$phone,$channel,$request,$status,$followUp,$notes]) {
            Lead::query()->updateOrCreate(['phone'=>$phone,'request'=>$request], ['name'=>$name,'channel'=>$channel,'status'=>$status,'assigned_to'=>$manager->id,'follow_up_at'=>$followUp,'notes'=>$notes]);
        }

        $bookingData = [
            ['11111111-1111-4111-8111-111111111111',$demo,'free-swimming',1,2,'confirmed','paid','Семейное посещение'],
            ['22222222-2222-4222-8222-222222222222',$olga,'adult-training',2,1,'confirmed','unpaid','Первое занятие'],
            ['33333333-3333-4333-8333-333333333333',$ivan,'massage',3,1,'new','unpaid','Предпочтительно вечером'],
            ['44444444-4444-4444-8444-444444444444',$maria,'aqua-fitness',4,1,'confirmed','paid','Регулярная тренировка'],
        ];

        foreach ($bookingData as [$uuid,$customer,$serviceSlug,$dayOffset,$people,$status,$paymentStatus,$comment]) {
            $service = Service::query()->where('slug', $serviceSlug)->firstOrFail();
            $slot = ScheduleSlot::query()->where('service_id', $service->id)->whereDate('starts_at', now()->addDays($dayOffset)->toDateString())->orderBy('starts_at')->firstOrFail();
            $booking = Booking::query()->updateOrCreate(['public_id'=>$uuid], [
                'customer_id'=>$customer->id,'service_id'=>$service->id,'schedule_slot_id'=>$slot->id,'trainer_id'=>$slot->trainer_id,
                'people'=>$people,'total'=>$service->price*$people,'status'=>$status,'payment_status'=>$paymentStatus,'comment'=>$comment,
                'source'=>'demo','confirmed_at'=>$status==='confirmed'?now():null,'cancelled_at'=>null,
            ]);
            $slot->update(['booked_count'=>(int) Booking::query()->where('schedule_slot_id',$slot->id)->where('status','!=','cancelled')->sum('people')]);
        }

        $orders = [
            ['ORD-DEMO-001',$demo,'pool-8','paid','completed',1,'DEMO-PAY-001'],
            ['ORD-DEMO-002',$olga,'gift-3000','paid','completed',1,'DEMO-PAY-002'],
            ['ORD-DEMO-003',$ivan,'single-pool','pending','new',2,'DEMO-PAY-003'],
            ['ORD-DEMO-004',$maria,'pool-12','paid','processing',1,'DEMO-PAY-004'],
        ];

        foreach ($orders as [$number,$customer,$productSlug,$paymentStatus,$status,$quantity,$externalId]) {
            $product = Product::query()->where('slug',$productSlug)->firstOrFail();
            $total = (float) $product->price * $quantity;
            $order = Order::query()->updateOrCreate(['number'=>$number], [
                'customer_id'=>$customer->id,'status'=>$status,'payment_status'=>$paymentStatus,'subtotal'=>$total,'total'=>$total,
                'promo_code'=>null,'source'=>'demo','paid_at'=>$paymentStatus==='paid'?now()->subDays(2):null,
            ]);
            $item = OrderItem::query()->updateOrCreate(['order_id'=>$order->id,'product_id'=>$product->id], [
                'name'=>$product->name,'quantity'=>$quantity,'price'=>$product->price,'total'=>$total,
                'ticket_code'=>$paymentStatus==='paid'?'T-'.$number:null,'valid_until'=>$paymentStatus==='paid'?now()->addDays($product->validity_days)->toDateString():null,
                'visits_left'=>$product->visits_count,
            ]);
            Payment::query()->updateOrCreate(['order_id'=>$order->id,'external_id'=>$externalId], [
                'provider'=>'manual','status'=>$paymentStatus==='paid'?'paid':'pending','amount'=>$total,
                'payload'=>['seeded'=>true,'order'=>$number],'paid_at'=>$paymentStatus==='paid'?now()->subDays(2):null,
            ]);

            if ($product->type === 'gift' && $paymentStatus === 'paid') {
                Certificate::query()->updateOrCreate(['serial'=>'GC-'.$number], [
                    'token'=>hash('sha256','certificate-'.$number),'order_item_id'=>$item->id,'customer_id'=>$customer->id,'product_id'=>$product->id,
                    'recipient_name'=>$customer->name,'sender_name'=>'Комплекс Греция','message'=>'Подарок для отдыха и восстановления.',
                    'amount'=>$product->price,'status'=>'active','valid_from'=>today(),'valid_until'=>now()->addDays($product->validity_days)->toDateString(),
                ]);
            }
        }

        Certificate::query()->updateOrCreate(['serial'=>'GC-DEMO-001'], [
            'token'=>hash('sha256','certificate-demo-main'),'customer_id'=>$demo->id,'product_id'=>Product::query()->where('slug','gift-5000')->value('id'),
            'recipient_name'=>$demo->name,'sender_name'=>'Комплекс Греция','message'=>'Пусть каждый визит приносит здоровье и хорошее настроение!',
            'amount'=>5000,'status'=>'active','valid_from'=>today(),'valid_until'=>now()->addMonths(6)->toDateString(),
        ]);
        Certificate::query()->updateOrCreate(['serial'=>'GC-DEMO-USED'], [
            'token'=>hash('sha256','certificate-demo-used'),'customer_id'=>$maria->id,'product_id'=>Product::query()->where('slug','gift-3000')->value('id'),
            'recipient_name'=>$maria->name,'sender_name'=>'Ирина','message'=>'С днём рождения!','amount'=>3000,'status'=>'used',
            'valid_from'=>now()->subMonths(2)->toDateString(),'valid_until'=>now()->addMonths(4)->toDateString(),'redeemed_at'=>now()->subDay(),'redeemed_by'=>$manager->id,
        ]);

        foreach([
            [$demo,now()->subDays(30),'subscription','Плавание по абонементу'],
            [$demo,now()->subDays(14),'subscription','Плавание по абонементу'],
            [$demo,now()->subDays(3),'reception','Свободное плавание'],
            [$olga,now()->subDays(8),'reception','Первичная тренировка'],
            [$maria,now()->subDay(),'certificate','Проход по подарочному сертификату'],
        ] as [$customer,$visitedAt,$source,$notes]) {
            Visit::query()->updateOrCreate(['customer_id'=>$customer->id,'visited_at'=>$visitedAt], ['guests'=>1,'source'=>$source,'notes'=>$notes]);
        }
    }
}
