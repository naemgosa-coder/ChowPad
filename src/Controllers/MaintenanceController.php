<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Repair;
use App\Models\Room;
use App\Services\Auth;
use App\Services\ImageUpload;
use App\Services\RepairService;

final class MaintenanceController extends Controller
{
    private const PER_PAGE = 10;

    /** เจ้าหน้าที่ซ่อมบำรุงเห็นทุกใบงาน ผู้ใช้ทั่วไปเห็นเฉพาะที่ตัวเองแจ้ง */
    private function scopeReporterId(): ?string
    {
        return Auth::hasRole(...config('roles.maintenance_staff')) ? null : Auth::id();
    }

    public function index(): void
    {
        $priority = (string) ($_GET['priority'] ?? '');
        $status = (string) ($_GET['status'] ?? '');
        $filters = [
            'search' => mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100),
            'priority' => in_array($priority, Repair::PRIORITIES, true) ? $priority : '',
            'status' => in_array($status, Repair::STATUSES, true) ? $status : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        [$repairs, $total] = Repair::paginate($filters, $page, self::PER_PAGE, $this->scopeReporterId());

        $this->view('maintenance/index', [
            'title' => 'ใบงานซ่อมบำรุง',
            'repairs' => $repairs,
            'filters' => $filters,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'perPage' => self::PER_PAGE,
            'allRepairs' => $this->scopeReporterId() === null,
        ]);
    }

    public function create(): void
    {
        $this->renderForm(['room_id' => (string) ($_GET['room'] ?? ''), 'priority' => 'Low']);
    }

    public function store(): void
    {
        $result = RepairService::validate($this->input());
        $errors = $result['errors'];

        // ตรวจรูปหลังข้อมูลอื่นผ่านแล้ว เพื่อไม่ให้เหลือไฟล์ค้างเมื่อฟอร์มไม่ผ่าน
        $upload = ['path' => null, 'error' => null];
        if (!$errors) {
            $upload = ImageUpload::store($_FILES['before_image'] ?? null, 'repairs');
            if ($upload['error']) {
                $errors['before_image'] = $upload['error'];
            }
        }

        if ($errors) {
            http_response_code(422);
            $this->renderForm($result['data'], $errors);
            return;
        }

        $id = RepairService::create($result['data'], $result['room'], $upload['path']);
        flash('success', "ส่งใบแจ้งซ่อมเลขที่ {$id} แล้ว ทีมช่างจะได้รับแจ้งทันที");
        redirect('/maintenance/' . $id);
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, string> $errors
     */
    private function renderForm(array $old, array $errors = []): void
    {
        $this->view('maintenance/create', [
            'title' => 'แจ้งซ่อม',
            'old' => $old,
            'errors' => $errors,
            'rooms' => Room::all(),
            'scripts' => ['js/maintenance.js'],
        ]);
    }

    public function show(string $id): void
    {
        $repair = $this->findAccessible($id);
        $this->view('maintenance/show', [
            'title' => 'ใบงานซ่อม ' . $repair['id'],
            'repair' => $repair,
            'canAssign' => $repair['status'] === 'Waiting' && Auth::hasRole(...config('roles.maintenance_leads')),
            'canWork' => Auth::hasRole(...config('roles.technicians')),
            'technicians' => Repair::technicians(),
            'scripts' => ['js/maintenance.js'],
        ]);
    }

    public function technician(): void
    {
        $isAdmin = !Auth::hasRole('Technician');
        $this->view('maintenance/technician', [
            'title' => 'สำหรับนายช่าง',
            'jobs' => Repair::forTechnician($isAdmin ? null : Auth::id()),
            'isAdmin' => $isAdmin,
            'scripts' => ['js/maintenance.js'],
        ]);
    }

    public function assign(string $id): void
    {
        $repair = $this->findOrFail($id);
        $error = RepairService::assign($repair, (string) ($this->input()['technician_id'] ?? ''));
        flash($error ? 'danger' : 'success', $error ?? "มอบหมายใบงาน {$id} แล้ว");
        redirect('/maintenance/' . $id);
    }

    public function accept(string $id): void
    {
        $repair = $this->findOrFail($id);
        $error = RepairService::accept($repair);
        flash($error ? 'danger' : 'success', $error ?? "รับใบงาน {$id} แล้ว");
        redirect($this->back());
    }

    public function start(string $id): void
    {
        $repair = $this->findOrFail($id);
        $error = RepairService::start($repair);
        flash($error ? 'danger' : 'success', $error ?? "เริ่มซ่อมใบงาน {$id} แล้ว");
        redirect($this->back());
    }

    public function complete(string $id): void
    {
        $repair = $this->findOrFail($id);
        $errors = RepairService::complete($repair, $this->input(), $_FILES['after_image'] ?? null);
        if ($errors) {
            flash('danger', 'ยังปิดงานไม่ได้: ' . implode(' · ', $errors));
        } else {
            flash('success', "ปิดใบงาน {$id} เรียบร้อยแล้ว");
        }
        redirect($this->back());
    }

    /** กลับไปหน้าที่ส่งฟอร์มมา เฉพาะ path ภายในระบบ */
    private function back(): string
    {
        $path = (string) ($this->input()['return_to'] ?? '');

        return preg_match('#^/[a-z0-9/_-]*$#i', $path) ? $path : '/technician';
    }

    /** @return array<string, mixed> */
    private function findOrFail(string $id): array
    {
        return Repair::find($id) ?? $this->abort(404);
    }

    /** @return array<string, mixed> */
    private function findAccessible(string $id): array
    {
        $repair = $this->findOrFail($id);
        if ($this->scopeReporterId() !== null && $repair['reported_by'] !== Auth::id()) {
            $this->abort(403);
        }

        return $repair;
    }
}
