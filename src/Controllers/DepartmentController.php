<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Services\Database;

final class DepartmentController extends Controller
{
    public function index(): void
    {
        $edit = isset($_GET['edit']) ? ($this->find((string) $_GET['edit']) ?? $this->abort(404)) : null;
        $this->render($edit, $edit ?? (isset($_GET['new']) ? [] : null));
    }

    /**
     * @param array<string, mixed>|null $editing
     * @param array<string, mixed>|null $form
     * @param array<string, string> $errors
     */
    private function render(?array $editing, ?array $form, array $errors = []): void
    {
        $this->view('departments', [
            'title' => 'แผนก/กอง',
            'departments' => Database::all(
                "SELECT d.*, COUNT(u.id) AS members, SUM(u.status = 'Active') AS active_members
                 FROM departments d LEFT JOIN users u ON u.department_id = d.id
                 GROUP BY d.id, d.name, d.code ORDER BY d.id"
            ),
            'editing' => $editing,
            'form' => $form,
            'errors' => $errors,
        ]);
    }

    /** @return array<string, mixed>|null */
    private function find(string $id): ?array
    {
        return Database::first('SELECT * FROM departments WHERE id = ?', [$id]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{data: array<string, string>, errors: array<string, string>}
     */
    private function validate(array $input, ?string $exceptId = null): array
    {
        $data = [
            'name' => mb_substr(trim((string) ($input['name'] ?? '')), 0, 150),
            'code' => strtoupper(mb_substr(trim((string) ($input['code'] ?? '')), 0, 20)),
        ];
        $errors = [];
        if ($data['name'] === '') {
            $errors['name'] = 'กรุณากรอกชื่อกอง / ฝ่าย';
        }
        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $data['code'])) {
            $errors['code'] = 'รหัสย่อเป็นภาษาอังกฤษหรือตัวเลข 2–20 ตัว เช่น GEN';
        } elseif (Database::value('SELECT 1 FROM departments WHERE code = ? AND id <> ?', [$data['code'], $exceptId ?? ''])) {
            $errors['code'] = 'รหัสย่อนี้มีอยู่แล้ว';
        }

        return ['data' => $data, 'errors' => $errors];
    }

    public function store(): void
    {
        $result = $this->validate($this->input());
        if ($result['errors']) {
            http_response_code(422);
            $this->render(null, $result['data'], $result['errors']);
            return;
        }
        $last = (int) Database::value("SELECT MAX(CAST(SUBSTRING(id, 5) AS UNSIGNED)) FROM departments WHERE id LIKE 'DEP-%'");
        $id = 'DEP-' . str_pad((string) ($last + 1), 2, '0', STR_PAD_LEFT);
        Database::query('INSERT INTO departments (id, name, code) VALUES (?, ?, ?)', [$id, $result['data']['name'], $result['data']['code']]);
        AuditLog::record('Create Department', "เพิ่มแผนก {$id} {$result['data']['name']}");
        flash('success', "เพิ่ม{$result['data']['name']}แล้ว");
        redirect('/departments');
    }

    public function update(string $id): void
    {
        $dept = $this->find($id) ?? $this->abort(404);
        $result = $this->validate($this->input(), $id);
        if ($result['errors']) {
            http_response_code(422);
            $this->render($dept, $result['data'], $result['errors']);
            return;
        }
        Database::query('UPDATE departments SET name = ?, code = ? WHERE id = ?', [$result['data']['name'], $result['data']['code'], $id]);
        AuditLog::record('Update Department', "แก้ไขแผนก {$id}");
        flash('success', 'บันทึกข้อมูลแผนกแล้ว');
        redirect('/departments');
    }

    public function delete(string $id): void
    {
        $dept = $this->find($id) ?? $this->abort(404);
        if (Database::value('SELECT 1 FROM users WHERE department_id = ? LIMIT 1', [$id])) {
            flash('danger', "ลบ{$dept['name']}ไม่ได้ เพราะยังมีบุคลากรในสังกัด กรุณาย้ายสังกัดก่อน");
            redirect('/departments');
        }
        Database::query('DELETE FROM departments WHERE id = ?', [$id]);
        AuditLog::record('Delete Department', "ลบแผนก {$id} {$dept['name']}");
        flash('success', "ลบ{$dept['name']}แล้ว");
        redirect('/departments');
    }
}
