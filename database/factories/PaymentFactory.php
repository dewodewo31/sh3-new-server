<?php

namespace Database\Factories;

use App\Models\EventParticipant;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'participant_id' => \App\Models\Participant::factory(),
            'invoice_number' => fake()->unique()->numerify('INV-####-####'),
            'payment_type' => 'event_registration',
            'paymentable_type' => EventParticipant::class,
            'paymentable_id' => EventParticipant::factory(),
            'amount' => fake()->numberBetween(50000, 500000),
            'payment_method' => 'transfer',
            'payment_proof' => null,
            'status' => 'pending',
            'confirmed_by' => null,
            'paid_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => 'confirmed',
            'paid_at' => now(),
        ]);
    }

    public function forMembership(\App\Models\MembershipHistory $history): static
    {
        return $this->state(fn () => [
            'payment_type' => 'membership',
            'paymentable_type' => \App\Models\MembershipHistory::class,
            'paymentable_id' => $history->id,
            'amount' => $history->price,
        ]);
    }

    public function forMerchandise(\App\Models\MerchandiseOrder $order): static
    {
        return $this->state(fn () => [
            'payment_type' => 'merchandise',
            'paymentable_type' => \App\Models\MerchandiseOrder::class,
            'paymentable_id' => $order->id,
            'amount' => $order->total_price,
        ]);
    }
}
