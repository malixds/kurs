<?php

namespace App\Repositories\Eloquent;

use App\DTOs\CheckIn\AnswerDto;
use App\Models\Employee;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Repositories\Contracts\SurveyAnswerRepositoryInterface;
use App\Services\Scoring\AnswerScoreCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyAnswerRepository implements SurveyAnswerRepositoryInterface
{
    public function __construct(
        private readonly AnswerScoreCalculator $scoreCalculator,
    ) {}

    public function storeAnswers(
        int $companyId,
        Employee $employee,
        array $answers,
        string $checkInDate,
    ): Collection {
        return DB::transaction(function () use ($companyId, $employee, $answers, $checkInDate): Collection {
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

                $stored->push(
                    SurveyAnswer::query()->create([
                        'company_id' => $companyId,
                        'employee_id' => $employee->id,
                        'survey_question_id' => $question->id,
                        'answer' => $answerDto->answer,
                        'score' => $this->scoreCalculator->calculate($question, $answerDto->answer),
                        'check_in_date' => $checkInDate,
                    ]),
                );
            }

            return $stored;
        });
    }
}
