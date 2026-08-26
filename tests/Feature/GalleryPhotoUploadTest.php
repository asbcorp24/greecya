<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_upload_gallery_photo_without_title_or_caption(): void
    {
        Storage::fake('public');

        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();
        $album = GalleryAlbum::create([
            'title' => 'Тестовый альбом',
            'slug' => 'test-gallery-upload',
            'is_published' => true,
            'sort_order' => 100,
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/gallery/albums/'.$album->slug.'/photos')
            ->post('/admin/gallery/albums/'.$album->slug.'/photos', [
                'images' => [UploadedFile::fake()->image('pool.jpg', 1200, 800)],
            ]);

        $response
            ->assertRedirect('/admin/gallery/albums/'.$album->slug.'/photos')
            ->assertSessionHas('success', 'Фотографии добавлены.');

        $photo = $album->photos()->firstOrFail();

        $this->assertNull($photo->title);
        $this->assertNull($photo->caption);
        $this->assertTrue($photo->is_published);
        Storage::disk('public')->assertExists($photo->image_path);

        $album->refresh();
        $this->assertSame($photo->image_path, $album->cover_path);
    }
}
