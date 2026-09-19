<div class="flex w-full flex-col gap-6 lg:flex-row lg:items-start">
    <aside class="w-full shrink-0 lg:sticky lg:top-6 lg:w-72">
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading>{{ $assessment->company->name }}</flux:heading>
            <flux:badge size="sm" class="mt-1" :color="match ($assessment->status) {
                'draft' => null,
                'in_progress' => 'amber',
                'completed' => 'green',
                default => null,
            }">
                {{ match ($assessment->status) {
                    'draft' => 'Nacrt',
                    'in_progress' => 'U toku',
                    'completed' => 'Završen',
                    default => $assessment->status,
                } }}
            </flux:badge>

            <flux:separator class="my-4" />

            <nav class="max-h-[60vh] space-y-3 overflow-y-auto pe-1 lg:max-h-[70vh]">
                @foreach ($this->workbooks->groupBy(fn ($w) => $w->area->name) as $areaName => $workbooksInArea)
                    <div>
                        <p class="px-2 text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                            {{ $areaName }}
                        </p>

                        <div class="mt-1 space-y-0.5">
                            @foreach ($workbooksInArea as $wb)
                                @php $p = $this->workbookProgress[$wb->id]; @endphp

                                <button
                                    type="button"
                                    wire:click="selectWorkbook('{{ $wb->key }}')"
                                    @class([
                                        'flex w-full items-center justify-between rounded-md px-2 py-1.5 text-start text-sm transition',
                                        'bg-zinc-200 font-medium dark:bg-zinc-700' => $this->currentWorkbook?->id === $wb->id,
                                        'hover:bg-zinc-100 dark:hover:bg-zinc-800' => $this->currentWorkbook?->id !== $wb->id,
                                    ])
                                >
                                    <span class="truncate">{{ $wb->name }}</span>
                                    <span @class([
                                        'shrink-0 ps-2 text-xs tabular-nums',
                                        'text-green-600 dark:text-green-400' => $p['total'] > 0 && $p['answered'] === $p['total'],
                                        'text-zinc-400' => ! ($p['total'] > 0 && $p['answered'] === $p['total']),
                                    ])>
                                        {{ $p['answered'] }}/{{ $p['total'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <flux:separator class="my-4" />

            @if ($assessment->status !== 'completed')
                <flux:button wire:click="finishAudit" variant="primary" class="w-full">
                    Završi audit
                </flux:button>
            @else
                <flux:button wire:click="finishAudit" variant="ghost" class="w-full">
                    Ponovo izračunaj rezultate
                </flux:button>
            @endif
        </div>
    </aside>

    <div class="min-w-0 flex-1 space-y-6">
        @if ($assessment->status === 'completed')
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30">
                <flux:heading>Rezultati audita</flux:heading>

                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold">{{ $this->overallScore }}</span>
                    <span class="text-zinc-500">/ 100 — Reunion Digital Score</span>
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($this->areaScores as $entry)
                        <div class="rounded-md border border-zinc-200 bg-white p-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-zinc-500">{{ $entry['area']->name }}</div>
                            <div class="text-lg font-semibold">{{ $entry['score'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($this->currentWorkbook)
            <div>
                <flux:heading size="xl">{{ $this->currentWorkbook->name }}</flux:heading>
                <flux:subheading>{{ $this->currentWorkbook->area->name }}</flux:subheading>
            </div>

            @foreach ($this->criteriaGroups as $groupLabel => $criteria)
                @php $isGroupGated = in_array($groupLabel, $this->gatedGroupLabels, true); @endphp

                <div wire:key="group-{{ $groupLabel }}" class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <flux:heading level="3">{{ $groupLabel }}</flux:heading>

                        @if ($isGroupGated)
                            <flux:badge size="sm">N/A — kanal nije relevantan</flux:badge>
                        @endif
                    </div>

                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($criteria as $criterion)
                            @php
                                $isGatedOut = ! $criterion->is_relevance_gate && $isGroupGated;
                                $isNa = $naFlags[$criterion->id] ?? false;
                                $currentAnswer = $this->answers->get($criterion->id);
                            @endphp

                            <div wire:key="criterion-{{ $criterion->id }}" class="p-4 {{ $isGatedOut ? 'opacity-60' : '' }}">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <flux:badge size="sm">{{ $criterion->external_id }}</flux:badge>
                                    <flux:badge size="sm" :color="match ($criterion->priority) {
                                        'kritican' => 'red',
                                        'vazan' => 'amber',
                                        default => null,
                                    }">
                                        {{ ['kritican' => 'Kritičan', 'vazan' => 'Važan', 'preporucen' => 'Preporučen'][$criterion->priority] ?? $criterion->priority }}
                                    </flux:badge>
                                    @if ($criterion->is_relevance_gate)
                                        <flux:badge size="sm" color="blue">Pitanje o relevantnosti</flux:badge>
                                    @endif
                                    @if ($isGatedOut)
                                        <flux:badge size="sm">Automatski N/A</flux:badge>
                                    @endif
                                </div>

                                <p class="mt-2 font-medium">{{ $criterion->text }}</p>

                                <div class="mt-3">
                                    <flux:radio.group wire:model.live="selectedOptions.{{ $criterion->id }}">
                                        @foreach ($criterion->options as $option)
                                            <flux:radio value="{{ $option->id }}" label="{{ $option->label }}" />
                                        @endforeach
                                    </flux:radio.group>
                                </div>

                                <div class="mt-3">
                                    <flux:checkbox wire:model.live="naFlags.{{ $criterion->id }}" label="N/A — nije primjenjivo" />

                                    @if ($isNa)
                                        <flux:input
                                            class="mt-2 max-w-md"
                                            placeholder="Razlog zašto nije primjenjivo"
                                            wire:model.blur="naReasonDrafts.{{ $criterion->id }}"
                                        />
                                    @endif
                                </div>

                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <flux:input type="file" wire:model="evidenceUploads.{{ $criterion->id }}" label="Dokaz (screenshot)" />

                                        <div wire:loading wire:target="evidenceUploads.{{ $criterion->id }}" class="mt-1 text-xs text-zinc-500">
                                            Otpremanje...
                                        </div>

                                        @if ($currentAnswer?->evidence_path)
                                            <flux:text class="mt-1 text-xs">
                                                <a href="{{ route('audits.evidence', $currentAnswer) }}" class="underline" target="_blank" rel="noopener">
                                                    Preuzmi trenutni dokaz
                                                </a>
                                            </flux:text>
                                        @endif
                                    </div>

                                    <flux:textarea
                                        wire:model.blur="noteDrafts.{{ $criterion->id }}"
                                        label="Napomena"
                                        rows="1"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
