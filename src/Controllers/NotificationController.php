<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Notification;
use App\Services\Auth;

final class NotificationController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $this->view('notifications', [
            'title' => 'การแจ้งเตือน',
            'notifications' => Notification::forUser($user['id'], $user['role']),
        ]);
    }

    public function read(string $id): void
    {
        $user = Auth::user();
        Notification::markRead($user['id'], $user['role'], (int) $id);
        redirect('/notifications');
    }

    public function readAll(): void
    {
        $user = Auth::user();
        Notification::markRead($user['id'], $user['role']);
        flash('success', 'ทำเครื่องหมายอ่านแล้วทั้งหมด');
        redirect('/notifications');
    }

    public function delete(string $id): void
    {
        $user = Auth::user();
        Notification::hide($user['id'], $user['role'], (int) $id);
        flash('success', 'ลบการแจ้งเตือนแล้ว');
        redirect('/notifications');
    }
}
