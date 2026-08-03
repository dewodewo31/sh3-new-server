<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private PaymentService $paymentService,
    ) {}

    public function store(PaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->createPayment($request->validated());

        return response()->json(['data' => $payment, 'message' => 'Pembayaran berhasil dibuat'], 201);
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->paymentRepository->findById($id, ['participant']);

        return response()->json(['data' => $payment]);
    }

    public function history(): JsonResponse
    {
        $user = auth()->user();
        $participant = $user->participants()->first();

        if (!$participant) {
            return response()->json(['data' => []]);
        }

        $payments = $this->paymentRepository->findByParticipant($participant->id);

        return response()->json(['data' => $payments]);
    }

    public function confirm(int $id): JsonResponse
    {
        $payment = $this->paymentRepository->findById($id);
        $this->paymentService->confirmPayment($payment, auth()->id());

        return response()->json(['message' => 'Pembayaran dikonfirmasi']);
    }
}
