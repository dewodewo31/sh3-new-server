<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Repositories\EventRepository;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventService $eventService,
    ) {}

    public function index(): JsonResponse
    {
        $events = $this->eventRepository->findPublished(['category']);

        return response()->json(['data' => $events]);
    }

    public function show(int $id): JsonResponse
    {
        $event = $this->eventRepository->findById($id, ['category', 'schedules']);

        return response()->json(['data' => $event]);
    }

    public function store(EventRequest $request): JsonResponse
    {
        $event = $this->eventRepository->create($request->validated());

        return response()->json(['data' => $event, 'message' => 'Event berhasil dibuat'], 201);
    }

    public function update(int $id, EventRequest $request): JsonResponse
    {
        $event = $this->eventRepository->findById($id);
        $this->eventRepository->update($event, $request->validated());

        return response()->json(['data' => $event->fresh(), 'message' => 'Event berhasil diupdate']);
    }

    public function destroy(int $id): JsonResponse
    {
        $event = $this->eventRepository->findById($id);
        $this->eventRepository->delete($event);

        return response()->json(['message' => 'Event berhasil dihapus']);
    }

    public function register(int $eventId): JsonResponse
    {
        $event = $this->eventRepository->findById($eventId);
        $participant = auth()->user()->participants()->first();

        if (!$participant) {
            return response()->json(['message' => 'Data peserta tidak ditemukan'], 404);
        }

        $this->eventService->registerParticipant($event, $participant);

        return response()->json(['message' => 'Pendaftaran berhasil']);
    }

    public function participants(int $eventId): JsonResponse
    {
        $event = $this->eventRepository->findById($eventId, ['eventParticipants.participant']);

        return response()->json(['data' => $event->eventParticipants]);
    }

    public function upcoming(): JsonResponse
    {
        $events = $this->eventRepository->findUpcoming(['category']);

        return response()->json(['data' => $events]);
    }
}
