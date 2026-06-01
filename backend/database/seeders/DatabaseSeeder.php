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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const DEMO_SECRET_KEY = 'demo_secret_key_12345678901234567890123456789012';

    private const PROJECT_SECRET_KEY = 'project_secret_key_12345678901234567890123456789';

    private const DEMO_EMPLOYEE_COUNT = 20;

    private const HISTORY_DAYS = 30;

    public function run(): void
    {
        $survey = Survey::query()->firstOrCreate(
            ['title' => 'Daily Wellbeing Check-in', 'company_id' => null],
            ['is_active' => true],
        );

        $questionModels = $this->seedSurveyQuestions($survey);

        $this->seedAcmeCompany($questionModels);
        $this->seedProjectCompany($questionModels);

        $this->call(IntegrationProviderSeeder::class);
    }

    private function seedAcmeCompany(Collection $questionModels): void
    {
        $company = Company::query()->firstOrCreate(
            ['secret_key' => self::DEMO_SECRET_KEY],
            ['name' => 'Acme Remote Corp'],
        );

        $departments = $this->seedDepartments($company);

        $this->seedDemoUser(
            email: 'owner@acme.test',
            name: 'Alex Morgan',
            role: UserRole::Admin,
            company: $company,
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

        $employees = $this->seedEmployees($company, $departments);

        if ($this->shouldRefreshCompanyAnswers($company, self::DEMO_EMPLOYEE_COUNT, 400)) {
            SurveyAnswer::query()->where('company_id', $company->id)->delete();
            $this->seedRealisticMonthAnswers($company, $employees, $questionModels);
        }
    }

    private function seedProjectCompany(Collection $questionModels): void
    {
        $company = Company::query()->firstOrCreate(
            ['secret_key' => self::PROJECT_SECRET_KEY],
            ['name' => 'project'],
        );

        $company->update(['name' => 'project']);

        $team = Department::query()->firstOrCreate(
            ['company_id' => $company->id, 'external_id' => 'team'],
            ['name' => 'Team'],
        );

        $this->seedDemoUser(
            email: 'hse@main.ru',
            name: 'HSE Admin',
            role: UserRole::Admin,
            company: $company,
        );

        $roster = [
            ['proj-001', 'Kesen Teng', 'kesentseng09@gmail.com'],
            ['proj-002', 'Masik', 'masik2003410@gmail.com'],
            ['proj-003', 'Pontsa', 'pontsa74@gmail.com'],
        ];

        $employees = collect($roster)->map(function (array $row) use ($company, $team) {
            [$externalId, $name, $email] = $row;
            $employee = Employee::query()->firstOrCreate(
                ['company_id' => $company->id, 'external_id' => $externalId],
                [
                    'name' => $name,
                    'email' => $email,
                    'department_id' => $team->id,
                ],
            );
            $employee->update(['name' => $name, 'email' => $email, 'department_id' => $team->id]);

            return $employee;
        });

        if ($this->shouldRefreshCompanyAnswers($company, 3, 50)) {
            SurveyAnswer::query()->where('company_id', $company->id)->delete();
            $this->seedRealisticMonthAnswers($company, $employees, $questionModels);
        }
    }

    private function seedDepartments(Company $company): array
    {
        $defs = [
            ['external_id' => 'eng', 'name' => 'Engineering'],
            ['external_id' => 'product', 'name' => 'Product'],
            ['external_id' => 'design', 'name' => 'Design'],
            ['external_id' => 'hr', 'name' => 'Human Resources'],
            ['external_id' => 'support', 'name' => 'Customer Support'],
        ];

        $result = [];
        foreach ($defs as $def) {
            $result[$def['external_id']] = Department::query()->firstOrCreate(
                ['company_id' => $company->id, 'external_id' => $def['external_id']],
                ['name' => $def['name']],
            );
        }

        return $result;
    }

    private function seedSurveyQuestions(Survey $survey): Collection
    {
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

        return collect($questions)->map(fn (array $data) => SurveyQuestion::query()->firstOrCreate(
            ['survey_id' => $survey->id, 'sort_order' => $data['sort_order']],
            $data,
        ));
    }

    private function seedEmployees(Company $company, array $departments): Collection
    {
        $roster = [
            ['eng', 'emp-001', 'Alice Johnson', 'alice@acme.test'],
            ['eng', 'emp-002', 'Bob Smith', 'bob@acme.test'],
            ['eng', 'emp-003', 'Diana Chen', 'diana@acme.test'],
            ['eng', 'emp-004', 'Erik Nilsson', 'erik@acme.test'],
            ['eng', 'emp-005', 'Fatima Hassan', 'fatima@acme.test'],
            ['eng', 'emp-006', 'George Miller', 'george@acme.test'],
            ['eng', 'emp-007', 'Hannah Lee', 'hannah@acme.test'],
            ['eng', 'emp-008', 'Ivan Petrov', 'ivan@acme.test'],
            ['product', 'emp-009', 'Julia Rossi', 'julia@acme.test'],
            ['product', 'emp-010', 'Kevin Okafor', 'kevin@acme.test'],
            ['product', 'emp-011', 'Laura Martins', 'laura@acme.test'],
            ['product', 'emp-012', 'Marco Silva', 'marco@acme.test'],
            ['product', 'emp-013', 'Nina Kowalski', 'nina@acme.test'],
            ['design', 'emp-014', 'Oliver Brown', 'oliver@acme.test'],
            ['design', 'emp-015', 'Paula Garcia', 'paula@acme.test'],
            ['design', 'emp-016', 'Quinn Taylor', 'quinn@acme.test'],
            ['hr', 'emp-017', 'Carol White', 'carol@acme.test'],
            ['hr', 'emp-018', 'Rita Novak', 'rita@acme.test'],
            ['support', 'emp-019', 'Sam Wilson', 'sam@acme.test'],
            ['support', 'emp-020', 'Tara Singh', 'tara@acme.test'],
        ];

        return collect($roster)->map(function (array $row) use ($company, $departments) {
            [$deptKey, $externalId, $name, $email] = $row;

            return Employee::query()->firstOrCreate(
                ['company_id' => $company->id, 'external_id' => $externalId],
                [
                    'name' => $name,
                    'email' => $email,
                    'department_id' => $departments[$deptKey]->id,
                ],
            );
        });
    }

    private function shouldRefreshCompanyAnswers(Company $company, int $minEmployees, int $minRecentAnswers): bool
    {
        $employeeCount = Employee::query()->where('company_id', $company->id)->count();

        if ($employeeCount < $minEmployees) {
            return true;
        }

        $recentAnswers = SurveyAnswer::query()
            ->where('company_id', $company->id)
            ->where('check_in_date', '>=', now()->subDays(self::HISTORY_DAYS - 1)->toDateString())
            ->count();

        return $recentAnswers < $minRecentAnswers;
    }

    private function seedRealisticMonthAnswers(Company $company, Collection $employees, Collection $questionModels): void
    {
        $profiles = $this->employeeProfiles();
        $textComments = [
            'Слишком много созвонов подряд, сложно сфокусироваться.',
            'Хороший день, команда помогла закрыть блокер.',
            'Чувствую усталость к концу спринта.',
            'Нагрузка разумная, успел сделать всё запланированное.',
            'Нужна помощь с приоритизацией задач.',
            'Отличная синхронизация с PM сегодня.',
            'Дедлайн поджимает, но справляюсь.',
            'Сегодня было проще, чем вчера.',
            'Хочется больше асинхронной работы, меньше звонков.',
        ];

        foreach (range(self::HISTORY_DAYS - 1, 0) as $daysAgo) {
            $date = Carbon::now()->subDays($daysAgo);
            $dayProgress = 1 - ($daysAgo / max(1, self::HISTORY_DAYS - 1));

            foreach ($employees as $index => $employee) {
                $profile = $profiles[$index % count($profiles)];

                if (! $this->employeeChecksInOnDay($profile, $date, $index)) {
                    continue;
                }

                [$mood, $stress] = $this->scoresForDay($profile, $dayProgress, $daysAgo, $index);
                $supported = $mood >= 3 && $stress <= 3 && random_int(1, 100) > 15;

                SurveyAnswer::query()->create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'survey_question_id' => $questionModels[0]->id,
                    'answer' => (string) $mood,
                    'score' => (float) $mood,
                    'check_in_date' => $date->toDateString(),
                ]);

                SurveyAnswer::query()->create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'survey_question_id' => $questionModels[1]->id,
                    'answer' => (string) $stress,
                    'score' => (float) $stress,
                    'check_in_date' => $date->toDateString(),
                ]);

                SurveyAnswer::query()->create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'survey_question_id' => $questionModels[2]->id,
                    'answer' => $supported ? 'yes' : 'no',
                    'score' => $supported ? 5.0 : 1.0,
                    'check_in_date' => $date->toDateString(),
                ]);

                if ($questionModels->count() > 3 && $this->shouldAddTextComment($mood, $stress)) {
                    SurveyAnswer::query()->create([
                        'company_id' => $company->id,
                        'employee_id' => $employee->id,
                        'survey_question_id' => $questionModels[3]->id,
                        'answer' => $textComments[array_rand($textComments)],
                        'score' => null,
                        'check_in_date' => $date->toDateString(),
                    ]);
                }
            }
        }
    }

    /**
     * @return list<array{attendance: float, mood_base: float, mood_trend: float, stress_base: float, stress_trend: float, volatility: int}>
     */
    private function employeeProfiles(): array
    {
        return [
            ['attendance' => 0.92, 'mood_base' => 4.2, 'mood_trend' => 0.0, 'stress_base' => 2.0, 'stress_trend' => 0.0, 'volatility' => 1],
            ['attendance' => 0.88, 'mood_base' => 3.8, 'mood_trend' => 0.0, 'stress_base' => 2.5, 'stress_trend' => 0.0, 'volatility' => 1],
            ['attendance' => 0.75, 'mood_base' => 3.5, 'mood_trend' => -0.8, 'stress_base' => 2.8, 'stress_trend' => 0.6, 'volatility' => 2],
            ['attendance' => 0.70, 'mood_base' => 2.8, 'mood_trend' => -1.2, 'stress_base' => 3.5, 'stress_trend' => 1.0, 'volatility' => 2],
            ['attendance' => 0.85, 'mood_base' => 2.5, 'mood_trend' => -0.5, 'stress_base' => 4.0, 'stress_trend' => 0.8, 'volatility' => 3],
            ['attendance' => 0.90, 'mood_base' => 3.2, 'mood_trend' => 0.6, 'stress_base' => 3.2, 'stress_trend' => -0.5, 'volatility' => 2],
            ['attendance' => 0.65, 'mood_base' => 3.0, 'mood_trend' => 0.0, 'stress_base' => 3.0, 'stress_trend' => 0.0, 'volatility' => 3],
            ['attendance' => 0.80, 'mood_base' => 4.0, 'mood_trend' => 0.3, 'stress_base' => 2.2, 'stress_trend' => -0.2, 'volatility' => 1],
            ['attendance' => 0.78, 'mood_base' => 3.6, 'mood_trend' => -0.3, 'stress_base' => 3.0, 'stress_trend' => 0.4, 'volatility' => 2],
            ['attendance' => 0.95, 'mood_base' => 4.5, 'mood_trend' => 0.2, 'stress_base' => 1.8, 'stress_trend' => 0.0, 'volatility' => 1],
        ];
    }

    private function employeeChecksInOnDay(array $profile, Carbon $date, int $employeeIndex): bool
    {
        if ($date->isWeekend() && random_int(1, 100) > 25) {
            return false;
        }

        $roll = random_int(1, 100) / 100;

        return $roll <= $profile['attendance'];
    }

    private function scoresForDay(array $profile, float $dayProgress, int $daysAgo, int $employeeIndex): array
    {
        $mood = $profile['mood_base']
            + ($profile['mood_trend'] * $dayProgress)
            + random_int(-$profile['volatility'], $profile['volatility']);

        $stress = $profile['stress_base']
            + ($profile['stress_trend'] * $dayProgress)
            + random_int(-$profile['volatility'], $profile['volatility']);

        if ($daysAgo < 3 && $employeeIndex % 5 === 0) {
            $stress = min(5, $stress + 1);
            $mood = max(1, $mood - 1);
        }

        return [
            max(1, min(5, (int) round($mood))),
            max(1, min(5, (int) round($stress))),
        ];
    }

    private function shouldAddTextComment(int $mood, int $stress): bool
    {
        if ($mood <= 2 || $stress >= 4) {
            return random_int(1, 100) <= 22;
        }

        if ($mood >= 4 && $stress <= 2) {
            return random_int(1, 100) <= 8;
        }

        return random_int(1, 100) <= 4;
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
            'password' => Hash::make('password'),
        ]);

        $user->companies()->syncWithoutDetaching([
            $company->id => ['role' => $role->value],
        ]);
    }
}
