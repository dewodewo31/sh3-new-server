<?php

namespace Database\Seeders;

use App\Models\Merchandise;
use App\Models\MerchandiseOrder;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class MerchandiseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'merchandise')->first()?->id ?? User::first()?->id;

        $merchandise = [
            ['name' => 'SH3 Running Jersey', 'description' => 'Jersey lari resmi SH3, bahan dry-fit', 'price' => 150_000, 'size_options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'], 'stock' => 50, 'status' => 'available'],
            ['name' => 'SH3 Singlet', 'description' => 'Singlet balap ringan', 'price' => 120_000, 'size_options' => ['S', 'M', 'L', 'XL'], 'stock' => 40, 'status' => 'available'],
            ['name' => 'SH3 Running Cap', 'description' => 'Topi lari dengan logo SH3', 'price' => 75_000, 'size_options' => ['S', 'M', 'L'], 'stock' => 30, 'status' => 'available'],
            ['name' => 'SH3 Tumbler', 'description' => 'Botol minum stainless 500ml', 'price' => 90_000, 'size_options' => null, 'stock' => 25, 'status' => 'available'],
            ['name' => 'SH3 Handuk Lari', 'description' => 'Handuk microfiber ukuran travel', 'price' => 65_000, 'size_options' => null, 'stock' => 0, 'status' => 'sold_out'],
            ['name' => 'SH3 Running Socks', 'description' => 'Kaos kaki lari anti blister', 'price' => 45_000, 'size_options' => ['S', 'M', 'L'], 'stock' => 60, 'status' => 'available'],
            ['name' => 'SH3 Jacket', 'description' => 'Jacket windbreaker ringan', 'price' => 250_000, 'size_options' => ['S', 'M', 'L', 'XL', 'XXL'], 'stock' => 20, 'status' => 'available'],
            ['name' => 'SH3 Wristband', 'description' => 'Gelang silikon warna komunitas', 'price' => 25_000, 'size_options' => null, 'stock' => 100, 'status' => 'available'],
        ];

        foreach ($merchandise as $data) {
            Merchandise::updateOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'size_options' => $data['size_options'],
                    'stock' => $data['stock'],
                    'status' => $data['status'],
                    'created_by' => $admin,
                ]
            );
        }

        $this->createOrders();
    }

    private function createOrders(): void
    {
        $items = Merchandise::where('status', 'available')->where('stock', '>', 0)->get();

        if ($items->isEmpty()) {
            return;
        }

        $participants = Participant::all();

        if ($participants->isEmpty()) {
            return;
        }

        $count = min(12, $participants->count());
        $selected = $participants->take($count);

        foreach ($selected as $participant) {
            $itemCount = 1 + ($participant->id % 3);
            $chosenItems = $items->take($itemCount);

            foreach ($chosenItems as $item) {
                $sizes = $item->size_options;
                $size = $sizes ? $sizes[($participant->id + $item->id) % count($sizes)] : null;
                $quantity = 1 + ($item->id % 3);
                $total = $item->price * $quantity;
                $status = $participant->id % 3 === 0 ? 'pending' : 'paid';

                $order = MerchandiseOrder::firstOrCreate(
                    [
                        'merchandise_id' => $item->id,
                        'participant_id' => $participant->id,
                        'size' => $size ?? '-',
                    ],
                    [
                        'customer_name' => $participant->name,
                        'customer_contact' => $participant->phone ?? $participant->email,
                        'quantity' => $quantity,
                        'total_price' => $total,
                        'payment_status' => $status,
                    ]
                );

                if ($order->wasRecentlyCreated && $status === 'paid') {
                    $payment = Payment::create([
                        'participant_id' => $participant->id,
                        'invoice_number' => 'INV/'.now()->format('Ymd').'/'.strtoupper(uniqid()),
                        'payment_type' => 'merchandise',
                        'paymentable_type' => MerchandiseOrder::class,
                        'paymentable_id' => $order->id,
                        'amount' => $total,
                        'payment_method' => 'qris',
                        'status' => 'confirmed',
                        'confirmed_by' => User::where('role', 'bendahara')->first()?->id ?? User::first()?->id,
                        'paid_at' => now()->subDays(rand(1, 10)),
                    ]);

                    $order->update(['payment_id' => $payment->id]);
                }

                if ($order->wasRecentlyCreated) {
                    $item->decrement('stock', $quantity);
                }
            }
        }
    }
}
