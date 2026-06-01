<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const DEMO_SECRET_KEY = 'demo_secret_key_12345678901234567890123456789012';

    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['secret_key' => self::DEMO_SECRET_KEY],
            ['name' => 'Acme Remote Corp'],
        );

        $engineering = Department::query()->firstOrCreate(
            ['company_id' => $company->id, 'external_id' => 'eng'],
            ['name' => 'Engineering'],
        );

        $hr = Department::query()->firstOrCreate(
            ['company_id' => $company->id, 'external_id' => 'hr'],
            ['name' => 'Human Resources'],
        );

        $this->seedDemoUser(
            email: 'admin@acme.test',
            name: 'Demo Admin',
            role: UserRole::Admin,
            company: $company,
        );

        $this->seedDemoUser(
            email: 'hr@acme.test',
            name: 'Demo HR',
            role: UserRole::Hr,
            company: $company,
        );

        $survey = Survey::query()->firstOrCreate(
            ['title' => 'Daily Wellbeing Check-in', 'company_id' => null],
            ['is_active' => true],
        );

        $questions = [
            [
                'question' => 'How would you rate your overall mood today?',
                'type' => QuestionType::Scale,
                'sort_order' => 1,
                'options' => ['min' => 1, 'max' => 5, 'labels' => ['Very low', 'Low', 'Neutral', 'Good', 'Excellent']],
            ],
            [
                'question' => 'How stressed do you feel right now?',
                'type' => QuestionType::Scale,
                'sort_order' => 2,
                'options' => ['min' => 1, 'max' => 5, 'labels' => ['Not at all', 'Slightly', 'Moderately', 'Very', 'Extremely']],
            ],
            [
                'question' => 'Did you feel supported by your team today?',
                'type' => QuestionType::Boolean,
                'sort_order' => 3,
                'options' => null,
            ],
            [
                'question' => 'Anything you would like to share? (optional)',
                'type' => QuestionType::Text,
                'sort_order' => 4,
                'options' => null,
            ],
        ];

        $questionModels = collect($questions)->map(fn (array $data) => SurveyQuestion::query()->firstOrCreate(
            ['survey_id' => $survey->id, 'sort_order' => $data['sort_order']],
            $data,
        ));

        $employees = collect([
            ['external_id' => 'emp-001', 'name' => 'Alice Johnson', 'email' => 'alice@acme.test', 'department_id' => $engineering->id],
            ['external_id' => 'emp-002', 'name' => 'Bob Smith', 'email' => 'bob@acme.test', 'department_id' => $engineering->id],
            ['external_id' => 'emp-003', 'name' => 'Carol White', 'email' => 'carol@acme.test', 'department_id' => $hr->id],
        ])->map(fn (array $data) => Employee::query()->firstOrCreate(
            ['company_id' => $company->id, 'external_id' => $data['external_id']],
            $data,
        ));

        if (! SurveyAnswer::query()->where('company_id', $company->id)->exists()) {
            $this->seedDemoAnswers($company, $employees, $questionModels);
        }

        $this->call(IntegrationProviderSeeder::class);
    }

    private function seedDemoUser(string $email, string $name, UserRole $role, Company $company): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'company_id' => $company->id,
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
            ],
        );

        $user->update([
            'company_id' => $company->id,
            'name' => $name,
            'role' => $role,
        ]);

        $user->companies()->syncWithoutDetaching([
            $company->id => ['role' => $role->value],
        ]);
    }

    private function seedDemoAnswers(Company $company, $employees, $questionModels): void
    {
        foreach (range(0, 13) as $daysAgo) {
            $date = Carbon::now()->subDays($daysAgo)->toDateString();

            foreach ($employees as $index => $employee) {
                $mood = max(1, min(5, 4 - ($index * 0.3) - ($daysAgo * 0.05) + random_int(-1, 1)));
                $stress = max(1, min(5, 2 + ($index * 0.2) + ($daysAgo * 0.04)));

                SurveyAnswer::query()->create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'survey_question_id' => $questionModels[0]->id,
                    'answer' => (string) $mood,
                    'score' => (float) $mood,
                    'check_in_date' => $date,
                ]);

                SurveyAnswer::query()->create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'survey_question_id' => $questionModels[1]->id,
                    'answer' => (string) $stress,
                    'score' => (float) $stress,
                    'check_in_date' => $date,
                ]);

                SurveyAnswer::query()->create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'survey_question_id' => $questionModels[2]->id,
                    'answer' => $mood >= 3 ? 'yes' : 'no',
                    'score' => $mood >= 3 ? 5.0 : 1.0,
                    'check_in_date' => $date,
                ]);
            }
        }
    }
}
