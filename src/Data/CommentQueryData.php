<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Enums\Platform;

final readonly class CommentQueryData extends CommentRequestData
{
    public function __construct(
        public string $user,
        public Platform|string $platform = Platform::Instagram,
        public ?string $post_id = null,
        public ?string $post_url = null,
        public ?int $limit = null,
        public ?string $after = null,
        public ?string $comment_id = null,
    ) {
        self::requireCommentValue($this->user, 'user');
        $platform = self::commentPlatform($this->platform, self::LIST_PLATFORMS, 'listing');
        self::requireExactlyOneCommentTarget([$this->post_id, $this->post_url]);

        if ($platform === Platform::TikTok->value && self::hasCommentValue($this->comment_id) && ! self::hasCommentValue($this->post_id)) {
            throw new InvalidArgumentException('post_id is required when listing TikTok comment replies.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user: self::stringOrNull($data['user'] ?? null) ?? '',
            platform: self::commentPlatformFrom($data['platform'] ?? Platform::Instagram),
            post_id: self::stringOrNull($data['post_id'] ?? null),
            post_url: self::stringOrNull($data['post_url'] ?? null),
            limit: self::intOrNull($data['limit'] ?? null),
            after: self::stringOrNull($data['after'] ?? null),
            comment_id: self::stringOrNull($data['comment_id'] ?? null),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'platform' => self::commentPlatform($this->platform, self::LIST_PLATFORMS, 'listing'),
            'user' => $this->user,
            'post_id' => $this->post_id,
            'post_url' => $this->post_url,
            'limit' => $this->limit === null ? null : (string) $this->limit,
            'after' => $this->after,
            'comment_id' => $this->comment_id,
        ], static fn (?string $value): bool => $value !== null && $value !== '');
    }
}
