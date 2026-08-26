<?php

namespace Tests\Feature;

use App\Models\AccessEvent;
use App\Models\CashRegister;
use App\Models\CashShift;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\MedicalClearance;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Order;
use App\Models\PoolZone;
use App\Models\Product;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointOfSaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_receptionist_can_register_customer_sell_ticket_and_customer_can_enter_by_ticket(): void
    {
        $receptionist = User::where('email', 'reception@greecya.local')->firstOrFail();
        $shift = $this->openShift($receptionist, 'RECEPTION-POS');
        $product = Product::create([
            'name' => 'Разовое посещение POS',
            'slug' => 'single-pos-ticket',
            'type' => 'ticket',
            'price' => 650,
            'visits_count' => 1,
            'validity_days' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($receptionist)
            ->get(route('reception.sales.create'))
            ->assertOk()
            ->assertSee('Первичная продажа')
            ->assertSee('Разовое посещение POS');

        $response = $this->actingAs($receptionist)->post(route('reception.sales.store'), [
            'name' => 'Первичный Клиент',
            'phone' => '+7 (999) 123-45-67',
            'email' => 'first-pos@example.test',
            'birth_date' => '1990-05-20',
            'product_id' => $product->id,
            'quantity' => 1,
            'payment_method' => 'card',
            'cash_shift_id' => $shift->id,
        ]);

        $customer = Customer::where('phone', '79991234567')->firstOrFail();
        $response->assertRedirect(route('reception.index', ['customer' => $customer->id]));

        $order = Order::where('customer_id', $customer->id)->where('source', 'reception_pos')->firstOrFail();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('completed', $order->status);
        $this->assertSame('650.00', $order->total);

        $item = $order->items()->firstOrFail();
        $this->assertNotNull($item->ticket_code);
        $this->assertSame(1, $item->visits_left);
        $this->assertTrue($item->valid_until->isSameDay(today()->addDay()));

        $payment = $order->payments()->firstOrFail();
        $this->assertSame('pos_card', $payment->provider);
        $this->assertSame('paid', $payment->status);

        $cash = CashTransaction::where('order_id', $order->id)->firstOrFail();
        $this->assertSame($shift->id, $cash->cash_shift_id);
        $this->assertSame('sale', $cash->type);
        $this->assertSame('card', $cash->method);
        $this->assertSame('650.00', $cash->amount);

        $zone = PoolZone::create([
            'name' => 'POS бассейн',
            'code' => 'POS-POOL',
            'type' => 'pool',
            'capacity' => 20,
            'is_active' => true,
        ]);
        MedicalClearance::create([
            'customer_id' => $customer->id,
            'type' => 'pool',
            'issued_on' => today(),
            'expires_on' => today()->addMonth(),
            'status' => 'valid',
            'access_blocked' => false,
            'verified_by' => $receptionist->id,
            'verified_at' => now(),
        ]);

        $this->actingAs($receptionist)
            ->from(route('reception.index', ['customer' => $customer->id]))
            ->post(route('admin.access.checkin'), [
                'customer_id' => $customer->id,
                'pool_zone_id' => $zone->id,
                'event_type' => 'enter',
            ])
            ->assertRedirect(route('reception.index', ['customer' => $customer->id]));

        $item->refresh();
        $this->assertSame(0, $item->visits_left);

        $visit = Visit::where('customer_id', $customer->id)->latest()->firstOrFail();
        $this->assertSame($item->id, $visit->order_item_id);
        $this->assertNull($visit->membership_id);
        $this->assertStringContainsString($item->ticket_code, (string) $visit->notes);

        $event = AccessEvent::where('customer_id', $customer->id)->latest('occurred_at')->firstOrFail();
        $this->assertSame('allowed', $event->result);
        $this->assertSame('enter', $event->event_type);

        $this->actingAs($receptionist)
            ->from(route('reception.index', ['customer' => $customer->id]))
            ->post(route('admin.access.checkin'), [
                'customer_id' => $customer->id,
                'pool_zone_id' => $zone->id,
                'event_type' => 'enter',
            ])
            ->assertSessionHasErrors('access');

        $this->assertSame(1, Visit::where('customer_id', $customer->id)->count());
    }

    public function test_pos_reuses_existing_customer_when_phone_format_differs(): void
    {
        $receptionist = User::where('email', 'reception@greecya.local')->firstOrFail();
        $shift = $this->openShift($receptionist, 'NORMALIZED-PHONE');
        $existing = Customer::create([
            'name' => 'Существующий клиент',
            'phone' => '+7 (911) 222-33-44',
            'email' => 'old@example.test',
            'source' => 'reception',
        ]);
        $product = Product::create([
            'name' => 'Билет без дубля',
            'slug' => 'no-duplicate-ticket',
            'type' => 'ticket',
            'price' => 500,
            'visits_count' => 1,
            'validity_days' => 1,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->actingAs($receptionist)->post(route('reception.sales.store'), [
            'name' => 'Существующий клиент',
            'phone' => '79112223344',
            'email' => 'new@example.test',
            'product_id' => $product->id,
            'quantity' => 1,
            'payment_method' => 'cash',
            'cash_shift_id' => $shift->id,
        ])->assertRedirect(route('reception.index', ['customer' => $existing->id]));

        $this->assertSame(1, Customer::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '(', ''), ')', ''), '-', ''), '.', '') = ?", ['79112223344'])->count());
        $this->assertSame($existing->id, Order::where('source', 'reception_pos')->latest()->firstOrFail()->customer_id);
    }

    public function test_manager_has_admin_pos_and_membership_is_activated_after_sale(): void
    {
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();
        $receptionist = User::where('email', 'reception@greecya.local')->firstOrFail();
        $shift = $this->openShift($manager, 'MANAGER-POS');
        $customer = Customer::create([
            'name' => 'Клиент абонемента',
            'phone' => '79995556677',
            'email' => 'membership-pos@example.test',
            'source' => 'manager',
        ]);
        $product = Product::create([
            'name' => 'Абонемент POS 8 посещений',
            'slug' => 'pos-membership-eight',
            'type' => 'membership',
            'price' => 3200,
            'visits_count' => 8,
            'validity_days' => 30,
            'is_active' => true,
            'sort_order' => 3,
        ]);
        $plan = MembershipPlan::create([
            'product_id' => $product->id,
            'name' => 'POS план 8',
            'code' => 'POS-PLAN-8',
            'type' => 'membership',
            'audience_type' => 'individual',
            'duration_days' => 30,
            'visits_included' => 8,
            'price' => 3200,
            'freeze_days' => 3,
            'guest_visits' => 0,
            'requires_medical_clearance' => true,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.sales.index', ['customer' => $customer->id]))
            ->assertOk()
            ->assertSee('Продажа клиенту')
            ->assertSee('Абонемент POS 8 посещений');

        $this->actingAs($receptionist)
            ->get(route('admin.sales.index'))
            ->assertForbidden();

        $response = $this->actingAs($manager)->post(route('admin.sales.store'), [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'payment_method' => 'sbp',
            'cash_shift_id' => $shift->id,
        ]);

        $order = Order::where('customer_id', $customer->id)->where('source', 'manager_pos')->firstOrFail();
        $response->assertRedirect(route('admin.sales.index', ['customer' => $customer->id, 'order' => $order->number]));

        $membership = Membership::where('customer_id', $customer->id)->where('order_item_id', $order->items()->firstOrFail()->id)->firstOrFail();
        $this->assertSame($plan->id, $membership->membership_plan_id);
        $this->assertSame('active', $membership->status);
        $this->assertSame(8, $membership->visits_total);
        $this->assertSame(0, $membership->visits_used);
        $this->assertTrue($membership->starts_on->isToday());
        $this->assertTrue($membership->ends_on->isSameDay(today()->addDays(29)));

        $cash = CashTransaction::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('sbp', $cash->method);
        $this->assertSame('3200.00', $cash->amount);
    }

    public function test_sale_is_blocked_without_open_cash_shift(): void
    {
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();
        $product = Product::create([
            'name' => 'Билет закрытая смена',
            'slug' => 'closed-shift-ticket',
            'type' => 'ticket',
            'price' => 400,
            'visits_count' => 1,
            'validity_days' => 1,
            'is_active' => true,
            'sort_order' => 4,
        ]);
        $shift = $this->openShift($manager, 'CLOSED-POS');
        $shift->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $manager->id, 'closing_cash' => 0]);

        $this->actingAs($manager)
            ->from(route('admin.sales.index'))
            ->post(route('admin.sales.store'), [
                'name' => 'Клиент закрытой смены',
                'phone' => '79990001122',
                'product_id' => $product->id,
                'quantity' => 1,
                'payment_method' => 'card',
                'cash_shift_id' => $shift->id,
            ])
            ->assertRedirect(route('admin.sales.index'))
            ->assertSessionHasErrors('cash_shift_id');

        $this->assertDatabaseMissing('orders', ['source' => 'manager_pos']);
    }

    private function openShift(User $user, string $code): CashShift
    {
        $register = CashRegister::create([
            'name' => 'Касса '.$code,
            'code' => $code,
            'location' => 'Ресепшен',
            'is_active' => true,
        ]);

        return CashShift::create([
            'cash_register_id' => $register->id,
            'opened_by' => $user->id,
            'opened_at' => now(),
            'opening_cash' => 0,
            'status' => 'open',
        ]);
    }
}
