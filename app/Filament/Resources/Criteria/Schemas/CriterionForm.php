<?php

namespace App\Filament\Resources\Criteria\Schemas;

use App\Models\Workbook;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CriterionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kriterij')
                    ->columns(2)
                    ->components([
                        Select::make('workbook_id')
                            ->label('Workbook')
                            ->relationship('workbook', 'name', modifyQueryUsing: fn ($query) => $query->with('area'))
                            ->getOptionLabelFromRecordUsing(fn (Workbook $record) => "{$record->area->name} / {$record->name}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('external_id')
                            ->label('ID (npr. WEB-001)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('group_label')
                            ->label('Grupa')
                            ->helperText('Kriteriji sa istom grupom unutar workbooka se prikazuju zajedno u auditu.')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Redoslijed')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Textarea::make('text')
                            ->label('Tekst pitanja')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Bodovanje i dokaz')
                    ->columns(2)
                    ->components([
                        Select::make('answer_type')
                            ->label('Tip odgovora')
                            ->options([
                                'binary' => 'Binarni (Da/Ne)',
                                'threshold' => 'Prag (metrički)',
                                'graded' => 'Djelimičan (više nivoa)',
                                'audit_opinion' => 'Audit opažanje',
                            ])
                            ->required(),
                        Select::make('priority')
                            ->label('Prioritet')
                            ->options([
                                'kritican' => 'Kritičan',
                                'vazan' => 'Važan',
                                'preporucen' => 'Preporučen',
                            ])
                            ->required(),
                        TextInput::make('evidence_source')
                            ->label('Izvor dokaza')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('self_service_eligible')
                            ->label('Dostupan u brzom scanu (self-service)'),
                        Toggle::make('is_relevance_gate')
                            ->label('Relevance gate')
                            ->helperText('Kad je odgovoren negativno, ostali kriteriji iz iste grupe automatski postaju N/A.'),
                    ]),
            ]);
    }
}
