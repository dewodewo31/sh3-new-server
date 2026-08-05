<?php

namespace App\Services;

use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private NotificationService $notificationService,
    ) {}

    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $data['invoice_number'] = $this->generateInvoiceNumber();

            $payment = $this->paymentRepository->create($data);

            $this->notificationService->notifyRoles(
                ['bendahara', 'admin_full_access'],
                'Pembayaran baru',
                'Pembayaran baru dengan invoice '.$payment->invoice_number.' menunggu konfirmasi.',
                'money',
                route('admin.payments.show', $payment->id),
            );

            return $payment;
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

        $this->notificationService->notifyAdmins(
            'Pembayaran dikonfirmasi',
            'Pembayaran '.$payment->invoice_number.' telah dikonfirmasi.',
            'check',
            route('admin.payments.show', $payment->id),
        );

        $this->notificationService->notifyParticipant(
            $payment->participant,
            'Pembayaran dikonfirmasi',
            'Pembayaran '.$payment->invoice_number.' telah dikonfirmasi.',
            'check',
        );
    }

    public function rejectPayment(Payment $payment, int $confirmedByUserId): void
    {
        $payment->update([
            'status' => 'rejected',
            'confirmed_by' => $confirmedByUserId,
        ]);

        $this->notificationService->notifyAdmins(
            'Pembayaran ditolak',
            'Pembayaran '.$payment->invoice_number.' telah ditolak.',
            'x',
            route('admin.payments.show', $payment->id),
        );

        $this->notificationService->notifyParticipant(
            $payment->participant,
            'Pembayaran ditolak',
            'Pembayaran '.$payment->invoice_number.' telah ditolak.',
            'x',
        );
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV/'.now()->format('Ymd').'/';
        $random = strtoupper(Str::random(6));

        return $prefix.$random;
    }
}
