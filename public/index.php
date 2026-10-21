<?php

declare(strict_types=1);

use App\Services\HttpException;
use App\Services\Router;

// php -S: ให้ built-in server ส่งไฟล์ static (css/js/images) เองโดยตรง
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/config/bootstrap.php';

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
    'use_strict_mode' => true,
]);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

$router = new Router();
require BASE_PATH . '/routes/web.php';

$isApi = str_starts_with($_SERVER['REQUEST_URI'], '/api/');
try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (HttpException $e) {
    $router->abort($e->getCode(), $isApi);
} catch (\Throwable $e) {
    error_log((string) $e);
    if (config('app.debug')) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo $e;
    } else {
        $router->abort(500, $isApi);
    }
}
