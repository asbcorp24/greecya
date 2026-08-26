<?php

namespace Tests\Feature;

use App\Models\AccessCard;
use App\Models\CashRegister;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerQrCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_primary_pos_sale_automatically_issues_single_qr_access_card(): void
    {
        $receptionist = User::where('email', 'reception@greecya.local')->firstOrFail();
        $shift = $this->openShift($receptionist, 'QR-POS');
        $product = Product::create([
            'name' => 'Разовый билет с QR-картой',
            'slug' => 'qr-card-ticket',
            'type' => 'ticket',
            'price' => 700,
            'visits_count' => 1,
            'validity_days' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($receptionist)->post(route('reception.sales.store'), [
            'name' => 'QR Клиент',
            'phone' => '+7 999 222-11-00',
            'product_id' => $product->id,
            'quantity' => 1,
            'payment_method' => 'card',
            'cash_shift_id' => $shift->id,
        ])->assertRedirect();

        $customer = Customer::where('phone', '79992221100')->firstOrFail();
        $card = AccessCard::where('customer_id', $customer->id)
            ->where('type', 'qr')
            ->where('status', 'active')
            ->firstOrFail();

        $this->assertStringStartsWith('QR-', $card->code);
        $this->assertNotNull($card->issued_at);
        $this->assertNull($card->expires_at);

        $this->actingAs($receptionist)->post(route('reception.sales.store'), [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'payment_method' => 'cash',
            'cash_shift_id' => $shift->id,
        ])->assertRedirect();

        $this->assertSame(
            1,
            AccessCard::where('customer_id', $customer->id)
                ->where('type', 'qr')
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_admin_can_issue_print_and_reissue_customer_qr_card(): void
    {
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();
        $customer = Customer::create([
            'name' => 'Клиент Для Печати',
            'phone' => '79998887766',
            'email' => 'print-card@example.test',
            'source' => 'admin',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.customers.show', $customer))
            ->post(route('admin.customers.card.issue', $customer))
            ->assertRedirect(route('admin.customers.show', $customer));

        $firstCard = AccessCard::where('customer_id', $customer->id)
            ->where('type', 'qr')
            ->where('status', 'active')
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Карта клиента / QR')
            ->assertSee($firstCard->code)
            ->assertSee('Распечатать карту');

        $this->actingAs($admin)
            ->get(route('admin.customers.card.print', $customer))
            ->assertOk()
            ->assertSee('Клиент Для Печати')
            ->assertSee($firstCard->code)
            ->assertSee('85.6mm', false)
            ->assertSee('54mm', false)
            ->assertSee('qrcode.min.js', false);

        $this->actingAs($admin)
            ->from(route('admin.customers.show', $customer))
            ->post(route('admin.customers.card.reissue', $customer))
            ->assertRedirect(route('admin.customers.show', $customer));

        $firstCard->refresh();
        $this->assertSame('replaced', $firstCard->status);

        $newCard = AccessCard::where('customer_id', $customer->id)
            ->where('type', 'qr')
            ->where('status', 'active')
            ->firstOrFail();

        $this->assertNotSame($firstCard->id, $newCard->id);
        $this->assertNotSame($firstCard->code, $newCard->code);

        $this->actingAs($admin)
            ->get(route('admin.customers.card.print', $customer))
            ->assertOk()
            ->assertSee($newCard->code)
            ->assertDontSee($firstCard->code);
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
