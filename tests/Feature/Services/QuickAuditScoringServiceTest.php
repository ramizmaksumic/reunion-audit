<?php

use App\Models\Assessment;
use App\Models\Criterion;
use App\Services\QuickAuditScoringService;
use Database\Seeders\RdsMethodologySeeder;

/**
 * Runs against the real seeded methodology (not factories) since the
 * quick_audit item set is a fixed, real subset of criteria with real
 * channel/block tagging — there's nothing meaningful to fake here.
 */
beforeEach(function () {
    $this->seed(RdsMethodologySeeder::class);
    $this->scoring = new QuickAuditScoringService;
});

function quickAnswerWithBestOption(Assessment $assessment, Criterion $criterion): void
{
    $best = $criterion->options()->reorder('points', 'desc')->firstOrFail();

    $assessment->answers()->create([
        'criterion_id' => $criterion->id,
        'selected_option_id' => $best->id,
    ]);
}

test('exactly 25 criteria are tagged quick_audit across the expected 6 blocks', function () {
    $items = $this->scoring->itemBreakdown(Assessment::factory()->create());

    expect($items)->toHaveCount(25);
    expect($items->pluck('block')->unique()->sort()->values()->all())->toBe([
        'drustvene_mreze', 'mjerenje_rast', 'odziv_procesi', 'pronalazljivost', 'reputacija_povjerenje', 'web',
    ]);
});

test('an answered non-channel criterion scores as points earned over max points', function () {
    $assessment = Assessment::factory()->create();
    $criterion = Criterion::where('external_id', 'SEO-005')->firstOrFail(); // channel=null, binary Da/Ne

    $da = $criterion->options()->where('label', 'Da')->firstOrFail();
    $assessment->answers()->create(['criterion_id' => $criterion->id, 'selected_option_id' => $da->id]);

    $item = $this->scoring->itemBreakdown($assessment)->firstWhere('criterion.external_id', 'SEO-005');

    expect($item['state'])->toBe('scored')
        ->and($item['value'])->toBe(1.0)
        ->and($item['weight'])->toBe(1.0);
});

test('a channel marked not_relevant excludes every item tagged with that channel', function () {
    $assessment = Assessment::factory()->create(['quick_channel_relevance' => ['web' => 'not_relevant']]);

    $webItems = $this->scoring->itemBreakdown($assessment)->where('criterion.channel', 'web');

    expect($webItems)->toHaveCount(10);
    $webItems->each(function (array $item) {
        expect($item['state'])->toBe('excluded')->and($item['value'])->toBeNull();
    });
});

test('a channel marked critical turns its unanswered items into missing_zero with weight 1', function () {
    $assessment = Assessment::factory()->create(['quick_channel_relevance' => ['web' => 'critical']]);

    $webItems = $this->scoring->itemBreakdown($assessment)->where('criterion.channel', 'web');

    $webItems->each(function (array $item) {
        expect($item['state'])->toBe('missing_zero')
            ->and($item['value'])->toBe(0.0)
            ->and($item['weight'])->toBe(1.0);
    });
});

test('a channel marked recommended turns its unanswered items into missing_zero with weight 0.5', function () {
    $assessment = Assessment::factory()->create(['quick_channel_relevance' => ['social' => 'recommended']]);

    $socialItems = $this->scoring->itemBreakdown($assessment)->where('criterion.channel', 'social');

    expect($socialItems)->toHaveCount(2);
    $socialItems->each(function (array $item) {
        expect($item['state'])->toBe('missing_zero')
            ->and($item['value'])->toBe(0.0)
            ->and($item['weight'])->toBe(0.5);
    });
});

test('a block is not scored until it has at least 2 scored items', function () {
    $assessment = Assessment::factory()->create(['quick_channel_relevance' => ['social' => 'not_relevant']]);

    // drustvene_mreze has BI-005 (channel=null) + DM-013/DM-017 (channel=social,
    // excluded above). Answer only BI-005 — 1 scored item, below the threshold.
    quickAnswerWithBestOption($assessment, Criterion::where('external_id', 'BI-005')->firstOrFail());

    expect($this->scoring->blockScores($assessment)->has('drustvene_mreze'))->toBeFalse();
});

test('answering every applicable item with its best option scores 100 with full confidence', function () {
    $assessment = Assessment::factory()->create();

    Criterion::where('quick_audit', true)->get()->each(
        fn (Criterion $criterion) => quickAnswerWithBestOption($assessment, $criterion)
    );

    $result = $this->scoring->overallScore($assessment);

    expect($result['score'])->toBe(100.0)
        ->and($result['confidence'])->toBe(1.0)
        ->and($result['scored_block_count'])->toBe(6)
        ->and($result['is_approximate'])->toBeFalse();
});

test('a mostly-excluded assessment is flagged as an approximate result', function () {
    $assessment = Assessment::factory()->create([
        'quick_channel_relevance' => ['web' => 'not_relevant', 'gbp' => 'not_relevant', 'social' => 'not_relevant'],
    ]);

    // Only the 6 channel=null criteria remain answerable — 3 blocks at most
    // (pronalazljivost, drustvene_mreze, odziv_procesi, mjerenje_rast each
    // have at most 1-2 null-channel items), so confidence/block-count should
    // trip the approximate flag.
    Criterion::where('quick_audit', true)->whereNull('channel')->get()->each(
        fn (Criterion $criterion) => quickAnswerWithBestOption($assessment, $criterion)
    );

    $result = $this->scoring->overallScore($assessment);

    expect($result['is_approximate'])->toBeTrue();
});
