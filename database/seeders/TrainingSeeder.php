<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Trainer;
use App\Models\TrainingPlan;
use App\Models\TrainingProgressEntry;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        $today = today();
        $demo = Customer::query()->where('email', 'client@greecya.local')->firstOrFail();
        $olga = Customer::query()->where('email', 'olga@example.com')->firstOrFail();
        $anna = Trainer::query()->where('name', 'Анна Соколова')->firstOrFail();
        $mikhail = Trainer::query()->where('name', 'Михаил Волков')->firstOrFail();

        $plan = TrainingPlan::query()->updateOrCreate(
            ['customer_id' => $demo->id, 'title' => 'Уверенное плавание: 4 недели'],
            [
                'trainer_id' => $anna->id,
                'goal' => 'Улучшить дыхание и технику кроля',
                'description' => 'Персональный план для уверенного и спокойного плавания.',
                'schedule_text' => '2 занятия в неделю по 45–60 минут',
                'recommendations' => 'Сохраняйте ровный темп, не задерживайте дыхание и записывайте дистанцию.',
                'starts_on' => $today->toDateString(),
                'ends_on' => $today->copy()->addWeeks(4)->toDateString(),
                'status' => 'active',
            ]
        );

        foreach ([
            ['Занятие 1', 'Разминка и скольжение', null, null, 15, null, 'Контроль положения тела', 10],
            ['Занятие 1', 'Кроль с доской', 4, '100 м', null, 400, 'Выдох в воду', 20],
            ['Занятие 2', 'Кроль в спокойном темпе', null, null, null, 800, 'Ровный ритм', 30],
            ['Занятие 2', 'Упражнения на дыхание', 6, '25 м', null, 150, 'Вдох в сторону', 40],
            ['Занятие 3', 'Непрерывное плавание', null, null, 20, null, 'Без ускорения', 50],
        ] as [$day, $exercise, $sets, $reps, $duration, $distance, $notes, $sort]) {
            $plan->items()->updateOrCreate(
                ['exercise' => $exercise],
                [
                    'day_label' => $day,
                    'sets' => $sets,
                    'reps' => $reps,
                    'duration_minutes' => $duration,
                    'distance_meters' => $distance,
                    'notes' => $notes,
                    'sort_order' => $sort,
                ]
            );
        }

        $plan2 = TrainingPlan::query()->updateOrCreate(
            ['customer_id' => $olga->id, 'title' => 'Постановка техники кроля'],
            [
                'trainer_id' => $mikhail->id,
                'goal' => 'Научиться проплывать 500 метров без остановки',
                'description' => 'Базовая программа на шесть недель.',
                'schedule_text' => 'Понедельник и четверг, 19:00',
                'recommendations' => 'Выполняйте разминку плечевого пояса перед каждым занятием.',
                'starts_on' => $today->toDateString(),
                'ends_on' => $today->copy()->addWeeks(6)->toDateString(),
                'status' => 'active',
            ]
        );

        foreach ([
            ['Неделя 1', 'Работа ног с доской', 6, '50 м', null, 300, 'Носки вытянуты', 10],
            ['Неделя 1', 'Скольжение после отталкивания', 8, '15 м', null, 120, 'Не поднимать голову', 20],
            ['Неделя 2', 'Кроль с дыханием на 3 гребка', 5, '100 м', null, 500, 'Ровный выдох', 30],
        ] as [$day, $exercise, $sets, $reps, $duration, $distance, $notes, $sort]) {
            $plan2->items()->updateOrCreate(
                ['exercise' => $exercise],
                [
                    'day_label' => $day,
                    'sets' => $sets,
                    'reps' => $reps,
                    'duration_minutes' => $duration,
                    'distance_meters' => $distance,
                    'notes' => $notes,
                    'sort_order' => $sort,
                ]
            );
        }

        foreach ([
            [$demo, $plan, $today->copy()->subDays(21)->toDateString(), 72.4, 600, 1200, 'Было тяжело держать дыхание.', 'Снизить темп и увеличить выдох.'],
            [$demo, $plan, $today->copy()->subDays(14)->toDateString(), 72.0, 800, 1500, 'Получилось проплыть без длинной остановки.', 'Хороший прогресс, добавить 100 метров.'],
            [$demo, $plan, $today->copy()->subDays(7)->toDateString(), 71.8, 1000, 1800, 'Ровный темп, самочувствие хорошее.', 'Продолжать по плану.'],
            [$olga, $plan2, $today->copy()->subDays(5)->toDateString(), null, 350, 1100, 'Первое самостоятельное занятие.', 'Следить за положением головы.'],
        ] as [$customer, $trainingPlan, $date, $weight, $distance, $seconds, $note, $comment]) {
            $entry = TrainingProgressEntry::query()
                ->where('customer_id', $customer->id)
                ->where('training_plan_id', $trainingPlan->id)
                ->whereDate('recorded_on', $date)
                ->first();

            $data = [
                'customer_id' => $customer->id,
                'training_plan_id' => $trainingPlan->id,
                'recorded_on' => $date,
                'weight' => $weight,
                'distance_meters' => $distance,
                'duration_seconds' => $seconds,
                'note' => $note,
                'coach_comment' => $comment,
            ];

            $entry ? $entry->update($data) : TrainingProgressEntry::query()->create($data);
        }
    }
}
