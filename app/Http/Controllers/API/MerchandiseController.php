<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchandiseOrderRequest;
use App\Http\Requests\UploadPaymentRequest;
use App\Http\Resources\MerchandiseResource;
use App\Repositories\MerchandiseOrderRepository;
use App\Repositories\MerchandiseRepository;
use App\Services\MerchandiseService;
use Illuminate\Http\JsonResponse;

class MerchandiseController extends Controller
{
    public function __construct(
        private MerchandiseRepository $merchandiseRepository,
        private MerchandiseOrderRepository $merchandiseOrderRepository,
        private MerchandiseService $merchandiseService,
    ) {}

    public function index(): JsonResponse
    {
        $merchandise = $this->merchandiseRepository->findAvailable();

        return response()->json(['data' => MerchandiseResource::collection($merchandise)]);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->merchandiseRepository->findById($id);

        return response()->json(['data' => new MerchandiseResource($item)]);
    }

    public function order(MerchandiseOrderRequest $request): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        $merchandise = $this->merchandiseRepository->findById($request->integer('merchandise_id'));

        $order = $this->merchandiseService->createOrder($merchandise, [
            'participant_id' => $participant->id,
            'customer_name' => $request->string('customer_name')->toString(),
            'customer_contact' => $request->string('customer_contact')->toString(),
            'size' => $request->string('size')->toString(),
            'quantity' => $request->integer('quantity'),
        ]);

        return response()->json([
            'data' => $order->load('merchandise', 'payment'),
            'message' => 'Order berhasil dibuat',
        ], 201);
    }

    public function orders(): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['data' => []]);
        }

        $orders = $this->merchandiseOrderRepository->findByParticipant($participant->id);

        return response()->json(['data' => $orders]);
    }

    public function orderDetail(int $id): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        $order = $this->merchandiseOrderRepository->findOwnedByParticipant($participant->id, $id);

        if (! $order) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $order]);
    }

    public function cancelOrder(int $id): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        $order = $this->merchandiseOrderRepository->findOwnedByParticipant($participant->id, $id);

        if (! $order) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        $this->merchandiseService->cancelOrder($order);

        return response()->json(['message' => 'Order dibatalkan.']);
    }

    public function uploadPayment(int $id, UploadPaymentRequest $request): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        $order = $this->merchandiseOrderRepository->findOwnedByParticipant($participant->id, $id);

        if (! $order) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        $paymentProof = ImageHelper::upload($request->file('payment_proof'), 'payments');

        $this->merchandiseService->uploadPayment(
            $order,
            $paymentProof,
            $request->input('payment_method'),
        );

        return response()->json([
            'data' => [
                'path' => $paymentProof,
                'url' => ImageHelper::getUrl($paymentProof),
            ],
            'message' => 'Bukti pembayaran berhasil diunggah.',
        ]);
    }

    private function currentParticipant()
    {
        return auth()->user()?->participants()->first();
    }
}
