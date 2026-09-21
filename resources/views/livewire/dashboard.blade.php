<section class="w-full max-w-5xl space-y-6">
    <div>
        <flux:heading size="xl">Dobrodošli nazad, {{ auth()->user()->name }}</flux:heading>
        <flux:subheading>Pregled Reunion Digital Standard audita.</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Kompanija</flux:text>
            <div class="mt-1 text-3xl font-bold">{{ $this->companiesCount }}</div>
        </div>

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Završeno audita</flux:text>
            <div class="mt-1 text-3xl font-bold">{{ $this->completedAuditsCount }}</div>
        </div>

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Prosječan score</flux:text>
            <div class="mt-1 text-3xl font-bold">
                @if ($this->averageScore !== null)
                    {{ number_format($this->averageScore, 0) }}<span class="text-base font-normal text-zinc-500">/100</span>
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between gap-4">
            <flux:heading size="lg">Posljednji auditi</flux:heading>

            <flux:button variant="primary" icon="plus" size="sm" :href="route('audits.create')" wire:navigate>
                Novi audit
            </flux:button>
        </div>

        <div class="mt-4 space-y-2">
            @forelse ($this->recentAssessments as $assessment)
                <a
                    href="{{ $assessment->status === 'completed' ? route('audits.results', $assessment) : route('audits.run', $assessment) }}"
                    wire:navigate
                    class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-3 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/50"
                >
                    <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $assessment->company->name }}</flux:text>

                    <div class="flex items-center gap-3">
                        <flux:badge size="sm" :color="match ($assessment->status) {
                            'draft' => 'zinc',
                            'in_progress' => 'amber',
                            'completed' => 'green',
                            default => 'zinc',
                        }">
                            {{ match ($assessment->status) {
                                'draft' => 'Nacrt',
                                'in_progress' => 'U toku',
                                'completed' => 'Završen',
                                default => $assessment->status,
                            } }}
                        </flux:badge>

                        <flux:text class="w-10 text-right tabular-nums">
                            {{ isset($this->recentAssessmentScores[$assessment->id]) ? round($this->recentAssessmentScores[$assessment->id]) : '—' }}
                        </flux:text>

                        <flux:icon name="chevron-right" variant="micro" class="text-zinc-400" />
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <flux:text>Još nema nijednog audita.</flux:text>
                    <div class="mt-3">
                        <flux:button variant="primary" icon="plus" :href="route('audits.create')" wire:navigate>
                            Kreiraj prvi audit
                        </flux:button>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($this->recentAssessments->isNotEmpty())
            <div class="mt-3 text-center">
                <flux:link :href="route('audits.index')" wire:navigate class="text-sm">Pogledaj sve audite →</flux:link>
            </div>
        @endif
    </div>
</section>
