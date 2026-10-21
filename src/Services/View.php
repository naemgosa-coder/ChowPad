<?php

declare(strict_types=1);

namespace App\Services;

final class View
{
    /**
     * render หน้าใน views/pages แล้วห่อด้วย layout
     * ชื่อหน้าและ layout มาจากโค้ด controller เท่านั้น ห้ามรับจาก input ของผู้ใช้
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $page, array $data = [], ?string $layout = 'app'): string
    {
        $content = self::partial('pages/' . $page, $data);
        if ($layout === null) {
            return $content;
        }

        return self::partial('layouts/' . $layout, $data + ['content' => $content]);
    }

    /** @param array<string, mixed> $data */
    public static function partial(string $name, array $data = []): string
    {
        $file = BASE_PATH . '/views/' . $name . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("ไม่พบไฟล์ view: {$name}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
