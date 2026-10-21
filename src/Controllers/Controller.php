<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\HttpException;
use App\Services\View;

abstract class Controller
{
    protected function abort(int $status): never
    {
        throw new HttpException($status);
    }

    /** @param array<string, mixed> $data */
    protected function view(string $page, array $data = [], ?string $layout = 'app'): void
    {
        echo View::render($page, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * รวมข้อมูลจากฟอร์ม (POST) และ JSON body ที่ส่งด้วย fetch()
     *
     * @return array<string, mixed>
     */
    protected function input(): array
    {
        $data = $_POST;
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $json = json_decode((string) file_get_contents('php://input'), true);
            $data = is_array($json) ? $json + $data : $data;
        }
        unset($data['_token']);

        return $data;
    }
}
