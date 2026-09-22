<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-900">
    @php
    // Illustrative mockup data for the hero/quiz preview cards below — not
    // live data. Area names/colors come straight from AreaPresentation
    // (the same source the real results dashboard uses), so the marketing
    // page never invents categories that don't exist in the product.
    $mockAreaScores = [
    ['key' => 'digitalna_prisutnost', 'label' => 'Digitalna prisutnost', 'score' => 82, 'icon' => 'globe-alt'],
    ['key' => 'korisnicko_iskustvo', 'label' => 'Korisničko iskustvo', 'score' => 74, 'icon' => 'cursor-arrow-rays'],
    ['key' => 'digitalna_efikasnost', 'label' => 'Digitalna efikasnost', 'score' => 68, 'icon' => 'cog-6-tooth'],
    ['key' => 'marketing_i_rast', 'label' => 'Marketing i rast', 'score' => 78, 'icon' => 'arrow-trending-up'],
    ];
    $heroScore = 76;
    $quizPreviewScore = 68;
    @endphp

    <x-site-header />

    <main>
        {{-- Hero --}}
        <section class="mx-auto max-w-7xl px-6 pt-16 pb-20 sm:pt-20">
            <div class="grid items-center gap-14 lg:grid-cols-2">
                <div>
                    <span class="text-xs font-semibold tracking-widest text-zinc-500 uppercase dark:text-zinc-400">
                        Digitalni audit za moderne kompanije
                    </span>

                    <h1 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                        Koliko je vaš biznis zaista spreman za digitalni rast?
                    </h1>

                    <flux:text class="mt-5 max-w-xl text-lg text-zinc-500 dark:text-zinc-400">
                        {{ config('app.name') }} analizira vašu digitalnu prisutnost, vidljivost, korisničko iskustvo i
                        marketing te vam daje jasne preporuke za unapređenje.
                    </flux:text>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <flux:button variant="primary" icon:trailing="arrow-right" :href="route('quick-audit.start')" wire:navigate>
                            Započni besplatno mjerenje
                        </flux:button>

                        <flux:button variant="ghost" icon="calendar" href="mailto:{{ config('mail.from.address') }}">
                            Zakažite 20-min sastanak
                        </flux:button>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-500 dark:text-zinc-400">
                        @foreach (['Brzo i jednostavno', 'Rezultati odmah', 'Bez obaveze'] as $point)
                        <span class="inline-flex items-center gap-1.5">
                            <flux:icon name="check-circle" variant="micro" class="text-emerald-500" />
                            {{ $point }}
                        </span>
                        @endforeach
                    </div>
                </div>

                {{-- Illustrative results mockup --}}
                <flux:card class="relative">
                    <div class="flex items-start justify-between gap-3">
                        <flux:heading size="lg">Pregled rezultata</flux:heading>
                        <flux:badge size="sm">{{ now()->format('d.m.Y.') }}</flux:badge>
                    </div>
                    <flux:text class="mt-0.5 text-sm text-zinc-400">Vaša kompanija d.o.o.</flux:text>

                    <div class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="relative flex size-28 shrink-0 items-center justify-center rounded-full"
                            style="background: conic-gradient(#2563eb {{ $heroScore * 3.6 }}deg, var(--color-zinc-200) 0deg)">
                            <div class="absolute inset-2 flex flex-col items-center justify-center rounded-full bg-white dark:bg-zinc-800">
                                <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $heroScore }}</span>
                                <span class="text-xs text-zinc-400">/100</span>
                            </div>
                        </div>

                        <div>
                            <flux:heading size="sm" class="text-blue-600 dark:text-blue-400">Dobar rezultat</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                Imate solidnu digitalnu osnovu. Postoje značajne prilike za unapređenje koje mogu
                                donijeti veći rast i efikasnost.
                            </flux:text>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-zinc-100 pt-5 dark:border-zinc-700">
                        <div class="mb-2 flex items-center justify-between">
                            <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Napredak kroz vrijeme</flux:text>
                            <flux:badge size="sm" color="lime">+28%</flux:badge>
                        </div>
                        <svg viewBox="0 0 240 48" class="h-12 w-full" preserveAspectRatio="none">
                            <polyline fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                points="0,40 40,34 80,36 120,22 160,18 200,8 240,4" />
                        </svg>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3 border-t border-zinc-100 pt-5 sm:grid-cols-4 dark:border-zinc-700">
                        @foreach ($mockAreaScores as $area)
                        <div class="text-center">
                            <flux:icon :name="$area['icon']" variant="micro" style="color: {{ \App\Services\AreaPresentation::color($area['key']) }}" class="mx-auto" />
                            <div class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">{{ $area['score'] }}</div>
                            <div class="text-[11px] leading-tight text-zinc-400">{{ $area['label'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </flux:card>
            </div>
        </section>

        {{-- Feature highlights --}}
        <section class="border-y border-zinc-100 bg-zinc-50 py-16 dark:border-zinc-800 dark:bg-zinc-900/50">
            <div class="mx-auto grid max-w-7xl gap-10 px-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                ['icon' => 'shield-check', 'title' => 'Objektivna procjena', 'text' => 'Mjerimo ono što je važno, bez pretpostavki.'],
                ['icon' => 'light-bulb', 'title' => 'Jasne preporuke', 'text' => 'Dobijate konkretne korake za unapređenje.'],
                ['icon' => 'clock', 'title' => 'Ušteda vremena', 'text' => 'Sve na jednom mjestu, jednostavno i pregledno.'],
                ['icon' => 'arrow-trending-up', 'title' => 'Stvarni rezultati', 'text' => 'Bolja vidljivost, više kupaca i efikasniji procesi.'],
                ] as $feature)
                <div>
                    <div class="inline-flex size-11 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-500/10">
                        <flux:icon :name="$feature['icon']" variant="outline" class="size-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <flux:heading size="sm" class="mt-4">{{ $feature['title'] }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $feature['text'] }}</flux:text>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Quick-audit teaser --}}
        <section id="kako-funkcionise" class="mx-auto max-w-7xl px-6 py-20">
            <div class="grid items-center gap-14 lg:grid-cols-2">
                <div>
                    <span class="text-xs font-semibold tracking-widest text-blue-600 uppercase dark:text-blue-400">
                        Brzo i jednostavno
                    </span>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Besplatno izmjerite svoju digitalnu spremnost
                    </h2>
                    <flux:text class="mt-4 max-w-lg text-zinc-500 dark:text-zinc-400">
                        Odgovorite na 25 pažljivo odabranih pitanja iz svih ključnih oblasti i odmah saznajte gdje se
                        trenutno nalazite.
                    </flux:text>

                    <div class="mt-7 flex flex-wrap items-center gap-4">
                        <flux:button variant="primary" icon:trailing="arrow-right" :href="route('quick-audit.start')" wire:navigate>
                            Započni brzo mjerenje
                        </flux:button>
                        <flux:text class="text-sm text-zinc-400">Potrebno samo 3–5 minuta</flux:text>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <flux:card>
                        <div class="mb-3 flex items-center justify-between text-sm">
                            <flux:text class="font-medium">Pitanje 12 od 25</flux:text>
                            <flux:text class="text-zinc-400">48%</flux:text>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                            <div class="h-full rounded-full bg-blue-600" style="width: 48%"></div>
                        </div>

                        <flux:text class="mt-5 block text-sm font-medium text-zinc-800 dark:text-zinc-100">
                            Da li vaša web stranica ima jasne pozive na akciju (CTA) na ključnim stranicama?
                        </flux:text>

                        <div class="mt-4 space-y-2 text-sm">
                            @foreach (['Da, jasno su istaknuti' => true, 'Djelimično' => false, 'Ne' => false, 'Nisam siguran/a' => false] as $option => $selected)
                            <div class="flex items-center gap-2.5 rounded-lg border {{ $selected ? 'border-blue-600 bg-blue-50 dark:bg-blue-500/10' : 'border-zinc-200 dark:border-zinc-700' }} px-3 py-2">
                                <span class="flex size-4 shrink-0 items-center justify-center rounded-full border-2 {{ $selected ? 'border-blue-600' : 'border-zinc-300 dark:border-zinc-600' }}">
                                    @if ($selected)
                                    <span class="size-2 rounded-full bg-blue-600"></span>
                                    @endif
                                </span>
                                {{ $option }}
                            </div>
                            @endforeach
                        </div>
                    </flux:card>

                    <flux:card>
                        <span class="text-xs font-semibold tracking-widest text-zinc-400 uppercase">Vaš brzi rezultat</span>

                        <div class="mt-3 flex items-center gap-4">
                            <div class="relative flex size-16 shrink-0 items-center justify-center rounded-full"
                                style="background: conic-gradient(#16a34a {{ $quizPreviewScore * 3.6 }}deg, var(--color-zinc-200) 0deg)">
                                <div class="absolute inset-1.5 flex items-center justify-center rounded-full bg-white dark:bg-zinc-800">
                                    <span class="text-base font-bold text-zinc-900 dark:text-white">{{ $quizPreviewScore }}</span>
                                </div>
                            </div>
                            <div>
                                <flux:heading size="sm" class="text-emerald-600 dark:text-emerald-400">Dobar početak!</flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    Imate solidne temelje, ali postoji značajan prostor za unapređenje.
                                </flux:text>
                            </div>
                        </div>

                        <div class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-zinc-700/40 dark:text-zinc-300">
                            <flux:icon name="arrow-trending-up" variant="micro" class="mb-1 text-emerald-500" />
                            Želite detaljan uvid i konkretan plan? Pokrenite kompletan audit ili zakažite besplatan
                            20-minutni sastanak s nama.
                        </div>
                    </flux:card>
                </div>
            </div>
        </section>

        {{-- Full audit upsell --}}
        <section id="sta-dobijate" class="mx-auto max-w-7xl px-6 py-20">
            <div class="grid items-center gap-14 lg:grid-cols-2">
                <div>
                    <span class="text-xs font-semibold tracking-widest text-zinc-500 uppercase dark:text-zinc-400">
                        Kompletan uvid
                    </span>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Od brzog uvida do detaljnog plana rasta
                    </h2>
                    <flux:text class="mt-4 max-w-lg text-zinc-500 dark:text-zinc-400">
                        Brzi audit je samo početak. Kompletan {{ config('app.name') }} pruža detaljnu analizu kroz 4
                        oblasti i 450+ kriterija, uz konkretne preporuke i akcioni plan.
                    </flux:text>

                    <div class="mt-7 flex flex-wrap gap-3">
                        <flux:button variant="primary" icon:trailing="arrow-right" href="mailto:{{ config('mail.from.address') }}">
                            Saznajte više o punom auditu
                        </flux:button>
                        <flux:button variant="ghost" icon="calendar" href="mailto:{{ config('mail.from.address') }}">
                            Zakažite 20-min sastanak
                        </flux:button>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <ul class="space-y-3">
                        @foreach ([
                        'Detaljna analiza po svim oblastima',
                        'Personalizovane preporuke',
                        'Prioriteti i akcioni plan',
                        'PDF izvještaj spreman za implementaciju',
                        'Podrška našeg tima',
                        ] as $item)
                        <li class="flex items-start gap-2.5 text-sm text-zinc-600 dark:text-zinc-300">
                            <flux:icon name="check-circle" variant="micro" class="mt-0.5 shrink-0 text-emerald-500" />
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>

                    <div class="flex min-h-40 flex-col justify-end rounded-xl bg-gradient-to-br from-zinc-900 to-blue-950 p-5 text-white">
                        <flux:icon name="rocket-launch" variant="outline" class="mb-3 size-6 text-blue-300" />
                        <p class="text-lg leading-snug font-semibold">Bolja digitalna budućnost počinje danas.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Industries --}}
        <section id="za-koga-je" class="border-t border-zinc-100 bg-zinc-50 py-16 dark:border-zinc-800 dark:bg-zinc-900/50">
            <div class="mx-auto max-w-7xl px-6">
                <span class="text-xs font-semibold tracking-widest text-zinc-500 uppercase dark:text-zinc-400">
                    Povjerenje nam ukazuju
                </span>
                <flux:text class="mt-2 block max-w-lg text-zinc-500 dark:text-zinc-400">
                    Kompanije iz različitih industrija koje žele rasti uz digitalne tehnologije.
                </flux:text>

                <div class="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ([
                    ['icon' => 'home', 'label' => 'Turizam'],
                    ['icon' => 'cake', 'label' => 'Ugostiteljstvo'],
                    ['icon' => 'shopping-bag', 'label' => 'Trgovina'],
                    ['icon' => 'cube', 'label' => 'Proizvodnja'],
                    ['icon' => 'heart', 'label' => 'Zdravstvo'],
                    ['icon' => 'building-office', 'label' => 'Usluge'],
                    ] as $industry)
                    <div class="flex flex-col items-center gap-2 text-center">
                        <flux:icon :name="$industry['icon']" variant="outline" class="size-6 text-zinc-400" />
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $industry['label'] }}</flux:text>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Closing CTA --}}
        <section class="mx-auto max-w-7xl px-6 py-16">
            <div class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-zinc-900 p-8 sm:flex-row sm:items-center sm:p-10">
                <div>
                    <flux:heading size="xl" class="text-white">Spremni za sljedeći korak?</flux:heading>
                    <flux:text class="mt-2 max-w-md text-zinc-400">
                        Otkrijte kako vaš biznis može više uz pravu digitalnu strategiju.
                    </flux:text>
                </div>

                <div class="flex shrink-0 flex-wrap gap-3">
                    <flux:button variant="primary" icon:trailing="arrow-right" :href="route('quick-audit.start')" wire:navigate>
                        Započni audit
                    </flux:button>
                    <flux:button variant="ghost" icon="calendar" class="text-white hover:bg-white/10" href="mailto:{{ config('mail.from.address') }}">
                        Zakažite 20-min sastanak
                    </flux:button>
                </div>
            </div>
        </section>
    </main>

    <x-site-footer />

    @fluxScripts
</body>

</html>