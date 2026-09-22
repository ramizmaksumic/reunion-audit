<?php

use App\Livewire\QuickAudit\Results;
use App\Livewire\QuickAudit\Wizard;
use Illuminate\Support\Facades\Route;

// Public, unauthenticated — this is the lead-gen entry point, so no auth
// middleware here. Page loads are throttled; the actual data-writing finish()
// action is separately rate-limited inside Wizard itself, since Livewire
// component actions run through Livewire's own update endpoint rather than
// this route.
Route::middleware(['throttle:30,1'])->group(function () {
    Route::livewire('brzi-audit', Wizard::class)->name('quick-audit.start');

    // Bound by public_token (not the default id) so results aren't
    // guessable, and so this works correctly however the URL is generated —
    // no dependence on which route is currently active.
    Route::livewire('brzi-audit/rezultati/{assessment:public_token}', Results::class)->name('quick-audit.results');
});
