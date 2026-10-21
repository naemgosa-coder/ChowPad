<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

final class HealthController extends Controller
{
    public function show(): void
    {
        try {
            Database::value('SELECT 1');
            $this->json(['status' => 'ok', 'database' => 'ok']);
        } catch (\PDOException) {
            $this->json(['status' => 'error', 'database' => 'unreachable'], 503);
        }
    }
}
