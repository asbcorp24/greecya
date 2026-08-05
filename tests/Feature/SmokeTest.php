<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_available(): void
    {
        foreach(['/','/booking','/tickets','/news','/gallery','/privacy','/login','/account/register'] as $url) $this->get($url)->assertOk();
    }

    public function test_admin_content_sections_are_rendered(): void
    {
        $admin=User::query()->create(['name'=>'Тестовый администратор','email'=>'admin-test@example.com','role'=>'admin','password'=>Hash::make('TestPassword123!')]);
        foreach(['/admin','/admin/news','/admin/gallery','/admin/slides','/admin/trainers','/admin/certificates','/admin/certificates/scan','/admin/training-plans'] as $url) $this->actingAs($admin)->get($url)->assertOk();
    }

    public function test_customer_account_is_rendered(): void
    {
        $customer=Customer::create(['name'=>'Клиент','phone'=>'+70000000000','email'=>'client@example.com']);
        $user=User::create(['customer_id'=>$customer->id,'name'=>$customer->name,'email'=>$customer->email,'phone'=>$customer->phone,'role'=>'customer','password'=>Hash::make('TestPassword123!')]);
        $this->actingAs($user)->get('/account')->assertOk()->assertSee('Личный кабинет');
    }

    public function test_certificate_can_be_verified_and_printed(): void
    {
        $certificate=Certificate::create(['serial'=>'GC-TEST-001','token'=>Str::random(48),'recipient_name'=>'Тестовый получатель','amount'=>3000,'status'=>'active','valid_from'=>today(),'valid_until'=>now()->addMonth()->toDateString()]);
        $this->get(route('certificate.verify',$certificate))->assertOk()->assertSee('GC-TEST-001');
        $this->get(route('certificate.print',$certificate))->assertOk()->assertSee('Подарочный сертификат');
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJson(['status'=>'ok']);
    }
}
