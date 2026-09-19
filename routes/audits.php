<?php

use App\Livewire\Audits\AuditIndex;
use App\Livewire\Audits\CreateAudit;
use App\Livewire\Audits\RunAudit;
use App\Models\AssessmentAnswer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('audits', AuditIndex::class)->name('audits.index');
    Route::livewire('audits/create', CreateAudit::class)->name('audits.create');

    Route::get('audits/evidence/{answer}', function (AssessmentAnswer $answer) {
        abort_unless($answer->evidence_path !== null, 404);

        return Storage::disk('local')->download($answer->evidence_path);
    })->name('audits.evidence');

    Route::livewire('audits/{assessment}', RunAudit::class)->name('audits.run');
});
