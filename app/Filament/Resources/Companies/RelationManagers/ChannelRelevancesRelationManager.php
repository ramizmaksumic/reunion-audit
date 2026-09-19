<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Models\CompanyChannelRelevance;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChannelRelevancesRelationManager extends RelationManager
{
    protected static string $relationship = 'channelRelevances';

    protected static ?string $title = 'Relevantnost digitalnih kanala';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('channel_key')
                    ->label('Kanal')
                    ->options(CompanyChannelRelevance::CHANNELS)
                    ->required(),
                Select::make('relevance')
                    ->label('Relevantnost')
                    ->options(CompanyChannelRelevance::RELEVANCE_LEVELS)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('channel_key')
            ->columns([
                TextColumn::make('channel_key')
                    ->label('Kanal')
                    ->formatStateUsing(fn (string $state): string => CompanyChannelRelevance::CHANNELS[$state] ?? $state),
                TextColumn::make('relevance')
                    ->label('Relevantnost')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CompanyChannelRelevance::RELEVANCE_LEVELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'recommended' => 'warning',
                        'not_relevant' => 'gray',
                        default => 'gray',
                    }),
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
