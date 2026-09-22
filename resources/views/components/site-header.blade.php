<header class="sticky top-0 z-10 border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5" wire:navigate>
            <img src="{{ asset('images/reunion-icon.png') }}" alt="{{ config('app.name') }}" class="h-8 w-8 shrink-0" />
            <span class="leading-tight">
                <span class="block text-sm font-bold text-zinc-900 dark:text-white">{{ config('app.name') }}</span>
                <span class="block text-xs text-zinc-500 dark:text-zinc-400">Jasna slika. Bolje odluke. Veći rast.</span>
            </span>
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium text-zinc-600 md:flex dark:text-zinc-300">
            <a href="{{ route('home') }}#kako-funkcionise" class="hover:text-zinc-900 dark:hover:text-white">Kako funkcioniše</a>
            <a href="{{ route('home') }}#sta-dobijate" class="hover:text-zinc-900 dark:hover:text-white">Šta dobijate</a>
            <a href="{{ route('home') }}#za-koga-je" class="hover:text-zinc-900 dark:hover:text-white">Za koga je</a>
        </nav>

        @if (Route::has('login'))
            <div class="flex items-center gap-2">
                @auth
                    <flux:button variant="primary" :href="route('dashboard')" wire:navigate>
                        Dashboard
                    </flux:button>
                @else
                    <flux:button variant="ghost" :href="route('login')" wire:navigate class="hidden sm:inline-flex">
                        Prijava
                    </flux:button>

                    @if (Route::has('quick-audit.start'))
                        <flux:button variant="primary" :href="route('quick-audit.start')" wire:navigate>
                            Započni audit
                        </flux:button>
                    @endif
                @endauth
            </div>
        @endif
    </div>
</header>
