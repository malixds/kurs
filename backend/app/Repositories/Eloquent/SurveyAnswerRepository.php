<?php

namespace App\Repositories\Eloquent;

use App\DTOs\CheckIn\AnswerDto;
use App\Models\CheckIn;
use App\Models\Employee;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Repositories\Contracts\SurveyAnswerRepositoryInterface;
use App\Services\Scoring\AnswerScoreCalculator;
use Illuminate\Support\Facades\DB;

class SurveyAnswerRepository implements SurveyAnswerRepositoryInterface
{
    public function __construct(
        private readonly AnswerScoreCalculator $scoreCalculator,
    ) {}

    public function storeCheckIn(
        int $companyId,
        Employee $employee,
        ?int $surveyId,
        array $answers,
        string $checkInDate,
    ): CheckIn {
        return DB::transaction(function () use ($companyId, $employee, $surveyId, $answers, $checkInDate): CheckIn {
            // One check-in per employee per day (enforced by the unique index);
            // a repeated submission updates the same row.
            $checkIn = CheckIn::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'check_in_date' => $checkInDate,
                ],
                [
                    'company_id' => $companyId,
                    'survey_id' => $surveyId,
                    'completed_at' => now(),
                ],
            );

            $questionIds = array_map(fn (AnswerDto $dto) => $dto->questionId, $answers);

            $questions = SurveyQuestion::query()
                ->whereIn('id', $questionIds)
                ->get()
                ->keyBy('id');

            $stored = collect();

            foreach ($answers as $answerDto) {
                $question = $questions->get($answerDto->questionId);

                if ($question === null) {
                    continue;
                }

                // Idempotent per question: latest answer wins, no duplicates.
                $stored->push(
                    SurveyAnswer::query()->updateOrCreate(
                        [
                            'check_in_id' => $checkIn->id,
                            'survey_question_id' => $question->id,
                        ],
                        [
                            'company_id' => $companyId,
                            'employee_id' => $employee->id,
                            'answer' => $answerDto->answer,
                            'score' => $this->scoreCalculator->calculate($question, $answerDto->answer),
                            'check_in_date' => $checkInDate,
                        ],
                    ),
                );
            }

            return $checkIn->setRelation('answers', $stored);
        });
    }
}
