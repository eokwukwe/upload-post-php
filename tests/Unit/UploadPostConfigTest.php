<?php

declare(strict_types=1);

use Softgeng\UploadPost\Support\UploadPostConfig;

test('config parses disabled validation exceptions from string values', function (): void {
    $config = UploadPostConfig::fromArray([
        'api_key' => 'test',
        'throw_on_validation' => 'false',
    ]);

    expect($config->throwOnValidation)->toBeFalse();
});
