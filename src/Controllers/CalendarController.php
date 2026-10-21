<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Room;

final class CalendarController extends Controller
{
    public function index(): void
    {
        $this->view('calendar', [
            'title' => 'ปฏิทินการจอง',
            'rooms' => Room::all(),
            'selectedRoom' => (string) ($_GET['room'] ?? ''),
            'scripts' => [
                'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js',
                'https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/th.global.min.js',
                'js/calendar.js',
            ],
        ]);
    }
}
