<?php

namespace App\Services;

/**
 * Validates uploaded avatars and re-encodes them as 256×256 PNGs so no
 * original bytes (or embedded payloads) are ever served back.
 */
class AvatarService
{
    private const SIZE = 256;
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * @return array{error?: string, path?: string} path is relative to /public
     */
    public function store(array $file, int $userId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return ['error' => 'The upload failed. Please try again.'];
        }

        $maxBytes = config('app.avatars.max_kb') * 1024;
        if ($file['size'] > $maxBytes) {
            return ['error' => 'Avatars must be smaller than ' . round($maxBytes / 1048576, 1) . ' MB.'];
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $info = @getimagesize($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED, true) || !$info) {
            return ['error' => 'Please upload a JPG, PNG, WEBP or GIF image.'];
        }

        $dir = config('app.avatars.dir');
        $absoluteDir = BASE_PATH . '/public/' . $dir;
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $name = $userId . '_' . bin2hex(random_bytes(8)) . '.png';
        if (!$this->resizeTo($file['tmp_name'], $mime, "$absoluteDir/$name")) {
            return ['error' => 'That image could not be processed.'];
        }

        return ['path' => "$dir/$name"];
    }

    public function delete(?string $path): void
    {
        $dir = config('app.avatars.dir');
        if ($path && str_starts_with($path, $dir . '/') && !str_contains($path, '..')) {
            @unlink(BASE_PATH . '/public/' . $path);
        }
    }

    private function resizeTo(string $source, string $mime, string $target): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png'  => @imagecreatefrompng($source),
            'image/webp' => @imagecreatefromwebp($source),
            'image/gif'  => @imagecreatefromgif($source),
        };
        if (!$image) {
            return false;
        }

        // Centre-crop to a square, then scale.
        $w = imagesx($image);
        $h = imagesy($image);
        $side = min($w, $h);
        $canvas = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled(
            $canvas, $image, 0, 0,
            (int) (($w - $side) / 2), (int) (($h - $side) / 2),
            self::SIZE, self::SIZE, $side, $side
        );

        return imagepng($canvas, $target, 6);
    }
}
