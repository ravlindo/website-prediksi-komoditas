<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\CommodityPrediction;
use App\Models\PredictionRun;
use App\Services\MarketPredictionEvaluationService;
use App\Services\PredictionChartService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PredictionController extends Controller
{
    public function index(Request $request, PredictionChartService $chartService): View
    {
        $activeRun = PredictionRun::where('status', 'active')->latest('activated_at')->first();
        $commodities = Commodity::where('is_active', true)
            ->with(['category', 'predictionProfile' => fn ($query) => $query->when($activeRun, fn ($q) => $q->where('prediction_run_id', $activeRun->id)), 'predictions' => fn ($query) => $query->when($activeRun, fn ($q) => $q->where('prediction_run_id', $activeRun->id))->orderBy('horizon_days')])
            ->orderBy('name')->get();
        $selected = $commodities->firstWhere('slug', $request->string('commodity')->toString())
            ?? $commodities->firstWhere(fn ($commodity) => $commodity->predictions->contains(fn ($prediction) => $prediction->horizon_days === 7 && $prediction->normalized_status === 'LAYAK'))
            ?? $commodities->firstWhere(fn ($commodity) => $commodity->predictions->isNotEmpty())
            ?? $commodities->first();
        $horizon = in_array($request->integer('horizon'), [1, 3, 7, 14, 30], true) ? $request->integer('horizon') : 7;
        $selectedPrediction = $selected?->predictions->firstWhere('horizon_days', $horizon);

        return view('predictions.index', [
            'commodities' => $commodities, 'selectedCommodity' => $selected, 'activeRun' => $activeRun,
            'selectedPrediction' => $selectedPrediction, 'selectedHorizon' => $horizon,
            'predictionChart' => $selected && $activeRun
                ? $chartService->build($selected, $activeRun, $horizon, $request->user() !== null)
                : null,
            'summary' => ['commodities' => $commodities->count(), 'modeled' => $commodities->filter(fn ($item) => $item->predictions->isNotEmpty())->count(),
                'insufficient' => $commodities->filter(fn ($item) => $item->predictionProfile?->availability_status === 'DATA TIDAK CUKUP')->count(),
                'layak' => CommodityPrediction::where('normalized_status', 'LAYAK')->when($activeRun, fn ($q) => $q->where('prediction_run_id', $activeRun->id))->count()],
        ]);
    }

    public function marketEvaluation(Request $request, MarketPredictionEvaluationService $service): View
    {
        $section = $request->string('section')->toString() === 'model' ? 'model' : 'comparison';
        $evaluation = $service->build(
            $request->string('commodity')->toString(),
            $request->string('market')->toString(),
            $request->integer('period', 30),
        );

        if ($section === 'model') {
            $evaluation['modelComparison'] = $this->paginateEvaluationRows(
                $evaluation['modelComparison'],
                $request,
            );
        } else {
            $evaluation['comparison']['table'] = $this->paginateEvaluationRows(
                $evaluation['comparison']['table'],
                $request,
            );
        }

        return view('predictions.market-evaluation', [
            'evaluation' => $evaluation,
            'evaluationSection' => $section,
        ]);
    }

    private function paginateEvaluationRows(Collection $rows, Request $request): LengthAwarePaginator
    {
        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage('page');

        return (new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page'],
        ))->appends($request->except('page'));
    }
}
