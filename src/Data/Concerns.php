<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use BackedEnum;
use DateTimeInterface;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\MultipartPayload;

trait Concerns
{
    protected static function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    /**
     * @param  list<Platform|string>  $platforms
     * @return list<string>
     */
    protected static function platformsToValues(array $platforms): array
    {
        return array_map(static fn (Platform|string $p): string => $p instanceof Platform ? $p->value : $p, $platforms);
    }

    protected static function date(DateTimeInterface|string|null $date): ?string
    {
        return $date instanceof DateTimeInterface ? $date->format(DateTimeInterface::ATOM) : $date;
    }

    /**
     * @param  array<string,mixed>  $extra
     */
    protected function addFields(MultipartPayload $payload, array $extra): MultipartPayload
    {
        foreach ($extra as $key => $value) {
            $payload->field($key, self::enumValue($value));
        }

        return $payload;
    }
}
