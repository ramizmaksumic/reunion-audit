<?php

namespace App\Filament\Resources\Criteria\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CriteriaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('external_id')
            ->columns([
                TextColumn::make('external_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('workbook.name')
                    ->label('Workbook')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('group_label')
                    ->label('Grupa')
                    ->searchable(),
                TextColumn::make('text')
                    ->label('Pitanje')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('priority')
                    ->label('Prioritet')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'kritican' => 'Kritičan',
                        'vazan' => 'Važan',
                        'preporucen' => 'Preporučen',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'kritican' => 'danger',
                        'vazan' => 'warning',
                        'preporucen' => 'gray',
                        default => 'gray',
                    }),
                IconColumn::make('is_relevance_gate')
                    ->label('Gate')
                    ->boolean(),
                IconColumn::make('self_service_eligible')
                    ->label('Self-service')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'web' => 'Web',
                        'gbp' => 'GBP',
                        'social' => 'Društvene mreže',
                        default => '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('quick_audit')
                    ->label('Quick audit')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('workbook')
                    ->label('Workbook')
                    ->relationship('workbook', 'name'),
                SelectFilter::make('priority')
                    ->label('Prioritet')
                    ->options([
                        'kritican' => 'Kritičan',
                        'vazan' => 'Važan',
                        'preporucen' => 'Preporučen',
                    ]),
                TernaryFilter::make('is_relevance_gate')
                    ->label('Relevance gate'),
                SelectFilter::make('channel')
                    ->label('Kanal')
                    ->options([
                        'web' => 'Web',
                        'gbp' => 'Google Business profil',
                        'social' => 'Društvene mreže',
                    ]),
                TernaryFilter::make('quick_audit')
                    ->label('Quick audit'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
