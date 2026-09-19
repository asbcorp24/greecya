<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnrestrictedBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_manager_can_enable_unrestricted_booking_from_schedule(): void
    {
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();
        $service = Service::where('slug', 'free-swimming')->firstOrFail();

        $this->actingAs($manager)
            ->patch(route('admin.schedule.unrestricted', $service), [
                'unrestricted_booking' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($service->fresh()->unrestricted_booking);

        $this->actingAs($manager)
            ->get(route('admin.schedule.index'))
            ->assertOk()
            ->assertSee('Без ограничений')
            ->assertSee('Любое время');
    }

    public function test_unrestricted_service_returns_free_time_mode_instead_of_slots(): void
    {
        $service = Service::where('slug', 'free-swimming')->firstOrFail();
        $service->update(['unrestricted_booking' => true]);

        $response = $this->getJson(route('booking.slots', [
            'service_id' => $service->id,
            'date' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJson([
                'unrestricted' => true,
                'duration_minutes' => (int) $service->duration_minutes,
            ])
            ->assertJsonStructure(['unrestricted', 'duration_minutes', 'base_price', 'message']);
    }

    public function test_unrestricted_service_accepts_any_future_time_and_same_time_can_be_booked_again(): void
    {
        $service = Service::where('slug', 'free-swimming')->firstOrFail();
        $service->update(['unrestricted_booking' => true]);

        $date = now()->addDay()->toDateString();
        $time = '13:37';

        foreach ([
            ['name' => 'Первый клиент', 'phone' => '+7 999 111-22-33', 'email' => 'free-time-1@example.com'],
            ['name' => 'Второй клиент', 'phone' => '+7 999 111-22-44', 'email' => 'free-time-2@example.com'],
        ] as $customer) {
            $this->post(route('booking.store'), [
                'service_id' => $service->id,
                'date' => $date,
                'requested_time' => $time,
                'name' => $customer['name'],
                'phone' => $customer['phone'],
                'email' => $customer['email'],
                'people' => 1,
                'privacy' => 1,
            ])->assertRedirect();
        }

        $bookings = Booking::query()
            ->where('service_id', $service->id)
            ->whereHas('customer', fn ($query) => $query->whereIn('email', [
                'free-time-1@example.com',
                'free-time-2@example.com',
            ]))
            ->with('slot')
            ->get();

        $this->assertCount(2, $bookings);

        foreach ($bookings as $booking) {
            $this->assertSame('unrestricted_booking', $booking->slot->session_type);
            $this->assertSame($date.' '.$time, $booking->slot->starts_at->format('Y-m-d H:i'));
            $this->assertSame('closed', $booking->slot->status);
        }

        $this->assertSame(
            2,
            ScheduleSlot::where('service_id', $service->id)
                ->where('session_type', 'unrestricted_booking')
                ->where('starts_at', 'like', $date.' '.$time.'%')
                ->count()
        );
    }

    public function test_manager_can_turn_unrestricted_mode_off_again(): void
    {
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();
        $service = Service::where('slug', 'free-swimming')->firstOrFail();
        $service->update(['unrestricted_booking' => true]);

        $this->actingAs($manager)
            ->patch(route('admin.schedule.unrestricted', $service), [
                'unrestricted_booking' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse($service->fresh()->unrestricted_booking);
    }
}
