<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'ระบบจองห้องประชุม'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', 'false') === 'true',
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Bangkok'),
    'poll_seconds' => (int) env('REALTIME_POLL_SECONDS', '15'),
    'chat' => [
        // ว่าง = ผู้ช่วยตอบเองจากคำสำคัญ ไม่ใช้ AI
        'api_key' => (string) env('ANTHROPIC_API_KEY', ''),
        'model' => env('CHAT_MODEL', 'claude-opus-5-5'),
        // จำนวนคำถามที่ส่งให้ AI ได้ต่อผู้ใช้ต่อชั่วโมง (กันค่าใช้จ่ายบานปลาย)
        'ai_per_hour' => (int) env('CHAT_AI_PER_HOUR', '30'),
    ],
];
