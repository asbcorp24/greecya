<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_available(): void
    {
        $this->get('/')->assertOk();
        $this->get('/booking')->assertOk();
        $this->get('/tickets')->assertOk();
        $this->get('/privacy')->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_admin_dashboard_is_rendered_for_admin_user(): void
    {
        $admin = User::query()->create([
            'name' => 'Тестовый администратор',
            'email' => 'admin-test@example.com',
            'role' => 'admin',
            'password' => Hash::make('TestPassword123!'),
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Обзор')
            ->assertSee('Греция');
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }
}
