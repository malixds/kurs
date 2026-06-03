<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\LlmRecommendationSource;
use App\Http\Controllers\Controller;
use App\Models\LlmRecommendation;
use App\Services\Company\ActiveCompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LlmRecommendationsController extends Controller
{
    public function __construct(
        private readonly ActiveCompanyService $activeCompanyService,
    ) {}

    public function index(Request $request): View
    {
        $companyId = $this->activeCompanyService->resolve(Auth::user());
        abort_if($companyId === null, 403);

        $sourceFilter = $request->input('source');
        $sourceEnum = is_string($sourceFilter) && $sourceFilter !== ''
            ? LlmRecommendationSource::tryFrom($sourceFilter)
            : null;

        if (is_string($sourceFilter) && $sourceFilter !== '' && $sourceEnum === null) {
            $sourceFilter = null;
        }

        $recommendations = LlmRecommendation::query()
            ->where('company_id', $companyId)
            ->with('user:id,name')
            ->when(
                $sourceEnum !== null,
                fn ($query) => $query->where('source', $sourceEnum),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.recommendations.index', [
            'recommendations' => $recommendations,
            'activeCompany' => Auth::user()->companies()->whereKey($companyId)->first(),
            'sourceFilter' => $sourceFilter,
            'sources' => LlmRecommendationSource::cases(),
        ]);
    }

    public function show(LlmRecommendation $recommendation): View
    {
        $companyId = $this->activeCompanyService->resolve(Auth::user());
        abort_if($companyId === null, 403);
        abort_if($recommendation->company_id !== $companyId, 404);

        $recommendation->load('user:id,name');

        return view('dashboard.recommendations.show', [
            'record' => $recommendation,
            'activeCompany' => Auth::user()->companies()->whereKey($companyId)->first(),
        ]);
    }
}
