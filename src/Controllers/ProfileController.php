<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Auth;
use App\Services\Database;
use App\Services\ImageUpload;

final class ProfileController extends Controller
{
    /** ช่องที่ผู้ใช้แก้เองได้ (บทบาท แผนก ชื่อผู้ใช้ แก้ได้เฉพาะผู้ดูแลระบบ) */
    private const EDITABLE = ['name', 'email', 'phone', 'position'];

    public function show(): void
    {
        $this->render();
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, string> $errors
     * @param array<string, string> $passwordErrors
     */
    private function render(array $old = [], array $errors = [], array $passwordErrors = []): void
    {
        $user = User::find(Auth::id());
        $this->view('profile', [
            'title' => 'โปรไฟล์ของฉัน',
            'user' => $user,
            'old' => $old + $user,
            'errors' => $errors,
            'passwordErrors' => $passwordErrors,
            'logins' => Database::all(
                "SELECT ip_address, created_at FROM audit_logs WHERE user_id = ? AND action = 'Login' ORDER BY id DESC LIMIT 5",
                [Auth::id()]
            ),
            'scripts' => ['js/maintenance.js'],
        ]);
    }

    public function update(): void
    {
        $result = User::validate($this->input(), self::EDITABLE, Auth::id());
        $errors = $result['errors'];

        $avatar = ['path' => null, 'error' => null];
        if (!$errors) {
            $avatar = ImageUpload::store($_FILES['avatar'] ?? null, 'avatars');
            if ($avatar['error']) {
                $errors['avatar'] = $avatar['error'];
            }
        }
        if ($errors) {
            http_response_code(422);
            $this->render($result['data'], $errors);
            return;
        }

        $data = $result['data'];
        if ($avatar['path']) {
            $data['avatar'] = $avatar['path'];
        }
        User::update(Auth::id(), $data);
        AuditLog::record('Update Profile', 'แก้ไขข้อมูลส่วนตัว');
        flash('success', 'บันทึกข้อมูลโปรไฟล์แล้ว');
        redirect('/profile');
    }

    public function password(): void
    {
        $input = $this->input();
        $hash = (string) Database::value('SELECT password_hash FROM users WHERE id = ?', [Auth::id()]);
        $errors = [];
        if (!password_verify((string) ($input['current_password'] ?? ''), $hash)) {
            $errors['current_password'] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } elseif ($error = User::passwordError((string) ($input['new_password'] ?? ''), (string) ($input['confirm_password'] ?? ''))) {
            $errors['new_password'] = $error;
        }
        if ($errors) {
            http_response_code(422);
            $this->render([], [], $errors);
            return;
        }

        User::update(Auth::id(), ['password_hash' => password_hash((string) $input['new_password'], PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        AuditLog::record('Change Password', 'เปลี่ยนรหัสผ่าน');
        flash('success', 'เปลี่ยนรหัสผ่านแล้ว');
        redirect('/profile');
    }
}
