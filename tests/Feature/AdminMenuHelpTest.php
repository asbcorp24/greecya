<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminMenuHelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_help_covers_every_current_admin_menu_item(): void
    {
        $guide = config('help_admin_menu');
        $items = collect($guide['sections'])
            ->flatMap(fn (array $section) => $section['items'])
            ->values();

        if ($posItem = config('help_pos.admin_menu_item')) {
            $items->push($posItem);
        }

        $this->assertCount(40, $items, 'Руководство должно покрывать 37 рабочих и 3 служебных пункта меню.');

        foreach ($items as $item) {
            $this->assertNotEmpty($item['purpose'] ?? null, 'Нет назначения для '.$item['title']);
            $this->assertNotEmpty($item['when'] ?? null, 'Нет сценария использования для '.$item['title']);
            $this->assertGreaterThanOrEqual(4, count($item['steps'] ?? []), 'Недостаточно шагов для '.$item['title']);
            $this->assertNotEmpty($item['checks'] ?? [], 'Нет контрольных пунктов для '.$item['title']);

            if (!empty($item['route'])) {
                $this->assertTrue(Route::has($item['route']), 'В инструкции указан несуществующий маршрут '.$item['route']);
            }
        }
    }

    public function test_admin_help_renders_full_menu_manual_and_key_sections(): void
    {
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Полное руководство по меню администратора')
            ->assertSee('Бассейн и проход → Бассейн и дорожки')
            ->assertSee('Клиенты и продажи → Клиенты 360°')
            ->assertSee('Клиенты и продажи → Продажа')
            ->assertSee('Финансы и товары → Касса и платежи')
            ->assertSee('Управление → Роли и права')
            ->assertSee('Сайт → Фотогалерея')
            ->assertSee('Настройки → SEO')
            ->assertSee('Служебные пункты → Выйти')
            ->assertSee('Контроль после выполнения:')
            ->assertSee('Важно:');
    }

    public function test_non_admin_role_does_not_receive_admin_menu_manual_by_default(): void
    {
        $trainer = User::where('email', 'trainer@greecya.local')->firstOrFail();

        $this->actingAs($trainer)
            ->get(route('help.index'))
            ->assertOk()
            ->assertDontSee('Полное руководство по меню администратора');
    }
}
