<?php

namespace Tests\Feature;

use App\Models\Panorama;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PanoramaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_manager_can_upload_panorama_and_it_appears_on_homepage(): void
    {
        Storage::fake('public');

        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $this->actingAs($manager)
            ->post(route('admin.panoramas.store'), [
                'title' => 'Бассейн 360',
                'description' => 'Панорама чаши бассейна.',
                'image' => UploadedFile::fake()->image('pool-360.jpg', 2000, 1000),
                'sort_order' => 10,
                'is_active' => '1',
                'is_homepage' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $panorama = Panorama::where('title', 'Бассейн 360')->firstOrFail();

        $this->assertTrue($panorama->is_active);
        $this->assertTrue($panorama->is_homepage);
        Storage::disk('public')->assertExists($panorama->image_path);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Посмотрите комплекс')
            ->assertSee('Бассейн 360')
            ->assertSee('Другие панорамы')
            ->assertSee(Storage::url($panorama->image_path), false)
            ->assertSee('pannellum');
    }

    public function test_inactive_panorama_is_not_exposed_on_homepage(): void
    {
        Panorama::query()->create([
            'title' => 'Активная панорама',
            'description' => 'Доступна посетителям.',
            'image_path' => 'panoramas/active.jpg',
            'is_active' => true,
            'is_homepage' => true,
            'sort_order' => 10,
        ]);

        Panorama::query()->create([
            'title' => 'Скрытая панорама',
            'description' => 'Не должна быть видна.',
            'image_path' => 'panoramas/hidden.jpg',
            'is_active' => false,
            'is_homepage' => false,
            'sort_order' => 20,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Активная панорама')
            ->assertDontSee('Скрытая панорама')
            ->assertDontSee('panoramas/hidden.jpg', false);
    }

    public function test_only_one_panorama_is_marked_as_homepage_after_update(): void
    {
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $first = Panorama::query()->create([
            'title' => 'Первая',
            'image_path' => 'panoramas/first.jpg',
            'is_active' => true,
            'is_homepage' => true,
            'sort_order' => 10,
        ]);

        $second = Panorama::query()->create([
            'title' => 'Вторая',
            'image_path' => 'panoramas/second.jpg',
            'is_active' => true,
            'is_homepage' => false,
            'sort_order' => 20,
        ]);

        $this->actingAs($manager)
            ->patch(route('admin.panoramas.update', $second), [
                'title' => 'Вторая',
                'description' => 'Теперь главная.',
                'sort_order' => 20,
                'is_active' => '1',
                'is_homepage' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($first->fresh()->is_homepage);
        $this->assertTrue($second->fresh()->is_homepage);
        $this->assertSame(1, Panorama::where('is_homepage', true)->count());
    }

    public function test_panorama_admin_page_is_available_to_content_manager(): void
    {
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('admin.panoramas.index'))
            ->assertOk()
            ->assertSee('360° панорамы')
            ->assertSee('Новая панорама');
    }
}
