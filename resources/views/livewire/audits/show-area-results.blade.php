<section class="w-full max-w-5xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:text class="text-sm">
                <flux:link :href="route('audits.results', $assessment)" wire:navigate>Rezultati audita</flux:link>
                / {{ $area->name }}
            </flux:text>
            <flux:heading size="xl">{{ $area->name }}</flux:heading>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-right">
                <div class="text-3xl font-bold">{{ number_format($this->areaScore, 0) }}<span class="text-base font-normal text-zinc-500">/100</span></div>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('audits.results', $assessment)" wire:navigate>
                Nazad na pregled
            </flux:button>
        </div>
    </div>

    @foreach ($this->workbooks as $entry)
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                <flux:heading level="2">{{ $entry['workbook']->name }}</flux:heading>
                <flux:badge>{{ number_format($entry['score'], 0) }}/100</flux:badge>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($entry['breakdown'] as $groupLabel => $rows)
                    <div class="p-4">
                        <flux:heading level="3" class="text-sm">{{ $groupLabel }}</flux:heading>

                        <div class="mt-3 space-y-3">
                            @foreach ($rows as $row)
                                @php
                                    $criterion = $row['criterion'];
                                    $answer = $row['answer'];
                                    $selectedLabel = $answer?->selected_option_id
                                        ? $criterion->options->firstWhere('id', $answer->selected_option_id)?->label
                                        : null;
                                @endphp

                                <div class="flex items-start justify-between gap-4 rounded-md border border-zinc-100 p-3 dark:border-zinc-800">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <flux:badge size="sm">{{ $criterion->external_id }}</flux:badge>
                                            @if (! $row['applicable'])
                                                <flux:badge size="sm">N/A</flux:badge>
                                            @endif
                                        </div>

                                        <p class="mt-1 text-sm font-medium">{{ $criterion->text }}</p>

                                        @if ($selectedLabel)
                                            <flux:text class="mt-1 text-sm">Odgovor: {{ $selectedLabel }}</flux:text>
                                        @elseif (! $row['applicable'] && $answer?->na_reason)
                                            <flux:text class="mt-1 text-sm text-zinc-500">Razlog N/A: {{ $answer->na_reason }}</flux:text>
                                        @elseif (! $selectedLabel)
                                            <flux:text class="mt-1 text-sm text-zinc-400">Bez odgovora</flux:text>
                                        @endif

                                        @if ($answer?->notes)
                                            <flux:text class="mt-1 block text-sm text-zinc-500">Napomena: {{ $answer->notes }}</flux:text>
                                        @endif

                                        @if ($answer?->evidence_path)
                                            <flux:link :href="route('audits.evidence', $answer)" target="_blank" class="mt-1 block text-sm">
                                                Preuzmi dokaz
                                            </flux:link>
                                        @endif
                                    </div>

                                    <div class="shrink-0 text-right text-sm tabular-nums text-zinc-500">
                                        @if ($row['applicable'])
                                            {{ number_format($row['earned'], 2) }} / {{ number_format($row['max'], 2) }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</section>
