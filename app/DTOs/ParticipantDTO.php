<?php

namespace App\DTOs;

class ParticipantDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $gender,
        public readonly ?string $dateOfBirth,
        public readonly ?string $address,
        public readonly ?string $emergencyContact,
        public readonly ?string $emergencyPhone,
        public readonly ?string $medicalConditions,
        public readonly ?string $bloodType,
        public readonly ?string $jerseySize,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            gender: $data['gender'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            address: $data['address'] ?? null,
            emergencyContact: $data['emergency_contact'] ?? null,
            emergencyPhone: $data['emergency_phone'] ?? null,
            medicalConditions: $data['medical_conditions'] ?? null,
            bloodType: $data['blood_type'] ?? null,
            jerseySize: $data['jersey_size'] ?? null,
        );
    }
}
