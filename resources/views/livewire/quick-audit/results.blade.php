<div class="mx-auto max-w-6xl px-6 py-12">
    @php
        $result = $this->result;
        $status = $this->status;
        $scoredBlocks = $this->blocks;
        $chartBlocks = collect($scoredBlocks)->filter(fn ($b) => $this->blockScores->has($b['key']))->values();
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="text-xs font-semibold tracking-widest text-blue-600 uppercase dark:text-blue-400">Vaš brzi rezultat</span>
            <flux:heading size="xl" class="mt-1">{{ $assessment->company->name }}</flux:heading>
        </div>
        <flux:link :href="route('home')" wire:navigate class="text-sm">Nazad na početnu</flux:link>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:card>
                    <flux:heading class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                        Ukupna ocjena
                    </flux:heading>

                    <div class="mt-4 flex flex-col items-center gap-6 sm:flex-row">
                        <div class="relative shrink-0" style="width: 152px; height: 152px;"
                             x-data="scoreRing({{ $result['score'] ?? 0 }}, '{{ $status['color'] }}')">
                            <canvas x-ref="canvas" width="152" height="152"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-3xl font-bold">{{ $result['score'] !== null ? number_format($result['score'], 0) : '—' }}</span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">/100</span>
                            </div>
                        </div>

                        <div>
                            @if ($result['is_approximate'])
                                <flux:badge size="sm" color="zinc" class="mb-1.5">Okvirna ocjena</flux:badge>
                            @endif
                            <flux:heading size="lg" :style="'color: '.$status['color']">
                                {{ Str::upper($status['label']) }}
                            </flux:heading>
                            <flux:text class="mt-1 text-sm">{{ $status['description'] }}</flux:text>
                        </div>
                    </div>
                </flux:card>

                <flux:card>
                    <flux:heading class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                        Raspodjela po oblastima
                    </flux:heading>

                    @if ($chartBlocks->isNotEmpty())
                        <div class="mt-4" style="height: 200px;" x-data="scoreBarChart(
                                @js($chartBlocks->pluck('name')),
                                @js($chartBlocks->map(fn ($b) => $this->blockScores[$b['key']])),
                                @js($chartBlocks->map(fn ($b) => \App\Services\QuickAuditBlockPresentation::color($b['key'])))
                            )">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    @else
                        <flux:text class="mt-4 text-sm text-zinc-400">Nema dovoljno odgovora za grafikon.</flux:text>
                    @endif
                </flux:card>
            </div>

            <div>
                <flux:heading size="lg">Rezultati po oblastima</flux:heading>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($scoredBlocks as $block)
                        <div class="flex flex-col items-center rounded-lg border border-zinc-200 p-4 text-center dark:border-zinc-700">
                            <flux:icon :name="\App\Services\QuickAuditBlockPresentation::icon($block['key'])" variant="micro" class="text-zinc-400" />
                            <flux:heading class="mt-1 text-sm">{{ $block['name'] }}</flux:heading>

                            @if ($this->blockScores->has($block['key']))
                                <div class="relative my-3" style="width: 96px; height: 96px;"
                                     x-data="scoreRing({{ $this->blockScores[$block['key']] }}, '{{ \App\Services\QuickAuditBlockPresentation::color($block['key']) }}')">
                                    <canvas x-ref="canvas" width="96" height="96"></canvas>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-xl font-bold">{{ number_format($this->blockScores[$block['key']], 0) }}</span>
                                        <span class="text-[10px] text-zinc-500 dark:text-zinc-400">/100</span>
                                    </div>
                                </div>
                            @else
                                <div class="my-3 flex items-center justify-center rounded-full border border-dashed border-zinc-300 text-xs text-zinc-400 dark:border-zinc-600"
                                     style="width: 96px; height: 96px;">
                                    Nije primjenjivo
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="lg:sticky lg:top-6 lg:col-span-1 lg:self-start">
            <flux:card>
                @if ($contactSubmitted)
                    <div class="flex items-start gap-3">
                        <flux:icon name="check-circle" class="mt-0.5 shrink-0 text-emerald-500" />
                        <div>
                            <flux:heading size="sm">Hvala! Javit ćemo vam se uskoro.</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                Vaš zahtjev je zaprimljen. Kontaktirat ćemo vas na {{ $contactEmail }} u vezi punog
                                audita ili dogovora oko sastanka.
                            </flux:text>
                        </div>
                    </div>
                @else
                    <flux:heading size="sm">Želite detaljniju analizu?</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Brzi audit je samo početak. Kompletan {{ config('app.name') }} donosi preko 450 kriterija,
                        detaljnu analizu i konkretan akcioni plan za vaš biznis.
                    </flux:text>

                    <ul class="mt-4 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                        @foreach ([
                            'Detaljna analiza po svim oblastima',
                            'Personalizovane preporuke',
                            'Prioriteti i akcioni plan',
                            'PDF izvještaj spreman za implementaciju',
                        ] as $item)
                            <li class="flex items-start gap-2">
                                <flux:icon name="check-circle" variant="micro" class="mt-0.5 shrink-0 text-emerald-500" />
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>

                    <form wire:submit="submitContactRequest" class="mt-5 space-y-4">
                        <flux:input wire:model="contactName" label="Ime i prezime" />
                        <flux:input wire:model="contactEmail" type="email" label="Email" />
                        <flux:input wire:model="contactPhone" label="Telefon (opciono)" />
                        <flux:textarea wire:model="contactMessage" label="Poruka (opciono)" rows="2" />

                        {{-- Honeypot --}}
                        <div class="hidden" aria-hidden="true">
                            <flux:input wire:model="website" label="Web stranica" tabindex="-1" autocomplete="off" />
                        </div>

                        <div class="space-y-2">
                            <flux:button type="submit" variant="primary" icon:trailing="arrow-right" class="w-full">
                                Zatražite puni audit
                            </flux:button>
                            <flux:button type="submit" variant="ghost" icon="calendar" class="w-full">
                                Zakažite 20-min sastanak
                            </flux:button>
                        </div>
                    </form>
                @endif
            </flux:card>
        </div>
    </div>
</div>
