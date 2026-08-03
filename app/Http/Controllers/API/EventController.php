<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\EventParticipant;
use App\Models\Payment;
use App\Repositories\EventParticipantRepository;
use App\Repositories\EventRepository;
use App\Services\EventService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventParticipantRepository $eventParticipantRepository,
        private EventService $eventService,
        private PaymentService $paymentService,
    ) {}

    public function index(): JsonResponse
    {
        $events = $this->eventRepository->findPublic(['category']);

        return response()->json(['data' => EventResource::collection($events)]);
    }

    public function show(int $id): JsonResponse
    {
        $event = $this->eventRepository->findById($id, ['category', 'schedules', 'createdBy', 'galleries']);

        return response()->json(['data' => new EventResource($event)]);
    }

    public function store(EventRequest $request): JsonResponse
    {
        $event = $this->eventRepository->create($request->validated());

        return response()->json(['data' => new EventResource($event), 'message' => 'Event berhasil dibuat'], 201);
    }

    public function update(int $id, EventRequest $request): JsonResponse
    {
        $event = $this->eventRepository->findById($id);
        $this->eventRepository->update($event, $request->validated());

        return response()->json(['data' => new EventResource($event->fresh()), 'message' => 'Event berhasil diupdate']);
    }

    public function destroy(int $id): JsonResponse
    {
        $event = $this->eventRepository->findById($id);
        $this->eventRepository->delete($event);

        return response()->json(['message' => 'Event berhasil dihapus']);
    }

    public function register(Request $request, int $eventId): JsonResponse
    {
        $event = $this->eventRepository->findById($eventId);
        $participant = auth()->user()->participants()->first();

        if (!$participant) {
            return response()->json(['message' => 'Data peserta tidak ditemukan'], 404);
        }

        $this->eventService->registerParticipant($event, $participant, null);

        $registration = $this->eventParticipantRepository->findByEventAndParticipant(
            $event->id, $participant->id
        );

        $paid = $event->price && $event->price > 0;

        if ($paid) {
            $validated = $request->validate([
                'payment_method' => ['nullable', 'in:transfer,cash,qris'],
                'payment_proof' => ['nullable', 'image', 'max:5120'],
            ]);

            $paymentProof = null;
            if ($request->hasFile('payment_proof')) {
                $paymentProof = ImageHelper::upload($request->file('payment_proof'), 'payments');
            }

            $payment = $this->paymentService->createPayment([
                'participant_id' => $participant->id,
                'payment_type' => 'event_registration',
                'paymentable_type' => EventParticipant::class,
                'paymentable_id' => $registration->id,
                'amount' => $event->price,
                'payment_method' => $validated['payment_method'] ?? 'transfer',
                'payment_proof' => $paymentProof,
                'status' => 'pending',
            ]);

            $registration->update(['payment_id' => $payment->id]);
        }

        return response()->json([
            'data' => $this->registrationPayload($registration->fresh('payment')),
            'message' => 'Pendaftaran berhasil',
        ]);
    }

    public function myEvents(): JsonResponse
    {
        $participant = auth()->user()->participants()->first();

        if (!$participant) {
            return response()->json(['data' => []]);
        }

        $registrations = $this->eventParticipantRepository->findEventsByParticipant($participant->id);

        $data = $registrations->map(function (EventParticipant $ep) {
            $event = $ep->event;

            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'image_url' => ImageHelper::getUrl($event->image),
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'status' => $event->status,
                'price' => $event->price,
                'event_participant_id' => $ep->id,
                'order' => [
                    'id' => $ep->id,
                    'status' => $this->orderStatus($ep),
                    'registration_type' => $ep->registration_type,
                    'invoice_number' => $ep->payment?->invoice_number,
                    'ticket_code' => $ep->qr_code,
                    'attendance' => [
                        'qr_code' => $ep->qr_code,
                    ],
                ],
            ];
        });

        return response()->json(['data' => $data->values()]);
    }

    private function orderStatus(EventParticipant $ep): string
    {
        return match ($ep->payment_status) {
            'confirmed' => $ep->amount > 0 ? 'paid' : 'free',
            'pending' => 'pending',
            'rejected' => 'cancelled',
            'refunded' => 'cancelled',
            default => 'pending',
        };
    }

    private function registrationPayload(EventParticipant $ep): array
    {
        return [
            'event_participant_id' => $ep->id,
            'payment_status' => $ep->payment_status,
            'registration_type' => $ep->registration_type,
            'amount' => $ep->amount,
            'qr_code' => $ep->qr_code,
            'invoice_number' => $ep->payment?->invoice_number,
            'ticket_code' => $ep->qr_code,
        ];
    }

    public function participants(int $eventId): JsonResponse
    {
        $event = $this->eventRepository->findById($eventId, ['eventParticipants.participant']);

        return response()->json(['data' => $event->eventParticipants]);
    }

    public function upcoming(): JsonResponse
    {
        $events = $this->eventRepository->findUpcoming(['category']);

        return response()->json(['data' => EventResource::collection($events)]);
    }

    public function qrCodes(int $eventId): JsonResponse
    {
        $event = $this->eventRepository->findById($eventId, ['eventParticipants.participant']);

        $data = $event->eventParticipants->map(function (EventParticipant $ep) {
            return [
                'event_participant_id' => $ep->id,
                'participant_id' => $ep->participant_id,
                'participant_name' => $ep->participant?->name,
                'registration_type' => $ep->registration_type,
                'payment_status' => $ep->payment_status,
                'qr_code' => $ep->qr_code,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
