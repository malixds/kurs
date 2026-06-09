<?php

namespace Tests\Unit;

use App\Enums\QuestionType;
use App\Models\SurveyQuestion;
use App\Services\Scoring\AnswerScoreCalculator;
use PHPUnit\Framework\TestCase;

class AnswerScoreCalculatorTest extends TestCase
{
    private AnswerScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new AnswerScoreCalculator;
    }

    private function question(QuestionType $type, ?array $options = null): SurveyQuestion
    {
        $question = new SurveyQuestion;
        $question->type = $type;
        $question->options = $options;

        return $question;
    }

    public function test_scale_answer_within_range_is_scored(): void
    {
        $question = $this->question(QuestionType::Scale, ['min' => 1, 'max' => 5]);

        $this->assertSame(4.0, $this->calculator->calculate($question, '4'));
    }

    public function test_scale_answer_out_of_range_is_rejected(): void
    {
        $question = $this->question(QuestionType::Scale, ['min' => 1, 'max' => 5]);

        $this->assertNull($this->calculator->calculate($question, '9'));
        $this->assertNull($this->calculator->calculate($question, '0'));
    }

    public function test_non_numeric_scale_answer_is_rejected(): void
    {
        $question = $this->question(QuestionType::Scale, ['min' => 1, 'max' => 5]);

        $this->assertNull($this->calculator->calculate($question, 'great'));
    }

    public function test_boolean_answers_map_to_extremes(): void
    {
        $question = $this->question(QuestionType::Boolean);

        $this->assertSame(5.0, $this->calculator->calculate($question, 'yes'));
        $this->assertSame(5.0, $this->calculator->calculate($question, 'Да'));
        $this->assertSame(1.0, $this->calculator->calculate($question, 'no'));
        $this->assertSame(1.0, $this->calculator->calculate($question, 'нет'));
        $this->assertNull($this->calculator->calculate($question, 'maybe'));
    }

    public function test_text_answers_are_never_scored(): void
    {
        $question = $this->question(QuestionType::Text);

        $this->assertNull($this->calculator->calculate($question, 'Feeling fine today'));
    }
}
