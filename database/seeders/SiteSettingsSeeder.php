<?php

namespace Database\Seeders;

use App\Models\SeoPage;
use App\Models\SiteSetting;
use App\Support\SiteSettings;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['site','site_name','Полное название сайта','text','Комплекс Греция',10],
            ['site','site_short_name','Короткое название','text','Греция',20],
            ['site','site_tagline','Подпись под логотипом','text','бассейн · SPA',30],
            ['site','site_description','Описание комплекса','textarea','Бассейн с морской водой и SPA-комплекс в Васильево.',40],
            ['site','footer_text','Текст в футере','textarea','Отдых, движение и восстановление в бассейне с морской водой и SPA-пространстве.',50],
            ['site','site_logo','Логотип','image',null,60],
            ['site','favicon','Favicon','image',null,70],
            ['site','default_og_image','Изображение по умолчанию для соцсетей','image','demo/og-default.svg',80],
            ['site','seo_default_title','SEO title по умолчанию','text','Комплекс Греция — бассейн и SPA',90],
            ['site','seo_default_description','SEO description по умолчанию','textarea','Бассейн с морской водой, тренировки и SPA-процедуры в комплексе «Греция».',100],
            ['site','seo_default_keywords','SEO keywords по умолчанию','textarea','бассейн, SPA, Васильево, плавание, массаж, сауна',110],
            ['site','seo_allow_indexing','Разрешить индексацию сайта','boolean','1',120],
            ['promo','promo_enabled','Показывать промо-панель','boolean','1',10],
            ['promo','promo_text','Текст промо-панели','textarea','Подпишитесь и напишите слово «БАССЕЙН» — сеанс соляной комнаты в подарок',20],
            ['contacts','phone','Основной телефон','text','+7 (965) 587-77-99',10],
            ['contacts','phone_alt','Дополнительный телефон','text','+7 (843) 000-00-00',20],
            ['contacts','email','Общий email','email','info@greecya.ru',30],
            ['contacts','booking_email','Email отдела записи','email','booking@greecya.ru',40],
            ['contacts','address_full','Полный адрес','textarea','Республика Татарстан, Зеленодольский район, пгт Васильево, ул. Ленина, 57а',50],
            ['contacts','address_short','Короткий адрес','text','пгт Васильево, ул. Ленина, 57а',60],
            ['contacts','city','Населённый пункт','text','Васильево',70],
            ['schedule','working_hours_weekdays','Режим работы в будни','text','Пн–Пт: 07:00–22:00',10],
            ['schedule','working_hours_weekends','Режим работы в выходные','text','Сб–Вс: 08:00–21:00',20],
            ['schedule','map_url','Ссылка на карту','url','https://yandex.ru/maps/?text=Васильево%20Ленина%2057а',30],
            ['schedule','map_embed','Код или ссылка для встраивания карты','textarea','',40],
            ['social','social_handle','Короткое имя сообщества','text','kompleksgrecia',10],
            ['social','social_vk','ВКонтакте','url','https://vk.com/kompleksgrecia',20],
            ['social','social_telegram','Telegram','url','https://t.me/kompleksgrecia',30],
            ['social','social_whatsapp','WhatsApp','url','https://wa.me/79655877799',40],
            ['social','social_instagram','Instagram','url','',50],
            ['company','company_name','Публичное название организации','text','Комплекс «Греция»',10],
            ['company','legal_name','Юридическое наименование','text','ООО «Комплекс Греция»',20],
            ['company','inn','ИНН','text','0000000000',30],
            ['company','kpp','КПП','text','000000000',40],
            ['company','ogrn','ОГРН','text','0000000000000',50],
            ['company','legal_address','Юридический адрес','textarea','Республика Татарстан, Зеленодольский район, пгт Васильево',60],
            ['company','bank_details','Банковские реквизиты','textarea','Заполните расчётный счёт, БИК, банк и корреспондентский счёт.',70],
            ['company','director','Руководитель','text','ФИО руководителя',80],
        ];

        foreach ($settings as [$group,$key,$label,$type,$value,$sort]) {
            SiteSetting::query()->updateOrCreate(['key'=>$key], [
                'group'=>$group,'label'=>$label,'type'=>$type,'value'=>$value,'is_public'=>true,'sort_order'=>$sort,
            ]);
        }

        $schema = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'HealthClub',
            'name' => 'Комплекс Греция',
            'description' => 'Бассейн с морской водой и SPA-комплекс.',
            'telephone' => '+7 (965) 587-77-99',
            'email' => 'info@greecya.ru',
            'address' => ['@type'=>'PostalAddress','streetAddress'=>'ул. Ленина, 57а','addressLocality'=>'Васильево','addressRegion'=>'Республика Татарстан','addressCountry'=>'RU'],
            'url' => config('app.url'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $pages = [
            ['home','Главная','Комплекс Греция — бассейн с морской водой и SPA','25-метровый бассейн, обучение плаванию, массаж, сауна и SPA-процедуры в Васильево.','бассейн Васильево, SPA Татарстан, обучение плаванию','index,follow',$schema],
            ['booking.index','Онлайн-запись','Онлайн-запись в бассейн и SPA — Комплекс Греция','Выберите услугу, дату и свободное время для посещения бассейна, тренировки или SPA.','запись в бассейн, расписание плавания','index,follow',null],
            ['catalog.index','Билеты и абонементы','Билеты, абонементы и сертификаты — Комплекс Греция','Купите разовое посещение, абонемент или подарочный сертификат с QR-кодом.','абонемент бассейн, подарочный сертификат SPA','index,follow',null],
            ['news.index','Новости','Новости бассейна и SPA — Комплекс Греция','Новости, акции, изменения расписания и советы тренеров комплекса «Греция».','новости бассейна, акции SPA','index,follow',null],
            ['news.show','Страница новости',null,null,null,'index,follow',null],
            ['gallery.index','Фотогалерея','Фотогалерея комплекса Греция','Фотографии бассейна, SPA-зон, тренировок и мероприятий.','фото бассейна, фото SPA','index,follow',null],
            ['gallery.show','Фотоальбом',null,null,null,'index,follow',null],
            ['privacy','Политика конфиденциальности','Политика конфиденциальности — Комплекс Греция','Правила обработки персональных данных посетителей сайта.','', 'index,follow',null],
            ['offer','Публичная оферта','Публичная оферта — Комплекс Греция','Условия покупки билетов, абонементов и сертификатов.','', 'index,follow',null],
            ['login','Вход','Вход в личный кабинет — Комплекс Греция','Вход для клиентов и сотрудников комплекса.','', 'noindex,nofollow',null],
            ['account.register','Регистрация клиента','Регистрация личного кабинета — Комплекс Греция','Создание личного кабинета клиента.','', 'noindex,nofollow',null],
            ['account.dashboard','Личный кабинет','Личный кабинет клиента — Комплекс Греция','Записи, сертификаты, абонементы и планы тренировок.','', 'noindex,nofollow',null],
            ['certificate.verify','Проверка сертификата','Проверка сертификата — Комплекс Греция','Проверка подлинности и срока действия сертификата.','', 'noindex,nofollow',null],
        ];

        foreach ($pages as [$route,$name,$title,$description,$keywords,$robots,$json]) {
            SeoPage::query()->updateOrCreate(['route_name'=>$route], [
                'page_name'=>$name,'meta_title'=>$title,'meta_description'=>$description,'meta_keywords'=>$keywords,
                'og_title'=>$title,'og_description'=>$description,'robots'=>$robots,'schema_json'=>$json,'is_active'=>true,
            ]);
        }

        SiteSettings::flush();
    }
}
