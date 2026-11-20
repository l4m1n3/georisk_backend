<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MediaService
{
    /** Maximum allowed file size in kilobytes (configurable via .env PHOTO_MAX_SIZE_KB) */
    private int $maxSizeKb;

    /** Allowed MIME types */
    private array $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct()
    {
        $this->maxSizeKb = (int) config('app.photo_max_size_kb', 5120);
    }

    /**
     * Validate and store an uploaded photo.
     *
     * @param  string  $directory  Target directory inside storage/app/public
     * @return string The public path (e.g. "photos/uuid.jpg")
     *
     * @throws InvalidArgumentException on validation failure
     * @throws RuntimeException on storage failure
     */
    public function upload(UploadedFile $file, string $directory = 'photos'): string
    {
        $this->validateFile($file);

        $extension = $file->extension();
        $filename = Str::uuid().'.'.$extension;

        $path = $file->storeAs($directory, $filename, 'public');

        if ($path === false) {
            throw new RuntimeException('Failed to store uploaded file.');
        }

        return $path;
    }

    /**
     * Delete a stored photo by its path.
     */
    public function delete(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Return the full public URL for a stored photo path.
     */
    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    // ─── Private ────────────────────────────────────────────────────────────

    private function validateFile(UploadedFile $file): void
    {
        // MIME type check
        if (! in_array($file->getMimeType(), $this->allowedMimes, true)) {
            throw new InvalidArgumentException(
                'Invalid file type. Only JPEG, PNG and WebP are accepted.'
            );
        }

        // Extension check (defense in depth)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array(strtolower($file->extension()), $allowedExtensions, true)) {
            throw new InvalidArgumentException('Invalid file extension.');
        }

        // File size check
        $sizeKb = $file->getSize() / 1024;
        if ($sizeKb > $this->maxSizeKb) {
            throw new InvalidArgumentException(
                "File too large. Maximum allowed size is {$this->maxSizeKb} KB."
            );
        }

        // Ensure the file is actually uploaded (not a local path exploit)
        if (! $file->isValid()) {
            throw new InvalidArgumentException('Uploaded file is not valid.');
        }
    }
}
