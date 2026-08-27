<?php

namespace Tests\Feature;

use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeServiceLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_service_cards_link_to_service_page_and_booking(): void
    {
        $service = Service::query()
            ->where('is_active', true)
            ->where('online_booking', true)
            ->orderBy('sort_order')
            ->firstOrFail();

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Все услуги')
            ->assertSee(route('services.index'), false)
            ->assertSee(route('services.show', $service), false)
            ->assertSee(route('booking.index', ['service' => $service->id]), false)
            ->assertSee('Подробнее')
            ->assertSee('Записаться');
    }
}
