<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    private User $userA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create();
        $this->companyB = Company::factory()->create();

        $this->userA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'role' => 'admin',
        ]);
        $this->companyA->members()->attach($this->userA->id, ['role' => 'admin']);
    }

    public function test_analytics_rejects_employee_id_from_another_company(): void
    {
        $foreignEmployee = Employee::factory()->for($this->companyB)->create();

        Sanctum::actingAs($this->userA);

        $response = $this->getJson(
            '/api/v1/dashboard/analytics/overview?employee_id='.$foreignEmployee->id,
        );

        $response->assertStatus(422)->assertJsonValidationErrors('employee_id');
    }

    public function test_analytics_rejects_department_id_from_another_company(): void
    {
        $foreignDepartment = Department::factory()->for($this->companyB)->create();

        Sanctum::actingAs($this->userA);

        $response = $this->getJson(
            '/api/v1/dashboard/analytics/overview?department_id='.$foreignDepartment->id,
        );

        $response->assertStatus(422)->assertJsonValidationErrors('department_id');
    }

    public function test_employee_history_of_another_company_returns_404(): void
    {
        $foreignEmployee = Employee::factory()->for($this->companyB)->create();

        Sanctum::actingAs($this->userA);

        $response = $this->getJson(
            "/api/v1/dashboard/analytics/employees/{$foreignEmployee->id}/history",
        );

        $response->assertNotFound();
    }

    public function test_overview_aggregates_only_own_company_data(): void
    {
        $question = SurveyQuestion::factory()
            ->for(Survey::factory()->create())
            ->create();

        $employeeA = Employee::factory()->for($this->companyA)->create();
        $employeeB = Employee::factory()->for($this->companyB)->create();

        SurveyAnswer::factory()->create([
            'company_id' => $this->companyA->id,
            'employee_id' => $employeeA->id,
            'survey_question_id' => $question->id,
            'answer' => '4',
            'score' => 4,
        ]);

        SurveyAnswer::factory()->create([
            'company_id' => $this->companyB->id,
            'employee_id' => $employeeB->id,
            'survey_question_id' => $question->id,
            'answer' => '1',
            'score' => 1,
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->getJson('/api/v1/dashboard/analytics/overview');

        $response->assertOk();
        $this->assertEquals(4.0, $response->json('data.average_mood_score'));

        $summaries = $response->json('data.employee_summaries');
        $this->assertCount(1, $summaries);
        $this->assertSame($employeeA->id, $summaries[0]['employee_id']);
    }

    public function test_analytics_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard/analytics/overview')->assertUnauthorized();
    }

    public function test_analytics_rejects_oversized_date_range(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->getJson(
            '/api/v1/dashboard/analytics/overview?from=2020-01-01&to='.now()->toDateString(),
        );

        $response->assertStatus(422)->assertJsonValidationErrors('to');
    }
}
