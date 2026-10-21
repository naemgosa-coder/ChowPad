<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Auth;
use App\Services\Database;

final class UserController extends Controller
{
    private const PER_PAGE = 15;
    private const FIELDS = ['name', 'username', 'email', 'role', 'department_id', 'position', 'phone', 'skill'];

    public function index(): void
    {
        $edit = null;
        if (isset($_GET['edit'])) {
            $edit = User::find((string) $_GET['edit']) ?? $this->abort(404);
        }
        $this->render($edit, isset($_GET['new']) ? [] : null);
    }

    /**
     * @param array<string, mixed>|null $editing ผู้ใช้ที่กำลังแก้ไข
     * @param array<string, mixed>|null $form ค่าฟอร์ม (null = ไม่แสดงฟอร์มเพิ่ม)
     * @param array<string, string> $errors
     */
    private function render(?array $editing, ?array $form, array $errors = []): void
    {
        $role = (string) ($_GET['role'] ?? '');
        $status = (string) ($_GET['status'] ?? '');
        $filters = [
            'search' => mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100),
            'role' => in_array($role, Auth::ROLES, true) ? $role : '',
            'department' => (string) ($_GET['department'] ?? ''),
            'status' => in_array($status, ['Active', 'Inactive'], true) ? $status : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        [$users, $total] = User::paginate($filters, $page, self::PER_PAGE);

        $this->view('users', [
            'title' => 'ผู้ใช้งาน',
            'users' => $users,
            'filters' => $filters,
            'departments' => Database::all('SELECT id, name FROM departments ORDER BY id'),
            'editing' => $editing,
            'form' => $editing !== null ? ($form ?? $editing) : $form,
            'errors' => $errors,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'perPage' => self::PER_PAGE,
        ]);
    }

    public function store(): void
    {
        $input = $this->input();
        $result = User::validate($input, self::FIELDS);
        $errors = $result['errors'];
        if ($error = User::passwordError((string) ($input['password'] ?? ''), (string) ($input['password_confirm'] ?? ''))) {
            $errors['password'] = $error;
        }
        if ($errors) {
            http_response_code(422);
            $this->render(null, $result['data'], $errors);
            return;
        }

        $id = User::nextId();
        $data = $result['data'] + ['id' => $id, 'password_hash' => password_hash((string) $input['password'], PASSWORD_DEFAULT)];
        $cols = array_keys($data);
        Database::query(
            sprintf('INSERT INTO users (%s) VALUES (%s)', implode(', ', $cols), implode(', ', array_fill(0, count($cols), '?'))),
            array_values($data)
        );
        AuditLog::record('Create User', "เพิ่มบัญชี {$id} ({$data['username']}) บทบาท " . role_label($data['role']));
        flash('success', "เพิ่มบัญชี {$data['name']} ({$id}) แล้ว");
        redirect('/users');
    }

    public function update(string $id): void
    {
        $user = User::find($id) ?? $this->abort(404);
        $input = $this->input();
        $result = User::validate($input, self::FIELDS, $id);
        $errors = $result['errors'];
        $data = $result['data'];

        // กันผู้ดูแลลดสิทธิ์ตัวเองจนไม่มีใครเข้าหน้าผู้ดูแลได้
        if ($id === Auth::id() && $data['role'] !== $user['role']) {
            $errors['role'] = 'ไม่สามารถเปลี่ยนบทบาทของบัญชีตัวเองได้';
        }
        $password = (string) ($input['password'] ?? '');
        if ($password !== '' && ($error = User::passwordError($password, (string) ($input['password_confirm'] ?? '')))) {
            $errors['password'] = $error;
        }
        if ($errors) {
            http_response_code(422);
            $this->render($user, $data, $errors);
            return;
        }

        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        User::update($id, $data);
        AuditLog::record('Update User', "แก้ไขบัญชี {$id}" . ($password !== '' ? ' และตั้งรหัสผ่านใหม่' : ''));
        flash('success', "บันทึกบัญชี {$data['name']} แล้ว");
        redirect('/users');
    }

    /** ระงับ/เปิดใช้บัญชี (แทนการลบ เพื่อเก็บประวัติการจองและใบงานที่อ้างถึงผู้ใช้ไว้) */
    public function toggle(string $id): void
    {
        $user = User::find($id) ?? $this->abort(404);
        if ($id === Auth::id()) {
            flash('danger', 'ไม่สามารถระงับบัญชีของตัวเองได้');
            redirect('/users');
        }
        $status = $user['status'] === 'Active' ? 'Inactive' : 'Active';
        User::update($id, ['status' => $status]);
        AuditLog::record($status === 'Active' ? 'Activate User' : 'Deactivate User', ($status === 'Active' ? 'เปิดใช้' : 'ระงับ') . "บัญชี {$id} ({$user['username']})");
        flash('success', ($status === 'Active' ? 'เปิดใช้' : 'ระงับ') . "บัญชี {$user['name']} แล้ว");
        redirect('/users' . query_with([]));
    }
}
