<?php

return [
    'roles' => [
        'admin'=>'Администратор','director'=>'Директор','manager'=>'Менеджер','accountant'=>'Бухгалтер','cashier'=>'Кассир','receptionist'=>'Администратор ресепшена','trainer'=>'Тренер','doctor'=>'Врач','marketer'=>'Маркетолог','customer'=>'Клиент',
    ],

    'permissions' => [
        'crm.access'=>['Доступ в CRM','Система'],'dashboard.view'=>['Просмотр главного экрана','Система'],'director.dashboard'=>['Директорский dashboard','Система'],'permissions.manage'=>['Управление ролями и правами','Система'],'audit.view'=>['Просмотр аудита действий','Система'],
        'reception.workspace'=>['Рабочее место ресепшена','Система'],'coach.workspace'=>['Рабочее место тренера','Система'],

        'customers.view'=>['Просмотр клиентов','Клиенты'],'customers.personal_data'=>['Просмотр персональных данных','Клиенты'],'customers.edit'=>['Изменение карточки клиента','Клиенты'],'customers.notes'=>['Комментарии и цели клиента','Клиенты'],'families.view'=>['Просмотр семей','Клиенты'],'families.manage'=>['Управление семьями и согласиями','Клиенты'],
        'bookings.view'=>['Просмотр записей','Записи'],'bookings.manage'=>['Управление записями','Записи'],'bookings.cancel'=>['Отмена записей','Записи'],'visits.adjust'=>['Корректировка и списание посещений','Записи'],
        'memberships.view'=>['Просмотр абонементов','Абонементы'],'memberships.manage'=>['Управление тарифами и абонементами','Абонементы'],'memberships.freeze'=>['Заморозка абонементов','Абонементы'],'wallet.manage'=>['Изменение депозитов и бонусов','Абонементы'],

        'pool.view'=>['Просмотр бассейна и дорожек','Бассейн'],'pool.manage'=>['Управление бассейном и дорожками','Бассейн'],'water.manage'=>['Ввод параметров воды','Бассейн'],'operations.view'=>['Просмотр эксплуатации бассейна','Бассейн'],'operations.manage'=>['Операции, нормативы, реагенты и чек-листы','Бассейн'],'incidents.view'=>['Просмотр инцидентов','Бассейн'],'incidents.manage'=>['Регистрация и разбор инцидентов','Бассейн'],
        'access.view'=>['Просмотр СКУД и проходов','Бассейн'],'access.manage'=>['Управление проходами, картами и шкафчиками','Бассейн'],'medical.view'=>['Просмотр медицинских допусков','Бассейн'],'medical.manage'=>['Изменение медицинских допусков','Бассейн'],
        'swim_school.view'=>['Просмотр школы плавания','Школа плавания'],'swim_school.manage'=>['Группы, занятия, посещаемость и прогресс','Школа плавания'],

        'cash.view'=>['Просмотр кассы','Финансы'],'cash.manage'=>['Кассовые операции','Финансы'],'cash.refund'=>['Возвраты денежных средств','Финансы'],'orders.view'=>['Просмотр заказов','Финансы'],'orders.manage'=>['Изменение заказов и оплат','Финансы'],'reports.view'=>['Просмотр финансовых и управленческих отчётов','Финансы'],'accounting.manage'=>['Обмен с 1С:Бухгалтерией','Финансы'],
        'sales.pos'=>['Первичная продажа на ресепшене и в POS','Продажи'],'products.view'=>['Просмотр товаров и тарифов','Продажи'],'products.manage'=>['Изменение товаров, цен и тарифов','Продажи'],'pricing.manage'=>['Управление динамическими ценами','Продажи'],'certificates.view'=>['Просмотр сертификатов','Продажи'],'certificates.manage'=>['Выдача и изменение сертификатов','Продажи'],'certificates.redeem'=>['Погашение сертификатов','Продажи'],'leads.view'=>['Просмотр лидов','Продажи'],'leads.manage'=>['Управление лидами','Продажи'],
        'inventory.view'=>['Просмотр склада','Склад'],'inventory.manage'=>['Складские движения, партии и сроки годности','Склад'],
        'staff.view'=>['Просмотр персонала и графиков','Персонал'],'staff.manage'=>['Изменение графиков персонала','Персонал'],'payroll.view'=>['Просмотр начислений зарплаты','Персонал'],'payroll.manage'=>['Расчёт и изменение зарплаты','Персонал'],'training.manage'=>['Планы тренировок и прогресс','Персонал'],
        'crm_plus.view'=>['Просмотр задач и коммуникаций CRM','CRM'],'crm_plus.manage'=>['Задачи, договоры и коммуникации CRM','CRM'],'marketing.manage'=>['Рассылки и маркетинг','CRM'],
        'content.manage'=>['Новости, галерея, слайдер и тренеры сайта','Сайт'],'settings.manage'=>['Настройки сайта и реквизиты','Сайт'],'seo.manage'=>['SEO сайта','Сайт'],
    ],

    'defaults' => [
        'admin'=>['*'],'director'=>['*'],
        'manager'=>['crm.access','dashboard.view','audit.view','reception.workspace','customers.view','customers.personal_data','customers.edit','customers.notes','families.view','families.manage','bookings.view','bookings.manage','bookings.cancel','visits.adjust','memberships.view','memberships.manage','memberships.freeze','wallet.manage','pool.view','pool.manage','water.manage','operations.view','operations.manage','incidents.view','incidents.manage','access.view','access.manage','medical.view','medical.manage','swim_school.view','swim_school.manage','cash.view','cash.manage','orders.view','orders.manage','reports.view','sales.pos','products.view','products.manage','pricing.manage','certificates.view','certificates.manage','certificates.redeem','leads.view','leads.manage','inventory.view','inventory.manage','staff.view','staff.manage','payroll.view','training.manage','crm_plus.view','crm_plus.manage','marketing.manage','content.manage','settings.manage','seo.manage'],
        'accountant'=>['crm.access','dashboard.view','customers.view','customers.personal_data','memberships.view','cash.view','cash.manage','cash.refund','orders.view','orders.manage','reports.view','accounting.manage','inventory.view','inventory.manage','staff.view','payroll.view','payroll.manage','products.view','certificates.view'],
        'cashier'=>['crm.access','dashboard.view','reception.workspace','customers.view','customers.personal_data','memberships.view','cash.view','cash.manage','orders.view','orders.manage','sales.pos','certificates.view','certificates.redeem'],
        'receptionist'=>['crm.access','dashboard.view','reception.workspace','customers.view','customers.personal_data','customers.edit','customers.notes','families.view','families.manage','bookings.view','bookings.manage','bookings.cancel','visits.adjust','memberships.view','memberships.freeze','pool.view','access.view','access.manage','medical.view','medical.manage','swim_school.view','incidents.view','incidents.manage','sales.pos'],
        'trainer'=>['crm.access','dashboard.view','coach.workspace','customers.view','customers.notes','bookings.view','pool.view','swim_school.view','swim_school.manage','training.manage','payroll.view'],
        'doctor'=>['crm.access','dashboard.view','customers.view','customers.personal_data','medical.view','medical.manage','access.view','incidents.view'],
        'marketer'=>['crm.access','dashboard.view','customers.view','leads.view','leads.manage','crm_plus.view','crm_plus.manage','marketing.manage','reports.view','content.manage'],
        'customer'=>[],
    ],

    'route_permissions' => [
        'reception.*'=>['*'=>'reception.workspace'],'coach.*'=>['*'=>'coach.workspace'],
        'admin.permissions.*'=>['*'=>'permissions.manage'],'admin.audit.*'=>['*'=>'audit.view'],'admin.dashboard'=>['*'=>'dashboard.view'],'admin.director.*'=>['*'=>'director.dashboard'],
        'admin.accounting.*'=>['*'=>'accounting.manage'],'admin.pricing.*'=>['*'=>'pricing.manage'],'admin.sales.*'=>['*'=>'sales.pos'],
        'admin.customers.index'=>['*'=>'customers.view'],'admin.customers.show'=>['*'=>'customers.view'],'admin.customers.update'=>['*'=>'customers.edit'],'admin.customers.notes.*'=>['*'=>'customers.notes'],'admin.customers.goals.*'=>['*'=>'customers.notes'],
        'admin.families.index'=>['*'=>'families.view'],'admin.families.show'=>['*'=>'families.view'],'admin.families.*'=>['*'=>'families.manage'],
        'admin.swim-school.index'=>['*'=>'swim_school.view'],'admin.swim-school.show'=>['*'=>'swim_school.view'],'admin.swim-school.*'=>['*'=>'swim_school.manage'],
        'admin.medical.index'=>['*'=>'medical.view'],'admin.medical.*'=>['*'=>'medical.manage'],
        'admin.operations.index'=>['*'=>'operations.view'],'admin.operations.*'=>['*'=>'operations.manage'],
        'admin.incidents.index'=>['*'=>'incidents.view'],'admin.incidents.*'=>['*'=>'incidents.manage'],
        'admin.bookings.index'=>['*'=>'bookings.view'],'admin.bookings.update'=>['*'=>'bookings.manage'],'admin.schedule.index'=>['*'=>'bookings.view'],'admin.schedule.*'=>['*'=>'bookings.manage'],
        'admin.memberships.index'=>['*'=>'memberships.view'],'admin.memberships.freeze'=>['*'=>'memberships.freeze'],'admin.memberships.wallet'=>['*'=>'wallet.manage'],'admin.memberships.*'=>['*'=>'memberships.manage'],
        'admin.pool.index'=>['*'=>'pool.view'],'admin.pool.water.store'=>['*'=>'water.manage'],'admin.pool.*'=>['*'=>'pool.manage'],
        'admin.access.index'=>['*'=>'access.view'],'admin.access.medical.store'=>['*'=>'medical.manage'],'admin.access.*'=>['*'=>'access.manage'],
        'admin.finance.index'=>['*'=>'cash.view'],'admin.finance.refunds.*'=>['*'=>'cash.refund'],'admin.finance.*'=>['*'=>'cash.manage'],
        'admin.orders.index'=>['*'=>'orders.view'],'admin.orders.*'=>['*'=>'orders.manage'],'admin.products.index'=>['*'=>'products.view'],'admin.products.*'=>['*'=>'products.manage'],
        'admin.certificates.index'=>['*'=>'certificates.view'],'admin.certificates.scan'=>['*'=>'certificates.view'],'admin.certificates.redeem'=>['*'=>'certificates.redeem'],'admin.certificates.*'=>['*'=>'certificates.manage'],
        'admin.leads.index'=>['*'=>'leads.view'],'admin.leads.*'=>['*'=>'leads.manage'],'admin.inventory.index'=>['*'=>'inventory.view'],'admin.inventory.*'=>['*'=>'inventory.manage'],
        'admin.staff.index'=>['*'=>'staff.view'],'admin.staff.payroll.*'=>['*'=>'payroll.manage'],'admin.staff.*'=>['*'=>'staff.manage'],'admin.training-plans.*'=>['*'=>'training.manage'],
        'admin.crm-plus.index'=>['*'=>'crm_plus.view'],'admin.crm-plus.campaigns.*'=>['*'=>'marketing.manage'],'admin.crm-plus.*'=>['*'=>'crm_plus.manage'],'admin.reports.*'=>['*'=>'reports.view'],
        'admin.news.*'=>['*'=>'content.manage'],'admin.gallery.*'=>['*'=>'content.manage'],'admin.slides.*'=>['*'=>'content.manage'],'admin.trainers.*'=>['*'=>'content.manage'],'admin.settings.*'=>['*'=>'settings.manage'],'admin.seo.*'=>['*'=>'seo.manage'],
    ],
];
