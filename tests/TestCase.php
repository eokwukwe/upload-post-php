<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Softgeng\UploadPost\Facades\UploadPost;
use Softgeng\UploadPost\UploadPostServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            UploadPostServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'UploadPost' => UploadPost::class,
        ];
    }

    /** @param \Illuminate\Foundation\Application $app */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('upload-post.api_key', 'test-key');
    }
}
