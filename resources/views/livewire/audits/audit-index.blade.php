<section class="w-full max-w-5xl">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Auditi</flux:heading>
            <flux:subheading>Svi započeti i završeni Reunion Digital Standard auditi.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('audits.create')" wire:navigate>
            Novi audit
        </flux:button>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($this->assessments as $assessment)
            @php
                $progress = $this->totalCriteriaCount > 0
                    ? (int) round(($assessment->answers_count / $this->totalCriteriaCount) * 100)
                    : 0;
            @endphp

            <a
                href="{{ route('audits.run', $assessment) }}"
                wire:navigate
                class="block rounded-lg border border-zinc-200 p-4 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/50"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <flux:heading>{{ $assessment->company->name }}</flux:heading>
                        <flux:text class="mt-0.5">
                            Pokrenut {{ $assessment->created_at->translatedFormat('d.m.Y.') }}
                            @if ($assessment->completed_at)
                                · Završen {{ $assessment->completed_at->translatedFormat('d.m.Y.') }}
                            @endif
                        </flux:text>
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:badge :color="match ($assessment->status) {
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

                        <flux:text class="tabular-nums">{{ $progress }}%</flux:text>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                <flux:text>Još nema nijednog audita.</flux:text>
            </div>
        @endforelse
    </div>
</section>
