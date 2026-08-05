<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(['email' => 'admin@greecya.local'], ['name' => 'Администратор', 'phone' => '+7 965 587-77-99', 'role' => 'admin', 'password' => Hash::make('ChangeMe123!')]);
        User::query()->updateOrCreate(['email' => 'manager@greecya.local'], ['name' => 'Менеджер', 'role' => 'manager', 'password' => Hash::make('ChangeMe123!')]);

        $services = [
            ['slug'=>'free-swimming','name'=>'Свободное плавание','category'=>'pool','description'=>'Самостоятельное посещение 25-метрового бассейна с морской водой.','duration_minutes'=>60,'price'=>700,'capacity'=>20,'requires_trainer'=>false,'sort_order'=>10],
            ['slug'=>'adult-training','name'=>'Обучение плаванию для взрослых','category'=>'training','description'=>'Индивидуальная тренировка с постановкой техники и дыхания.','duration_minutes'=>60,'price'=>1800,'capacity'=>1,'requires_trainer'=>true,'sort_order'=>20],
            ['slug'=>'kids-training','name'=>'Обучение плаванию для детей','category'=>'training','description'=>'Безопасное знакомство с водой и обучение под руководством тренера.','duration_minutes'=>45,'price'=>1600,'capacity'=>1,'requires_trainer'=>true,'sort_order'=>30],
            ['slug'=>'massage','name'=>'Массаж','category'=>'spa','description'=>'Расслабляющая и восстановительная процедура по предварительной записи.','duration_minutes'=>60,'price'=>2200,'capacity'=>1,'requires_trainer'=>false,'sort_order'=>40],
            ['slug'=>'pressotherapy','name'=>'Прессотерапия','category'=>'spa','description'=>'Аппаратная процедура для отдыха, лёгкости и восстановления.','duration_minutes'=>40,'price'=>1000,'capacity'=>1,'requires_trainer'=>false,'sort_order'=>50],
            ['slug'=>'charcot','name'=>'Душ Шарко','category'=>'spa','description'=>'Тонизирующая водная процедура по предварительной консультации.','duration_minutes'=>20,'price'=>800,'capacity'=>1,'requires_trainer'=>false,'sort_order'=>60],
            ['slug'=>'sauna','name'=>'Сауна','category'=>'spa','description'=>'Тёплое пространство для отдыха после плавания или отдельного посещения.','duration_minutes'=>60,'price'=>1500,'capacity'=>6,'requires_trainer'=>false,'sort_order'=>70],
            ['slug'=>'salt-room','name'=>'Соляная комната','category'=>'salt','description'=>'Спокойный сеанс в комфортном микроклимате соляной комнаты.','duration_minutes'=>40,'price'=>500,'capacity'=>6,'requires_trainer'=>false,'sort_order'=>80],
        ];
        foreach ($services as $data) Service::query()->updateOrCreate(['slug'=>$data['slug']], $data + ['is_active'=>true]);

        $trainers = collect([
            Trainer::query()->updateOrCreate(['name'=>'Тренер по плаванию №1'], ['specialization'=>'Дети и начинающие взрослые','is_active'=>true]),
            Trainer::query()->updateOrCreate(['name'=>'Тренер по плаванию №2'], ['specialization'=>'Техника плавания и персональные занятия','is_active'=>true]),
        ]);

        foreach (Service::query()->where('is_active', true)->get() as $service) {
            for ($day = 1; $day <= 14; $day++) {
                foreach ([9, 11, 14, 16, 18, 20] as $index => $hour) {
                    $startsAt = now()->addDays($day)->setTime($hour, 0, 0);
                    $trainer = $service->requires_trainer ? $trainers[$index % $trainers->count()] : null;
                    ScheduleSlot::query()->firstOrCreate(
                        ['service_id'=>$service->id,'trainer_id'=>$trainer?->id,'starts_at'=>$startsAt],
                        ['ends_at'=>(clone $startsAt)->addMinutes($service->duration_minutes),'capacity'=>$service->capacity,'booked_count'=>0,'status'=>'open']
                    );
                }
            }
        }

        $products = [
            ['slug'=>'single-pool','name'=>'Разовое посещение бассейна','type'=>'ticket','description'=>'Одно посещение свободного плавания по предварительной записи.','price'=>700,'visits_count'=>1,'validity_days'=>30,'sort_order'=>10],
            ['slug'=>'pool-8','name'=>'Абонемент на 8 посещений','type'=>'subscription','description'=>'Восемь посещений бассейна в течение 60 дней.','price'=>4800,'visits_count'=>8,'validity_days'=>60,'sort_order'=>20],
            ['slug'=>'gift-3000','name'=>'Подарочный сертификат','type'=>'gift','description'=>'Сертификат на услуги бассейна и SPA-комплекса.','price'=>3000,'visits_count'=>null,'validity_days'=>180,'sort_order'=>30],
        ];
        foreach ($products as $data) Product::query()->updateOrCreate(['slug'=>$data['slug']], $data + ['is_active'=>true]);
    }
}
