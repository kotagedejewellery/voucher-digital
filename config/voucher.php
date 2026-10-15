<?php

return [
    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
        'dataset_id' => env('META_DATASET_ID'),
        'token' => env('META_ACCESS_TOKEN'),
        'version' => env('META_API_VERSION', 'v26.0'),
        'test_event_code' => env('META_TEST_EVENT_CODE'),
    ],
    'waco' => [
        'enabled' => env('WACO_ENABLED', false),
        'token' => env('WACO_TOKEN'),
        'template' => env('WACO_TEMPLATE'),
        'sender' => env('WACO_SENDER'),
        'ready' => env('WACO_ENABLED', false) && env('WACO_TOKEN') && env('WACO_TEMPLATE') && env('WACO_SENDER') && parse_url(env('APP_URL', ''), PHP_URL_SCHEME)==='https',
    ],
];
