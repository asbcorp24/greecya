<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Panorama;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PanoramaController extends Controller
{
    private const JPEG_QUALITY = 82;
    private const MAX_WIDTH = 8192;

    public function index()
    {
        return view('admin.panoramas.index', [
            'panoramas' => Panorama::query()
                ->orderByDesc('is_homepage')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $makeHomepage = $request->boolean('is_homepage');
        $imagePath = $this->storeOptimizedJpeg($request->file('image'));

        try {
            DB::transaction(function () use ($request, $data, $makeHomepage, $imagePath) {
                if ($makeHomepage) {
                    Panorama::query()->update(['is_homepage' => false]);
                }

                Panorama::query()->create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'image_path' => $imagePath,
                    'sort_order' => $data['sort_order'] ?? 100,
                    'is_active' => $makeHomepage ? true : $request->boolean('is_active'),
                    'is_homepage' => $makeHomepage,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($imagePath);
            throw $e;
        }

        return back()->with('success', '360° панорама добавлена и оптимизирована в JPEG.');
    }

    public function update(Request $request, Panorama $panorama)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $makeHomepage = $request->boolean('is_homepage');
        $newImagePath = $request->hasFile('image')
            ? $this->storeOptimizedJpeg($request->file('image'))
            : null;
        $oldImagePath = $panorama->image_path;

        try {
            DB::transaction(function () use ($request, $data, $panorama, $makeHomepage, $newImagePath, $oldImagePath) {
                if ($makeHomepage) {
                    Panorama::query()
                        ->where('id', '!=', $panorama->id)
                        ->update(['is_homepage' => false]);
                }

                $panorama->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'image_path' => $newImagePath ?: $oldImagePath,
                    'sort_order' => $data['sort_order'] ?? 100,
                    'is_active' => $makeHomepage ? true : $request->boolean('is_active'),
                    'is_homepage' => $makeHomepage,
                ]);
            });
        } catch (\Throwable $e) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $e;
        }

        if ($newImagePath && $oldImagePath && $oldImagePath !== $newImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return back()->with('success', '360° панорама обновлена и оптимизирована в JPEG.');
    }

    public function compress(Panorama $panorama)
    {
        $disk = Storage::disk('public');
        $oldImagePath = $panorama->image_path;

        if (! $oldImagePath || ! $disk->exists($oldImagePath)) {
            throw ValidationException::withMessages([
                'image' => 'Исходный файл панорамы не найден на сервере.',
            ]);
        }

        $beforeSize = (int) $disk->size($oldImagePath);
        $sourceTemporaryPath = tempnam(sys_get_temp_dir(), 'greecya-panorama-source-');

        if ($sourceTemporaryPath === false) {
            throw ValidationException::withMessages([
                'image' => 'Не удалось подготовить временный файл для сжатия панорамы.',
            ]);
        }

        $readStream = null;
        $writeStream = null;
        $newImagePath = null;

        try {
            $readStream = $disk->readStream($oldImagePath);
            $writeStream = fopen($sourceTemporaryPath, 'wb');

            if (! is_resource($readStream) || $writeStream === false) {
                throw ValidationException::withMessages([
                    'image' => 'Не удалось прочитать исходный файл панорамы.',
                ]);
            }

            stream_copy_to_stream($readStream, $writeStream);

            fclose($readStream);
            $readStream = null;
            fclose($writeStream);
            $writeStream = null;

            $sourceFile = new UploadedFile(
                $sourceTemporaryPath,
                basename($oldImagePath),
                mime_content_type($sourceTemporaryPath) ?: null,
                null,
                true
            );

            $newImagePath = $this->storeOptimizedJpeg($sourceFile);
            $afterSize = (int) $disk->size($newImagePath);

            try {
                $panorama->update(['image_path' => $newImagePath]);
            } catch (\Throwable $e) {
                $disk->delete($newImagePath);
                throw $e;
            }

            if ($oldImagePath !== $newImagePath) {
                $disk->delete($oldImagePath);
            }

            return back()->with(
                'success',
                'Панорама сжата: '.$this->formatBytes($beforeSize).' → '.$this->formatBytes($afterSize).'. Файл сохранён в JPEG, запись в базе обновлена.'
            );
        } finally {
            if (is_resource($readStream)) {
                fclose($readStream);
            }

            if (is_resource($writeStream)) {
                fclose($writeStream);
            }

            @unlink($sourceTemporaryPath);
        }
    }

    public function destroy(Panorama $panorama)
    {
        $path = $panorama->image_path;
        $panorama->delete();

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', '360° панорама удалена.');
    }

    private function storeOptimizedJpeg(UploadedFile $file): string
    {
        $path = 'panoramas/'.Str::uuid().'.jpg';
        $temporaryPath = tempnam(sys_get_temp_dir(), 'greecya-panorama-');

        if ($temporaryPath === false) {
            throw ValidationException::withMessages([
                'image' => 'Не удалось подготовить временный файл для обработки панорамы.',
            ]);
        }

        $stream = null;

        try {
            if (class_exists(\Imagick::class)) {
                $this->optimizeWithImagick($file, $temporaryPath);
            } else {
                $this->optimizeWithGd($file, $temporaryPath);
            }

            $stream = fopen($temporaryPath, 'rb');

            if ($stream === false || ! Storage::disk('public')->put($path, $stream)) {
                throw ValidationException::withMessages([
                    'image' => 'Не удалось сохранить оптимизированную панораму.',
                ]);
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            @unlink($temporaryPath);
        }

        return $path;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' Б';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', ' ').' КБ';
        }

        return number_format($bytes / (1024 * 1024), 1, ',', ' ').' МБ';
    }

    private function optimizeWithImagick(UploadedFile $file, string $targetPath): void
    {
        try {
            $source = new \Imagick($file->getRealPath());
            $source->setIteratorIndex(0);
            $image = $source->getImage();
            $source->clear();
            $source->destroy();

            if ($image->getImageWidth() > self::MAX_WIDTH) {
                $image->thumbnailImage(self::MAX_WIDTH, 0, true);
            }

            if ($image->getImageAlphaChannel()) {
                $image->setImageBackgroundColor('white');
                $flattened = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $image->clear();
                $image->destroy();
                $image = $flattened;
            }

            $image->setImageFormat('jpeg');
            $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $image->setImageCompressionQuality(self::JPEG_QUALITY);
            $image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
            $image->stripImage();

            if (! $image->writeImage($targetPath)) {
                throw new \RuntimeException('Imagick could not write JPEG.');
            }

            $image->clear();
            $image->destroy();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'image' => 'Не удалось обработать панораму. Проверьте, что файл является корректным JPG, PNG или WebP.',
            ]);
        }
    }

    private function optimizeWithGd(UploadedFile $file, string $targetPath): void
    {
        if (! function_exists('imagejpeg')) {
            throw ValidationException::withMessages([
                'image' => 'На сервере недоступна обработка изображений GD/Imagick.',
            ]);
        }

        $realPath = $file->getRealPath();
        $mime = $file->getMimeType();

        $source = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($realPath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($realPath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($realPath) : false,
            default => false,
        };

        if (! $source) {
            throw ValidationException::withMessages([
                'image' => 'Не удалось прочитать изображение. Проверьте формат файла или поддержку WebP на сервере.',
            ]);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $output = $source;

        try {
            if ($width > self::MAX_WIDTH) {
                $targetWidth = self::MAX_WIDTH;
                $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));
                $resized = imagecreatetruecolor($targetWidth, $targetHeight);

                if (! $resized) {
                    throw new \RuntimeException('GD could not allocate resized image.');
                }

                $white = imagecolorallocate($resized, 255, 255, 255);
                imagefill($resized, 0, 0, $white);

                if (! imagecopyresampled(
                    $resized,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $width,
                    $height
                )) {
                    imagedestroy($resized);
                    throw new \RuntimeException('GD could not resize image.');
                }

                $output = $resized;
            }

            imageinterlace($output, true);

            if (! imagejpeg($output, $targetPath, self::JPEG_QUALITY)) {
                throw new \RuntimeException('GD could not write JPEG.');
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'image' => 'Не удалось сжать панораму. Попробуйте уменьшить исходное изображение и загрузить его снова.',
            ]);
        } finally {
            if ($output !== $source && is_resource($output)) {
                imagedestroy($output);
            } elseif ($output !== $source && $output instanceof \GdImage) {
                imagedestroy($output);
            }

            if (is_resource($source)) {
                imagedestroy($source);
            } elseif ($source instanceof \GdImage) {
                imagedestroy($source);
            }
        }
    }
}
