<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Enums\Platform;

final readonly class DeleteCommentData extends CommentRequestData
{
    public function __construct(
        public string $user,
        public string $comment_id,
        public Platform|string $platform = Platform::Instagram,
        public ?string $post_id = null,
    ) {
        self::requireCommentValue($this->user, 'user');
        self::requireCommentValue($this->comment_id, 'comment_id');

        $platform = self::commentPlatform($this->platform, self::DELETE_PLATFORMS, 'deleting');

        if ($platform === Platform::LinkedIn->value) {
            self::requireCommentValue($this->post_id, 'post_id');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user: self::stringOrNull($data['user'] ?? null) ?? '',
            comment_id: self::stringOrNull($data['comment_id'] ?? null) ?? '',
            platform: self::commentPlatformFrom($data['platform'] ?? Platform::Instagram),
            post_id: self::stringOrNull($data['post_id'] ?? null),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return self::withoutBlankValues([
            'platform' => self::commentPlatform($this->platform, self::DELETE_PLATFORMS, 'deleting'),
            'user' => $this->user,
            'comment_id' => $this->comment_id,
            'post_id' => $this->post_id,
        ]);
    }
}
