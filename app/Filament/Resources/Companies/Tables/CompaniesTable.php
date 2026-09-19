<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('industry')
                    ->label('Djelatnost')
                    ->searchable(),
                TextColumn::make('b2b_or_b2c')
                    ->label('B2B/B2C')
                    ->badge(),
                TextColumn::make('market_scope')
                    ->label('Tržište')
                    ->badge(),
                IconColumn::make('sells_online')
                    ->label('Online prodaja')
                    ->boolean(),
                TextColumn::make('assessments_count')
                    ->label('Auditi')
                    ->counts('assessments')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Kreirano')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
