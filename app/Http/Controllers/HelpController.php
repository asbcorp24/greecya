<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $roles = config('help.roles', []);
        $common = config('help.common', []);
        $roles = $this->withAdminMenuGuide($roles);
        $roles = $this->withPosRoleGuide($roles);

        $canBrowseAll = in_array($user->role, ['admin', 'director', 'manager'], true);
        $selectedRole = $canBrowseAll
            ? (string) $request->query('role', $user->role)
            : (string) $user->role;

        abort_unless(isset($roles[$selectedRole]), 404);

        if ($user->role === 'customer') {
            $layout = 'layouts.app';
        } elseif (in_array($user->role, ['receptionist', 'cashier', 'trainer'], true)) {
            $layout = 'workspace';
        } else {
            $layout = 'admin.layout';
        }

        return view('help.index', [
            'layout' => $layout,
            'common' => $common,
            'roles' => $roles,
            'roleHelp' => $roles[$selectedRole],
            'selectedRole' => $selectedRole,
            'canBrowseAll' => $canBrowseAll,
            'canOpenRoleLinks' => $selectedRole === $user->role || in_array($user->role, ['admin', 'director'], true),
        ]);
    }

    private function withAdminMenuGuide(array $roles): array
    {
        if (!isset($roles['admin']['workflows'])) {
            return $roles;
        }

        $guide = config('help_admin_menu', []);
        $sections = $guide['sections'] ?? [];
        $posItem = config('help_pos.admin_menu_item');
        $servicesItem = config('help_services.admin_menu_item');

        if ($posItem) {
            foreach ($sections as &$section) {
                if (($section['title'] ?? null) !== 'Клиенты и продажи') {
                    continue;
                }

                $items = $section['items'] ?? [];
                $position = collect($items)->search(fn (array $item) => ($item['title'] ?? null) === 'Динамические цены');
                if ($position === false) {
                    $items[] = $posItem;
                } else {
                    array_splice($items, $position, 0, [$posItem]);
                }
                $section['items'] = $items;
                break;
            }
            unset($section);
        }

        if ($servicesItem) {
            foreach ($sections as &$section) {
                if (($section['title'] ?? null) !== 'Финансы и товары') {
                    continue;
                }

                $items = $section['items'] ?? [];
                $position = collect($items)->search(fn (array $item) => ($item['title'] ?? null) === 'Товары и тарифы');
                if ($position === false) {
                    $items[] = $servicesItem;
                } else {
                    array_splice($items, $position, 0, [$servicesItem]);
                }
                $section['items'] = $items;
                break;
            }
            unset($section);
        }

        if (!$sections) {
            return $roles;
        }

        $roles['admin']['workflows'][] = [
            'title' => $guide['title'] ?? 'Полное руководство по меню администратора',
            'route' => 'admin.dashboard',
            'steps' => [
                $guide['intro'] ?? 'Ниже разобран каждый пункт меню администратора.',
                'Инструкции расположены в том же порядке, что и левое меню CRM.',
                'Найдите нужный раздел через строку поиска справки и выполняйте шаги сверху вниз.',
            ],
            'example' => 'Поиск по словам «Бассейн и дорожки», «Продажа», «Услуги комплекса», «Касса и платежи», «SEO» или «Роли и права» сразу показывает соответствующую инструкцию.',
            'errors' => [],
        ];

        $number = 1;
        foreach ($sections as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $steps = [
                    'Для чего нужен раздел: '.($item['purpose'] ?? '—'),
                    'Когда использовать: '.($item['when'] ?? '—'),
                ];

                foreach ($item['steps'] ?? [] as $index => $step) {
                    $steps[] = 'Шаг '.($index + 1).'. '.$step;
                }
                foreach ($item['checks'] ?? [] as $check) {
                    $steps[] = 'Контроль после выполнения: '.$check;
                }
                foreach ($item['warnings'] ?? [] as $warning) {
                    $steps[] = 'Важно: '.$warning;
                }

                $roles['admin']['workflows'][] = [
                    'title' => 'Меню '.$number.'. '.$section['title'].' → '.$item['title'],
                    'route' => $item['route'] ?? null,
                    'steps' => $steps,
                    'example' => 'Раздел меню «'.$item['title'].'»: перед сохранением проверяйте выбранный объект, клиента, период и статус.',
                    'errors' => [],
                ];
                $number++;
            }
        }

        return $roles;
    }

    private function withPosRoleGuide(array $roles): array
    {
        $workflow = config('help_pos.manager_workflow');
        if (! $workflow) {
            return $roles;
        }

        foreach (['manager', 'cashier', 'receptionist'] as $role) {
            if (isset($roles[$role]['workflows'])) {
                $roles[$role]['workflows'][] = $workflow;
            }
        }

        return $roles;
    }
}
