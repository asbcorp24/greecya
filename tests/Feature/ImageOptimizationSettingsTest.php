<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\HeroSlide;
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
        Cache::forget('sitewide-image-optimization-state-v2');
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
            ->assertSee('Конвертировать и сжать всё');
    }

    public function test_batch_optimizer_converts_to_webp_updates_database_and_resizes(): void
    {
        $this->requireWebpSupport();
        Storage::fake('public');

        $image = UploadedFile::fake()->image('large.jpg', 3200, 1600);
        $oldPath = 'slides/large.jpg';
        Storage::disk('public')->put($oldPath, file_get_contents($image->getRealPath()));

        $slide = HeroSlide::query()->create([
            'title' => 'Большой слайд',
            'image_path' => $oldPath,
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $response = $this->actingAs($manager)
            ->postJson(route('admin.settings.images.compress'), [
                'offset' => 0,
                'section' => 'slides',
                'force' => true,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'total' => 1,
                'done' => true,
                'processed' => 1,
                'converted' => 1,
                'failed' => 0,
                'database_updates' => 1,
            ]);

        $slide->refresh();
        $newPath = $slide->image_path;

        $this->assertNotSame($oldPath, $newPath);
        $this->assertStringEndsWith('.webp', $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);

        $info = getimagesize(Storage::disk('public')->path($newPath));

        $this->assertIsArray($info);
        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(2560, $info[0]);
        $this->assertSame(1280, $info[1]);
    }

    public function test_gallery_cover_path_is_updated_after_webp_conversion(): void
    {
        $this->requireWebpSupport();
        Storage::fake('public');

        $image = UploadedFile::fake()->image('cover.png', 1400, 900);
        $oldPath = 'gallery/covers/cover.png';
        Storage::disk('public')->put($oldPath, file_get_contents($image->getRealPath()));

        $album = GalleryAlbum::query()->create([
            'title' => 'Тестовый альбом',
            'slug' => 'test-album',
            'cover_path' => $oldPath,
            'is_published' => true,
            'sort_order' => 10,
        ]);

        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $this->actingAs($manager)
            ->postJson(route('admin.settings.images.compress'), [
                'offset' => 0,
                'section' => 'gallery.covers',
                'force' => true,
            ])
            ->assertOk()
            ->assertJson([
                'converted' => 1,
                'failed' => 0,
                'database_updates' => 1,
            ]);

        $album->refresh();

        $this->assertStringEndsWith('.webp', $album->cover_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($album->cover_path);
    }

    public function test_gallery_covers_and_gallery_photos_are_separate_sections(): void
    {
        Storage::fake('public');

        $cover = UploadedFile::fake()->image('cover.jpg', 1200, 800);
        $photo = UploadedFile::fake()->image('photo.jpg', 1200, 800);
        $slide = UploadedFile::fake()->image('slide.jpg', 1920, 900);

        Storage::disk('public')->put('gallery/covers/cover.jpg', file_get_contents($cover->getRealPath()));
        Storage::disk('public')->put('gallery/photos/photo.jpg', file_get_contents($photo->getRealPath()));
        Storage::disk('public')->put('slides/slide.jpg', file_get_contents($slide->getRealPath()));

        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('admin.settings.images'))
            ->assertOk()
            ->assertSee('Обложки фотогалереи')
            ->assertSee('storage/app/public/gallery/covers/')
            ->assertSee('Фото галереи')
            ->assertSee('storage/app/public/gallery/photos/')
            ->assertSee('Слайдер')
            ->assertSee('storage/app/public/slides/');
    }

    public function test_optimizer_skips_unchanged_file_after_first_successful_run(): void
    {
        $this->requireWebpSupport();
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
    private function requireWebpSupport(): void
    {
        if (! class_exists(\Imagick::class) && ! function_exists('imagewebp')) {
            $this->markTestSkipped('Imagick или GD WebP недоступны.');
        }
    }
}
