<?php

namespace App\DTOs;

class EventDTO
{
    public function __construct(
        public readonly int $categoryId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $location,
        public readonly ?string $address,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $registrationStartDate,
        public readonly string $registrationEndDate,
        public readonly ?int $quota,
        public readonly ?float $price,
        public readonly bool $isFreeForMembers = true,
        public readonly string $status = 'draft',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            categoryId: $data['category_id'],
            title: $data['title'],
            description: $data['description'] ?? null,
            location: $data['location'] ?? null,
            address: $data['address'] ?? null,
            startDate: $data['start_date'],
            endDate: $data['end_date'],
            registrationStartDate: $data['registration_start_date'],
            registrationEndDate: $data['registration_end_date'],
            quota: $data['quota'] ?? null,
            price: $data['price'] ?? null,
            isFreeForMembers: $data['is_free_for_members'] ?? true,
            status: $data['status'] ?? 'draft',
        );
    }
}
