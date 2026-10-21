<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Router แบบเรียบง่าย ทุก route ต้องล็อกอินเป็นค่าเริ่มต้น
 * ส่วน request ที่ไม่ใช่ GET จะถูกตรวจ CSRF ที่นี่ที่เดียว
 */
final class Router
{
    /** @var array<string, array<int, array{string, array{class-string, string}, array<string, mixed>}>> */
    private array $routes = [];

    /**
     * @param array{class-string, string} $handler
     * @param array{auth?: bool, roles?: string[]} $options
     */
    public function get(string $path, array $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    /**
     * @param array{class-string, string} $handler
     * @param array{auth?: bool, roles?: string[]} $options
     */
    public function post(string $path, array $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    /**
     * @param array{class-string, string} $handler
     * @param array<string, mixed> $options
     */
    private function add(string $method, string $path, array $handler, array $options): void
    {
        $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path) . '$#';
        $this->routes[$method][] = [$pattern, $handler, $options + ['auth' => true, 'roles' => []]];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = $method === 'HEAD' ? 'GET' : $method;
        $path = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');
        $isApi = str_starts_with($path, '/api/');

        foreach ($this->routes[$method] ?? [] as [$pattern, $handler, $options]) {
            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            if ($method !== 'GET' && !Csrf::verify()) {
                $this->abort(419, $isApi);
                return;
            }
            if ($options['auth'] && !Auth::check()) {
                if ($isApi) {
                    $this->abort(401, true);
                } else {
                    redirect('/login');
                }
                return;
            }
            if ($options['roles'] && !Auth::hasRole(...$options['roles'])) {
                $this->abort(403, $isApi);
                return;
            }

            [$class, $action] = $handler;
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            (new $class())->$action(...$params);
            return;
        }

        $this->abort(404, $isApi);
    }

    public function abort(int $status, bool $json): void
    {
        $messages = [
            401 => 'กรุณาเข้าสู่ระบบก่อนใช้งาน',
            403 => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้',
            404 => 'ไม่พบหน้าที่ต้องการ',
            419 => 'หน้าเว็บหมดอายุ กรุณารีเฟรชแล้วลองอีกครั้ง',
            500 => 'ระบบขัดข้อง กรุณาลองใหม่ภายหลัง',
        ];
        http_response_code($status);
        $message = $messages[$status] ?? 'เกิดข้อผิดพลาด';

        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo View::render('error', ['status' => $status, 'message' => $message, 'title' => $message], 'error');
    }
}
