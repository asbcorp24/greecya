<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sees_logout_button_in_account_header_and_can_logout(): void
    {
        $this->seed(DatabaseSeeder::class);
        $customer = User::where('email', 'client@greecya.local')->firstOrFail();

        $this->actingAs($customer)
            ->get('/account')
            ->assertOk()
            ->assertSee('Выйти')
            ->assertSee('action="'.route('logout').'"', false);

        $this->actingAs($customer)
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_guest_does_not_see_customer_logout_button(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('bi-box-arrow-right');
    }
}
