<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class ActionResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?string $message = null,
        public ?string $recipient_id = null,
        public ?string $message_id = null,
        public ?string $id = null,
        public ?string $platform = null,
        public ?string $action = null,
        public ?string $comment_id = null,
        public ?string $gbp_location_id = null,
        public ?string $gbp_location_name = null,
        public ?string $facebook_page_id = null,
        public ?string $facebook_page_name = null,
        public ?string $linkedin_page_id = null,
        public ?string $linkedin_page_name = null,
        public ?int $credits_refunded = null,
        /** @var array<string, mixed> */
        public array $result = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::stringOrNull(Arr::get($raw, 'message')),
            self::stringOrNull(Arr::get($raw, 'recipient_id')),
            self::stringOrNull(Arr::get($raw, 'message_id')),
            self::stringOrNull(Arr::get($raw, 'id')),
            self::stringOrNull(Arr::get($raw, 'platform')),
            self::stringOrNull(Arr::get($raw, 'action')),
            self::stringOrNull(Arr::get($raw, 'comment_id')),
            self::stringOrNull(Arr::get($raw, 'gbp_location_id')),
            self::stringOrNull(Arr::get($raw, 'gbp_location_name')),
            self::stringOrNull(Arr::get($raw, 'facebook_page_id')),
            self::stringOrNull(Arr::get($raw, 'facebook_page_name')),
            self::stringOrNull(Arr::get($raw, 'linkedin_page_id')),
            self::stringOrNull(Arr::get($raw, 'linkedin_page_name')),
            self::intOrNull(Arr::get($raw, 'credits_refunded')),
            is_array(Arr::get($raw, 'result')) ? Arr::get($raw, 'result') : [],
        );
    }
}
