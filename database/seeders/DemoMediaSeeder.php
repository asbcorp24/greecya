<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoMediaSeeder extends Seeder
{
    public function run(): void
    {
        $media = [
            'og-default.svg' => ['Комплекс Греция', 'Бассейн и SPA', '#043b52', '#1aa7c4'],
            'slide-pool.svg' => ['Ваше море рядом', '25-метровый бассейн с морской водой', '#03445d', '#3fc3d8'],
            'slide-spa.svg' => ['SPA-восстановление', 'Массаж, сауна и процедуры для отдыха', '#513d69', '#d78da2'],
            'slide-family.svg' => ['Отдых для всей семьи', 'Плавание, тренировки и хорошее настроение', '#075d67', '#83d4b5'],
            'trainer-anna.svg' => ['Анна Соколова', 'Дети и начинающие взрослые', '#075b78', '#75d5e7'],
            'trainer-mikhail.svg' => ['Михаил Волков', 'Техника и персональные занятия', '#284f73', '#8cb8e8'],
            'trainer-elena.svg' => ['Елена Орлова', 'Аквафитнес и восстановление', '#56527d', '#c2a9dc'],
            'news-opening.svg' => ['Летнее расписание', 'Больше времени для плавания', '#075b78', '#59c7dd'],
            'news-training.svg' => ['Персональные тренировки', 'Новые программы для взрослых и детей', '#315b87', '#8eb8eb'],
            'news-spa.svg' => ['Неделя SPA', 'Специальные комплексные программы', '#6a496c', '#d79dc4'],
            'news-family.svg' => ['Семейное воскресенье', 'Отдыхайте вместе', '#17685f', '#84d2ae'],
            'gallery-pool-1.svg' => ['Бассейн', 'Просторная 25-метровая чаша', '#075b78', '#67cfe0'],
            'gallery-pool-2.svg' => ['Свободное плавание', 'Комфортная температура воды', '#0b6d83', '#99e0e7'],
            'gallery-pool-3.svg' => ['Тренировка', 'Занятия с профессиональным тренером', '#315b87', '#8eb8eb'],
            'gallery-spa-1.svg' => ['SPA-зона', 'Пространство для восстановления', '#5c486f', '#c3a8db'],
            'gallery-spa-2.svg' => ['Сауна', 'Тепло и спокойствие', '#744a4a', '#e4a27d'],
            'gallery-spa-3.svg' => ['Соляная комната', 'Мягкий микроклимат', '#7a6b52', '#d8c28c'],
        ];

        foreach ($media as $file => [$title, $subtitle, $start, $end]) {
            Storage::disk('public')->put('demo/'.$file, $this->svg($title, $subtitle, $start, $end));
        }
    }

    private function svg(string $title, string $subtitle, string $start, string $end): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $subtitle = htmlspecialchars($subtitle, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900" viewBox="0 0 1600 900">
<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="{$start}"/><stop offset="1" stop-color="{$end}"/></linearGradient><pattern id="p" width="90" height="90" patternUnits="userSpaceOnUse"><circle cx="12" cy="12" r="3" fill="#fff" opacity=".18"/></pattern></defs>
<rect width="1600" height="900" fill="url(#g)"/><rect width="1600" height="900" fill="url(#p)"/>
<circle cx="1380" cy="120" r="310" fill="#fff" opacity=".08"/><circle cx="180" cy="820" r="260" fill="none" stroke="#fff" stroke-width="70" opacity=".08"/>
<path d="M0 690 C320 600 520 810 820 700 S1300 570 1600 710 V900 H0Z" fill="#fff" opacity=".12"/>
<text x="120" y="390" fill="#fff" font-family="Georgia,serif" font-size="82">{$title}</text>
<text x="125" y="470" fill="#fff" opacity=".82" font-family="Arial,sans-serif" font-size="34">{$subtitle}</text>
<text x="125" y="590" fill="#fff" opacity=".95" font-family="Georgia,serif" font-size="74">Γ</text>
</svg>
SVG;
    }
}
