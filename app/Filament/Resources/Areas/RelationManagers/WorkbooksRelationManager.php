<?php

namespace App\Filament\Resources\Areas\RelationManagers;

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

class WorkbooksRelationManager extends RelationManager
{
    protected static string $relationship = 'workbooks';

    protected static ?string $title = 'Workbookovi';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Ključ')
                    ->helperText('Jedinstveni identifikator, npr. "web". Koristi se u kodu.')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Naziv')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->label('Redoslijed')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('weight')
                    ->label('Ponder')
                    ->helperText('Udio ovog workbooka u rezultatu oblasti. Podrazumijevano se svi workbookovi unutar oblasti dijele ravnomjerno (npr. 1/6 za 6 workbookova).')
                    ->required()
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Redoslijed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable(),
                TextColumn::make('key')
                    ->label('Ključ')
                    ->searchable(),
                TextColumn::make('weight')
                    ->label('Ponder')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('criteria_count')
                    ->label('Kriterija')
                    ->counts('criteria'),
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
