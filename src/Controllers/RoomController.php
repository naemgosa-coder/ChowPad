<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Room;

final class RoomController extends Controller
{
    public function index(): void
    {
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'building' => (string) ($_GET['building'] ?? ''),
            'status' => (string) ($_GET['status'] ?? ''),
        ];
        $live = array_column(Room::liveStatus(), null, 'id');

        $this->view('rooms', [
            'title' => 'ห้องประชุม',
            'filters' => $filters,
            'rooms' => Room::all($filters),
            'live' => $live,
            'buildings' => Room::buildings(),
            'scripts' => ['js/live-rooms.js'],
        ]);
    }
}
