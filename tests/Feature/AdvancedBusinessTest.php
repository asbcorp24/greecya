<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use App\Services\AccountingExportService;
use App\Services\DynamicPricingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdvancedBusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_specific_workspaces_and_new_management_pages_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $director=User::where('email','director@greecya.local')->firstOrFail();
        $accountant=User::where('email','accountant@greecya.local')->firstOrFail();
        $manager=User::where('email','manager@greecya.local')->firstOrFail();
        $reception=User::where('email','reception@greecya.local')->firstOrFail();
        $trainer=User::where('email','trainer@greecya.local')->firstOrFail();
        $doctor=User::where('email','doctor@greecya.local')->firstOrFail();

        $this->actingAs($director)->get('/admin/director')->assertOk()->assertSee('Директорский dashboard');
        $this->actingAs($accountant)->get('/admin/accounting')->assertOk()->assertSee('1С:Бухгалтерия');
        $this->actingAs($manager)->get('/admin/pricing')->assertOk()->assertSee('Динамическое ценообразование');
        $this->actingAs($reception)->get('/reception')->assertOk()->assertSee('Ресепшен');
        $this->actingAs($trainer)->get('/coach')->assertOk()->assertSee('Рабочее место тренера');
        $this->actingAs($doctor)->get('/admin/medical')->assertOk()->assertSee('Медицинский модуль');

        $this->actingAs($manager)->get('/admin/accounting')->assertForbidden();
        $this->actingAs($accountant)->get('/admin/pricing')->assertForbidden();
        $this->actingAs($manager)->get('/admin/director')->assertForbidden();
    }

    public function test_dynamic_pricing_is_calculated_and_fixed_in_booking(): void
    {
        $this->seed(DatabaseSeeder::class);
        $service=Service::where('slug','free-swimming')->firstOrFail();
        $slot=ScheduleSlot::where('service_id',$service->id)->where('starts_at','>',now())->orderBy('starts_at')->firstOrFail();
        $quote=app(DynamicPricingService::class)->forService($service,$slot,null);
        $this->assertGreaterThan(0,$quote['price']);

        $response=$this->post('/booking',[
            'service_id'=>$service->id,'date'=>$slot->starts_at->toDateString(),'schedule_slot_id'=>$slot->id,
            'name'=>'Клиент динамической цены','phone'=>'+7 999 111-22-33','email'=>'dynamic@example.com','people'=>1,'privacy'=>1,
        ]);
        $response->assertRedirect();
        $booking=Booking::whereHas('customer',fn($q)=>$q->where('email','dynamic@example.com'))->latest()->firstOrFail();
        $this->assertNotNull($booking->base_total);
        $this->assertNotNull($booking->pricing_meta);
        $this->assertEqualsWithDelta($quote['price'],(float)$booking->total,0.01);
    }

    public function test_accounting_export_contains_business_entities_and_xml(): void
    {
        $this->seed(DatabaseSeeder::class);
        $service=app(AccountingExportService::class);
        $payload=$service->build(now()->subMonth(),now()->addDay());
        $this->assertArrayHasKey('sales',$payload);
        $this->assertArrayHasKey('payments',$payload);
        $this->assertArrayHasKey('cash_operations',$payload);
        $this->assertNotEmpty($payload['sales']);
        $xml=$service->encode($payload,'xml');
        $this->assertStringContainsString('<GreecyaAccountingExchange',$xml);
        $this->assertStringContainsString('<sales>',$xml);
    }

    public function test_pwa_assets_exist(): void
    {
        foreach(['manifest.webmanifest','sw.js','offline.html','icons/pwa-192.svg','icons/pwa-512.svg'] as $file){
            $this->assertFileExists(public_path($file));
        }
        $this->assertStringContainsString('serviceWorker',file_get_contents(resource_path('views/layouts/app.blade.php')));
    }

    public function test_new_seeded_tables_are_idempotent(): void
    {
        $tables=['permissions','role_permissions','families','family_members','family_wallets','swim_groups','swim_group_members','medical_clearance_logs','pool_operation_logs','safety_incidents','inventory_batches','accounting_integrations','pricing_rules'];
        $this->seed(DatabaseSeeder::class);
        $first=collect($tables)->mapWithKeys(fn($table)=>[$table=>DB::table($table)->count()])->all();
        $this->seed(DatabaseSeeder::class);
        $second=collect($tables)->mapWithKeys(fn($table)=>[$table=>DB::table($table)->count()])->all();
        $this->assertSame($first,$second);
    }
}
