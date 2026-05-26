<?php

namespace App\Services\CheckIn;

use App\DTOs\CheckIn\CheckInDto;
use App\Jobs\ProcessCheckInAnalyticsJob;
use App\Models\Company;
use App\Models\Employee;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\SurveyAnswerRepositoryInterface;
use App\Repositories\Contracts\SurveyRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CheckInService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly DepartmentRepositoryInterface $departmentRepository,
        private readonly SurveyRepositoryInterface $surveyRepository,
        private readonly SurveyAnswerRepositoryInterface $surveyAnswerRepository,
    ) {}

    public function process(CheckInDto $dto): array
    {
        $company = $this->resolveCompany($dto->secretKey);
        $employee = $this->resolveEmployee($company, $dto);
        $this->validateAnswers($company, $dto);

        $checkInDate = now()->toDateString();

        $answers = $this->surveyAnswerRepository->storeAnswers(
            $company->id,
            $employee,
            $dto->answers,
            $checkInDate,
        );

        ProcessCheckInAnalyticsJob::dispatch($company->id, $employee->id);

        return [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'check_in_date' => $checkInDate,
            'answers_stored' => $answers->count(),
        ];
    }

    public function getActiveSurveyQuestions(string $secretKey): Collection
    {
        $company = $this->resolveCompany($secretKey);

        return $this->surveyRepository->getActiveQuestionsForCompany($company->id);
    }

    private function resolveCompany(string $secretKey): Company
    {
        $company = $this->companyRepository->findBySecretKey($secretKey);

        if ($company === null) {
            throw ValidationException::withMessages([
                'secret_key' => ['Invalid company secret key.'],
            ]);
        }

        return $company;
    }

    private function resolveEmployee(Company $company, CheckInDto $dto): Employee
    {
        $departmentId = null;

        if ($dto->employee->departmentExternalId !== null) {
            $department = $this->departmentRepository->findByExternalId(
                $company->id,
                $dto->employee->departmentExternalId,
            );
            $departmentId = $department?->id;
        }

        return $this->employeeRepository->upsertFromCheckIn(
            $company->id,
            $dto->employee->externalId,
            $dto->employee->email,
            $dto->employee->name,
            $departmentId,
        );
    }

    private function validateAnswers(Company $company, CheckInDto $dto): void
    {
        $activeQuestions = $this->surveyRepository
            ->getActiveQuestionsForCompany($company->id)
            ->keyBy('id');

        if ($activeQuestions->isEmpty()) {
            throw new InvalidArgumentException('No active survey configured.');
        }

        $errors = [];

        foreach ($dto->answers as $index => $answer) {
            if (! $activeQuestions->has($answer->questionId)) {
                $errors["answers.{$index}.question_id"] = ['Invalid or inactive question.'];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
