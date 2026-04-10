<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;

class PhotoProcessor
{
    /**
     * Process an uploaded photo: orient, resize, compress, hash, and store.
     *
     * @return array{photo_path: string, photo_hash: string}
     */
    public function process(UploadedFile $file): array
    {
        $maxDim = config('hds.photo.max_dimension');
        $quality = config('hds.photo.jpeg_quality');
        $maxBytes = config('hds.photo.max_size_bytes');

        $image = Image::decodePath($file->path());
        $image->orient();
        $image->scaleDown(width: $maxDim, height: $maxDim);

        $encoded = $image->encode(new JpegEncoder(quality: $quality));

        while (strlen((string) $encoded) > $maxBytes && $quality > 40) {
            $quality -= 10;
            $encoded = $image->encode(new JpegEncoder(quality: $quality));
        }

        $filename = Str::uuid().'.jpg';
        $path = 'personnel/'.$filename;

        Storage::disk('public')->put($path, (string) $encoded);

        return [
            'photo_path' => $path,
            'photo_hash' => md5((string) $encoded),
        ];
    }

    /** Delete a photo from the public disk. */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
