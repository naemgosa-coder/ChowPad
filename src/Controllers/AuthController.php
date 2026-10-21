<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Services\Auth;

final class AuthController extends Controller
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        $this->view('login', ['title' => 'เข้าสู่ระบบ'], 'auth');
    }

    public function login(): void
    {
        $input = $this->input();
        $login = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        $error = null;
        if ($login === '' || $password === '') {
            $error = 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน';
        } elseif (AuditLog::recentFailedLogins($ip, self::LOCKOUT_MINUTES) >= self::MAX_FAILED_ATTEMPTS) {
            $error = 'เข้าสู่ระบบไม่สำเร็จหลายครั้งเกินไป กรุณารอ ' . self::LOCKOUT_MINUTES . ' นาทีแล้วลองใหม่';
        } elseif (($user = Auth::attempt($login, $password)) === null) {
            AuditLog::record('LoginFailed', "เข้าสู่ระบบไม่สำเร็จ (ชื่อผู้ใช้: {$login})", null, $login);
            $error = 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง';
        }

        if ($error !== null) {
            http_response_code(422);
            $this->view('login', ['title' => 'เข้าสู่ระบบ', 'error' => $error, 'username' => $login], 'auth');
            return;
        }

        AuditLog::record('Login', 'เข้าสู่ระบบสำเร็จ', $user);
        flash('success', 'ยินดีต้อนรับ คุณ' . $user['name']);
        redirect('/dashboard');
    }

    public function logout(): void
    {
        AuditLog::record('Logout', 'ออกจากระบบ');
        Auth::logout();
        redirect('/login');
    }
}
