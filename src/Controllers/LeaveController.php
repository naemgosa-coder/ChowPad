<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LeaveRequest;
use App\Services\Auth;
use App\Services\LeaveService;

final class LeaveController extends Controller
{
    public function index(): void
    {
        $this->renderIndex();
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, string> $errors
     */
    private function renderIndex(array $old = [], array $errors = []): void
    {
        $this->view('leave/index', [
            'title' => 'ใบลา',
            'quotas' => LeaveService::quotas(Auth::id()),
            'fiscalYear' => LeaveService::fiscalYear(),
            'leaves' => LeaveRequest::forUser(Auth::id()),
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    public function store(): void
    {
        $result = LeaveService::request(Auth::id(), $this->input());
        if ($result['errors']) {
            http_response_code(422);
            $this->renderIndex($result['data'], $result['errors']);
            return;
        }
        flash('success', 'ส่งใบลาเรียบร้อยแล้ว รอหัวหน้างานหรือฝ่ายบุคคลอนุมัติ');
        redirect('/leave');
    }

    public function cancel(string $id): void
    {
        $error = LeaveService::cancel($this->findOrFail($id));
        flash($error ? 'danger' : 'success', $error ?? 'ยกเลิกใบลาแล้ว');
        redirect('/leave');
    }

    public function approval(): void
    {
        $pending = LeaveRequest::pending();
        // แสดงสิทธิ์คงเหลือของผู้ยื่นแต่ละคน ช่วยผู้อนุมัติตัดสินใจ
        $remaining = [];
        foreach (array_unique(array_column($pending, 'user_id')) as $userId) {
            foreach (LeaveService::quotas($userId) as $q) {
                $remaining[$userId][$q['id']] = $q['remaining'];
            }
        }

        $this->view('leave/approval', [
            'title' => 'อนุมัติใบลา',
            'leaves' => $pending,
            'remaining' => $remaining,
        ]);
    }

    public function approve(string $id): void
    {
        $error = LeaveService::approve($this->findOrFail($id));
        flash($error ? 'danger' : 'success', $error ?? 'อนุมัติใบลาแล้ว');
        redirect('/leave/approval');
    }

    public function reject(string $id): void
    {
        $error = LeaveService::reject($this->findOrFail($id));
        flash($error ? 'danger' : 'success', $error ?? 'บันทึกผลไม่อนุมัติใบลาแล้ว');
        redirect('/leave/approval');
    }

    /** @return array<string, mixed> */
    private function findOrFail(string $id): array
    {
        return ctype_digit($id) ? (LeaveRequest::find((int) $id) ?? $this->abort(404)) : $this->abort(404);
    }
}
