<?php

namespace App\DTOs;

class PaymentDTO
{
    public function __construct(
        public readonly int $participantId,
        public readonly string $paymentType,
        public readonly string $paymentableType,
        public readonly int $paymentableId,
        public readonly float $amount,
        public readonly string $paymentMethod,
        public readonly ?string $paymentProof = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            participantId: $data['participant_id'],
            paymentType: $data['payment_type'],
            paymentableType: $data['paymentable_type'],
            paymentableId: $data['paymentable_id'],
            amount: $data['amount'],
            paymentMethod: $data['payment_method'],
            paymentProof: $data['payment_proof'] ?? null,
        );
    }
}
