<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Cache::forget('sitewide-image-optimization-state-v1');
    }

    public function test_manager_can_open_sitewide_image_optimization_page(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('photo.jpg', 1200, 800);
        Storage::disk('public')->put('gallery/photo.jpg', file_get_contents($image->getRealPath()));

        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('admin.settings.images'))
            ->assertOk()
            ->assertSee('Сжатие изображений')
            ->assertSee('Фотогалерея')
            ->assertSee('Сжать все изображения');
    }

    public function test_batch_optimizer_keeps_path_and_resizes_large_site_image(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('large.jpg', 3200, 1600);
        $path = 'gallery/large.jpg';
        Storage::disk('public')->put($path, file_get_contents($image->getRealPath()));

        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $response = $this->actingAs($manager)
            ->postJson(route('admin.settings.images.compress'), [
                'offset' => 0,
                'section' => 'gallery',
                'force' => true,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'total' => 1,
                'done' => true,
                'processed' => 1,
                'failed' => 0,
            ]);

        Storage::disk('public')->assertExists($path);

        $info = getimagesize(Storage::disk('public')->path($path));

        $this->assertIsArray($info);
        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(2560, $info[0]);
        $this->assertSame(1280, $info[1]);
    }

    public function test_optimizer_skips_unchanged_file_after_first_successful_run(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('repeat.jpg', 1400, 900);
        Storage::disk('public')->put('news/repeat.jpg', file_get_contents($image->getRealPath()));

        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $this->actingAs($manager)
            ->postJson(route('admin.settings.images.compress'), [
                'offset' => 0,
                'section' => 'news',
                'force' => false,
            ])
            ->assertOk();

        $this->actingAs($manager)
            ->postJson(route('admin.settings.images.compress'), [
                'offset' => 0,
                'section' => 'news',
                'force' => false,
            ])
            ->assertOk()
            ->assertJson([
                'optimized' => 0,
                'skipped' => 1,
                'failed' => 0,
            ]);
    }
}
