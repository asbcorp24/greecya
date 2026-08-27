<?php

namespace Tests\Feature;

use App\Models\AccessCard;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualCustomerCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_manager_can_open_manual_customer_form_and_create_customer_with_photo_and_qr(): void
    {
        Storage::fake('public');
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Новый клиент');

        $this->actingAs($manager)
            ->get(route('admin.customers.create'))
            ->assertOk()
            ->assertSee('Создание клиента')
            ->assertSee('Сразу выдать QR-карту');

        $response = $this->actingAs($manager)->post(route('admin.customers.store'), [
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'patronymic' => 'Иванович',
            'phone' => '+7 (999) 111-22-33',
            'email' => 'manual-client@example.test',
            'birth_date' => '1992-03-14',
            'gender' => 'male',
            'emergency_contact' => 'Петрова Анна +7 999 000-00-00',
            'source' => 'manual',
            'notes' => 'Создан вручную из клиентской базы.',
            'photo' => UploadedFile::fake()->image('client.jpg', 600, 800),
            'privacy_consent' => '1',
            'marketing_consent' => '1',
            'issue_qr' => '1',
        ]);

        $customer = Customer::where('phone', '79991112233')->firstOrFail();
        $response->assertRedirect(route('admin.customers.show', $customer));

        $this->assertSame('Иванов Иван Иванович', $customer->name);
        $this->assertSame('manual', $customer->source);
        $this->assertTrue($customer->marketing_consent);
        $this->assertNotNull($customer->privacy_consent_at);
        $this->assertNotNull($customer->photo_path);
        Storage::disk('public')->assertExists($customer->photo_path);

        $card = AccessCard::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('qr', $card->type);
        $this->assertSame('active', $card->status);
        $this->assertStringStartsWith('QR-', $card->code);
    }

    public function test_manual_create_rejects_duplicate_phone_even_when_existing_phone_has_other_format(): void
    {
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();
        $existing = Customer::create([
            'name' => 'Существующий Клиент',
            'phone' => '+7 (911) 222-33-44',
            'source' => 'legacy',
        ]);

        $this->actingAs($manager)
            ->from(route('admin.customers.create'))
            ->post(route('admin.customers.store'), [
                'last_name' => 'Новый',
                'first_name' => 'Дубликат',
                'phone' => '79112223344',
                'source' => 'manual',
                'privacy_consent' => '1',
                'issue_qr' => '1',
            ])
            ->assertRedirect(route('admin.customers.create'))
            ->assertSessionHasErrors('phone');

        $this->assertSame(1, Customer::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '(', ''), ')', ''), '-', ''), '.', '') = ?", ['79112223344'])->count());
        $this->assertSame(0, AccessCard::where('customer_id', $existing->id)->count());
    }

    public function test_trainer_cannot_open_or_submit_manual_customer_creation(): void
    {
        $trainer = User::where('email', 'trainer@greecya.local')->firstOrFail();

        $this->actingAs($trainer)
            ->get(route('admin.customers.create'))
            ->assertForbidden();

        $this->actingAs($trainer)
            ->post(route('admin.customers.store'), [
                'last_name' => 'Запрещённый',
                'first_name' => 'Клиент',
                'phone' => '79990000001',
                'source' => 'manual',
                'privacy_consent' => '1',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('customers', ['phone' => '79990000001']);
    }
}
