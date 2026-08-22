<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_open_help_for_every_role(): void
    {
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        foreach (array_keys(config('help.roles')) as $role) {
            $response = $this->actingAs($admin)->get(route('help.index', ['role' => $role]));

            $response
                ->assertOk()
                ->assertSee(config('help.roles.'.$role.'.label'))
                ->assertSee('Быстрый старт')
                ->assertSee('Рабочие сценарии с примерами')
                ->assertSee('Типичные ошибки роли')
                ->assertSee('Общие системные ошибки')
                ->assertSee('Чек-лист роли');
        }
    }

    public function test_manager_can_browse_role_manuals(): void
    {
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('help.index', ['role' => 'doctor']))
            ->assertOk()
            ->assertSee('Врач')
            ->assertSee('Медицинские допуски');
    }

    public function test_receptionist_is_forced_to_own_manual(): void
    {
        $receptionist = User::where('email', 'reception@greecya.local')->firstOrFail();

        $this->actingAs($receptionist)
            ->get(route('help.index', ['role' => 'accountant']))
            ->assertOk()
            ->assertSee('Администратор ресепшена')
            ->assertDontSee('Справка по ролям');
    }

    public function test_trainer_help_renders_in_workspace(): void
    {
        $trainer = User::where('email', 'trainer@greecya.local')->firstOrFail();

        $this->actingAs($trainer)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Тренер')
            ->assertSee('Отметить посещаемость группы')
            ->assertSee('Справка');
    }

    public function test_customer_can_only_see_customer_manual(): void
    {
        $customer = User::where('email', 'client@greecya.local')->firstOrFail();

        $this->actingAs($customer)
            ->get(route('help.index', ['role' => 'admin']))
            ->assertOk()
            ->assertSee('Клиент')
            ->assertSee('Записаться на занятие')
            ->assertSee('Установить PWA на телефон')
            ->assertDontSee('Справка по ролям');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/help')->assertRedirect('/login');
    }
}
