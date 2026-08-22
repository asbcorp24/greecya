<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_page_renders_for_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/operations')
            ->assertOk()
            ->assertSee('Эксплуатация бассейна')
            ->assertSee('Динамика воды')
            ->assertSee('Технические чек-листы');
    }
}
