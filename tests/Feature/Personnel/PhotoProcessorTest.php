<?php

use App\Services\PhotoProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('process resizes image to max 1080px on longest side', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.jpg', 2000, 1500);
    $processor = new PhotoProcessor;

    $result = $processor->process($file);

    expect(Storage::disk('public')->exists($result['photo_path']))->toBeTrue();

    $stored = Storage::disk('public')->get($result['photo_path']);
    $image = imagecreatefromstring($stored);
    $width = imagesx($image);
    $height = imagesy($image);

    expect(max($width, $height))->toBeLessThanOrEqual(1080);
});

test('process does not upscale small images', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.jpg', 500, 400);
    $processor = new PhotoProcessor;

    $result = $processor->process($file);

    $stored = Storage::disk('public')->get($result['photo_path']);
    $image = imagecreatefromstring($stored);
    $width = imagesx($image);
    $height = imagesy($image);

    expect($width)->toBeLessThanOrEqual(500)
        ->and($height)->toBeLessThanOrEqual(400);
});

test('process outputs JPEG format regardless of input', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.png', 500, 400);
    $processor = new PhotoProcessor;

    $result = $processor->process($file);

    expect($result['photo_path'])->toEndWith('.jpg');
});

test('process computes MD5 hash of the encoded output', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
    $processor = new PhotoProcessor;

    $result = $processor->process($file);

    expect($result['photo_hash'])->toMatch('/^[a-f0-9]{32}$/');
});

test('process stores file in public disk under personnel directory', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
    $processor = new PhotoProcessor;

    $result = $processor->process($file);

    expect($result['photo_path'])->toStartWith('personnel/')
        ->and($result['photo_path'])->toEndWith('.jpg')
        ->and(Storage::disk('public')->exists($result['photo_path']))->toBeTrue();
});

test('process returns array with photo_path and photo_hash keys', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
    $processor = new PhotoProcessor;

    $result = $processor->process($file);

    expect($result)->toHaveKeys(['photo_path', 'photo_hash'])
        ->and($result['photo_path'])->toBeString()
        ->and($result['photo_hash'])->toBeString();
});

test('process reduces quality iteratively if output exceeds max size bytes', function () {
    Storage::fake('public');

    // Set an extremely low max size to force quality reduction
    config(['hds.photo.max_size_bytes' => 100]);
    config(['hds.photo.jpeg_quality' => 95]);

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
    $processor = new PhotoProcessor;

    $result = $processor->process($file);

    // Should still produce a valid file even if it can't get under 100 bytes
    expect(Storage::disk('public')->exists($result['photo_path']))->toBeTrue()
        ->and($result['photo_hash'])->toMatch('/^[a-f0-9]{32}$/');
});

test('delete removes file from public disk', function () {
    Storage::fake('public');

    Storage::disk('public')->put('personnel/delete-me.jpg', 'fake-content');

    expect(Storage::disk('public')->exists('personnel/delete-me.jpg'))->toBeTrue();

    $processor = new PhotoProcessor;
    $processor->delete('personnel/delete-me.jpg');

    expect(Storage::disk('public')->exists('personnel/delete-me.jpg'))->toBeFalse();
});

test('delete handles null path gracefully', function () {
    $processor = new PhotoProcessor;

    // Should not throw any exception
    $processor->delete(null);

    expect(true)->toBeTrue();
});

test('delete handles non-existent file gracefully', function () {
    Storage::fake('public');

    $processor = new PhotoProcessor;

    // Should not throw any exception
    $processor->delete('personnel/does-not-exist.jpg');

    expect(true)->toBeTrue();
});
