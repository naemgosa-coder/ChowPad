<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Auth;
use App\Services\Database;

final class User
{
    private const SELECT = 'SELECT u.id, u.username, u.email, u.name, u.role, u.department_id, u.position, u.phone,
                                   u.skill, u.avatar, u.status, u.created_at, d.name AS department_name
                            FROM users u LEFT JOIN departments d ON d.id = u.department_id';

    /** @return array<string, mixed>|null ไม่มี password_hash */
    public static function find(string $id): ?array
    {
        return Database::first(self::SELECT . ' WHERE u.id = ?', [$id]);
    }

    /**
     * @param array{search?: string, role?: string, department?: string, status?: string} $filters
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    public static function paginate(array $filters, int $page, int $perPage): array
    {
        $where = [];
        $params = [];
        foreach (['role' => 'u.role', 'department' => 'u.department_id', 'status' => 'u.status'] as $key => $col) {
            if (!empty($filters[$key])) {
                $where[] = "{$col} = ?";
                $params[] = $filters[$key];
            }
        }
        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE ? OR u.username LIKE ? OR u.id LIKE ? OR u.email LIKE ?)';
            array_push($params, ...array_fill(0, 4, '%' . $filters['search'] . '%'));
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $total = (int) Database::value("SELECT COUNT(*) FROM users u{$sqlWhere}", $params);
        $offset = max(0, ($page - 1) * $perPage);

        return [Database::all(self::SELECT . "{$sqlWhere} ORDER BY u.id LIMIT {$perPage} OFFSET {$offset}", $params), $total];
    }

    public static function nextId(): string
    {
        $last = (int) Database::value("SELECT MAX(CAST(SUBSTRING(id, 5) AS UNSIGNED)) FROM users WHERE id LIKE 'USR-%'");

        return 'USR-' . str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
    }

    public static function taken(string $column, string $value, ?string $exceptId = null): bool
    {
        $column = $column === 'email' ? 'email' : 'username';

        return (bool) Database::value("SELECT 1 FROM users WHERE {$column} = ? AND id <> ?", [$value, $exceptId ?? '']);
    }

    /**
     * ตรวจข้อมูลบัญชีผู้ใช้ (ใช้ทั้งเพิ่มและแก้ไขโดยผู้ดูแล และแก้โปรไฟล์ตัวเอง)
     *
     * @param array<string, mixed> $input
     * @param string[] $fields ช่องที่อนุญาตให้แก้
     * @return array{data: array<string, mixed>, errors: array<string, string>}
     */
    public static function validate(array $input, array $fields, ?string $exceptId = null): array
    {
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = mb_substr(trim((string) ($input[$field] ?? '')), 0, $field === 'name' || $field === 'email' ? 150 : 100);
        }
        $errors = [];

        if (in_array('name', $fields, true) && $data['name'] === '') {
            $errors['name'] = 'กรุณากรอกชื่อ-นามสกุล';
        }
        if (in_array('email', $fields, true)) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'กรุณากรอกอีเมลให้ถูกรูปแบบ';
            } elseif (self::taken('email', $data['email'], $exceptId)) {
                $errors['email'] = 'อีเมลนี้มีผู้ใช้แล้ว';
            }
        }
        if (in_array('username', $fields, true)) {
            if (!preg_match('/^[a-z0-9._-]{3,50}$/', $data['username'])) {
                $errors['username'] = 'ชื่อผู้ใช้ต้องเป็นตัวอักษรภาษาอังกฤษพิมพ์เล็ก ตัวเลข . _ - ยาว 3–50 ตัว';
            } elseif (self::taken('username', $data['username'], $exceptId)) {
                $errors['username'] = 'ชื่อผู้ใช้นี้มีผู้ใช้แล้ว';
            }
        }
        if (in_array('role', $fields, true) && !in_array($data['role'], Auth::ROLES, true)) {
            $errors['role'] = 'กรุณาเลือกบทบาท';
        }
        if (in_array('department_id', $fields, true)) {
            if ($data['department_id'] === '') {
                $data['department_id'] = null;
            } elseif (!Database::value('SELECT 1 FROM departments WHERE id = ?', [$data['department_id']])) {
                $errors['department_id'] = 'กรุณาเลือกแผนกจากรายการ';
            }
        }
        foreach (['position', 'phone', 'skill'] as $optional) {
            if (isset($data[$optional]) && $data[$optional] === '') {
                $data[$optional] = null;
            }
        }

        return ['data' => $data, 'errors' => $errors];
    }

    /** @param array<string, mixed> $data */
    public static function update(string $id, array $data): void
    {
        if (!$data) {
            return;
        }
        $set = implode(', ', array_map(static fn ($col) => "{$col} = ?", array_keys($data)));
        Database::query("UPDATE users SET {$set} WHERE id = ?", [...array_values($data), $id]);
    }

    /** กติการหัสผ่าน: อย่างน้อย 8 ตัวอักษร */
    public static function passwordError(string $password, string $confirm): ?string
    {
        if (mb_strlen($password) < 8) {
            return 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
        }
        if ($password !== $confirm) {
            return 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';
        }

        return null;
    }
}
