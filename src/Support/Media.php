<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Support;

use InvalidArgumentException;
use SplFileInfo;

/**
 * Represents uploadable media accepted by Upload-Post.
 *
 * This class intentionally has no Laravel dependency. It accepts:
 * - local file path string
 * - remote http(s) URL string
 * - SplFileInfo
 * - any object exposing Laravel/Symfony-like getRealPath() and getClientOriginalName()/getFilename()
 */
final readonly class Media
{
    public function __construct(public string|object $value) {}

    public static function from(string|object $value): self
    {
        return new self($value);
    }

    public function isUrl(): bool
    {
        return is_string($this->value) && preg_match('/^https?:\/\//i', $this->value) === 1;
    }

    /** 
     * @return array{
     *  name:string,
     *  contents:resource|string,
     *  filename?:string
     * } 
     */
    public function toMultipartPart(string $field): array
    {
        if (is_string($this->value) && $this->isUrl()) {
            return ['name' => $field, 'contents' => $this->value];
        }

        if ($this->value instanceof SplFileInfo) {
            $path = $this->value->getRealPath();
            if ($path === false || ! is_file($path)) {
                throw new InvalidArgumentException("File not found for {$field}.");
            }

            return ['name' => $field, 'contents' => $this->openFile($path, $field), 'filename' => $this->value->getFilename()];
        }

        if (is_string($this->value) && is_file($this->value)) {
            return ['name' => $field, 'contents' => $this->openFile($this->value, $field), 'filename' => basename($this->value)];
        }

        if (is_object($this->value) && method_exists($this->value, 'getRealPath')) {
            $path = $this->value->getRealPath();
            if (! is_string($path) || ! is_file($path)) {
                throw new InvalidArgumentException("Invalid uploaded file for {$field}.");
            }

            $filename = method_exists($this->value, 'getClientOriginalName')
                ? $this->value->getClientOriginalName()
                : (method_exists($this->value, 'getFilename') ? $this->value->getFilename() : basename($path));

            return ['name' => $field, 'contents' => $this->openFile($path, $field), 'filename' => (string) $filename];
        }

        throw new InvalidArgumentException("Invalid media for {$field}.");
    }

    /** 
     * @return resource 
     */
    private function openFile(string $path, string $field)
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException("File could not be opened for {$field}.");
        }

        return $handle;
    }
}
