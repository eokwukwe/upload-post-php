<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Facades;

use Illuminate\Support\Facades\Facade;
use Softgeng\UploadPost\UploadPostClient;

/**
 * @method static \Softgeng\UploadPost\Data\Responses\UploadResponse uploadVideo(\Softgeng\UploadPost\Data\UploadVideoData $data)
 * @method static \Softgeng\UploadPost\Data\Responses\UploadResponse uploadPhotos(\Softgeng\UploadPost\Data\UploadPhotosData $data)
 * @method static \Softgeng\UploadPost\Data\Responses\UploadResponse uploadText(\Softgeng\UploadPost\Data\UploadTextData $data)
 * @method static \Softgeng\UploadPost\Data\Responses\UploadResponse uploadDocument(\Softgeng\UploadPost\Data\UploadDocumentData $data)
 */
final class UploadPost extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UploadPostClient::class;
    }
}
