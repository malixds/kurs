<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionCheckInFlowTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private SurveyQuestion $scaleQuestion;

    private SurveyQuestion $booleanQuestion;

    private SurveyQuestion $textQuestion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $survey = Survey::factory()->create();
        $this->scaleQuestion = SurveyQuestion::factory()->for($survey)->create();
        $this->booleanQuestion = SurveyQuestion::factory()->boolean()->for($survey)->create();
        $this->textQuestion = SurveyQuestion::factory()->text()->for($survey)->create();
    }

    public function test_questions_endpoint_accepts_company_key_header(): void
    {
        $response = $this->getJson(
            '/api/v1/extension/survey/questions',
            ['X-Company-Key' => $this->company->secret_key],
        );

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_questions_endpoint_rejects_unknown_company_key(): void
    {
        $response = $this->getJson(
            '/api/v1/extension/survey/questions',
            ['X-Company-Key' => str_repeat('x', 48)],
        );

        $response->assertStatus(422);
    }

    public function test_check_in_creates_employee_and_scores_answers(): void
    {
        $response = $this->postJson('/api/v1/extension/check-in', [
            'employee' => [
                'external_id' => 'emp-flow-1',
                'email' => 'employee@example.com',
                'name' => 'Test Employee',
            ],
            'answers' => [
                ['question_id' => $this->scaleQuestion->id, 'answer' => '4'],
                ['question_id' => $this->booleanQuestion->id, 'answer' => 'yes'],
                ['question_id' => $this->textQuestion->id, 'answer' => 'Feeling good'],
            ],
        ], ['X-Company-Key' => $this->company->secret_key]);

        $response->assertCreated()->assertJsonPath('data.answers_stored', 3);

        $employee = Employee::query()
            ->where('company_id', $this->company->id)
            ->where('external_id', 'emp-flow-1')
            ->first();

        $this->assertNotNull($employee);

        $scores = SurveyAnswer::query()
            ->where('employee_id', $employee->id)
            ->pluck('score', 'survey_question_id');

        $this->assertEquals(4.0, (float) $scores[$this->scaleQuestion->id]);
        $this->assertEquals(5.0, (float) $scores[$this->booleanQuestion->id]);
        $this->assertNull($scores[$this->textQuestion->id]);
    }

    public function test_check_in_rejects_question_from_another_survey(): void
    {
        $foreignQuestion = SurveyQuestion::factory()
            ->for(Survey::factory()->create(['is_active' => false]))
            ->create();

        $response = $this->postJson('/api/v1/extension/check-in', [
            'employee' => [
                'external_id' => 'emp-flow-2',
                'email' => 'employee2@example.com',
                'name' => 'Test Employee 2',
            ],
            'answers' => [
                ['question_id' => $foreignQuestion->id, 'answer' => '3'],
            ],
        ], ['X-Company-Key' => $this->company->secret_key]);

        $response->assertStatus(422);
        $this->assertSame(0, SurveyAnswer::query()->count());
    }
}
