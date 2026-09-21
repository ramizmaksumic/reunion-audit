<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <header class="border-b border-zinc-200 dark:border-zinc-700">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                <img src="{{ asset('images/reunion-logo.png') }}" alt="{{ config('app.name') }}" class="w-48" />

            </a>

            @if (Route::has('login'))
            <nav class="flex items-center gap-2">
                @auth
                <flux:button variant="primary" :href="route('dashboard')" wire:navigate>
                    Dashboard
                </flux:button>
                @else
                <flux:button variant="ghost" :href="route('login')" wire:navigate>
                    Prijava
                </flux:button>

                @if (Route::has('register'))
                <flux:button variant="primary" :href="route('register')" wire:navigate>
                    Registracija
                </flux:button>
                @endif
                @endauth
            </nav>
            @endif
        </div>
    </header>

    <main class="mx-auto flex max-w-3xl flex-col items-center px-6 py-24 text-center">
        <img src="{{ asset('images/reunion-icon.png') }}" alt="" class="mb-6 h-14 w-14" />

        <flux:heading size="xl" class="text-3xl sm:text-4xl">{{ config('app.name') }}</flux:heading>

        <flux:text class="mt-4 max-w-xl text-lg text-zinc-500 dark:text-zinc-400">
            Standardizovan audit alat koji objektivno mjeri digitalnu zrelost biznisa — web, Google Business,
            SEO, reputaciju, brend, korisničko iskustvo i marketing — kroz jasno bodovane kriterije, ne
            subjektivnu procjenu.
        </flux:text>

        <div class="mt-8">
            @auth
            <flux:button variant="primary" :href="route('dashboard')" wire:navigate>
                Idi na Dashboard
            </flux:button>
            @else
            <flux:button variant="primary" :href="route('login')" wire:navigate>
                Prijavi se
            </flux:button>
            @endauth
        </div>
    </main>

    @fluxScripts
</body>

</html>