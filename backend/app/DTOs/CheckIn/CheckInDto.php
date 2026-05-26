<?php

namespace App\DTOs\CheckIn;

readonly class CheckInDto
{
    /**
     * @param  list<AnswerDto>  $answers
     */
    public function __construct(
        public string $secretKey,
        public EmployeeDataDto $employee,
        public array $answers,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            secretKey: (string) $data['secret_key'],
            employee: EmployeeDataDto::fromArray($data['employee']),
            answers: array_map(
                fn (array $answer) => AnswerDto::fromArray($answer),
                $data['answers'] ?? [],
            ),
        );
    }
}
