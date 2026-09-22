<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\Workbook;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RdsMethodologySeeder extends Seeder
{
    /**
     * Bosnian priority labels used in the seed JSON, mapped to the
     * `criteria.priority` enum values.
     *
     * @var array<string, string>
     */
    private const PRIORITY_MAP = [
        'Kritičan' => 'kritican',
        'Važan' => 'vazan',
        'Preporučen' => 'preporucen',
    ];

    /**
     * Workbooks added after the original v2.0 methodology, mapped to the
     * version that introduced them. Not present in the seed JSON itself (it
     * carries no version metadata) — this is application-level knowledge
     * used by ScoringService to keep older assessments' scores from being
     * silently pulled down by workbooks that didn't exist when they were
     * answered. Every workbook not listed here is treated as "since v2.0"
     * (introduced_in_version stays null).
     *
     * @var array<string, string>
     */
    private const NEW_WORKBOOKS_INTRODUCED_IN = [
        'drustvene_mreze' => 'v2.1',
        'ai_vidljivost' => 'v2.1',
    ];

    /**
     * Read database/seeders/data/rds_methodology_seed.json and upsert it into
     * areas, workbooks, criteria and criterion_options. Safe to re-run: rows
     * are matched by key/external_id, so a changed methodology just updates
     * existing rows instead of duplicating them. Never deletes existing
     * areas/workbooks/criteria/options, so assessment answers are never
     * orphaned by a re-seed.
     */
    public function run(): void
    {
        $data = json_decode(
            File::get(database_path('seeders/data/rds_methodology_seed.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->validate($data);

        DB::transaction(function () use ($data) {
            $areas = $this->seedAreas($data['areas']);
            $workbooks = $this->seedWorkbooks($data['workbooks'], $data['areas'], $areas);
            $this->seedCriteria($data['workbooks'], $workbooks);
        });

        $this->report();
    }

    /**
     * Structural validation of the seed JSON, run before anything touches
     * the database. Aborts with every problem found (not just the first)
     * so a bad seed file can be fixed in one pass instead of one error at a
     * time.
     *
     * @param  array<string, mixed>  $data
     */
    private function validate(array $data): void
    {
        $errors = [];
        $areaKeys = array_keys($data['areas'] ?? []);
        $seenCriterionIds = [];

        foreach ($data['workbooks'] ?? [] as $workbookData) {
            $workbookKey = $workbookData['workbook_key'] ?? '(nepoznat workbook_key)';

            if (! in_array($workbookData['area_key'] ?? null, $areaKeys, true)) {
                $errors[] = "Workbook '{$workbookKey}' referencira nepostojeći area_key '".($workbookData['area_key'] ?? 'null')."'.";
            }

            $actualCount = count($workbookData['criteria'] ?? []);
            $declaredCount = $workbookData['criteria_count'] ?? null;

            if ($declaredCount !== $actualCount) {
                $errors[] = "Workbook '{$workbookKey}': criteria_count={$declaredCount}, a stvarno ima {$actualCount} kriterija.";
            }

            foreach ($workbookData['criteria'] ?? [] as $criterionData) {
                $id = $criterionData['id'] ?? null;

                if ($id === null) {
                    $errors[] = "Workbook '{$workbookKey}' ima kriterij bez 'id' polja.";

                    continue;
                }

                if (isset($seenCriterionIds[$id])) {
                    $errors[] = "Kriterij id '{$id}' se pojavljuje više puta (workbook '{$workbookKey}' i '{$seenCriterionIds[$id]}').";
                } else {
                    $seenCriterionIds[$id] = $workbookKey;
                }

                if (! isset(self::PRIORITY_MAP[$criterionData['priority'] ?? ''])) {
                    $errors[] = "Kriterij '{$id}': nepoznat priority '".($criterionData['priority'] ?? 'null')."'.";
                }

                if (empty($criterionData['options']) || ! is_array($criterionData['options'])) {
                    $errors[] = "Kriterij '{$id}' nema opcije.";
                } else {
                    foreach ($criterionData['options'] as $i => $option) {
                        if (! isset($option['label']) || $option['label'] === '') {
                            $errors[] = "Kriterij '{$id}', opcija #{$i}: nema 'label'.";
                        }

                        if (! isset($option['points']) || ! is_numeric($option['points'])) {
                            $errors[] = "Kriterij '{$id}', opcija #{$i}: 'points' nije numerički.";
                        }
                    }
                }
            }
        }

        if ($errors !== []) {
            throw new \RuntimeException(
                'Seed JSON validacija nije prošla ('.count($errors)." problema):\n- ".implode("\n- ", $errors)
            );
        }
    }

    /**
     * @param  array<string, array{area_name: string, workbooks: array<int, string>}>  $areasData
     * @return array<string, Area> Keyed by area_key.
     */
    private function seedAreas(array $areasData): array
    {
        $areas = [];
        $sortOrder = 0;

        foreach ($areasData as $areaKey => $areaData) {
            $areas[$areaKey] = Area::updateOrCreate(
                ['key' => $areaKey],
                ['name' => $areaData['area_name'], 'sort_order' => $sortOrder++],
            );
        }

        return $areas;
    }

    /**
     * @param  array<int, array<string, mixed>>  $workbooksData
     * @param  array<string, array{area_name: string, workbooks: array<int, string>}>  $areasData
     * @param  array<string, Area>  $areas
     * @return array<string, Workbook> Keyed by workbook_key.
     */
    private function seedWorkbooks(array $workbooksData, array $areasData, array $areas): array
    {
        $workbooks = [];

        foreach ($workbooksData as $sortOrder => $workbookData) {
            $areaKey = $workbookData['area_key'];
            $workbookCountInArea = count($areasData[$areaKey]['workbooks']);

            $workbooks[$workbookData['workbook_key']] = Workbook::updateOrCreate(
                ['key' => $workbookData['workbook_key']],
                [
                    'area_id' => $areas[$areaKey]->id,
                    'name' => $workbookData['workbook_name'],
                    'sort_order' => $sortOrder,
                    'weight' => 1 / $workbookCountInArea,
                    'introduced_in_version' => self::NEW_WORKBOOKS_INTRODUCED_IN[$workbookData['workbook_key']] ?? null,
                ],
            );
        }

        return $workbooks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $workbooksData
     * @param  array<string, Workbook>  $workbooks
     */
    private function seedCriteria(array $workbooksData, array $workbooks): void
    {
        foreach ($workbooksData as $workbookData) {
            $workbook = $workbooks[$workbookData['workbook_key']];

            foreach ($workbookData['criteria'] as $sortOrder => $criterionData) {
                $criterion = Criterion::updateOrCreate(
                    ['external_id' => $criterionData['id']],
                    [
                        'workbook_id' => $workbook->id,
                        'group_label' => $criterionData['group'],
                        'text' => $criterionData['text'],
                        'answer_type' => $criterionData['answer_type'],
                        'priority' => self::PRIORITY_MAP[$criterionData['priority']],
                        'evidence_source' => $criterionData['evidence_source'],
                        'self_service_eligible' => $criterionData['self_service_eligible'],
                        'is_relevance_gate' => $criterionData['is_relevance_gate'],
                        'sort_order' => $sortOrder,
                        'channel' => $criterionData['channel'] ?? null,
                        'quick_audit' => (bool) ($criterionData['quick_audit'] ?? false),
                        'quick_block' => $criterionData['quick_block'] ?? null,
                        'quick_source' => $criterionData['quick_source'] ?? null,
                        'quick_question' => $criterionData['quick_question'] ?? null,
                        'quick_option_labels' => $criterionData['quick_option_labels'] ?? null,
                        'quick_note' => $criterionData['quick_note'] ?? null,
                    ],
                );

                $this->seedOptions($criterion, $criterionData['options']);
            }
        }
    }

    /**
     * @param  array<int, array{label: string, points: float}>  $optionsData
     */
    private function seedOptions(Criterion $criterion, array $optionsData): void
    {
        foreach ($optionsData as $sortOrder => $optionData) {
            CriterionOption::updateOrCreate(
                ['criterion_id' => $criterion->id, 'label' => $optionData['label']],
                ['points' => $optionData['points'], 'sort_order' => $sortOrder],
            );
        }
    }

    private function report(): void
    {
        $rows = Workbook::query()
            ->withCount('criteria')
            ->with('area')
            ->orderBy('area_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Workbook $workbook) => [
                $workbook->area->name,
                $workbook->name,
                $workbook->criteria_count,
            ]);

        $this->command->table(['Oblast', 'Workbook', 'Kriteriji'], $rows);

        $total = (int) $rows->sum(fn (array $row) => $row[2]);

        $this->command->info("Ukupno kriterija: {$total}".($total === 456 ? ' (očekivano 456 — OK)' : ' — očekivano 456, provjeri seed JSON ako je ovo neočekivano'));
    }
}
