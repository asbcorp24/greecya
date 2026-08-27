<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServicePublicPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_catalog_and_service_page_show_all_service_information_and_photos(): void
    {
        $service = Service::query()->create([
            'name' => 'Семейное плавание',
            'slug' => 'family-swimming-public-test',
            'category' => 'pool',
            'description' => "Подробное описание семейного плавания.\nВторая строка с особенностями услуги.",
            'main_image_path' => 'services/test/main/family.jpg',
            'duration_minutes' => 75,
            'price' => 1900,
            'capacity' => 6,
            'requires_trainer' => true,
            'online_booking' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $service->photos()->create([
            'image_path' => 'services/test/gallery/one.jpg',
            'caption' => 'Дополнительное фото бассейна',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $service->photos()->create([
            'image_path' => 'services/test/gallery/hidden.jpg',
            'caption' => 'Скрытое фото',
            'sort_order' => 20,
            'is_active' => false,
        ]);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('Услуги комплекса')
            ->assertSee('Семейное плавание')
            ->assertSee(route('services.show', ['service' => $service->slug]), false);

        $this->get(route('services.show', ['service' => $service->slug]))
            ->assertOk()
            ->assertSee('Семейное плавание')
            ->assertSee('Подробное описание семейного плавания.')
            ->assertSee('75 мин')
            ->assertSee('1 900 ₽')
            ->assertSee('Дополнительное фото бассейна')
            ->assertSee('services/test/main/family.jpg', false)
            ->assertSee('services/test/gallery/one.jpg', false)
            ->assertDontSee('Скрытое фото');
    }

    public function test_inactive_service_is_hidden_from_catalog_and_returns_404_by_slug(): void
    {
        $service = Service::query()->create([
            'name' => 'Архивная услуга',
            'slug' => 'inactive-public-service-test',
            'category' => 'spa',
            'description' => 'Не должна быть доступна посетителям.',
            'duration_minutes' => 30,
            'price' => 800,
            'capacity' => 1,
            'requires_trainer' => false,
            'online_booking' => false,
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertDontSee('Архивная услуга');

        $this->get(route('services.show', ['service' => $service->slug]))
            ->assertNotFound();
    }

    public function test_manager_can_upload_main_and_additional_photos_and_manage_gallery(): void
    {
        Storage::fake('public');

        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();
        $service = Service::where('slug', 'free-swimming')->firstOrFail();

        $this->actingAs($manager)
            ->patch(route('admin.services.update', $service), [
                'name' => $service->name,
                'slug' => $service->slug,
                'category' => $service->category,
                'description' => 'Новое подробное описание услуги.',
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'capacity' => $service->capacity,
                'sort_order' => $service->sort_order,
                'online_booking' => '1',
                'is_active' => '1',
                'main_image' => UploadedFile::fake()->image('main.jpg', 1600, 900),
                'additional_images' => [
                    UploadedFile::fake()->image('gallery-1.jpg', 1200, 800),
                    UploadedFile::fake()->image('gallery-2.jpg', 1200, 800),
                ],
            ])
            ->assertSessionHas('success');

        $service->refresh();
        $this->assertNotNull($service->main_image_path);
        Storage::disk('public')->assertExists($service->main_image_path);
        $this->assertSame(2, $service->photos()->count());

        $photo = $service->photos()->firstOrFail();
        Storage::disk('public')->assertExists($photo->image_path);

        $this->actingAs($manager)
            ->patch(route('admin.services.photos.update', [$service, $photo]), [
                'caption' => 'Подпись после редактирования',
                'sort_order' => 5,
            ])
            ->assertSessionHas('success');

        $photo->refresh();
        $this->assertSame('Подпись после редактирования', $photo->caption);
        $this->assertSame(5, $photo->sort_order);
        $this->assertFalse($photo->is_active);

        $path = $photo->image_path;
        $this->actingAs($manager)
            ->delete(route('admin.services.photos.destroy', [$service, $photo]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('service_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
