<?php

namespace App\Services;

use App\Models\Merchandise;
use App\Models\MerchandiseOrder;
use App\Repositories\MerchandiseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchandiseService
{
    public function __construct(
        private MerchandiseRepository $merchandiseRepository,
        private PaymentService $paymentService,
        private NotificationService $notificationService,
    ) {}

    public function createOrder(Merchandise $merchandise, array $data): MerchandiseOrder
    {
        return DB::transaction(function () use ($merchandise, $data) {
            if ($merchandise->stock < $data['quantity']) {
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

            $order = $merchandise->orders()->create([
                'participant_id' => $data['participant_id'],
                'customer_name' => $data['customer_name'],
                'customer_contact' => $data['customer_contact'],
                'size' => $data['size'],
                'quantity' => $data['quantity'],
                'total_price' => $merchandise->price * $data['quantity'],
                'payment_status' => MerchandiseOrder::STATUS_PENDING,
            ]);

            $merchandise->decrement('stock', $data['quantity']);

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
                $data['customer_name'].' memesan '.$merchandise->name.' ('.$data['quantity'].' pcs).',
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
