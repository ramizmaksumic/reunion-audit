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
     * Read database/seeders/data/rds_methodology_seed.json and upsert it into
     * areas, workbooks, criteria and criterion_options. Safe to re-run: rows
     * are matched by key/external_id, so a changed methodology just updates
     * existing rows instead of duplicating them.
     */
    public function run(): void
    {
        $data = json_decode(
            File::get(database_path('seeders/data/rds_methodology_seed.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        DB::transaction(function () use ($data) {
            $areas = $this->seedAreas($data['areas']);
            $workbooks = $this->seedWorkbooks($data['workbooks'], $data['areas'], $areas);
            $this->seedCriteria($data['workbooks'], $workbooks);
        });

        $this->report();
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

        $this->command->info("Ukupno kriterija: {$total}".($total === 420 ? ' (očekivano 420 — OK)' : ' — OČEKIVANO 420, PROVJERI SEED JSON'));
    }
}
