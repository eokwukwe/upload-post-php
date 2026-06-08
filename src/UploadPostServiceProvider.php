<?php

declare(strict_types=1);

namespace Softgeng\UploadPost;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Softgeng\UploadPost\Support\UploadPostConfig;

final class UploadPostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/upload-post.php', 'upload-post');

        $this->app->singleton(UploadPostClient::class, fn (): UploadPostClient => new UploadPostClient(
            UploadPostConfig::fromArray(config('upload-post')),
            $this->app->make(HttpFactory::class),
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/upload-post.php' => config_path('upload-post.php'),
        ], 'upload-post-config');
    }
}
