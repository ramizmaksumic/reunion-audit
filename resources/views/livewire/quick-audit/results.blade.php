<div class="mx-auto max-w-3xl px-6 py-12">
    @php
        $result = $this->result;
        $blockIcons = [
            'pronalazljivost' => 'magnifying-glass',
            'web' => 'computer-desktop',
            'reputacija_povjerenje' => 'star',
            'drustvene_mreze' => 'share',
            'odziv_procesi' => 'chat-bubble-left-right',
            'mjerenje_rast' => 'chart-bar',
        ];
        $scoreColor = $result['score'] === null ? '#a1a1aa' : match (true) {
            $result['score'] < 40 => '#dc2626',
            $result['score'] < 60 => '#d97706',
            $result['score'] < 80 => '#2563eb',
            default => '#16a34a',
        };
    @endphp

    <div class="text-center">
        <span class="text-xs font-semibold tracking-widest text-blue-600 uppercase dark:text-blue-400">Vaš brzi rezultat</span>
        <flux:heading size="xl" class="mt-2">{{ $assessment->company->name }}</flux:heading>
    </div>

    <flux:card class="mt-8">
        <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
            <div class="relative flex size-28 shrink-0 items-center justify-center rounded-full"
                 style="background: conic-gradient({{ $scoreColor }} {{ ($result['score'] ?? 0) * 3.6 }}deg, var(--color-zinc-200) 0deg)">
                <div class="absolute inset-2 flex flex-col items-center justify-center rounded-full bg-white dark:bg-zinc-800">
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $result['score'] !== null ? number_format($result['score'], 0) : '—' }}</span>
                    <span class="text-xs text-zinc-400">/100</span>
                </div>
            </div>

            <div class="text-center sm:text-start">
                @if ($result['is_approximate'])
                    <flux:badge color="zinc">Okvirna ocjena</flux:badge>
                @endif
                <flux:text class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($result['is_approximate'])
                        Odgovorili ste na dio pitanja koji je bio primjenjiv na vaš biznis — ocjena je okvirna dok se
                        ne uradi kompletan audit.
                    @else
                        Ocjena je izračunata na osnovu vaših odgovora na primjenjiva pitanja iz svih ključnih oblasti.
                    @endif
                </flux:text>
            </div>
        </div>

        <div class="mt-6 grid gap-3 border-t border-zinc-100 pt-5 sm:grid-cols-3 dark:border-zinc-700">
            @foreach ($this->blocks as $block)
                <div class="flex items-center justify-between rounded-lg border border-zinc-100 px-3 py-2 text-sm dark:border-zinc-700">
                    <span class="inline-flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                        <flux:icon :name="$blockIcons[$block['key']] ?? 'sparkles'" variant="micro" />
                        {{ $block['name'] }}
                    </span>

                    @if ($this->blockScores->has($block['key']))
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($this->blockScores[$block['key']], 0) }}</span>
                    @else
                        <span class="text-xs text-zinc-400">Nije primjenjivo</span>
                    @endif
                </div>
            @endforeach
        </div>
    </flux:card>

    <flux:card class="mt-6">
        @if ($contactSubmitted)
            <div class="flex items-start gap-3">
                <flux:icon name="check-circle" class="mt-0.5 shrink-0 text-emerald-500" />
                <div>
                    <flux:heading size="sm">Hvala! Javit ćemo vam se uskoro.</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Vaš zahtjev je zaprimljen. Kontaktirat ćemo vas na {{ $contactEmail }} u vezi punog audita ili
                        dogovora oko sastanka.
                    </flux:text>
                </div>
            </div>
        @else
            <flux:heading size="sm">Želite detaljniju analizu?</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Brzi audit je samo početak. Kompletan {{ config('app.name') }} donosi preko 450 kriterija, detaljnu
                analizu i konkretan akcioni plan za vaš biznis. Ostavite kontakt i javit ćemo vam se — za puni audit
                ili kratak 20-minutni razgovor.
            </flux:text>

            <form wire:submit="submitContactRequest" class="mt-4 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="contactName" label="Ime i prezime" />
                    <flux:input wire:model="contactEmail" type="email" label="Email" />
                </div>
                <flux:input wire:model="contactPhone" label="Telefon (opciono)" />
                <flux:textarea wire:model="contactMessage" label="Poruka (opciono)" rows="2" />

                {{-- Honeypot --}}
                <div class="hidden" aria-hidden="true">
                    <flux:input wire:model="website" label="Web stranica" tabindex="-1" autocomplete="off" />
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:button type="submit" variant="primary" icon:trailing="arrow-right">
                        Zatražite puni audit
                    </flux:button>
                    <flux:button type="submit" variant="ghost" icon="calendar">
                        Zakažite 20-min sastanak
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:card>

    <flux:text class="mt-6 block text-center text-xs text-zinc-400">
        <flux:link :href="route('home')" wire:navigate>Nazad na početnu</flux:link>
    </flux:text>
</div>
