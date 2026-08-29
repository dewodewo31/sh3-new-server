<?php

namespace App\Services;

use App\Models\Merchandise;
use App\Models\MerchandiseOrder;
use App\Models\Participant;
use App\Repositories\MerchandiseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchandiseService
{
    public function __construct(
        private MerchandiseRepository $merchandiseRepository,
        private PaymentService $paymentService,
        private NotificationService $notificationService,
        private PointService $pointService,
    ) {}

    public function createOrder(Merchandise $merchandise, array $data): MerchandiseOrder
    {
        return DB::transaction(function () use ($merchandise, $data) {
            // Lock the merchandise + participant rows FOR UPDATE so stock and
            // point balance are serialized across concurrent requests.
            $merchandise = Merchandise::query()->whereKey($merchandise->id)->lockForUpdate()->firstOrFail();

            $quantity = (int) $data['quantity'];

            if ($merchandise->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Stok tidak mencukupi.'],
                ]);
            }

            if (! empty($merchandise->size_options)
                && ! in_array($data['size'], $merchandise->size_options, true)) {
                throw ValidationException::withMessages([
                    'size' => ['Ukuran tidak tersedia untuk merchandise ini.'],
                ]);
            }

            $participant = Participant::query()
                ->whereKey($data['participant_id'])
                ->lockForUpdate()
                ->first();

            $usePoints = ! empty($data['use_points']) && $participant !== null;

            // --- Server-side point redemption math (per-unit, Model B) ---
            $pointsUsed = 0;
            $discountAmount = 0;
            $pointsPerUnitSnapshot = null;
            $discountPerUnitSnapshot = null;

            if ($usePoints && $merchandise->isPointRedeemable()) {
                $pointsPerUnit = (int) $merchandise->points_required;
                $discountPerUnit = $merchandise->discountPerUnit();

                $pointsUsed = $pointsPerUnit * $quantity;
                $discountAmount = $discountPerUnit * $quantity;

                // Safety cap: discount can never exceed the payable subtotal.
                $subtotal = (float) $merchandise->price * $quantity;
                if ($discountAmount > $subtotal) {
                    $discountAmount = (int) $subtotal;
                }

                if ($participant->point_balance < $pointsUsed) {
                    throw ValidationException::withMessages([
                        'use_points' => ['Poin tidak mencukupi untuk penukaran merchandise.'],
                    ]);
                }

                $pointsPerUnitSnapshot = $pointsPerUnit;
                $discountPerUnitSnapshot = $discountPerUnit;
            }

            $cashAmount = max(0, (float) ($merchandise->price * $quantity) - $discountAmount);

            $order = $merchandise->orders()->create([
                'participant_id' => $data['participant_id'],
                'customer_name' => $data['customer_name'],
                'customer_contact' => $data['customer_contact'],
                'size' => $data['size'],
                'quantity' => $quantity,
                'total_price' => $cashAmount,
                'points_used' => $pointsUsed > 0 ? $pointsUsed : null,
                'discount_amount' => $discountAmount,
                'unit_price_snapshot' => $merchandise->price,
                'quantity_snapshot' => $quantity,
                'points_per_unit_snapshot' => $pointsPerUnitSnapshot,
                'discount_per_unit_snapshot' => $discountPerUnitSnapshot,
                'cash_amount_snapshot' => $cashAmount,
                'payment_status' => MerchandiseOrder::STATUS_PENDING,
            ]);

            $merchandise->decrement('stock', $quantity);

            // Deduct points atomically (REDEEM ledger row, balance decrement).
            if ($pointsUsed > 0 && $participant) {
                $this->pointService->redeemForOrder($participant, $order, $pointsUsed);
            }

            $payment = $this->paymentService->createPayment([
                'participant_id' => $data['participant_id'],
                'payment_type' => 'merchandise',
                'paymentable_type' => MerchandiseOrder::class,
                'paymentable_id' => $order->id,
                'amount' => $order->total_price,
                'payment_method' => $data['payment_method'] ?? 'transfer',
                'payment_proof' => $data['payment_proof'] ?? null,
                'status' => 'pending',
            ]);

            $order->update(['payment_id' => $payment->id]);

            $this->notificationService->notifyRoles(
                ['merchandise', 'admin_full_access'],
                'Order merchandise baru',
                $data['customer_name'].' memesan '.$merchandise->name.' ('.$quantity.' pcs)'.($pointsUsed > 0 ? ' dengan poin' : '').'.',
                'cart',
                route('admin.merchandise.index'),
            );

            return $order;
        });
    }

    public function cancelOrder(MerchandiseOrder $order): void
    {
        if ($order->payment_status !== MerchandiseOrder::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'payment_status' => ['Order hanya bisa dibatalkan saat status pending.'],
            ]);
        }

        DB::transaction(function () use ($order) {
            $order->merchandise()->increment('stock', $order->quantity);
            $order->update(['payment_status' => MerchandiseOrder::STATUS_CANCELLED]);

            // Invariant: cancelling an order that used points must return them
            // to the participant (idempotent reversal).
            if (($order->points_used ?? 0) > 0 && $order->participant) {
                $this->pointService->refundRedemption($order->participant, $order);
            }
        });
    }

    public function uploadPayment(MerchandiseOrder $order, ?string $paymentProof, ?string $paymentMethod = null): void
    {
        if ($order->payment_status !== MerchandiseOrder::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'payment_status' => ['Bukti bayar hanya bisa diunggah saat status pending.'],
            ]);
        }

        DB::transaction(function () use ($order, $paymentProof, $paymentMethod) {
            $payment = $order->payment;

            if (! $payment) {
                $payment = $this->paymentService->createPayment([
                    'participant_id' => $order->participant_id,
                    'payment_type' => 'merchandise',
                    'paymentable_type' => MerchandiseOrder::class,
                    'paymentable_id' => $order->id,
                    'amount' => $order->total_price,
                    'payment_method' => $paymentMethod ?? 'transfer',
                    'payment_proof' => $paymentProof,
                    'status' => 'pending',
                ]);

                $order->update(['payment_id' => $payment->id]);

                return;
            }

            $data = [];

            if ($paymentProof !== null) {
                $data['payment_proof'] = $paymentProof;
            }

            if ($paymentMethod !== null) {
                $data['payment_method'] = $paymentMethod;
            }

            if ($data !== []) {
                $payment->update($data);
            }
        });
    }

    public function confirmPayment(Merchandise $merchandise, int $orderId): void
    {
        $order = $merchandise->orders()->findOrFail($orderId);
        $order->markAsPaid();
    }
}
