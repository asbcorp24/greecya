<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    private const CACHE_KEY = 'sitewide-image-optimization-state-v1';
    private const DEFAULT_MAX_WIDTH = 2560;
    private const PANORAMA_MAX_WIDTH = 8192;
    private const JPEG_QUALITY = 82;
    private const WEBP_QUALITY = 82;

    private const SECTION_LABELS = [
        'gallery.covers' => 'Обложки фотогалереи',
        'gallery.photos' => 'Фото галереи',
        'gallery' => 'Фотогалерея',
        'panoramas' => '360° панорамы',
        'news' => 'Новости',
        'slides' => 'Слайдер',
        'trainers' => 'Тренеры',
        'settings' => 'Настройки и бренд',
        'services' => 'Услуги',
        'seo' => 'SEO',
        'uploads' => 'Загрузки',
    ];

    public function scan(): array
    {
        $state = $this->loadState();
        $sections = [];
        $totalSize = 0;
        $optimized = 0;
        $files = $this->imageFiles();

        foreach ($files as $path) {
            $size = $this->safeSize($path);
            $section = $this->sectionKey($path);
            $isOptimized = $this->isCurrentState($path, $state);

            if (! isset($sections[$section])) {
                $sections[$section] = [
                    'key' => $section,
                    'path' => $this->sectionPath($section),
                    'label' => self::SECTION_LABELS[$section] ?? $this->humanizeSection($section),
                    'count' => 0,
                    'size' => 0,
                    'optimized' => 0,
                ];
            }

            $sections[$section]['count']++;
            $sections[$section]['size'] += $size;
            $sections[$section]['optimized'] += $isOptimized ? 1 : 0;
            $totalSize += $size;
            $optimized += $isOptimized ? 1 : 0;
        }

        uasort($sections, fn (array $a, array $b) => $b['size'] <=> $a['size']);

        foreach ($sections as &$section) {
            $section['size_formatted'] = $this->formatBytes($section['size']);
        }
        unset($section);

        return [
            'count' => count($files),
            'size' => $totalSize,
            'size_formatted' => $this->formatBytes($totalSize),
            'optimized' => $optimized,
            'pending' => max(0, count($files) - $optimized),
            'sections' => array_values($sections),
        ];
    }

    public function optimizeBatch(int $offset, int $limit, ?string $section = null, bool $force = false): array
    {
        $files = $this->imageFiles($section);
        $total = count($files);
        $slice = array_slice($files, $offset, $limit);
        $state = $this->loadState();

        $optimized = 0;
        $skipped = 0;
        $failed = 0;
        $bytesBefore = 0;
        $bytesAfter = 0;
        $items = [];

        foreach ($slice as $path) {
            $before = $this->safeSize($path);
            $bytesBefore += $before;

            if (! $force && $this->isCurrentState($path, $state)) {
                $skipped++;
                $bytesAfter += $before;
                $items[] = [
                    'path' => $path,
                    'status' => 'skipped',
                    'before' => $before,
                    'after' => $before,
                ];
                continue;
            }

            try {
                $result = $this->optimizeFile($path);
                $after = $this->safeSize($path);
                $bytesAfter += $after;

                $state[$path] = $this->signature($path);

                if ($result['changed']) {
                    $optimized++;
                } else {
                    $skipped++;
                }

                $items[] = [
                    'path' => $path,
                    'status' => $result['changed'] ? 'optimized' : 'skipped',
                    'before' => $before,
                    'after' => $after,
                    'dimensions' => $result['dimensions'],
                ];
            } catch (\Throwable $e) {
                $failed++;
                $bytesAfter += $before;
                $items[] = [
                    'path' => $path,
                    'status' => 'failed',
                    'before' => $before,
                    'after' => $before,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->saveState($state);

        $processed = count($slice);
        $nextOffset = min($total, $offset + $processed);

        return [
            'processed' => $processed,
            'next_offset' => $nextOffset,
            'total' => $total,
            'done' => $nextOffset >= $total,
            'optimized' => $optimized,
            'skipped' => $skipped,
            'failed' => $failed,
            'bytes_before' => $bytesBefore,
            'bytes_after' => $bytesAfter,
            'saved_bytes' => max(0, $bytesBefore - $bytesAfter),
            'items' => $items,
        ];
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' Б';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', ' ').' КБ';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1, ',', ' ').' МБ';
        }

        return number_format($bytes / (1024 * 1024 * 1024), 2, ',', ' ').' ГБ';
    }

    private function imageFiles(?string $section = null): array
    {
        $files = array_values(array_filter(
            Storage::disk('public')->allFiles(),
            function (string $path) use ($section): bool {
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    return false;
                }

                if ($section !== null && $section !== '' && $this->sectionKey($path) !== $section) {
                    return false;
                }

                return true;
            }
        ));

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return $files;
    }

    private function optimizeFile(string $path): array
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            throw new \RuntimeException('Файл не найден.');
        }

        $absolute = $disk->path($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $maxWidth = str_starts_with($path, 'panoramas/')
            ? self::PANORAMA_MAX_WIDTH
            : self::DEFAULT_MAX_WIDTH;

        $temporary = $absolute.'.opt-'.Str::uuid().'.tmp';

        try {
            $result = class_exists(\Imagick::class)
                ? $this->optimizeWithImagick($absolute, $temporary, $extension, $maxWidth)
                : $this->optimizeWithGd($absolute, $temporary, $extension, $maxWidth);

            $before = filesize($absolute);
            $after = is_file($temporary) ? filesize($temporary) : false;

            if ($before === false || $after === false) {
                throw new \RuntimeException('Не удалось определить размер обработанного файла.');
            }

            $shouldReplace = $result['resized'] || $after < $before;

            if (! $shouldReplace) {
                @unlink($temporary);

                return [
                    'changed' => false,
                    'dimensions' => $result['dimensions'],
                ];
            }

            if (! @rename($temporary, $absolute)) {
                if (! @copy($temporary, $absolute)) {
                    throw new \RuntimeException('Не удалось заменить исходный файл.');
                }

                @unlink($temporary);
            }

            clearstatcache(true, $absolute);

            return [
                'changed' => true,
                'dimensions' => $result['dimensions'],
            ];
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function optimizeWithImagick(string $sourcePath, string $targetPath, string $extension, int $maxWidth): array
    {
        $source = new \Imagick($sourcePath);
        $source->setIteratorIndex(0);
        $image = $source->getImage();
        $source->clear();
        $source->destroy();

        $resized = false;

        try {
            if ($image->getImageWidth() > $maxWidth) {
                $image->thumbnailImage($maxWidth, 0, true);
                $resized = true;
            }

            $image->stripImage();

            if (in_array($extension, ['jpg', 'jpeg'], true)) {
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
            } elseif ($extension === 'webp') {
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(self::WEBP_QUALITY);
                $image->setOption('webp:method', '6');
            } elseif ($extension === 'png') {
                $image->setImageFormat('png');
                $image->setOption('png:compression-level', '9');
                $image->setOption('png:compression-filter', '5');
            } else {
                throw new \RuntimeException('Неподдерживаемый формат изображения.');
            }

            if (! $image->writeImage($targetPath)) {
                throw new \RuntimeException('Не удалось записать оптимизированное изображение.');
            }

            return [
                'resized' => $resized,
                'dimensions' => $image->getImageWidth().'×'.$image->getImageHeight(),
            ];
        } finally {
            $image->clear();
            $image->destroy();
        }
    }

    private function optimizeWithGd(string $sourcePath, string $targetPath, string $extension, int $maxWidth): array
    {
        $source = match ($extension) {
            'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($sourcePath) : false,
            'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (! $source) {
            throw new \RuntimeException('Сервер не смог прочитать изображение формата '.strtoupper($extension).'.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $resized = false;
        $output = $source;

        try {
            if ($width > $maxWidth) {
                $targetWidth = $maxWidth;
                $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));
                $output = imagecreatetruecolor($targetWidth, $targetHeight);

                if (! $output) {
                    throw new \RuntimeException('Не удалось выделить память для изменения размера.');
                }

                if (in_array($extension, ['png', 'webp'], true)) {
                    imagealphablending($output, false);
                    imagesavealpha($output, true);
                    $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
                    imagefill($output, 0, 0, $transparent);
                } else {
                    $white = imagecolorallocate($output, 255, 255, 255);
                    imagefill($output, 0, 0, $white);
                }

                if (! imagecopyresampled(
                    $output,
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
                    throw new \RuntimeException('Не удалось уменьшить изображение.');
                }

                $width = $targetWidth;
                $height = $targetHeight;
                $resized = true;
            }

            $written = match ($extension) {
                'jpg', 'jpeg' => $this->writeJpeg($output, $targetPath),
                'png' => imagepng($output, $targetPath, 9),
                'webp' => function_exists('imagewebp') ? imagewebp($output, $targetPath, self::WEBP_QUALITY) : false,
                default => false,
            };

            if (! $written) {
                throw new \RuntimeException('Не удалось записать оптимизированное изображение.');
            }

            return [
                'resized' => $resized,
                'dimensions' => $width.'×'.$height,
            ];
        } finally {
            if ($output !== $source && (is_resource($output) || $output instanceof \GdImage)) {
                imagedestroy($output);
            }

            if (is_resource($source) || $source instanceof \GdImage) {
                imagedestroy($source);
            }
        }
    }

    private function writeJpeg($image, string $targetPath): bool
    {
        imageinterlace($image, true);

        return imagejpeg($image, $targetPath, self::JPEG_QUALITY);
    }

    private function sectionKey(string $path): string
    {
        if (str_starts_with($path, 'gallery/covers/')) {
            return 'gallery.covers';
        }

        if (str_starts_with($path, 'gallery/photos/')) {
            return 'gallery.photos';
        }

        return explode('/', $path, 2)[0] ?: 'root';
    }

    private function sectionPath(string $section): string
    {
        return match ($section) {
            'gallery.covers' => 'gallery/covers',
            'gallery.photos' => 'gallery/photos',
            default => $section,
        };
    }

    private function humanizeSection(string $section): string
    {
        return Str::headline(str_replace(['_', '-'], ' ', $section));
    }

    private function safeSize(string $path): int
    {
        try {
            return (int) Storage::disk('public')->size($path);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function signature(string $path): string
    {
        $disk = Storage::disk('public');

        try {
            return $disk->size($path).':'.$disk->lastModified($path);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function isCurrentState(string $path, array $state): bool
    {
        return isset($state[$path]) && $state[$path] !== '' && $state[$path] === $this->signature($path);
    }

    private function loadState(): array
    {
        $state = Cache::get(self::CACHE_KEY, []);

        return is_array($state) ? $state : [];
    }

    private function saveState(array $state): void
    {
        Cache::forever(self::CACHE_KEY, $state);
    }
}
