<?php

namespace App\Services;

use App\Support\SiteSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    private const CACHE_KEY = 'sitewide-image-optimization-state-v2';
    private const DEFAULT_MAX_WIDTH = 2560;
    private const PANORAMA_MAX_WIDTH = 8192;
    private const WEBP_QUALITY = 82;

    private const SECTION_LABELS = [
        'gallery.covers' => 'Обложки фотогалереи',
        'gallery.photos' => 'Фото галереи',
        'gallery' => 'Фотогалерея',
        'panoramas' => '360° панорамы',
        'news' => 'Новости',
        'slides' => 'Слайдер',
        'trainers' => 'Тренеры',
        'customers' => 'Клиенты',
        'settings' => 'Настройки и бренд',
        'services' => 'Услуги',
        'seo' => 'SEO',
        'incidents' => 'Инциденты',
        'uploads' => 'Загрузки',
    ];

    private const DATABASE_IMAGE_COLUMNS = [
        ['table' => 'trainers', 'column' => 'photo_path'],
        ['table' => 'news_posts', 'column' => 'image_path'],
        ['table' => 'gallery_albums', 'column' => 'cover_path'],
        ['table' => 'gallery_photos', 'column' => 'image_path'],
        ['table' => 'hero_slides', 'column' => 'image_path'],
        ['table' => 'seo_pages', 'column' => 'og_image_path'],
        ['table' => 'services', 'column' => 'main_image_path'],
        ['table' => 'service_photos', 'column' => 'image_path'],
        ['table' => 'panoramas', 'column' => 'image_path'],
        ['table' => 'trainer_photos', 'column' => 'image_path'],
        ['table' => 'customers', 'column' => 'photo_path'],
        ['table' => 'safety_incidents', 'column' => 'photo_path'],
        ['table' => 'site_settings', 'column' => 'value'],
    ];

    public function scan(): array
    {
        $state = $this->loadState();
        $sections = [];
        $totalSize = 0;
        $optimized = 0;
        $webp = 0;
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
                    'webp' => 0,
                ];
            }

            $sections[$section]['count']++;
            $sections[$section]['size'] += $size;
            $sections[$section]['optimized'] += $isOptimized ? 1 : 0;
            $sections[$section]['webp'] += $this->isWebp($path) ? 1 : 0;

            $totalSize += $size;
            $optimized += $isOptimized ? 1 : 0;
            $webp += $this->isWebp($path) ? 1 : 0;
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
            'webp' => $webp,
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
        $converted = 0;
        $skipped = 0;
        $failed = 0;
        $databaseUpdates = 0;
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
                    'new_path' => $path,
                    'status' => 'skipped',
                    'before' => $before,
                    'after' => $before,
                ];
                continue;
            }

            try {
                $result = $this->optimizeFile($path);
                $newPath = $result['path'];
                $after = $this->safeSize($newPath);
                $bytesAfter += $after;

                unset($state[$path]);
                $state[$newPath] = $this->signature($newPath);

                if ($result['changed']) {
                    $optimized++;
                } else {
                    $skipped++;
                }

                if ($result['converted']) {
                    $converted++;
                }

                $databaseUpdates += $result['database_updates'];

                $items[] = [
                    'path' => $path,
                    'new_path' => $newPath,
                    'status' => $result['changed'] ? 'optimized' : 'skipped',
                    'converted' => $result['converted'],
                    'database_updates' => $result['database_updates'],
                    'before' => $before,
                    'after' => $after,
                    'dimensions' => $result['dimensions'],
                ];
            } catch (\Throwable $e) {
                $failed++;
                $bytesAfter += $before;
                $items[] = [
                    'path' => $path,
                    'new_path' => $path,
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
            'converted' => $converted,
            'skipped' => $skipped,
            'failed' => $failed,
            'database_updates' => $databaseUpdates,
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

        $targetPath = $this->isWebp($path)
            ? $path
            : $this->availableWebpPath($path);

        $targetAbsolute = $disk->path($targetPath);
        $temporary = $targetAbsolute.'.opt-'.Str::uuid().'.tmp';

        try {
            $result = class_exists(\Imagick::class)
                ? $this->convertWithImagick($absolute, $temporary, $maxWidth)
                : $this->convertWithGd($absolute, $temporary, $extension, $maxWidth);

            $before = filesize($absolute);
            $after = is_file($temporary) ? filesize($temporary) : false;

            if ($before === false || $after === false) {
                throw new \RuntimeException('Не удалось определить размер обработанного файла.');
            }

            $converted = $targetPath !== $path;
            $shouldReplace = $converted || $result['resized'] || $after < $before;

            if (! $shouldReplace) {
                @unlink($temporary);

                return [
                    'changed' => false,
                    'converted' => false,
                    'path' => $path,
                    'database_updates' => 0,
                    'dimensions' => $result['dimensions'],
                ];
            }

            $targetDirectory = dirname($targetAbsolute);
            if (! is_dir($targetDirectory) && ! @mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
                throw new \RuntimeException('Не удалось подготовить каталог для WebP.');
            }

            if (! @rename($temporary, $targetAbsolute)) {
                if (! @copy($temporary, $targetAbsolute)) {
                    throw new \RuntimeException('Не удалось сохранить WebP-файл.');
                }

                @unlink($temporary);
            }

            clearstatcache(true, $targetAbsolute);

            $databaseUpdates = 0;

            if ($converted) {
                try {
                    $databaseUpdates = DB::transaction(
                        fn (): int => $this->replaceDatabaseReferences($path, $targetPath)
                    );
                } catch (\Throwable $e) {
                    @unlink($targetAbsolute);
                    throw $e;
                }

                if (! $disk->delete($path) && $disk->exists($path)) {
                    throw new \RuntimeException('WebP создан и база обновлена, но старый файл не удалось удалить.');
                }
            }

            return [
                'changed' => true,
                'converted' => $converted,
                'path' => $targetPath,
                'database_updates' => $databaseUpdates,
                'dimensions' => $result['dimensions'],
            ];
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function convertWithImagick(string $sourcePath, string $targetPath, int $maxWidth): array
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
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(self::WEBP_QUALITY);
            $image->setOption('webp:method', '6');

            if (! $image->writeImage($targetPath)) {
                throw new \RuntimeException('Не удалось записать WebP.');
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

    private function convertWithGd(string $sourcePath, string $targetPath, string $extension, int $maxWidth): array
    {
        if (! function_exists('imagewebp')) {
            throw new \RuntimeException('На сервере GD собран без поддержки WebP. Установите Imagick или GD WebP.');
        }

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

                imagealphablending($output, false);
                imagesavealpha($output, true);
                $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
                imagefill($output, 0, 0, $transparent);

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

            if (! imagewebp($output, $targetPath, self::WEBP_QUALITY)) {
                throw new \RuntimeException('Не удалось записать WebP.');
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

    private function replaceDatabaseReferences(string $oldPath, string $newPath): int
    {
        $updated = 0;
        $siteSettingsChanged = false;

        foreach (self::DATABASE_IMAGE_COLUMNS as $reference) {
            $table = $reference['table'];
            $column = $reference['column'];

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $count = DB::table($table)
                ->where($column, $oldPath)
                ->update([$column => $newPath]);

            $updated += $count;

            if ($count > 0 && $table === 'site_settings') {
                $siteSettingsChanged = true;
            }
        }

        if ($siteSettingsChanged) {
            SiteSettings::flush();
        }

        return $updated;
    }

    private function availableWebpPath(string $path): string
    {
        $disk = Storage::disk('public');
        $directory = dirname($path);
        $directory = $directory === '.' ? '' : $directory.'/';
        $name = pathinfo($path, PATHINFO_FILENAME);
        $candidate = $directory.$name.'.webp';

        if (! $disk->exists($candidate)) {
            return $candidate;
        }

        do {
            $candidate = $directory.$name.'-'.Str::lower(Str::random(8)).'.webp';
        } while ($disk->exists($candidate));

        return $candidate;
    }

    private function isWebp(string $path): bool
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'webp';
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
