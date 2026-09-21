<?php

namespace App\Livewire;

use App\Models\Assessment;
use App\Models\Company;
use App\Services\ScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read int $companiesCount
 * @property-read int $completedAuditsCount
 * @property-read float|null $averageScore
 * @property-read Collection<int, Assessment> $recentAssessments
 * @property-read array<int, float> $recentAssessmentScores
 */
#[Title('Dashboard')]
class Dashboard extends Component
{
    private const RECENT_LIMIT = 8;

    #[Computed]
    public function companiesCount(): int
    {
        return Company::count();
    }

    #[Computed]
    public function completedAuditsCount(): int
    {
        return Assessment::where('status', 'completed')->count();
    }

    /**
     * Average Reunion Digital Score across completed audits only — in-progress
     * ones don't have a meaningful final score yet.
     */
    #[Computed]
    public function averageScore(): ?float
    {
        $completed = Assessment::where('status', 'completed')->get();

        if ($completed->isEmpty()) {
            return null;
        }

        $scoring = app(ScoringService::class);

        return round($completed->avg(fn (Assessment $assessment) => $scoring->overallScore($assessment)), 1);
    }

    /**
     * @return Collection<int, Assessment>
     */
    #[Computed]
    public function recentAssessments(): Collection
    {
        return Assessment::query()
            ->with('company')
            ->latest('updated_at')
            ->take(self::RECENT_LIMIT)
            ->get();
    }

    /**
     * Overall score for each *completed* assessment in recentAssessments(),
     * keyed by assessment id — computed once here rather than per row in the
     * view.
     *
     * @return array<int, float>
     */
    #[Computed]
    public function recentAssessmentScores(): array
    {
        $scoring = app(ScoringService::class);

        return $this->recentAssessments
            ->where('status', 'completed')
            ->mapWithKeys(fn (Assessment $assessment) => [$assessment->id => $scoring->overallScore($assessment)])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
