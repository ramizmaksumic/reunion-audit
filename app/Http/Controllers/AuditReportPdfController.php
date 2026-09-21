<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Assessment;
use App\Services\ActionPlanService;
use App\Services\AreaPresentation;
use App\Services\ScoringService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AuditReportPdfController extends Controller
{
    public function __invoke(Assessment $assessment, ScoringService $scoring, ActionPlanService $actionPlan): Response
    {
        $assessment->load('company');

        $overallScore = $scoring->overallScore($assessment);

        $areaScores = Area::query()->orderBy('sort_order')->get()->map(fn (Area $area) => [
            'area' => $area,
            'score' => $scoring->areaScore($assessment, $area),
            'color' => AreaPresentation::color($area->key),
        ]);

        $pdf = Pdf::loadView('pdf.audit-report', [
            'assessment' => $assessment,
            'overallScore' => $overallScore,
            'status' => $scoring->scoreStatus($overallScore),
            'areaScores' => $areaScores,
            'strengths' => $actionPlan->topStrengths($assessment, 5),
            'priorities' => $actionPlan->generate($assessment)->collapse()->take(5)->values(),
            'actionPlan' => $actionPlan->generate($assessment),
        ]);

        $filename = 'RDS-'.Str::slug($assessment->company->name).'-'.$assessment->id.'.pdf';

        return $pdf->download($filename);
    }
}
