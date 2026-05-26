<?php

namespace App\DTOs\CheckIn;

readonly class AnswerDto
{
    public function __construct(
        public int $questionId,
        public string $answer,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            questionId: (int) $data['question_id'],
            answer: (string) $data['answer'],
        );
    }
}
