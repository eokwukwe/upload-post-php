<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Support;

use BackedEnum;

final class MultipartPayload
{
    /** @var list<array{name:string,contents:mixed,filename?:string}> */
    private array $parts = [];

    public function field(string $name, mixed $value): self
    {
        if ($value === null || $value === '') {
            return $this;
        }
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->field($name, $item);
            }

            return $this;
        }
        $this->parts[] = ['name' => $name, 'contents' => (string) $value];

        return $this;
    }

    public function media(string $name, Media $media): self
    {
        $this->parts[] = $media->toMultipartPart($name);

        return $this;
    }

    /** @return list<array{name:string,contents:mixed,filename?:string}> */
    public function all(): array
    {
        return $this->parts;
    }
}
