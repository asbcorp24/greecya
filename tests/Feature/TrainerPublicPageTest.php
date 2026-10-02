<?php

namespace Tests\Feature;

use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainerPublicPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_links_to_active_trainer_public_page(): void
    {
        $trainer = Trainer::query()->where('is_active', true)->whereNotNull('slug')->firstOrFail();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Наши тренеры')
            ->assertSee('Подробнее')
            ->assertSee(route('trainers.show', ['trainer' => $trainer->slug]), false);
    }

    public function test_public_trainer_page_shows_bio_and_only_active_gallery_photos(): void
    {
        $trainer = Trainer::query()->create([
            'name' => 'Ирина Тестова',
            'specialization' => 'Тренер по плаванию',
            'bio' => "Тренер по плаванию с индивидуальным подходом.\nРаботает со взрослыми и детьми.",
            'photo_path' => 'trainers/main/irina.jpg',
            'experience_years' => 9,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $trainer->photos()->create([
            'image_path' => 'trainers/'.$trainer->id.'/gallery/active.jpg',
            'caption' => 'Тренировка в бассейне',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $trainer->photos()->create([
            'image_path' => 'trainers/'.$trainer->id.'/gallery/hidden.jpg',
            'caption' => 'Скрытая фотография',
            'sort_order' => 20,
            'is_active' => false,
        ]);

        $this->get(route('trainers.show', ['trainer' => $trainer->slug]))
            ->assertOk()
            ->assertSee('Ирина Тестова')
            ->assertSee('Тренер по плаванию')
            ->assertSee('Тренер по плаванию с индивидуальным подходом.')
            ->assertSee('Стаж 9 лет')
            ->assertSee('Фотогалерея')
            ->assertSee('Тренировка в бассейне')
            ->assertSee('active.jpg', false)
            ->assertDontSee('Скрытая фотография')
            ->assertDontSee('hidden.jpg', false);
    }

    public function test_inactive_trainer_public_page_returns_404(): void
    {
        $trainer = Trainer::query()->create([
            'name' => 'Скрытый Тренер',
            'specialization' => 'Тренер',
            'bio' => 'Не должен быть доступен на сайте.',
            'experience_years' => 1,
            'sort_order' => 100,
            'is_active' => false,
        ]);

        $this->get(route('trainers.show', ['trainer' => $trainer->slug]))
            ->assertNotFound();
    }

    public function test_manager_can_upload_manage_and_delete_trainer_gallery_photo(): void
    {
        Storage::fake('public');

        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();
        $trainer = Trainer::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($manager)
            ->post(route('admin.trainers.photos.store', $trainer), [
                'gallery_images' => [
                    UploadedFile::fake()->image('one.jpg', 1200, 800),
                    UploadedFile::fake()->image('two.jpg', 1200, 800),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, $trainer->photos()->count());

        $photo = $trainer->photos()->firstOrFail();
        Storage::disk('public')->assertExists($photo->image_path);

        $this->actingAs($manager)
            ->patch(route('admin.trainers.photos.update', [$trainer, $photo]), [
                'caption' => 'Фото с занятия',
                'sort_order' => 5,
                'is_active' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $photo->refresh();
        $this->assertSame('Фото с занятия', $photo->caption);
        $this->assertSame(5, $photo->sort_order);
        $this->assertFalse($photo->is_active);

        $path = $photo->image_path;

        $this->actingAs($manager)
            ->delete(route('admin.trainers.photos.destroy', [$trainer, $photo]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('trainer_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_active_trainer_page_is_in_sitemap(): void
    {
        $trainer = Trainer::query()->where('is_active', true)->whereNotNull('slug')->firstOrFail();

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee(route('trainers.show', ['trainer' => $trainer->slug]), false);
    }
}
