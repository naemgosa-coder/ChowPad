<?php

declare(strict_types=1);

namespace App\Services;

/**
 * รับไฟล์รูปจากฟอร์ม ตรวจชนิดจากเนื้อไฟล์ แล้ว encode ใหม่เป็น JPEG ด้วย GD
 * การ encode ใหม่ตัดข้อมูล EXIF (รวมพิกัด GPS จากมือถือ) และเนื้อหาแฝงที่ไม่ใช่ภาพทิ้ง
 */
final class ImageUpload
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_SIDE = 1600;
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @param array<string, mixed>|null $file รายการจาก $_FILES
     * @return array{path: string|null, error: string|null} path = null และ error = null เมื่อไม่ได้แนบไฟล์
     */
    public static function store(?array $file, string $folder): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > self::MAX_BYTES) {
            return ['path' => null, 'error' => 'ไฟล์รูปต้องมีขนาดไม่เกิน 5 MB'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return ['path' => null, 'error' => 'อัปโหลดรูปไม่สำเร็จ กรุณาลองอีกครั้ง'];
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $size = @getimagesize($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED, true) || $size === false || $size[0] * $size[1] > 40_000_000) {
            return ['path' => null, 'error' => 'รองรับเฉพาะไฟล์รูป JPG, PNG หรือ WebP'];
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png' => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        };
        if ($source === false) {
            return ['path' => null, 'error' => 'ไฟล์รูปเสียหาย กรุณาเลือกรูปอื่น'];
        }

        $source = self::orient($source, $mime === 'image/jpeg' ? $file['tmp_name'] : null);
        [$w, $h] = [imagesx($source), imagesy($source)];
        $scale = min(1, self::MAX_SIDE / max($w, $h));
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);

        // พื้นขาวสำหรับรูปโปร่งใส เพราะ JPEG ไม่มี alpha
        $canvas = imagecreatetruecolor($nw, $nh);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $dir = BASE_PATH . '/public/uploads/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("สร้างโฟลเดอร์อัปโหลดไม่ได้: {$dir}");
        }
        $name = bin2hex(random_bytes(16)) . '.jpg';
        if (!imagejpeg($canvas, "{$dir}/{$name}", 82)) {
            throw new \RuntimeException('บันทึกรูปไม่สำเร็จ');
        }

        return ['path' => "/uploads/{$folder}/{$name}", 'error' => null];
    }

    /** หมุนรูปตาม EXIF Orientation ก่อนที่ข้อมูล EXIF จะถูกตัดทิ้ง */
    private static function orient(\GdImage $image, ?string $path): \GdImage
    {
        if ($path === null || !function_exists('exif_read_data')) {
            return $image;
        }
        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        return $angle ? imagerotate($image, $angle, 0) : $image;
    }
}
