<?php

declare(strict_types=1);

use Softgeng\UploadPost\Support\Arr;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\UploadPostConfig;

test('arr get reads direct nested and default values', function (): void {
    $array = ['profile' => ['name' => 'upload-post'], 'count' => 3];

    expect(Arr::get($array, 'count'))->toBe(3)
        ->and(Arr::get($array, 'profile.name'))->toBe('upload-post')
        ->and(Arr::get($array, 'profile.missing', 'fallback'))->toBe('fallback')
        ->and(Arr::get($array, 'missing', 'fallback'))->toBe('fallback');
});

test('arr where not blank keeps false and zero values', function (): void {
    expect(Arr::whereNotBlank([
        'null' => null,
        'empty' => '',
        'false' => false,
        'zero' => 0,
        'value' => 'ok',
    ]))->toBe([
        'false' => false,
        'zero' => 0,
        'value' => 'ok',
    ]);
});

test('media handles urls local files spl files and uploaded file like objects', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'upload-post-');
    file_put_contents($file, 'content');

    $uploadedFile = new class($file)
    {
        public function __construct(private readonly string $path) {}

        public function getRealPath(): string
        {
            return $this->path;
        }

        public function getClientOriginalName(): string
        {
            return 'uploaded.txt';
        }
    };

    $urlPart = Media::from('https://example.com/photo.jpg')->toMultipartPart('photo');
    $pathPart = Media::from($file)->toMultipartPart('file');
    $splPart = Media::from(new SplFileInfo($file))->toMultipartPart('spl');
    $uploadedPart = Media::from($uploadedFile)->toMultipartPart('upload');

    expect($urlPart)->toBe(['name' => 'photo', 'contents' => 'https://example.com/photo.jpg'])
        ->and($pathPart['filename'])->toBe(basename($file))
        ->and(is_resource($pathPart['contents']))->toBeTrue()
        ->and($splPart['filename'])->toBe(basename($file))
        ->and(is_resource($splPart['contents']))->toBeTrue()
        ->and($uploadedPart['filename'])->toBe('uploaded.txt')
        ->and(is_resource($uploadedPart['contents']))->toBeTrue();
});

test('media falls back to uploaded file get filename', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'upload-post-');
    file_put_contents($file, 'content');

    $uploadedFile = new class($file)
    {
        public function __construct(private readonly string $path) {}

        public function getRealPath(): string
        {
            return $this->path;
        }

        public function getFilename(): string
        {
            return 'fallback.txt';
        }
    };

    $part = Media::from($uploadedFile)->toMultipartPart('upload');

    expect($part['filename'])->toBe('fallback.txt')
        ->and(is_resource($part['contents']))->toBeTrue();
});

test('media rejects invalid uploaded file like objects', function (): void {
    $uploadedFile = new class
    {
        public function getRealPath(): false
        {
            return false;
        }
    };

    expect(fn (): array => Media::from($uploadedFile)->toMultipartPart('upload'))
        ->toThrow(InvalidArgumentException::class, 'Invalid uploaded file for upload.');
});

test('media rejects missing files and unsupported values', function (): void {
    expect(fn (): array => Media::from('missing-file.jpg')->toMultipartPart('file'))
        ->toThrow(InvalidArgumentException::class, 'Invalid media for file.');
});

test('upload post config rejects blank api keys and trims base url', function (): void {
    expect(fn (): UploadPostConfig => new UploadPostConfig(apiKey: ''))
        ->toThrow(InvalidArgumentException::class, 'Upload-Post API key is required.');

    $config = UploadPostConfig::fromArray([
        'api_key' => 'test',
        'base_url' => 'https://api.example.com/',
    ]);

    expect($config->baseUrl)->toBe('https://api.example.com');
});
