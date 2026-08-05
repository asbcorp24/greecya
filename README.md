# Комплекс «Греция»

Laravel 9 + Bootstrap 5: публичный сайт бассейна и SPA, онлайн-запись, продажи, CRM и личный кабинет клиента.

## Возможности

- слайдер главной страницы с загрузкой фотографий из CRM;
- новости с главным фото и отдельными страницами;
- фотогалерея с альбомами и пакетной загрузкой изображений;
- CRUD тренеров и связь с расписанием;
- билеты, абонементы и подарочные сертификаты;
- печатный сертификат формата A4 с QR-кодом;
- проверка и погашение QR-кода на стойке администратора;
- личный кабинет: записи, билеты, сертификаты, посещения, планы и прогресс тренировок;
- CRM для записей, клиентов, лидов, заказов, расписания, контента и тренировочных планов.

## Установка

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

После обновления существующей установки:

```bash
git pull origin feature/mvp-pool-spa-crm
php artisan migrate
php artisan storage:link
php artisan optimize:clear
```

## Тестовые учётные записи

- администратор: `admin@greecya.local` / `ChangeMe123!`
- менеджер: `manager@greecya.local` / `ChangeMe123!`
- клиент: `client@greecya.local` / `ChangeMe123!`

Перед production замените пароли, реквизиты, цены, расписание и демонстрационные данные.
