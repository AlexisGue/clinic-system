<?php

namespace App\Support\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;

/**
 * Secure image pipeline:
 * 1) Trust Laravel's MIME validation (real content sniffing).
 * 2) Re-encode with Intervention (strips EXIF / embedded payloads).
 * 3) Store under a random name — never use the client filename.
 */
class SecureImageUploader
{
    public function __construct(
        private readonly string $disk = 'public',
        private readonly int $maxWidth = 1600,
        private readonly int $quality = 82,
    ) {}

    public function store(UploadedFile $file, string $directory = 'products'): string
    {
        $directory = trim($directory, '/');
        $filename = Str::uuid()->toString().'.jpg';
        $path = $directory.'/'.$filename;

        try {
            $encoded = Image::read($file->getRealPath())
                ->scaleDown(width: $this->maxWidth)
                ->toJpeg(quality: $this->quality);
        } catch (\Throwable $e) {
            throw new RuntimeException('La imagen no pudo procesarse de forma segura.', 0, $e);
        }

        Storage::disk($this->disk)->put($path, (string) $encoded);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }
}
