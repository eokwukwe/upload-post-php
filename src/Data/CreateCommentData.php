<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Enums\Platform;

final readonly class CreateCommentData extends CommentRequestData
{
    public function __construct(
        public string $user,
        public string $message,
        public Platform|string $platform = Platform::Instagram,
        public ?string $comment_id = null,
        public ?string $post_id = null,
        public ?string $post_url = null,
        public ?string $attachment_url = null,
        public ?string $attachment_share_url = null,
    ) {
        self::requireCommentValue($this->user, 'user');
        self::requireCommentValue($this->message, 'message');

        $platform = self::commentPlatform($this->platform, self::LIST_PLATFORMS, 'creating');

        if ($platform === Platform::TikTok->value) {
            self::requireCommentValue($this->post_id, 'post_id');

            if (self::hasCommentValue($this->post_url)) {
                throw new InvalidArgumentException('post_url cannot be used for TikTok comments; use post_id.');
            }
        } else {
            self::requireExactlyOneCommentTarget([$this->comment_id, $this->post_id, $this->post_url]);
        }

        if ($platform === Platform::Instagram->value) {
            self::requireCommentValue($this->comment_id, 'comment_id');
        }

        if ($platform !== Platform::Facebook->value
            && (self::hasCommentValue($this->attachment_url) || self::hasCommentValue($this->attachment_share_url))) {
            throw new InvalidArgumentException('attachment_url and attachment_share_url are only supported for Facebook comments.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user: self::stringOrNull($data['user'] ?? null) ?? '',
            message: self::stringOrNull($data['message'] ?? null) ?? '',
            platform: self::commentPlatformFrom($data['platform'] ?? Platform::Instagram),
            comment_id: self::stringOrNull($data['comment_id'] ?? null),
            post_id: self::stringOrNull($data['post_id'] ?? null),
            post_url: self::stringOrNull($data['post_url'] ?? null),
            attachment_url: self::stringOrNull($data['attachment_url'] ?? null),
            attachment_share_url: self::stringOrNull($data['attachment_share_url'] ?? null),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return self::withoutBlankValues([
            'platform' => self::commentPlatform($this->platform, self::LIST_PLATFORMS, 'creating'),
            'user' => $this->user,
            'message' => $this->message,
            'comment_id' => $this->comment_id,
            'post_id' => $this->post_id,
            'post_url' => $this->post_url,
            'attachment_url' => $this->attachment_url,
            'attachment_share_url' => $this->attachment_share_url,
        ]);
    }
}
