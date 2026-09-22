<?php

use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\Workbook;
use Database\Seeders\RdsMethodologySeeder;

test('seeds all 16 workbooks and 456 criteria from the methodology JSON', function () {
    $this->seed(RdsMethodologySeeder::class);

    expect(Workbook::count())->toBe(16)
        ->and(Criterion::count())->toBe(456);
});

test('running the seeder twice does not duplicate or change row counts', function () {
    $this->seed(RdsMethodologySeeder::class);

    $workbooks = Workbook::count();
    $criteria = Criterion::count();
    $options = CriterionOption::count();

    $this->seed(RdsMethodologySeeder::class);

    expect(Workbook::count())->toBe($workbooks)
        ->and(Criterion::count())->toBe($criteria)
        ->and(CriterionOption::count())->toBe($options);
});

test('the two new workbooks are stamped with introduced_in_version, the rest stay null', function () {
    $this->seed(RdsMethodologySeeder::class);

    $new = Workbook::whereIn('key', ['drustvene_mreze', 'ai_vidljivost'])->get();
    $old = Workbook::whereNotIn('key', ['drustvene_mreze', 'ai_vidljivost'])->get();

    expect($new)->toHaveCount(2);
    $new->each(fn (Workbook $w) => expect($w->introduced_in_version)->toBe('v2.1'));

    expect($old)->toHaveCount(14);
    $old->each(fn (Workbook $w) => expect($w->introduced_in_version)->toBeNull());
});

test('quick-audit fields on criteria are populated from the JSON', function () {
    $this->seed(RdsMethodologySeeder::class);

    $criterion = Criterion::where('external_id', 'AI-001')->firstOrFail();

    expect($criterion->quick_audit)->toBeTrue()
        ->and($criterion->channel)->toBe('web')
        ->and($criterion->quick_block)->not->toBeNull()
        ->and($criterion->quick_source)->not->toBeNull();
});
