<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

final class AuditLogController extends Controller
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        $search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
        $action = (string) ($_GET['action'] ?? '');
        $actions = Database::query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(\PDO::FETCH_COLUMN);
        $action = in_array($action, $actions, true) ? $action : '';

        $where = [];
        $params = [];
        if ($action !== '') {
            $where[] = 'action = ?';
            $params[] = $action;
        }
        if ($search !== '') {
            $where[] = '(username LIKE ? OR details LIKE ? OR ip_address LIKE ?)';
            array_push($params, ...array_fill(0, 3, "%{$search}%"));
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = (int) Database::value("SELECT COUNT(*) FROM audit_logs{$sqlWhere}", $params);
        $offset = ($page - 1) * self::PER_PAGE;

        $this->view('audit-logs', [
            'title' => 'บันทึกการใช้งาน',
            'logs' => Database::all("SELECT * FROM audit_logs{$sqlWhere} ORDER BY id DESC LIMIT " . self::PER_PAGE . " OFFSET {$offset}", $params),
            'actions' => $actions,
            'filters' => ['search' => $search, 'action' => $action],
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'perPage' => self::PER_PAGE,
        ]);
    }
}
