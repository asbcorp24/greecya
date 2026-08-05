<?php

namespace Database\Seeders;

use App\Models\GalleryAlbum;
use App\Models\HeroSlide;
use App\Models\NewsPost;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            ['Ваше море рядом','25-метровый бассейн с морской водой температурой 32°C.','demo/slide-pool.svg','Подобрать время','/booking',10],
            ['SPA-восстановление','Массаж, сауна, соляная комната и процедуры для полноценного отдыха.','demo/slide-spa.svg','Посмотреть услуги','/#services',20],
            ['Отдых для всей семьи','Свободное плавание и занятия с тренерами для взрослых и детей.','demo/slide-family.svg','Записаться','/booking',30],
        ];
        foreach($slides as [$title,$subtitle,$image,$button,$url,$sort]) {
            HeroSlide::query()->updateOrCreate(['title'=>$title],['subtitle'=>$subtitle,'image_path'=>$image,'button_text'=>$button,'button_url'=>$url,'sort_order'=>$sort,'is_active'=>true]);
        }

        $posts = [
            ['letnee-raspisanie','Летнее расписание бассейна','В летний период мы увеличили количество утренних и вечерних слотов.','Теперь свободное плавание доступно ежедневно. Выберите удобное время в онлайн-расписании и оформите запись без звонка.','demo/news-opening.svg',now()->subDays(2)],
            ['personalnye-trenirovki','Новые программы персональных тренировок','Добавили программы для начинающих взрослых и детей.','Тренер проведёт первичную оценку, поможет поставить дыхание и составит персональный план. Результаты и рекомендации будут доступны в личном кабинете.','demo/news-training.svg',now()->subDays(6)],
            ['nedelya-spa','Неделя SPA-восстановления','Специальные программы массажа, сауны и соляной комнаты.','Соберите собственную программу восстановления после тренировок или рабочей недели. Администратор поможет подобрать последовательность процедур.','demo/news-spa.svg',now()->subDays(12)],
            ['semeinoe-voskresenie','Семейное воскресенье','Каждое воскресенье — дополнительные семейные часы.','Приходите плавать всей семьёй. Для детей доступны занятия с тренером, а взрослые могут выбрать свободное плавание или SPA-процедуры.','demo/news-family.svg',now()->subDays(18)],
        ];
        foreach($posts as [$slug,$title,$excerpt,$body,$image,$published]) {
            NewsPost::query()->updateOrCreate(['slug'=>$slug],['title'=>$title,'excerpt'=>$excerpt,'body'=>$body,'image_path'=>$image,'published_at'=>$published,'is_published'=>true]);
        }

        $pool = GalleryAlbum::query()->updateOrCreate(['slug'=>'bassein-i-trenirovki'],['title'=>'Бассейн и тренировки','description'=>'Бассейн, свободное плавание и занятия с тренерами.','cover_path'=>'demo/gallery-pool-1.svg','is_published'=>true,'sort_order'=>10]);
        $spa = GalleryAlbum::query()->updateOrCreate(['slug'=>'spa-i-otdyh'],['title'=>'SPA и отдых','description'=>'Сауна, соляная комната и пространство для восстановления.','cover_path'=>'demo/gallery-spa-1.svg','is_published'=>true,'sort_order'=>20]);

        foreach([
            [$pool,'demo/gallery-pool-1.svg','25-метровый бассейн','Просторная чаша с морской водой',10],
            [$pool,'demo/gallery-pool-2.svg','Свободное плавание','Комфортная температура воды',20],
            [$pool,'demo/gallery-pool-3.svg','Занятия с тренером','Персональные и групповые программы',30],
            [$spa,'demo/gallery-spa-1.svg','SPA-зона','Пространство для восстановления',10],
            [$spa,'demo/gallery-spa-2.svg','Сауна','Тепло и спокойствие',20],
            [$spa,'demo/gallery-spa-3.svg','Соляная комната','Мягкий комфортный микроклимат',30],
        ] as [$album,$path,$title,$caption,$sort]) {
            $album->photos()->updateOrCreate(['image_path'=>$path],['title'=>$title,'caption'=>$caption,'sort_order'=>$sort,'is_published'=>true]);
        }
    }
}
