<?php

return [
    'default' => [
        'driver' => 'redis',
        'connection' => null,
        'decay_minutes' => env('RATE_LIMITER_DECAY_MINUTES', 1),
        'max_attempts' => env('RATE_LIMITER_MAX_ATTEMPTS', 5),
    ],
];
