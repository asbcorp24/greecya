<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(['email'=>'admin@greecya.local'],['name'=>'Администратор','phone'=>'+7 965 587-77-99','role'=>'admin','password'=>Hash::make('ChangeMe123!')]);
        User::query()->updateOrCreate(['email'=>'manager@greecya.local'],['name'=>'Менеджер','phone'=>'+7 900 111-22-33','role'=>'manager','password'=>Hash::make('ChangeMe123!')]);

        $services = [
            ['free-swimming','Свободное плавание','pool','Самостоятельное посещение 25-метрового бассейна с морской водой.',60,700,20,false,10],
            ['adult-training','Обучение плаванию для взрослых','training','Индивидуальная тренировка с постановкой техники и дыхания.',60,1800,1,true,20],
            ['kids-training','Обучение плаванию для детей','training','Безопасное знакомство с водой и обучение под руководством тренера.',45,1600,1,true,30],
            ['aqua-fitness','Аквафитнес','training','Групповая тренировка в воде для тонуса и хорошего самочувствия.',50,900,12,true,35],
            ['massage','Массаж','spa','Расслабляющая и восстановительная процедура.',60,2200,1,false,40],
            ['pressotherapy','Прессотерапия','spa','Аппаратная процедура для отдыха и восстановления.',40,1000,1,false,50],
            ['charcot','Душ Шарко','spa','Тонизирующая водная процедура.',20,800,1,false,60],
            ['sauna','Сауна','spa','Тёплое пространство для отдыха после плавания.',60,1500,6,false,70],
            ['salt-room','Соляная комната','salt','Сеанс в комфортном микроклимате соляной комнаты.',40,500,6,false,80],
        ];
        foreach($services as [$slug,$name,$category,$description,$duration,$price,$capacity,$trainer,$sort]) {
            Service::query()->updateOrCreate(['slug'=>$slug],['name'=>$name,'category'=>$category,'description'=>$description,'duration_minutes'=>$duration,'price'=>$price,'capacity'=>$capacity,'requires_trainer'=>$trainer,'sort_order'=>$sort,'is_active'=>true]);
        }

        $trainers = collect([
            Trainer::query()->updateOrCreate(['name'=>'Анна Соколова'],['specialization'=>'Дети и начинающие взрослые','phone'=>'+7 900 100-10-10','bio'=>'Тренер по обучению плаванию и адаптации к воде.','photo_path'=>'demo/trainer-anna.svg','experience_years'=>8,'sort_order'=>10,'is_active'=>true]),
            Trainer::query()->updateOrCreate(['name'=>'Михаил Волков'],['specialization'=>'Техника плавания и персональные занятия','phone'=>'+7 900 200-20-20','bio'=>'Специалист по постановке техники и индивидуальным программам.','photo_path'=>'demo/trainer-mikhail.svg','experience_years'=>11,'sort_order'=>20,'is_active'=>true]),
            Trainer::query()->updateOrCreate(['name'=>'Елена Орлова'],['specialization'=>'Аквафитнес и восстановление','phone'=>'+7 900 300-30-30','bio'=>'Проводит групповые тренировки и мягкие восстановительные программы.','photo_path'=>'demo/trainer-elena.svg','experience_years'=>7,'sort_order'=>30,'is_active'=>true]),
        ]);

        foreach(Service::query()->where('is_active',true)->get() as $service) {
            for($day=1;$day<=21;$day++) {
                foreach([8,10,12,15,17,19,21] as $index=>$hour) {
                    $startsAt=now()->addDays($day)->setTime($hour,0,0);
                    $trainer=$service->requires_trainer?$trainers[$index%$trainers->count()]:null;
                    ScheduleSlot::query()->updateOrCreate(
                        ['service_id'=>$service->id,'trainer_id'=>$trainer?->id,'starts_at'=>$startsAt],
                        ['ends_at'=>(clone $startsAt)->addMinutes($service->duration_minutes),'capacity'=>$service->capacity,'status'=>'open']
                    );
                }
            }
        }

        $products = [
            ['single-pool','Разовое посещение бассейна','ticket','Одно посещение свободного плавания.',700,1,30,10],
            ['pool-8','Абонемент на 8 посещений','subscription','Восемь посещений бассейна в течение 60 дней.',4800,8,60,20],
            ['pool-12','Абонемент на 12 посещений','subscription','Двенадцать посещений бассейна в течение 90 дней.',6600,12,90,30],
            ['gift-3000','Подарочный сертификат 3 000 ₽','gift','Печатный и электронный сертификат с QR-кодом.',3000,null,180,40],
            ['gift-5000','Подарочный сертификат 5 000 ₽','gift','Сертификат на любые услуги бассейна и SPA.',5000,null,180,50],
        ];
        foreach($products as [$slug,$name,$type,$description,$price,$visits,$days,$sort]) {
            Product::query()->updateOrCreate(['slug'=>$slug],['name'=>$name,'type'=>$type,'description'=>$description,'price'=>$price,'visits_count'=>$visits,'validity_days'=>$days,'sort_order'=>$sort,'is_active'=>true]);
        }
    }
}
