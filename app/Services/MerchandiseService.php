<?php

namespace App\Services;

use App\Models\Merchandise;
use App\Repositories\MerchandiseRepository;
use Illuminate\Validation\ValidationException;

class MerchandiseService
{
    public function __construct(
        private MerchandiseRepository $merchandiseRepository,
        private NotificationService $notificationService,
    ) {}

    public function createOrder(Merchandise $merchandise, array $data): void
    {
        if ($merchandise->stock < $data['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => ['Stok tidak mencukupi.'],
            ]);
        }

        $merchandise->orders()->create([
            'participant_id' => $data['participant_id'],
            'customer_name' => $data['customer_name'],
            'customer_contact' => $data['customer_contact'],
            'size' => $data['size'],
            'quantity' => $data['quantity'],
            'total_price' => $merchandise->price * $data['quantity'],
            'payment_status' => 'pending',
        ]);

        $merchandise->decrement('stock', $data['quantity']);

        $this->notificationService->notifyRoles(
            ['merchandise', 'admin_full_access'],
            'Order merchandise baru',
            $data['customer_name'].' memesan '.$merchandise->name.' ('.$data['quantity'].' pcs).',
            'cart',
            route('admin.merchandise.index'),
        );
    }

    public function confirmPayment(Merchandise $merchandise, int $orderId): void
    {
        $order = $merchandise->orders()->findOrFail($orderId);
        $order->update(['payment_status' => 'paid']);
    }
}
