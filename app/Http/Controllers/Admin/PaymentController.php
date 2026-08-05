<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private PaymentService $paymentService,
    ) {}

    public function index()
    {
        $payments = $this->paymentRepository->paginateWithParticipant();

        return view('payments.index', compact('payments'));
    }

    public function show(int $id)
    {
        $payment = $this->paymentRepository->findById($id, ['participant', 'confirmedBy']);

        return view('payments.show', compact('payment'));
    }

    public function confirm(int $id)
    {
        $payment = $this->paymentRepository->findById($id);
        $this->paymentService->confirmPayment($payment, auth()->id());

        return redirect()->back()->with('success', 'Pembayaran dikonfirmasi');
    }

    public function reject(int $id)
    {
        $payment = $this->paymentRepository->findById($id);
        $this->paymentService->rejectPayment($payment, auth()->id());

        return redirect()->back()->with('success', 'Pembayaran ditolak');
    }
}
