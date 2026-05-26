<?php

namespace App\Http\Controllers\Api\V1\Extension;

use App\DTOs\CheckIn\CheckInDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Extension\CheckInRequest;
use App\Http\Requests\Api\V1\Extension\SurveyQuestionsRequest;
use App\Http\Resources\Api\V1\SurveyQuestionResource;
use App\Services\CheckIn\CheckInService;
use Illuminate\Http\JsonResponse;

class CheckInController extends Controller
{
    public function __construct(
        private readonly CheckInService $checkInService,
    ) {}

    public function store(CheckInRequest $request): JsonResponse
    {
        $result = $this->checkInService->process(
            CheckInDto::fromArray($request->validated()),
        );

        return response()->json([
            'message' => 'Check-in submitted successfully.',
            'data' => $result,
        ], 201);
    }

    public function questions(SurveyQuestionsRequest $request): JsonResponse
    {
        $questions = $this->checkInService->getActiveSurveyQuestions(
            $request->validated('secret_key'),
        );

        return response()->json([
            'data' => SurveyQuestionResource::collection($questions),
        ]);
    }
}
