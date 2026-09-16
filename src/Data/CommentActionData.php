<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Enums\Platform;

final readonly class CommentActionData extends CommentRequestData
{
    public function __construct(
        public string $user,
        public string $action,
        public Platform|string $platform = Platform::Instagram,
        public ?string $comment_id = null,
        public ?string $post_id = null,
        public ?string $message = null,
        public ?bool $ban_author = null,
    ) {
        self::requireCommentValue($this->user, 'user');
        self::requireCommentValue($this->action, 'action');

        $platform = self::commentPlatform($this->platform, self::ACTION_PLATFORMS, 'moderating');
        $action = trim($this->action);

        $supportedActions = [
            Platform::TikTok->value => ['hide', 'unhide', 'like', 'unlike', 'pin', 'unpin'],
            Platform::Facebook->value => ['hide', 'unhide', 'like', 'unlike', 'edit'],
            Platform::Instagram->value => ['hide', 'unhide', 'enable_comments', 'disable_comments'],
            Platform::YouTube->value => ['hide', 'unhide', 'hold'],
            Platform::Threads->value => ['hide', 'unhide', 'approve', 'ignore'],
        ][$platform] ?? [];

        if (! in_array($action, $supportedActions, true)) {
            throw new InvalidArgumentException("{$action} is not supported for {$platform} comments.");
        }

        if ($platform === Platform::Instagram->value && in_array($action, ['enable_comments', 'disable_comments'], true)) {
            self::requireCommentValue($this->post_id, 'post_id');
        } else {
            self::requireCommentValue($this->comment_id, 'comment_id');
        }

        if (($platform === Platform::TikTok->value && in_array($action, ['hide', 'unhide', 'pin', 'unpin'], true))
            || $platform === Platform::YouTube->value) {
            self::requireCommentValue($this->post_id, 'post_id');
        }

        if ($platform === Platform::Facebook->value && $action === 'edit') {
            self::requireCommentValue($this->message, 'message');
        }

        if ($this->ban_author !== null && ($platform !== Platform::YouTube->value || $action !== 'hide')) {
            throw new InvalidArgumentException('ban_author is only supported for YouTube hide actions.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user: self::stringOrNull($data['user'] ?? null) ?? '',
            action: self::stringOrNull($data['action'] ?? null) ?? '',
            platform: self::commentPlatformFrom($data['platform'] ?? Platform::Instagram),
            comment_id: self::stringOrNull($data['comment_id'] ?? null),
            post_id: self::stringOrNull($data['post_id'] ?? null),
            message: self::stringOrNull($data['message'] ?? null),
            ban_author: self::boolOrNull($data['ban_author'] ?? null),
        );
    }

    /**
     * @return array<string, string|bool>
     */
    public function toArray(): array
    {
        return self::withoutBlankValues([
            'platform' => self::commentPlatform($this->platform, self::ACTION_PLATFORMS, 'moderating'),
            'user' => $this->user,
            'action' => $this->action,
            'comment_id' => $this->comment_id,
            'post_id' => $this->post_id,
            'message' => $this->message,
            'ban_author' => $this->ban_author,
        ]);
    }
}
