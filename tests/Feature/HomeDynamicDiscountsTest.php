<?php

namespace Tests\Feature;

use App\Models\PricingRule;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeDynamicDiscountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_shows_discount_rules_but_not_surcharges(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Скидки и')
            ->assertSee('выгодное время')
            ->assertSee('Будни утром −20%')
            ->assertSee('Низкая загрузка −15%')
            ->assertSee('Детский тариф −15%')
            ->assertSee('Пенсионный тариф −15%')
            ->assertSee('Семейная скидка −10%')
            ->assertSee('по будням')
            ->assertSee('07:00–12:00')
            ->assertDontSee('Вечерний пик +10%')
            ->assertDontSee('Выходные +10%');
    }

    public function test_expired_and_inactive_discounts_are_hidden(): void
    {
        PricingRule::query()->create([
            'name' => 'Просроченная скидка −90%',
            'target_type' => 'service',
            'customer_segment' => 'all',
            'ends_on' => today()->subDay(),
            'adjustment_type' => 'percent',
            'adjustment_value' => -90,
            'priority' => 1,
            'is_active' => true,
        ]);

        PricingRule::query()->create([
            'name' => 'Выключенная скидка −80%',
            'target_type' => 'service',
            'customer_segment' => 'all',
            'adjustment_type' => 'percent',
            'adjustment_value' => -80,
            'priority' => 1,
            'is_active' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Просроченная скидка −90%')
            ->assertDontSee('Выключенная скидка −80%');
    }

    public function test_service_specific_discount_links_to_selected_service_booking(): void
    {
        $service = Service::query()
            ->where('is_active', true)
            ->where('online_booking', true)
            ->firstOrFail();

        PricingRule::query()->create([
            'name' => 'Персональная акция −25%',
            'target_type' => 'service',
            'service_id' => $service->id,
            'customer_segment' => 'all',
            'adjustment_type' => 'percent',
            'adjustment_value' => -25,
            'priority' => 1,
            'combinable' => false,
            'is_active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Персональная акция −25%')
            ->assertSee('−25%')
            ->assertSee(route('booking.index', ['service' => $service->id]), false);
    }
}
