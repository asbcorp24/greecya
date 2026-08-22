<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerShowViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_360_page_renders_for_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();
        $customer = Customer::where('email', 'ivan@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Клиент 360°')
            ->assertSee($customer->name)
            ->assertSee('Единая хронология');
    }
}
