<?php

use Illuminate\Support\Str;

return [

    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'mtdstore'), '_') . '_horizon:'
    ),

    'middleware' => ['web', 'auth', 'can:viewHorizon'],

    'waits' => [
        'redis:payments' => 30,
        'redis:delivery' => 60,
        'redis:default'  => 60,
        'redis:cache'    => 300,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [
        // App\Jobs\RevalidateStorefrontCache::class,
    ],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 128,

    'defaults' => [
        'supervisor-payments' => [
            'connection' => 'redis',
            'queue' => ['payments'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 1,
            'maxProcesses' => 3,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'tries' => 3,
            'backoff' => [10, 30, 60],
            'timeout' => 60,
            'nice' => 0,
        ],

        'supervisor-delivery' => [
            'connection' => 'redis',
            'queue' => ['delivery'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 2,
            'tries' => 3,
            'backoff' => [15, 45, 90],
            'timeout' => 60,
            'nice' => 0,
        ],

        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'simple',
            'minProcesses' => 1,
            'maxProcesses' => 2,
            'tries' => 2,
            'timeout' => 60,
            'nice' => 5,
        ],

        'supervisor-cache' => [
            'connection' => 'redis',
            'queue' => ['cache'],
            'balance' => 'simple',
            'minProcesses' => 1,
            'maxProcesses' => 1,
            'tries' => 2,
            'timeout' => 30,
            'nice' => 10,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-payments' => ['maxProcesses' => 2],
            'supervisor-delivery' => ['maxProcesses' => 1],
            'supervisor-default'  => ['maxProcesses' => 1],
            'supervisor-cache'    => ['maxProcesses' => 1],
        ],

        'local' => [
            'supervisor-payments' => ['maxProcesses' => 1],
            'supervisor-delivery' => ['maxProcesses' => 1],
            'supervisor-default'  => ['maxProcesses' => 1],
            'supervisor-cache'    => ['maxProcesses' => 1],
        ],
    ],

];
