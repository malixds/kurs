<?php

namespace App\DTOs\CheckIn;

readonly class EmployeeDataDto
{
    public function __construct(
        public string $externalId,
        public ?string $email = null,
        public ?string $name = null,
        public ?string $departmentExternalId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            externalId: (string) $data['external_id'],
            email: isset($data['email']) ? (string) $data['email'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            departmentExternalId: isset($data['department_external_id'])
                ? (string) $data['department_external_id']
                : null,
        );
    }
}
