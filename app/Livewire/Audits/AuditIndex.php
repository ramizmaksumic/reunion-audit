<?php

namespace App\Livewire\Audits;

use App\Models\Assessment;
use App\Models\Criterion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read Collection<int, Assessment> $assessments
 * @property-read int $totalCriteriaCount
 */
#[Title('Auditi')]
class AuditIndex extends Component
{
    /**
     * @return Collection<int, Assessment>
     */
    #[Computed]
    public function assessments(): Collection
    {
        return Assessment::query()
            ->with('company')
            ->withCount('answers')
            ->latest('updated_at')
            ->get();
    }

    #[Computed]
    public function totalCriteriaCount(): int
    {
        return Criterion::count();
    }

    public function render(): View
    {
        return view('livewire.audits.audit-index');
    }
}
