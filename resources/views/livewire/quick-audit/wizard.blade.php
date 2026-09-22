<div class="mx-auto max-w-3xl px-6 py-12">
    @if ($step === 'intro')
        <div class="text-center">
            <span class="text-xs font-semibold tracking-widest text-blue-600 uppercase dark:text-blue-400">Brzi audit</span>
            <flux:heading size="xl" class="mt-2">Provjerite gdje je vaš biznis danas</flux:heading>
            <flux:text class="mx-auto mt-2 max-w-lg text-zinc-500 dark:text-zinc-400">
                Odgovorite na nekoliko kratkih pitanja i odmah dobijte trenutni rezultat i najvažnije preporuke.
            </flux:text>
        </div>

        <flux:card class="mt-8">
            <form wire:submit="startQuestions" class="space-y-5">
                <flux:input wire:model="companyName" label="Naziv firme" placeholder="npr. Pekara Zlatno Zrno" />
                <flux:input wire:model="website" label="Web adresa (opciono)" placeholder="https://..." />

                {{-- Honeypot: visually hidden, never filled by a real visitor. --}}
                <div class="hidden" aria-hidden="true">
                    <flux:input wire:model="companyPhone" label="Telefon" tabindex="-1" autocomplete="off" />
                </div>

                <flux:separator />

                <div>
                    <flux:heading size="sm">Vaši digitalni kanali</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Za svaki kanal recite da li ga imate, a ako ne — koliko vam je bitan.
                    </flux:text>
                </div>

                @foreach ([
                    'web' => 'Web stranica',
                    'gbp' => 'Google Business profil',
                    'social' => 'Facebook / Instagram',
                ] as $channelKey => $channelLabel)
                    <flux:select wire:model="channelRelevance.{{ $channelKey }}" label="{{ $channelLabel }}">
                        <flux:select.option value="">Odaberite...</flux:select.option>
                        <flux:select.option value="has">Da, imamo</flux:select.option>
                        <flux:select.option value="critical">Ne, ali nam je kritično važno</flux:select.option>
                        <flux:select.option value="recommended">Ne, ali bi bilo preporučeno</flux:select.option>
                        <flux:select.option value="not_relevant">Ne, i nije nam relevantno</flux:select.option>
                    </flux:select>
                @endforeach

                <flux:button type="submit" variant="primary" icon:trailing="arrow-right" class="w-full">
                    Nastavi na pitanja
                </flux:button>
            </form>
        </flux:card>
    @else
        @php
            $total = $this->answerableCriteria->count();
            $current = $this->currentCriterion;
            $percent = $total > 0 ? (int) round((($this->currentIndex + 1) / $total) * 100) : 0;
        @endphp

        @if ($current)
            <div>
                <div class="flex items-center justify-between text-sm">
                    <flux:text class="font-medium">Pitanje {{ $this->currentIndex + 1 }} od {{ $total }}</flux:text>
                    <flux:text class="text-zinc-400">{{ $percent }}%</flux:text>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                    <div class="h-full rounded-full bg-blue-600 transition-all" style="width: {{ $percent }}%"></div>
                </div>
            </div>

            <flux:card class="mt-6" wire:key="criterion-{{ $current->id }}">
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <flux:icon :name="\App\Services\QuickAuditBlockPresentation::icon($current->quick_block)" variant="micro" />
                    {{ collect($this->blocks)->firstWhere('key', $current->quick_block)['name'] ?? $current->quick_block }}
                </div>

                <flux:heading size="lg" class="mt-2">
                    {{ $current->quick_question ?: $current->text }}
                </flux:heading>

                @if ($current->quick_note)
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $current->quick_note }}</flux:text>
                @endif

                <div class="mt-5">
                    <flux:radio.group wire:model.live="answers.{{ $current->id }}">
                        @foreach ($current->options as $index => $option)
                            <flux:radio value="{{ $option->id }}" label="{{ $current->quick_option_labels[$index] ?? $option->label }}" />
                        @endforeach
                    </flux:radio.group>
                </div>

                @error('answers')
                    <flux:text class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                <div class="mt-6 flex items-center justify-between">
                    <flux:button variant="ghost" icon="arrow-left" wire:click="{{ $this->currentIndex === 0 ? 'backToIntro' : 'previous' }}">
                        Prethodno
                    </flux:button>

                    @if ($this->currentIndex < $total - 1)
                        <flux:button variant="primary" icon:trailing="arrow-right" wire:click="next" :disabled="! isset($answers[$current->id])">
                            Sljedeće pitanje
                        </flux:button>
                    @else
                        <flux:button variant="primary" icon:trailing="arrow-right" wire:click="finish">
                            Pogledaj rezultat
                        </flux:button>
                    @endif
                </div>
            </flux:card>

            <flux:text class="mt-4 block text-center text-xs text-zinc-400">
                Svi odgovori se koriste isključivo za generisanje vašeg rezultata.
            </flux:text>
        @endif
    @endif
</div>
