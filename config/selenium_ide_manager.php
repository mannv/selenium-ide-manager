<?php
return [
    'storage' => env('SELENIUM_STORAGE', 'public'), //public, s3,
    'middleware' => ['web', 'auth'],
    'replace_domain' => [
        'frontend' => ['local.vitop-career.com', 'test.vitop-career.com', 'staging.vitop.vn'],
        'backend' => ['backend.local.vitop-career.com', 'backend.test.vitop-career.com', 'staging.backend.vitop.vn'],
    ]
];
