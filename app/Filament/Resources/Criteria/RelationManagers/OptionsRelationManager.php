<?php

namespace App\Filament\Resources\Criteria\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Opcije odgovora';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Naziv opcije')
                    ->required()
                    ->maxLength(255),
                TextInput::make('points')
                    ->label('Bodovi')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0),
                TextInput::make('sort_order')
                    ->label('Redoslijed')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Redoslijed')
                    ->numeric(),
                TextColumn::make('label')
                    ->label('Naziv opcije')
                    ->searchable(),
                TextColumn::make('points')
                    ->label('Bodovi')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
