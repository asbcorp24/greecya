<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\Trainer;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(['email'=>'admin@greecya.local'],['name'=>'Администратор','phone'=>'+7 965 587-77-99','role'=>'admin','password'=>Hash::make('ChangeMe123!')]);
        User::query()->updateOrCreate(['email'=>'manager@greecya.local'],['name'=>'Менеджер','role'=>'manager','password'=>Hash::make('ChangeMe123!')]);
        $services=[
            ['slug'=>'free-swimming','name'=>'Свободное плавание','category'=>'pool','description'=>'Самостоятельное посещение 25-метрового бассейна с морской водой.','duration_minutes'=>60,'price'=>700,'capacity'=>20,'requires_trainer'=>false,'sort_order'=>10],
            ['slug'=>'adult-training','name'=>'Обучение плаванию для взрослых','category'=>'training','description'=>'Индивидуальная тренировка с постановкой техники и дыхания.','duration_minutes'=>60,'price'=>1800,'capacity'=>1,'requires_trainer'=>true,'sort_order'=>20],
            ['slug'=>'kids-training','name'=>'Обучение плаванию для детей','category'=>'training','description'=>'Безопасное знакомство с водой и обучение под руководством тренера.','duration_minutes'=>45,'price'=>1600,'capacity'=>1,'requires_trainer'=>true,'sort_order'=>30],
            ['slug'=>'massage','name'=>'Массаж','category'=>'spa','description'=>'Расслабляющая и восстановительная процедура.','duration_minutes'=>60,'price'=>2200,'capacity'=>1,'requires_trainer'=>false,'sort_order'=>40],
            ['slug'=>'pressotherapy','name'=>'Прессотерапия','category'=>'spa','description'=>'Аппаратная процедура для отдыха и восстановления.','duration_minutes'=>40,'price'=>1000,'capacity'=>1,'requires_trainer'=>false,'sort_order'=>50],
            ['slug'=>'charcot','name'=>'Душ Шарко','category'=>'spa','description'=>'Тонизирующая водная процедура.','duration_minutes'=>20,'price'=>800,'capacity'=>1,'requires_trainer'=>false,'sort_order'=>60],
            ['slug'=>'sauna','name'=>'Сауна','category'=>'spa','description'=>'Тёплое пространство для отдыха после плавания.','duration_minutes'=>60,'price'=>1500,'capacity'=>6,'requires_trainer'=>false,'sort_order'=>70],
            ['slug'=>'salt-room','name'=>'Соляная комната','category'=>'salt','description'=>'Сеанс в комфортном микроклимате соляной комнаты.','duration_minutes'=>40,'price'=>500,'capacity'=>6,'requires_trainer'=>false,'sort_order'=>80],
        ];
        foreach($services as $data) Service::query()->updateOrCreate(['slug'=>$data['slug']],$data+['is_active'=>true]);
        $trainers=collect([
            Trainer::query()->updateOrCreate(['name'=>'Анна Соколова'],['specialization'=>'Дети и начинающие взрослые','experience_years'=>8,'sort_order'=>10,'bio'=>'Тренер по обучению плаванию и адаптации к воде.','is_active'=>true]),
            Trainer::query()->updateOrCreate(['name'=>'Михаил Волков'],['specialization'=>'Техника плавания и персональные занятия','experience_years'=>11,'sort_order'=>20,'bio'=>'Специалист по постановке техники и индивидуальным программам.','is_active'=>true]),
        ]);
        foreach(Service::query()->where('is_active',true)->get() as $service){for($day=1;$day<=14;$day++){foreach([9,11,14,16,18,20] as $index=>$hour){$startsAt=now()->addDays($day)->setTime($hour,0,0);$trainer=$service->requires_trainer?$trainers[$index%$trainers->count()]:null;ScheduleSlot::query()->firstOrCreate(['service_id'=>$service->id,'trainer_id'=>$trainer?->id,'starts_at'=>$startsAt],['ends_at'=>(clone $startsAt)->addMinutes($service->duration_minutes),'capacity'=>$service->capacity,'booked_count'=>0,'status'=>'open']);}}}
        $products=[
            ['slug'=>'single-pool','name'=>'Разовое посещение бассейна','type'=>'ticket','description'=>'Одно посещение свободного плавания.','price'=>700,'visits_count'=>1,'validity_days'=>30,'sort_order'=>10],
            ['slug'=>'pool-8','name'=>'Абонемент на 8 посещений','type'=>'subscription','description'=>'Восемь посещений бассейна в течение 60 дней.','price'=>4800,'visits_count'=>8,'validity_days'=>60,'sort_order'=>20],
            ['slug'=>'gift-3000','name'=>'Подарочный сертификат','type'=>'gift','description'=>'Печатный и электронный сертификат с QR-кодом.','price'=>3000,'visits_count'=>null,'validity_days'=>180,'sort_order'=>30],
        ];
        foreach($products as $data) Product::query()->updateOrCreate(['slug'=>$data['slug']],$data+['is_active'=>true]);
        $customer=Customer::query()->updateOrCreate(['email'=>'client@greecya.local'],['name'=>'Демо-клиент','phone'=>'+7 900 000-00-00','source'=>'demo']);
        User::query()->updateOrCreate(['email'=>'client@greecya.local'],['customer_id'=>$customer->id,'name'=>$customer->name,'phone'=>$customer->phone,'role'=>'customer','password'=>Hash::make('ChangeMe123!')]);
        $gift=Product::query()->where('slug','gift-3000')->first();
        Certificate::query()->firstOrCreate(['serial'=>'GC-DEMO-001'],['token'=>Str::random(48),'customer_id'=>$customer->id,'product_id'=>$gift?->id,'recipient_name'=>$customer->name,'sender_name'=>'Комплекс Греция','message'=>'Пусть каждый визит приносит здоровье и хорошее настроение!','amount'=>3000,'status'=>'active','valid_from'=>today(),'valid_until'=>now()->addMonths(6)->toDateString()]);
        $plan=TrainingPlan::query()->firstOrCreate(['customer_id'=>$customer->id,'title'=>'Уверенное плавание: 4 недели'],['trainer_id'=>$trainers->first()->id,'goal'=>'Улучшить дыхание и технику кроля','description'=>'Демонстрационный персональный план.','schedule_text'=>'2 занятия в неделю по 45–60 минут','recommendations'=>'Сохраняйте ровный темп и записывайте дистанцию.','starts_on'=>today(),'ends_on'=>now()->addWeeks(4)->toDateString(),'status'=>'active']);
        if($plan->items()->count()===0)$plan->items()->createMany([
            ['day_label'=>'Занятие 1','exercise'=>'Разминка и скольжение','duration_minutes'=>15,'notes'=>'Контроль положения тела','sort_order'=>10],
            ['day_label'=>'Занятие 1','exercise'=>'Кроль с доской','distance_meters'=>400,'notes'=>'Выдох в воду','sort_order'=>20],
            ['day_label'=>'Занятие 2','exercise'=>'Кроль в спокойном темпе','distance_meters'=>800,'notes'=>'Ровный ритм','sort_order'=>30],
        ]);
    }
}
