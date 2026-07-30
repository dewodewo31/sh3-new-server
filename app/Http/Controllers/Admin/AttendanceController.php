<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\EventRepository;
use App\Repositories\AttendanceRepository;

class AttendanceController extends Controller
{
    public function __construct(
        private EventRepository $eventRepository,
        private AttendanceRepository $attendanceRepository,
    ) {}

    public function byEvent(int $eventId)
    {
        $event = $this->eventRepository->findById($eventId);
        $attendances = $this->attendanceRepository->findByEvent($eventId);

        return view('attendance.index', compact('event', 'attendances'));
    }

    public function report()
    {
        $events = $this->eventRepository->all();

        return view('attendance.report', compact('events'));
    }
}
