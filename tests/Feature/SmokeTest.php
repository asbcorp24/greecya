<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_available(): void
    {
        foreach(['/','/booking','/tickets','/news','/gallery','/privacy','/login','/account/register','/sitemap.xml','/robots.txt'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_sections_are_rendered(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email','admin@greecya.local')->firstOrFail();

        foreach(['/admin','/admin/news','/admin/gallery','/admin/slides','/admin/trainers','/admin/certificates','/admin/certificates/scan','/admin/training-plans','/admin/settings','/admin/settings/contacts','/admin/seo','/admin/pool','/admin/memberships','/admin/access','/admin/finance','/admin/staff','/admin/inventory','/admin/crm-plus','/admin/reports'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_accountant_has_financial_access_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $accountant = User::where('email','accountant@greecya.local')->firstOrFail();

        $this->actingAs($accountant)->get('/admin')->assertRedirect(route('admin.finance.index'));
        $this->actingAs($accountant)->get('/admin/finance')->assertOk()->assertSee('Бухгалтер');
        $this->actingAs($accountant)->get('/admin/orders')->assertOk();
        $this->actingAs($accountant)->get('/admin/customers')->assertOk();
        $this->actingAs($accountant)->get('/admin/inventory')->assertOk();
        $this->actingAs($accountant)->get('/admin/staff')->assertOk();
        $this->actingAs($accountant)->get('/admin/reports')->assertOk();

        $this->actingAs($accountant)->get('/admin/pool')->assertForbidden();
        $this->actingAs($accountant)->get('/admin/access')->assertForbidden();
        $this->actingAs($accountant)->get('/admin/news')->assertForbidden();
        $this->actingAs($accountant)->get('/admin/settings')->assertForbidden();
        $this->actingAs($accountant)->get('/admin/seo')->assertForbidden();
    }

    public function test_paid_site_subscription_activates_crm_membership(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email','admin@greecya.local')->firstOrFail();
        $customer = Customer::where('email','ivan@example.com')->firstOrFail();
        $product = Product::with('membershipPlan')->where('slug','pool-8')->firstOrFail();

        $this->assertNotNull($product->membershipPlan);

        $order = Order::create([
            'number' => 'ORD-WEB-LINK-001',
            'customer_id' => $customer->id,
            'status' => 'new',
            'payment_status' => 'pending',
            'subtotal' => $product->price,
            'total' => $product->price,
            'source' => 'site',
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 1,
            'price' => $product->price,
            'total' => $product->price,
            'visits_left' => $product->visits_count,
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.update',$order), [
            'status' => 'completed',
            'payment_status' => 'paid',
        ])->assertRedirect();

        $this->assertDatabaseHas('memberships', [
            'customer_id' => $customer->id,
            'membership_plan_id' => $product->membershipPlan->id,
            'order_item_id' => $item->id,
            'status' => 'active',
        ]);
    }

    public function test_customer_account_is_rendered(): void
    {
        $customer = Customer::create(['name'=>'Клиент','phone'=>'+70000000000','email'=>'client@example.com']);
        $user = User::create(['customer_id'=>$customer->id,'name'=>$customer->name,'email'=>$customer->email,'phone'=>$customer->phone,'role'=>'customer','password'=>Hash::make('TestPassword123!')]);
        $this->actingAs($user)->get('/account')->assertOk()->assertSee('Личный кабинет');
    }

    public function test_certificate_can_be_verified_and_printed(): void
    {
        $certificate = Certificate::create(['serial'=>'GC-TEST-001','token'=>Str::random(48),'recipient_name'=>'Тестовый получатель','amount'=>3000,'status'=>'active','valid_from'=>today(),'valid_until'=>now()->addMonth()->toDateString()]);
        $this->get(route('certificate.verify',$certificate))->assertOk()->assertSee('GC-TEST-001');
        $this->get(route('certificate.print',$certificate))->assertOk()->assertSee('Подарочный сертификат');
    }

    public function test_full_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tables=['site_settings','seo_pages','services','trainers','schedule_slots','products','hero_slides','news_posts','gallery_albums','gallery_photos','customers','leads','bookings','orders','order_items','payments','certificates','visits','training_plans','training_plan_items','training_progress_entries','pool_zones','pool_lanes','schedule_slot_lane','membership_plans','memberships','customer_wallets','wallet_transactions','access_cards','access_events','lockers','locker_rentals','medical_clearances','waitlist_entries','crm_tasks','customer_interactions','staff_shifts','payroll_rules','cash_registers','cash_shifts','cash_transactions','inventory_items','inventory_movements','corporate_accounts','corporate_members','document_templates','customer_documents','campaigns','pool_water_logs','maintenance_tasks'];
        $first = collect($tables)->mapWithKeys(fn($table)=>[$table=>DB::table($table)->count()])->all();
        $this->seed(DatabaseSeeder::class);
        $second = collect($tables)->mapWithKeys(fn($table)=>[$table=>DB::table($table)->count()])->all();
        $this->assertSame($first,$second);
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJson(['status'=>'ok']);
    }
}
