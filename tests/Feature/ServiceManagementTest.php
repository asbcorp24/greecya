<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_manager_can_create_edit_and_disable_service(): void
    {
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('admin.services.index'))
            ->assertOk()
            ->assertSee('Услуги комплекса')
            ->assertSee('Новая услуга');

        $response = $this->actingAs($manager)->post(route('admin.services.store'), [
            'name' => 'Тестовая гидротренировка',
            'slug' => '',
            'category' => 'training',
            'description' => 'Тестовая услуга для проверки CRUD.',
            'duration_minutes' => 45,
            'price' => 1250,
            'capacity' => 8,
            'sort_order' => 15,
            'requires_trainer' => '1',
            'online_booking' => '1',
            'is_active' => '1',
        ]);

        $service = Service::where('name', 'Тестовая гидротренировка')->firstOrFail();
        $response->assertRedirect(route('admin.services.edit', $service));
        $this->assertNotEmpty($service->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $service->slug);
        $this->assertTrue($service->requires_trainer);
        $this->assertTrue($service->online_booking);
        $this->assertTrue($service->is_active);

        $this->actingAs($manager)->patch(route('admin.services.update', $service), [
            'name' => 'Гидротренировка PRO',
            'slug' => $service->slug,
            'category' => 'training',
            'description' => 'Обновлённое описание.',
            'duration_minutes' => 60,
            'price' => 1500,
            'capacity' => 10,
            'sort_order' => 20,
            'requires_trainer' => '1',
            'is_active' => '1',
        ])->assertSessionHas('success');

        $service->refresh();
        $this->assertSame('Гидротренировка PRO', $service->name);
        $this->assertSame('1500.00', $service->price);
        $this->assertFalse($service->online_booking, 'Снятый чекбокс должен отключать онлайн-запись.');
        $this->assertTrue($service->is_active);

        $this->actingAs($manager)
            ->patch(route('admin.services.toggle', $service))
            ->assertSessionHas('success');

        $this->assertFalse($service->fresh()->is_active);
    }

    public function test_service_without_online_booking_is_hidden_from_public_booking(): void
    {
        $service = Service::query()->create([
            'name' => 'Только офлайн',
            'slug' => 'offline-only-service',
            'category' => 'spa',
            'description' => 'Эту услугу оформляет только сотрудник.',
            'duration_minutes' => 30,
            'price' => 900,
            'capacity' => 1,
            'requires_trainer' => false,
            'online_booking' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('booking.index'))
            ->assertOk()
            ->assertDontSee('Только офлайн');

        $this->getJson(route('booking.slots', [
            'service_id' => $service->id,
            'date' => now()->addDay()->toDateString(),
        ]))->assertStatus(422)
          ->assertJsonValidationErrors('service_id');
    }

    public function test_trainer_cannot_open_service_management(): void
    {
        $trainer = User::where('email', 'trainer@greecya.local')->firstOrFail();

        $this->actingAs($trainer)
            ->get(route('admin.services.index'))
            ->assertForbidden();
    }
}
