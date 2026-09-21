<section class="w-full max-w-3xl">
    <flux:heading size="xl">Novi audit</flux:heading>
    <flux:subheading>Korak {{ $step === 'company' ? '1' : '2' }} od 2</flux:subheading>

    @if ($step === 'company')
        <form wire:submit="proceedToConfirm" class="mt-6 space-y-8">
            <flux:select label="Kompanija" wire:change="$event.target.value ? usingExistingCompany($event.target.value) : usingNewCompany()">
                <flux:select.option value="">+ Nova kompanija</flux:select.option>
                @foreach ($this->companies as $company)
                    <flux:select.option value="{{ $company->id }}" :selected="$companyId === $company->id">
                        {{ $company->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="space-y-4">
                <flux:heading>Osnovni podaci</flux:heading>

                <flux:input label="Naziv kompanije" wire:model="name" required />

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input label="Djelatnost" wire:model="industry" />

                    <flux:select label="B2B ili B2C" wire:model="b2bOrB2c">
                        <flux:select.option value="">—</flux:select.option>
                        <flux:select.option value="b2b">B2B</flux:select.option>
                        <flux:select.option value="b2c">B2C</flux:select.option>
                        <flux:select.option value="both">Kombinovano</flux:select.option>
                    </flux:select>

                    <flux:select label="Geografsko tržište" wire:model="marketScope">
                        <flux:select.option value="">—</flux:select.option>
                        <flux:select.option value="local">Lokalno</flux:select.option>
                        <flux:select.option value="regional">Regionalno</flux:select.option>
                        <flux:select.option value="national">Nacionalno</flux:select.option>
                        <flux:select.option value="international">Međunarodno</flux:select.option>
                    </flux:select>
                </div>

                <flux:textarea label="Napomene o poslovnom modelu" wire:model="businessModelNotes" rows="3" />
            </div>

            <div class="space-y-4">
                <flux:heading>Poslovni model</flux:heading>

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:checkbox label="Ima fizičku lokaciju za korisnike" wire:model="hasPhysicalLocation" />
                    <flux:checkbox label="Prodaje proizvode online" wire:model="sellsOnline" />
                    <flux:checkbox label="Pruža usluge online" wire:model="providesOnlineServices" />
                    <flux:checkbox label="Radi po rezervacijama ili terminima" wire:model="worksByAppointment" />
                    <flux:checkbox label="Posluje kroz više poslovnica" wire:model="hasMultipleLocations" />
                </div>
            </div>

            <div class="space-y-4">
                <flux:heading>Relevantnost digitalnih kanala</flux:heading>
                <flux:subheading>Za svaki kanal označi da li je kritičan, preporučen ili nije relevantan za ovu kompaniju. Kanale koje ne poznaješ ostavi neoznačene.</flux:subheading>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Models\CompanyChannelRelevance::CHANNELS as $key => $label)
                        <flux:select :label="$label" wire:model="channelRelevance.{{ $key }}">
                            <flux:select.option value="">—</flux:select.option>
                            @foreach (\App\Models\CompanyChannelRelevance::RELEVANCE_LEVELS as $value => $relevanceLabel)
                                <flux:select.option value="{{ $value }}">{{ $relevanceLabel }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button type="submit" variant="primary">Dalje</flux:button>
            </div>
        </form>
    @else
        <div class="mt-6 space-y-6">
            <flux:callout icon="information-circle">
                <flux:callout.heading>Pregled prije pokretanja</flux:callout.heading>
                <flux:callout.text>
                    Kreira se novi audit ({{ $companyId ? 'postojeća kompanija' : 'nova kompanija' }}) u punom obimu (svih 420 kriterija kroz 14 workbookova). Status će ostati "Nacrt" dok ne odgovoriš na prvo pitanje.
                </flux:callout.text>
            </flux:callout>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading>{{ $name }}</flux:heading>
                <dl class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                    <div class="flex justify-between sm:justify-start sm:gap-2">
                        <dt class="text-zinc-500">Djelatnost:</dt>
                        <dd>{{ $industry ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between sm:justify-start sm:gap-2">
                        <dt class="text-zinc-500">B2B/B2C:</dt>
                        <dd>{{ \App\Models\Company::B2B_OR_B2C_LABELS[$b2bOrB2c] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between sm:justify-start sm:gap-2">
                        <dt class="text-zinc-500">Tržište:</dt>
                        <dd>{{ \App\Models\Company::MARKET_SCOPE_LABELS[$marketScope] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between sm:justify-start sm:gap-2">
                        <dt class="text-zinc-500">Kanala označeno:</dt>
                        <dd>{{ count(array_filter($channelRelevance)) }} / {{ count($channelRelevance) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex justify-between gap-3">
                <flux:button wire:click="backToCompany" variant="ghost">Nazad</flux:button>
                <flux:button wire:click="startAudit" variant="primary">Kreiraj i pokreni audit</flux:button>
            </div>
        </div>
    @endif
</section>
