<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private NotificationService $notificationService,
        private PointService $pointService,
        private AttendanceService $attendanceService,
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
        DB::transaction(function () use ($payment, $confirmedByUserId) {
            $payment->update([
                'status' => 'rejected',
                'confirmed_by' => $confirmedByUserId,
            ]);

            $paymentable = $payment->paymentable;

            if ($paymentable && method_exists($paymentable, 'markAsRejected')) {
                $paymentable->markAsRejected();
            }

            // V12 (audit H1): an EVENT REGISTRATION payment rejection must
            // invalidate the attendance (if any) and reverse the EARN. This is
            // idempotent — invalidating the same attendance twice is a no-op and
            // reverseEarn reverses only once.
            if ($paymentable instanceof EventParticipant) {
                $attendance = $paymentable->attendance;
                if ($attendance) {
                    $this->attendanceService->invalidateAttendance(
                        $attendance->id,
                        $confirmedByUserId,
                        'Pembayaran event ditolak.'
                    );
                }
            }

            // Invariant: if the paymentable is a merchandise order that used
            // points, rejecting the payment reverses the redemption so the
            // participant is never charged points for a rejected order.
            $order = $this->resolveMerchandiseOrder($paymentable);
            if ($order && ($order->points_used ?? 0) > 0 && $order->participant) {
                $this->pointService->refundRedemption($order->participant, $order);
            }
        });

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

    /**
     * Refund a confirmed payment (audit finding H5 / V12).
     *
     * Lifecycle:
     *   EVENT REGISTRATION refund -> attendance invalid -> EARN reversal
     *   MERCHANDISE refund        -> preserves the existing redemption reversal
     *
     * All transitions are idempotent: re-calling on an already-refunded payment
     * is a clean no-op, and reverseEarn/refundRedemption reverse only once.
     */
    public function refundPayment(Payment $payment, int $refundedByUserId): void
    {
        if ($payment->status === 'refunded') {
            return; // idempotent no-op
        }

        DB::transaction(function () use ($payment, $refundedByUserId) {
            $payment->update([
                'status' => 'refunded',
                'refunded_by' => $refundedByUserId,
                'refunded_at' => now(),
            ]);

            $paymentable = $payment->paymentable;

            // V12 (audit H1): event registration refund invalidates attendance
            // (if any) and reverses the EARN.
            if ($paymentable instanceof EventParticipant) {
                if (method_exists($paymentable, 'markAsRejected')) {
                    $paymentable->markAsRejected();
                }

                $attendance = $paymentable->attendance;
                if ($attendance) {
                    $this->attendanceService->invalidateAttendance(
                        $attendance->id,
                        $refundedByUserId,
                        'Pembayaran event direfund.'
                    );
                }
            }

            // Preserve existing merchandise redemption reversal behaviour.
            $order = $this->resolveMerchandiseOrder($paymentable);
            if ($order && ($order->points_used ?? 0) > 0 && $order->participant) {
                $this->pointService->refundRedemption($order->participant, $order);
            }
        });

        $this->notificationService->notifyAdmins(
            'Pembayaran direfund',
            'Pembayaran '.$payment->invoice_number.' telah direfund.',
            'x',
            route('admin.payments.show', $payment->id),
        );

        $this->notificationService->notifyParticipant(
            $payment->participant,
            'Pembayaran direfund',
            'Pembayaran '.$payment->invoice_number.' telah direfund.',
            'x',
        );
    }

    /**
     * Resolve a MerchandiseOrder from a polymorphic paymentable, when applicable.
     */
    private function resolveMerchandiseOrder($paymentable): ?\App\Models\MerchandiseOrder
    {
        if ($paymentable instanceof \App\Models\MerchandiseOrder) {
            return $paymentable;
        }

        return null;
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV/'.now()->format('Ymd').'/';
        $random = strtoupper(Str::random(6));

        return $prefix.$random;
    }
}
