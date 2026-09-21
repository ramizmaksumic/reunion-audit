<section class="w-full max-w-6xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $assessment->company->name }}</flux:heading>
            <flux:subheading>
                Reunion Digital Standard — rezultati audita
                @if ($assessment->status === 'completed')
                    · Završen {{ $assessment->completed_at?->translatedFormat('d.m.Y.') }}
                @else
                    · U toku od {{ $assessment->started_at?->translatedFormat('d.m.Y.') ?? $assessment->created_at->translatedFormat('d.m.Y.') }}
                @endif
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @if ($assessment->status === 'completed')
                <flux:button variant="primary" icon="arrow-down-tray" :href="route('audits.results.pdf', $assessment)">
                    Izvezi izvještaj
                </flux:button>
            @endif

            <flux:button variant="ghost" icon="arrow-left" :href="route('audits.run', $assessment)" wire:navigate>
                Nazad na audit
            </flux:button>
        </div>
    </div>

    @if ($assessment->status !== 'completed')
        <flux:callout icon="information-circle" color="amber">
            <flux:callout.heading>Audit je još u toku</flux:callout.heading>
            <flux:callout.text>
                Rezultati ispod su preliminarni i računaju se iz trenutno unesenih odgovora. Kriteriji bez odgovora se
                tretiraju kao 0 bodova dok se ne popune.
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                Ukupni digitalni score
            </flux:heading>

            <div class="mt-4 flex flex-col items-center gap-6 sm:flex-row">
                <div class="relative shrink-0" style="width: 176px; height: 176px;" x-data="scoreRing({{ $this->overallScore }}, '{{ $this->status['color'] }}')">
                    <canvas x-ref="canvas" width="176" height="176"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-bold">{{ number_format($this->overallScore, 0) }}</span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">/100</span>
                    </div>
                </div>

                <div>
                    <flux:heading size="lg" :style="'color: '.$this->status['color']">
                        {{ Str::upper($this->status['label']) }}
                    </flux:heading>
                    <flux:text class="mt-1">{{ $this->status['description'] }}</flux:text>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                Raspodjela rezultata po oblastima
            </flux:heading>

            <div class="mt-4" style="height: 220px;" x-data="scoreBarChart(
                    @js($this->areaScores->pluck('area.name')),
                    @js($this->areaScores->pluck('score')),
                    @js($this->areaScores->pluck('color'))
                )">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <div>
        <flux:heading size="lg">Rezultati po oblastima</flux:heading>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->areaScores as $entry)
                <div class="flex flex-col rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading class="text-sm">{{ $entry['area']->name }}</flux:heading>

                    <div class="relative mx-auto my-3" style="width: 112px; height: 112px;" x-data="scoreRing({{ $entry['score'] }}, '{{ $entry['color'] }}')">
                        <canvas x-ref="canvas" width="112" height="112"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold">{{ number_format($entry['score'], 0) }}</span>
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400">/100</span>
                        </div>
                    </div>

                    @if ($entry['description'])
                        <flux:text class="grow text-center text-sm">{{ $entry['description'] }}</flux:text>
                    @endif

                    <flux:link :href="route('audits.results.area', [$assessment, $entry['area']])" wire:navigate class="mt-3 text-center text-sm">
                        Pogledaj detalje →
                    </flux:link>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30">
            <flux:heading class="flex items-center gap-2 text-sm">
                <flux:icon name="check-circle" variant="micro" class="text-green-600" />
                Glavne snage
            </flux:heading>

            @if ($this->strengths->isEmpty())
                <flux:text class="mt-3 text-sm">Nema još dovoljno odgovora da se izdvoje snage.</flux:text>
            @else
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($this->strengths as $row)
                        <li class="flex gap-2">
                            <span class="text-green-600">✓</span>
                            <span>{{ $row['criterion']->text }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
            <flux:heading class="flex items-center gap-2 text-sm">
                <flux:icon name="exclamation-triangle" variant="micro" class="text-amber-600" />
                Prioriteti za unapređenje
            </flux:heading>

            @if ($this->priorities->isEmpty())
                <flux:text class="mt-3 text-sm">Nema još dovoljno odgovora da se izdvoje prioriteti.</flux:text>
            @else
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($this->priorities as $row)
                        <li class="flex gap-2">
                            <span class="text-amber-600">!</span>
                            <span>{{ $row['criterion']->text }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
            <flux:heading class="flex items-center gap-2 text-sm">
                <flux:icon name="arrow-trending-up" variant="micro" class="text-blue-600" />
                Ukupni potencijal rasta
            </flux:heading>

            <div class="mt-2 text-3xl font-bold text-blue-700 dark:text-blue-400">
                +{{ $this->growthPotential }}
            </div>
            <flux:text class="mt-1 text-sm">
                Poena do maksimalnog Reunion Digital Score-a, na osnovu trenutnih odgovora.
            </flux:text>
        </div>
    </div>
</section>
