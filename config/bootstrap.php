<?php

declare(strict_types=1);

use App\Services\Env;

define('BASE_PATH', dirname(__DIR__));

$autoload = BASE_PATH . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit("ยังไม่ได้ติดตั้ง dependency: รัน `composer install` ที่โฟลเดอร์โปรเจกต์ก่อน\n");
}
require $autoload;

Env::load(BASE_PATH . '/.env');

date_default_timezone_set(config('app.timezone'));

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
