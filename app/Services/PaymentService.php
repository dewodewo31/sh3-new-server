<?php

namespace App\Services;

use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepository
    ) {}

    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $data['invoice_number'] = $this->generateInvoiceNumber();

            return $this->paymentRepository->create($data);
        });
    }

    public function confirmPayment(Payment $payment, int $confirmedByUserId): void
    {
        DB::transaction(function () use ($payment, $confirmedByUserId) {
            $payment->update([
                'status' => 'confirmed',
                'confirmed_by' => $confirmedByUserId,
                'paid_at' => now(),
            ]);

            if ($payment->paymentable) {
                $payment->paymentable->markAsPaid();
            }
        });
    }

    public function rejectPayment(Payment $payment, int $confirmedByUserId): void
    {
        $payment->update([
            'status' => 'rejected',
            'confirmed_by' => $confirmedByUserId,
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV/' . now()->format('Ymd') . '/';
        $random = strtoupper(Str::random(6));

        return $prefix . $random;
    }
}
