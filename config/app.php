<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'ระบบจองห้องประชุม'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', 'false') === 'true',
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Bangkok'),
    'poll_seconds' => (int) env('REALTIME_POLL_SECONDS', '15'),
];
