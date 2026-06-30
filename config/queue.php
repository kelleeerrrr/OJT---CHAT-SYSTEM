<?php

return [

    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => env('QUEUE_TABLE', 'jobs'),
            'queue' => env('QUEUE_FAILED_TABLE', 'failed_jobs'),
            'retry_after' => 90,
            'after_commit' => false,
        ],
        'sync' => [
            'driver' => 'sync',
        ],
    ],
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => env('QUEUE_FAILED_TABLE', 'failed_jobs'),
    ],
];
